<?php

declare(strict_types=1);

namespace Quillstack\Framework\Providers;

interface ServiceProviderRegistryInterface
{
    /**
     * The providers of the application, given as class names, in the order they register.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function getProviders(): array;
}
