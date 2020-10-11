<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuillStack\DI\Container;
use QuillStack\Framework\Http\Controllers\NotFoundController;
use QuillStack\Framework\Decorators\DecoratingRequestHandler;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Framework\Middleware\RoutingMiddleware;
use QuillStack\Http\Request\Factory\ServerRequest\RequestFromGlobalsFactory;
use QuillStack\Router\Dispatcher;
use QuillStack\Router\Router;

final class Kernel
{
    /**
     * @var Dispatcher
     */
    public Dispatcher $dispatcher;

    /**
     * @var RequestFromGlobalsFactory
     */
    public RequestFromGlobalsFactory $requestFromGlobalsFactory;

    /**
     * @var Router
     */
    public Router $router;

    /**
     * @var array
     */
    private array $middleware = [
        RoutingMiddleware::class,
    ];

    /**
     * @param Container $container
     *
     * @return ResponseInterface
     */
    public function boot(Container $container): ResponseInterface
    {
        $this->loadRoutes($container);
        $handler = $this->buildMiddlewareLayers($container);

        // Handle request.
        return $handler->handle(
            $this->requestFromGlobalsFactory->createServerRequest()
        );
    }

    /**
     * @param Container $container
     */
    private function loadRoutes(Container $container): void
    {
        $routeProvider = $container->get(RouteProviderInterface::class);
        $routeProvider->getRoutes($this->router);
    }

    /**
     * @param Container $container
     *
     * @return RequestHandlerInterface
     */
    private function buildMiddlewareLayers(Container $container): RequestHandlerInterface
    {
        $handler = $nextHandler = $container->get(NotFoundController::class);

        foreach ($this->middleware as $middlewareClass) {
            $middleware = $container->get($middlewareClass);
            $middleware->container = $container;
            $handler = new DecoratingRequestHandler($middleware, $nextHandler);
            $nextHandler = $handler;
        }

        return $handler;
    }
}
