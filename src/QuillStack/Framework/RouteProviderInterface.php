<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use QuillStack\Router\Router;

interface RouteProviderInterface
{
    /**
     * @param Router $router
     */
    public function getRoutes(Router &$router): void;
}
