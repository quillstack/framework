<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Providers;

use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Mocks\Controllers\VersionController;
use QuillStack\Router\Router;

final class RouteProvider implements RouteProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getRoutes(Router &$router): void
    {
        $router->get('/version', VersionController::class);
    }
}
