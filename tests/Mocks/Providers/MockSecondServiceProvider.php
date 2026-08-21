<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Providers;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Providers\ServiceProvider;

class MockSecondServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        MockServiceProvider::$order[] = 'register: second';

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function boot(ContainerInterface $container): void
    {
        MockServiceProvider::$order[] = 'boot: second';
    }
}
