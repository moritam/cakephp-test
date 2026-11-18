<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * API リクエストで未認証の場合に 401 JSON を返す（リダイレクトしない）
 */
class ApiUnauthorizedMiddleware implements MiddlewareInterface
{
    /**
     * /api 配下へのリクエストで未認証なら 401 を返し、それ以外は次の処理へ渡す
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (str_starts_with($path, '/api')) {
            $auth = $request->getAttribute('authentication');
            if ($auth !== null && !$auth->getResult()->isValid()) {
                return (new Response())
                    ->withStatus(401)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode(['error' => 'Unauthorized'], JSON_THROW_ON_ERROR));
            }
        }

        return $handler->handle($request);
    }
}
