<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuillStack\DI\Container;
use QuillStack\Framework\Http\Controllers\NotFoundController;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Framework\Providers\MiddlewareProvider;
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
    private array $middleware = [];

    /**
     * @param Container $container
     * @param array $middleware
     *
     * @return ResponseInterface
     */
    public function boot(Container $container, array $middleware): ResponseInterface
    {
        $this->middleware = array_reverse(array_merge(Config::DEFAULT_MIDDLEWARE, $middleware));

        $this->loadRoutes($container);
        $handler = $this->buildHandlerWithMiddleware($container);

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
    private function buildHandlerWithMiddleware(Container $container): RequestHandlerInterface
    {
        $middlewareProvider = new MiddlewareProvider(
            $container->get(NotFoundController::class)
        );

        foreach ($this->middleware as $middlewareClass) {
            $middleware = $container->get($middlewareClass);

            if (isset($middleware->container)) {
                $middleware->container = $container;
            }

            $middlewareProvider->add($middleware);
        }

        return $middlewareProvider;
    }
}
