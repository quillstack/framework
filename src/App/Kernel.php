<?php

declare(strict_types=1);

namespace Quillstack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Quillstack\Framework\Http\Controllers\NotFoundController;
use Quillstack\Framework\Http\Middleware\ErrorMiddleware;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Middleware\MiddlewareBuilder;
use Quillstack\Router\Router;
use Quillstack\ServerRequest\Factory\ServerRequest\ServerRequestFromGlobalsFactory;

class Kernel
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ServerRequestFromGlobalsFactory $requestFromGlobalsFactory,
        private readonly Router $router
    ) {
        //
    }

    /**
     * @param array<int, class-string> $middleware
     */
    public function boot(array $middleware): ResponseInterface
    {
        // Load all routes.
        $this->loadRoutes();

        // Load all middleware classes.
        $middlewareBuilder = $this->loadMiddleware($middleware);

        // Get handler.
        /** @var NotFoundController $notFound */
        $notFound = $this->container->get(NotFoundController::class);
        $handler = $middlewareBuilder->build($notFound);

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
        /** @var RouteProviderInterface $routeProvider */
        $routeProvider = $this->container->get(RouteProviderInterface::class);
        $routeProvider->setRoutes($this->router);
    }

    /**
     * A header holds a list of values, and each of them is sent on a line of its own. The
     * first one replaces whatever was set before, the rest are added next to it.
     */
    /**
     * @param array<string, string[]> $headers
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

    /**
     * @param array<int, class-string> $middleware
     */
    private function loadMiddleware(array $middleware): MiddlewareBuilder
    {
        $classes = array_reverse(array_merge(Config::DEFAULT_MIDDLEWARE, $middleware));

        // Whatever the application adds, nothing runs outside the error middleware, or an
        // exception thrown there would reach the client as a fatal error.
        array_unshift($classes, ErrorMiddleware::class);

        return new MiddlewareBuilder($classes, $this->container);
    }
}
