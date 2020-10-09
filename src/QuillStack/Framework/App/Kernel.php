<?php

declare(strict_types=1);

namespace QuillStack\Framework\App;

use Psr\Http\Message\ResponseInterface;
use QuillStack\DI\Container;
use QuillStack\Framework\Providers\RouteProvider;
use QuillStack\Framework\RouteProviderInterface;
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
     * @param Container $container
     *
     * @return ResponseInterface
     */
    public function boot(Container $container): ResponseInterface
    {
        // Get routes definition.
        $routeProvider = $container->get(RouteProviderInterface::class);
        $routeProvider->getRoutes($this->router);

        // Dispatch route.
        $request = $this->requestFromGlobalsFactory->createServerRequest();
        $route = $this->dispatcher->dispatch($request);

        // Create controller object.
        $controller = $container->get(
            $route->getController()
        );

        // Handle request.
        return $controller->handle($request);
    }
}
