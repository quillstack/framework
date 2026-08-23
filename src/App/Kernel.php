<?php

declare(strict_types=1);

namespace Quillstack\Framework\App;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Quillstack\Framework\Http\Controllers\FallbackController;
use Quillstack\Framework\Http\Middleware\ErrorMiddleware;
use Quillstack\Auth\IdentityProviderInterface;
use Quillstack\Auth\Middleware\AuthenticationMiddleware;
use Quillstack\Framework\Exceptions\NoIdentityProviderException;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Middleware\MiddlewareBuilder;
use Quillstack\Router\GuardedRouteInterface;
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

        // Get handler. Nothing matched a route ends up here, which is where the difference
        // between an unknown path and an unknown method is answered.
        /** @var FallbackController $fallback */
        $fallback = $this->container->get(FallbackController::class);
        $handler = $middlewareBuilder->build($fallback);

        // Handle request.
        $response = $handler->handle(
            $this->requestFromGlobalsFactory->createServerRequest()
        );

        // Say what happened, and then how.
        $this->sendStatus($response);
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

        $this->refuseGuardsNobodyEnforces();
    }

    /**
     * A route which says only somebody may reach it, in an application which has not said who
     * anybody is, would be open while reading as guarded.
     *
     * That is the one failure the arrangement exists to prevent, so it is refused here —
     * before a single request is served, rather than on the first one that should have been
     * turned away.
     */
    private function refuseGuardsNobodyEnforces(): void
    {
        if ($this->container->has(IdentityProviderInterface::class)) {
            return;
        }

        foreach ($this->router->getRoutes() as $route) {
            if ($route instanceof GuardedRouteInterface && $route->requiresAuthentication()) {
                throw new NoIdentityProviderException(
                    "The route `{$route->getKey()}` requires authentication, and nothing "
                    . 'answers for `' . IdentityProviderInterface::class . '`. Configure one, '
                    . 'or the route is open while reading as guarded.'
                );
            }
        }
    }

    /**
     * The status line, which used to be left alone: a response saying 404 went out as 200,
     * and anything reading the status rather than the body was told the request had worked.
     */
    private function sendStatus(ResponseInterface $response): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        http_response_code($response->getStatusCode());
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

        // Who a request is from is worked out where the application has said who its users
        // are, and not otherwise — the same rule as the queue and the entities.
        //
        // It goes second: inside the error middleware, so a refusal is answered rather than
        // thrown, and outside everything else, so a request which is going to be refused is
        // refused before any work is done for it. Adding it to the end of the list would put
        // it after routing, which is to say never — routing calls the controller.
        if ($this->container->has(IdentityProviderInterface::class)) {
            array_splice($classes, 1, 0, [AuthenticationMiddleware::class]);
        }

        return new MiddlewareBuilder($classes, $this->container);
    }
}
