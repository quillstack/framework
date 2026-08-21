<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Providers;

use Psr\Container\ContainerInterface;
use Quillstack\Framework\Providers\ServiceProvider;
use Quillstack\Framework\Tests\Mocks\Services\VersionService;

class MockServiceProvider extends ServiceProvider
{
    /**
     * @var string[]
     */
    public static array $order = [];

    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        self::$order[] = 'register: first';

        return ['brought.by.the.first.provider' => 'yes'];
    }

    /**
     * {@inheritDoc}
     */
    public function boot(ContainerInterface $container): void
    {
        self::$order[] = 'boot: first';

        // Booting can count on what every provider registered.
        self::$order[] = 'sees: ' . (string) $container->get(VersionService::class)->getVersion();
    }
}
