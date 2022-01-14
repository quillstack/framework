<?php

declare(strict_types=1);

namespace QuillStack\Framework\Providers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareProvider implements RequestHandlerInterface
{
    /**
     * @var array
     */
    private array $middleware = [];

    /**
     * @var RequestHandlerInterface
     */
    private RequestHandlerInterface $fallbackHandler;

    /**
     * @param RequestHandlerInterface $fallbackHandler
     */
    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function add(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (0 === count($this->middleware)) {
            return $this->fallbackHandler->handle($request);
        }

        $currentMiddleware = array_shift($this->middleware);

        return $currentMiddleware->process($request, $this);
    }
}
