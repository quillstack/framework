<?php

declare(strict_types=1);

namespace QuillStack\Framework\Decorators;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DecoratingRequestHandler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface
     */
    private MiddlewareInterface $middleware;

    /**
     * @var RequestHandlerInterface
     */
    private RequestHandlerInterface $nextHandler;

    /**
     * @param MiddlewareInterface $middleware
     * @param RequestHandlerInterface $nextHandler
     */
    public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $nextHandler)
    {
        $this->middleware = $middleware;
        $this->nextHandler = $nextHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->nextHandler);
    }
}
