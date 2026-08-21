<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Providers;

use Quillstack\Framework\Providers\ServiceProviderRegistryInterface;

class MockServiceProviderRegistry implements ServiceProviderRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getProviders(): array
    {
        return [
            MockServiceProvider::class,
            MockSecondServiceProvider::class,
        ];
    }
}
