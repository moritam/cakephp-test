<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Middleware\LoginThrottleMiddleware Test Case
 *
 * フィクスチャーの alice@example.com のパスワードは password123。
 * 上限: 同じ IP + メールアドレスは 5 回、同じ IP は 20 回 (15 分)。
 */
class LoginThrottleMiddlewareTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('rate_limit');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Cache::clear('rate_limit');
        parent::tearDown();
    }

    /**
     * 指定 IP からログインを試みる
     *
     * @param string $email Email.
     * @param string $password Password.
     * @param string $ip 接続元 IP (REMOTE_ADDR)
     * @param array<string, string> $extraHeaders 追加ヘッダー
     * @return void
     */
    private function attemptLogin(
        string $email,
        string $password,
        string $ip = '203.0.113.10',
        array $extraHeaders = [],
    ): void {
        $token = (new CsrfProtectionMiddleware())->createToken();
        $this->cookie('csrfToken', $token);
        // configRequest() は前回の設定と再帰的にマージされ、REMOTE_ADDR が配列になってしまうため毎回初期化する
        $this->_request = [];
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-CSRF-Token' => $token,
            ] + $extraHeaders,
            'environment' => ['REMOTE_ADDR' => $ip],
        ]);
        $this->post('/users/login', json_encode(['email' => $email, 'password' => $password]));
    }

    /**
     * 5 回失敗すると、6 回目は正しいパスワードでも 429 になりログインできない
     *
     * @return void
     */
    public function testLocksAfterFiveFailures(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password');
            $this->assertResponseCode(401, "attempt {$i}");
        }

        $this->attemptLogin('alice@example.com', 'password123');
        $this->assertResponseCode(429);
        $this->assertHeaderContains('Retry-After', '');
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertStringContainsString('分後にもう一度お試しください', $body['message']);
        $this->assertSessionNotHasKey('Auth');
    }

    /**
     * メールアドレスの大文字・小文字や前後の空白を変えても、同じアカウントとして数える
     *
     * @return void
     */
    public function testEmailIsNormalized(): void
    {
        foreach (['alice@example.com', 'ALICE@example.com', ' Alice@Example.com ', 'alice@EXAMPLE.com', 'aLiCe@example.com'] as $email) {
            $this->attemptLogin($email, 'wrong-password');
            $this->assertResponseCode(401);
        }

        $this->attemptLogin('alice@example.com', 'password123');
        $this->assertResponseCode(429);
    }

    /**
     * ログインに成功すると、その IP + メールアドレスのカウントは 0 に戻る
     *
     * @return void
     */
    public function testSuccessResetsCounter(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password');
            $this->assertResponseCode(401);
        }
        $this->attemptLogin('alice@example.com', 'password123');
        $this->assertResponseOk();

        for ($i = 1; $i <= 5; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password');
            $this->assertResponseCode(401, "attempt {$i} after reset");
        }
    }

    /**
     * 別の IP からは、同じアカウントにログインできる (本人が締め出されにくい)
     *
     * @return void
     */
    public function testOtherIpIsNotAffected(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password', '203.0.113.10');
        }
        $this->attemptLogin('alice@example.com', 'password123', '203.0.113.10');
        $this->assertResponseCode(429);

        $this->attemptLogin('alice@example.com', 'password123', '198.51.100.20');
        $this->assertResponseOk();
    }

    /**
     * 同じ IP から 20 回失敗すると、別のメールアドレスでも 429 になる
     *
     * @return void
     */
    public function testLocksIpAfterTwentyFailuresAcrossAccounts(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->attemptLogin("user{$i}@example.com", 'wrong-password');
            $this->assertResponseCode(401, "attempt {$i}");
        }

        $this->attemptLogin('alice@example.com', 'password123');
        $this->assertResponseCode(429);
    }

    /**
     * X-Forwarded-For を偽装しても制限をすり抜けられない
     *
     * @return void
     */
    public function testForwardedForHeaderIsNotTrusted(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password', '203.0.113.10', [
                'X-Forwarded-For' => "192.0.2.{$i}",
            ]);
            $this->assertResponseCode(401);
        }

        $this->attemptLogin('alice@example.com', 'password123', '203.0.113.10', [
            'X-Forwarded-For' => '192.0.2.99',
        ]);
        $this->assertResponseCode(429);
    }

    /**
     * TRUST_PROXY のとき: プロキシが付けた X-Forwarded-For の最後のアドレスで数える
     * (クライアントが先頭に偽のアドレスを入れても、プロキシが末尾に本当の接続元を追加する)
     *
     * @return void
     */
    public function testTrustProxyUsesLastForwardedAddress(): void
    {
        Configure::write('App.trustProxy', true);
        for ($i = 1; $i <= 5; $i++) {
            $this->attemptLogin('alice@example.com', 'wrong-password', '127.0.0.1', [
                'X-Forwarded-For' => "192.0.2.{$i}, 198.51.100.7",
            ]);
            $this->assertResponseCode(401);
        }

        // 同じ接続元 (末尾が 198.51.100.7) は先頭を変えてもロックされる
        $this->attemptLogin('alice@example.com', 'password123', '127.0.0.1', [
            'X-Forwarded-For' => '192.0.2.99, 198.51.100.7',
        ]);
        $this->assertResponseCode(429);

        // 別の接続元 (同じプロキシ経由) は影響を受けない
        $this->attemptLogin('alice@example.com', 'password123', '127.0.0.1', [
            'X-Forwarded-For' => '198.51.100.8',
        ]);
        $this->assertResponseOk();
    }

    /**
     * CSRF トークンのないリクエスト (403) は数えない
     *
     * @return void
     */
    public function testCsrfRejectedRequestsAreNotCounted(): void
    {
        $this->_request = [];
        $this->configRequest([
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'environment' => ['REMOTE_ADDR' => '203.0.113.10'],
        ]);
        for ($i = 1; $i <= 6; $i++) {
            $this->_cookie = [];
            $this->post('/users/login', json_encode(['email' => 'alice@example.com', 'password' => 'x']));
            $this->assertResponseCode(403);
        }

        $this->attemptLogin('alice@example.com', 'password123');
        $this->assertResponseOk();
    }
}
