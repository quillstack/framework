<?php

declare(strict_types=1);

namespace QuillStack\Framework\Interfaces;

use QuillStack\Router\Router;

interface RouteProviderInterface
{
    /**
     * @param Router $router
     */
    public function getRoutes(Router &$router): void;
}
