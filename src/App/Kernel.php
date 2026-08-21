<?php

declare(strict_types=1);

namespace Quillstack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use Quillstack\DI\Container;
use Quillstack\Framework\Http\Controllers\NotFoundController;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Middleware\MiddlewareBuilder;
use Quillstack\Router\Dispatcher;
use Quillstack\Router\Router;
use Quillstack\ServerRequest\Factory\ServerRequest\ServerRequestFromGlobalsFactory;

class Kernel
{
    private ?Container $container;
    public Dispatcher $dispatcher;
    public ServerRequestFromGlobalsFactory $requestFromGlobalsFactory;
    public Router $router;

    public function boot(Container $container, array $middleware): ResponseInterface
    {
        $this->container = $container;

        // Load all routes.
        $this->loadRoutes();

        // Load all middleware classes.
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
        $routeProvider->setRoutes($this->router);
    }

    /**
     * A header holds a list of values, and each of them is sent on a line of its own. The
     * first one replaces whatever was set before, the rest are added next to it.
     */
    private function loadHeaders(array $headers): void
    {
        // There are no HTTP headers to send in the console.
        if (PHP_SAPI === 'cli') {
            return;
        }

        foreach ($headers as $name => $values) {
            foreach (array_values((array) $values) as $index => $value) {
                header("{$name}: {$value}", $index === 0);
            }
        }
    }

    private function loadMiddleware(array $middleware): MiddlewareBuilder
    {
        $middlewareBuilder = new MiddlewareBuilder(
            array_reverse(array_merge(Config::DEFAULT_MIDDLEWARE, $middleware))
        );

        $middlewareBuilder->container = $this->container;

        return $middlewareBuilder;
    }
}
