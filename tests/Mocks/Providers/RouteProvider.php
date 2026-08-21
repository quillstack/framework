<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Providers;

use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Controllers\ServiceVersionController;
use Quillstack\Framework\Tests\Mocks\Controllers\UserPostController;
use Quillstack\Framework\Tests\Mocks\Controllers\UserRequestController;
use Quillstack\Framework\Tests\Mocks\Controllers\VersionController;
use Quillstack\Router\Router;

class RouteProvider implements RouteProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function setRoutes(Router $router): void
    {
        $router->get('/version', VersionController::class);
        $router->get('/version/service', ServiceVersionController::class);
        $router->get('/users/:user/posts/:post', UserPostController::class)->name('user.post');
        $router->delete('/users/{user}/posts/{post}', UserRequestController::class)->name('user.post.delete');
    }
}
