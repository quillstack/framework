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
     * @var Container
     */
    public Container $container;

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $dispatcher = $this->container->get(Dispatcher::class);
        $route = $dispatcher->dispatch($request);

        if (!$route->isSuccess()) {
            return $handler->handle($request);
        }

        $controller = $this->container->get(
            $route->getController()
        );

        return $controller->handle($controller->request ?? $request);
    }
}
