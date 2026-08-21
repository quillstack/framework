<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets a header holding several values, and one whose single value carries commas.
 */
class HeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('Set-Cookie', ['first=1', 'second=2'])
            ->withHeader('Last-Modified', 'Fri, 11 Sep 2020 20:46:34 GMT');
    }
}
