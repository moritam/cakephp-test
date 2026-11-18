<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Cache\Cache;
use Cake\Http\RateLimit\FixedWindowRateLimiter;
use Cake\Http\RateLimit\RateLimiterInterface;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ログインの総当たり (ブルートフォース) 対策
 *
 * POST /users/login の失敗 (401) を数え、上限を超えたら 429 を返す。
 * - 同じ IP + 同じメールアドレス: 1 アカウントへの総当たり対策
 * - 同じ IP: 多数のアカウントを順に試す攻撃 (クレデンシャルスタッフィング) 対策
 *
 * ログインに成功すると、その IP + メールアドレスのカウントを 0 に戻す。
 * 上限中は認証処理 (パスワード照合) まで進ませない。
 *
 * IP は ServerRequest::clientIp() で取得する。既定では REMOTE_ADDR を使い、
 * 偽装可能な X-Forwarded-For は信用しない (リバースプロキシ配下では trustProxy の設定が必要)。
 */
class LoginThrottleMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        'path' => '/users/login',
        // 集計の時間枠 (秒)
        'window' => 900,
        // 同じ IP + 同じメールアドレスの失敗回数の上限
        'maxPerIpAndEmail' => 5,
        // 同じ IP の失敗回数の上限
        'maxPerIp' => 20,
        // カウントを保存する Cache の設定名
        'cache' => 'rate_limit',
    ];

    protected ?RateLimiterInterface $limiter = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config 設定 (既定値は $config を参照)
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + $this->config;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== $this->config['path']) {
            return $handler->handle($request);
        }

        $keys = $this->keys($request);
        $limits = [
            $keys['ipEmail'] => $this->config['maxPerIpAndEmail'],
            $keys['ip'] => $this->config['maxPerIp'],
        ];

        // 上限に達していないか確認する (cost 0 なのでカウントは増えない)
        foreach ($limits as $key => $limit) {
            $result = $this->limiter()->attempt($key, $limit, $this->config['window'], 0);
            if ($result['remaining'] <= 0) {
                return $this->tooManyAttempts($result['reset']);
            }
        }

        $response = $handler->handle($request);

        if ($response->getStatusCode() === 401) {
            foreach ($limits as $key => $limit) {
                $this->limiter()->attempt($key, $limit, $this->config['window']);
            }
        } elseif ($response->getStatusCode() === 200) {
            $this->resetCounter($keys['ipEmail']);
        }

        return $response;
    }

    /**
     * 現在の時間枠のカウントを削除する
     *
     * FixedWindowRateLimiter::reset() は時間枠を 1 時間と決め打ちしてキーを作るため
     * (cakephp/cakephp 5.3)、設定した window と一致せず削除できない。
     * そのため FixedWindowRateLimiter と同じ規則 ("{key}_{枠の開始時刻}") でキーを作って削除する。
     *
     * @param string $key カウント用のキー
     * @return void
     */
    protected function resetCounter(string $key): void
    {
        $window = $this->config['window'];
        $windowStart = intdiv(time(), $window) * $window;
        Cache::pool($this->config['cache'])->delete($key . '_' . $windowStart);
    }

    /**
     * カウント用のキーを作る (メールアドレスはハッシュ化して保存する)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return array{ip: string, ipEmail: string}
     */
    protected function keys(ServerRequestInterface $request): array
    {
        $ip = method_exists($request, 'clientIp')
            ? (string)$request->clientIp()
            : (string)($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
        $body = $request->getParsedBody();
        $email = is_array($body) && is_string($body['email'] ?? null) ? $body['email'] : '';
        $email = mb_strtolower(trim($email));

        return [
            'ip' => 'login_ip_' . hash('sha256', $ip),
            'ipEmail' => 'login_ip_email_' . hash('sha256', $ip . '|' . $email),
        ];
    }

    /**
     * 429 レスポンスを作る
     *
     * @param int $resetAt 制限が解除される UNIX 時刻
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function tooManyAttempts(int $resetAt): ResponseInterface
    {
        $retryAfter = max(1, $resetAt - time());
        $minutes = (int)ceil($retryAfter / 60);

        return (new Response())
            ->withStatus(429)
            ->withType('application/json')
            ->withHeader('Retry-After', (string)$retryAfter)
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => __(
                    'ログインの試行回数が上限を超えました。{0} 分後にもう一度お試しください。',
                    $minutes,
                ),
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return \Cake\Http\RateLimit\RateLimiterInterface
     */
    protected function limiter(): RateLimiterInterface
    {
        return $this->limiter ??= new FixedWindowRateLimiter(Cache::pool($this->config['cache']));
    }
}
