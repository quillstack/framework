<?php

declare(strict_types=1);

namespace Quillstack\Framework\Interfaces;

use Quillstack\Router\Router;

interface RouteProviderInterface
{
    /**
     * @param Router $router
     */
    public function setRoutes(Router &$router): void;
}
