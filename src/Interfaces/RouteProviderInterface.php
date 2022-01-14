<?php

declare(strict_types=1);

namespace Quillstack\Framework\Interfaces;

use Quillstack\Router\Router;

interface RouteProviderInterface
{
    public function getRoutes(Router &$router): void;
}
