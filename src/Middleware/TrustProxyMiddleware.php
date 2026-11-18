<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * リバースプロキシの後ろで動かす場合に、ServerRequest::clientIp() が
 * プロキシの付けた X-Forwarded-For を使うようにする (App.trustProxy が true のときだけ)。
 *
 * CakePHP の clientIp() は X-Forwarded-For の最後のアドレス (= 手前のプロキシが追加したもの) を返す。
 * クライアントが X-Forwarded-For を偽装しても、プロキシが本当の接続元を末尾に追加するため影響しない。
 * ただし、アプリのポートにプロキシを通さず直接つながる構成では偽装できてしまうので有効にしないこと。
 */
class TrustProxyMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof ServerRequest && Configure::read('App.trustProxy')) {
            $request->trustProxy = true;
        }

        return $handler->handle($request);
    }
}
