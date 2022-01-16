<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Providers;

use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Controllers\ServiceVersionController;
use Quillstack\Framework\Tests\Mocks\Controllers\VersionController;
use Quillstack\Router\Router;

class RouteProvider implements RouteProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function setRoutes(Router &$router): void
    {
        $router->get('/version', VersionController::class);
        $router->get('/version/service', ServiceVersionController::class);
    }
}
