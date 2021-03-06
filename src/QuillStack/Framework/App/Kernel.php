<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use QuillStack\DI\Container;
use QuillStack\Framework\Http\Controllers\NotFoundController;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Http\Request\Factory\ServerRequest\RequestFromGlobalsFactory;
use QuillStack\Middleware\MiddlewareBuilder;
use QuillStack\Router\Dispatcher;
use QuillStack\Router\Router;

final class Kernel
{
    /**
     * @var Container|null
     */
    private ?Container $container;

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
     * @param Container $container
     * @param array $middleware
     *
     * @return ResponseInterface
     */
    public function boot(Container $container, array $middleware): ResponseInterface
    {
        $this->container = $container;

        // Load all routes.
        $this->loadRoutes();

        // Load all middleware.
        $middlewareBuilder = $this->loadMiddleware($middleware);

        // Get handler.
        $handler = $middlewareBuilder->build(
            $this->container->get(NotFoundController::class)
        );

        // Handle request.
        $response = $handler->handle(
            $this->requestFromGlobalsFactory->createServerRequest()
        );

        // Set headers for the response.
        $this->loadHeaders(
            $response->getHeaders()
        );

        // We're ready to return a response.
        return $response;
    }

    private function loadRoutes(): void
    {
        $routeProvider = $this->container->get(RouteProviderInterface::class);
        $routeProvider->getRoutes($this->router);
    }

    /**
     * @param array $headers
     */
    private function loadHeaders(array $headers): void
    {
        // We don't want to send HTTP headers in console.
        if (defined('STDIN')) {
            return;
        }

        foreach ($headers as $name => $header) {
            header("{$name}: {$header}");
        }
    }

    /**
     * @param array $middleware
     *
     * @return MiddlewareBuilder
     */
    private function loadMiddleware(array $middleware): MiddlewareBuilder
    {
        $middlewareBuilder = new MiddlewareBuilder(
            array_reverse(array_merge(Config::DEFAULT_MIDDLEWARE, $middleware))
        );

        $middlewareBuilder->container = $this->container;

        return $middlewareBuilder;
    }
}
