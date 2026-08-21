<?php

declare(strict_types=1);

namespace Quillstack\Framework\Providers;

use Psr\Container\ContainerInterface;

/**
 * A piece of the application which brings its own services. Registering happens first, for
 * every provider, so booting can count on everything being there.
 */
abstract class ServiceProvider
{
    /**
     * Definitions this provider adds to the container. What the application configured
     * itself is left alone, so a provider brings defaults rather than decisions.
     *
     * @return array<string, mixed>
     */
    public function register(): array
    {
        return [];
    }

    /**
     * Run once every provider has registered, with everything available. Listening for
     * events, warming a cache or anything else needing services belongs here rather than
     * in register().
     */
    public function boot(ContainerInterface $container): void
    {
        //
    }
}
