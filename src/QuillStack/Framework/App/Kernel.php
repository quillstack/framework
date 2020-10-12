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
        $this->container = $container;

        // Load all routes.
        $this->loadRoutes();

        // Include all middleware classes.
        $this->middleware = array_reverse(array_merge(Config::DEFAULT_MIDDLEWARE, $middleware));
        $handler = $this->buildHandlerWithMiddleware();

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
     * @return RequestHandlerInterface
     */
    private function buildHandlerWithMiddleware(): RequestHandlerInterface
    {
        $middlewareProvider = new MiddlewareProvider(
            $this->container->get(NotFoundController::class)
        );

        array_walk($this->middleware, fn ($class) => $this->addMiddleware($middlewareProvider, $class));

        return $middlewareProvider;
    }

    /**
     * @param MiddlewareProvider $middlewareProvider
     * @param string $middlewareClass
     */
    private function addMiddleware(MiddlewareProvider &$middlewareProvider, string $middlewareClass): void
    {
        $middleware = $this->container->get($middlewareClass);

        if (isset($middleware->container)) {
            $middleware->container = $this->container;
        }

        $middlewareProvider->add($middleware);
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

        array_walk($headers, fn (&$header, $name) => header("{$name}: {$header}"));
    }
}
