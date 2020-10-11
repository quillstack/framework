<?php

declare(strict_types=1);

namespace QuillStack\Framework\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuillStack\DI\Container;
use QuillStack\Router\Dispatcher;

final class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var Dispatcher
     */
    public Dispatcher $dispatcher;

    /**
     * @var Container
     */
    public Container $container;

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->dispatcher->dispatch($request);

        if ($route->isSuccess()) {
            $handler = $this->container->get(
                $route->getController()
            );

            return $handler->handle($request);
        }

        return $handler->handle($request);
    }
}
