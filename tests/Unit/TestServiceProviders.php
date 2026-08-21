<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Providers\ServiceProviderRegistryInterface;
use Quillstack\Framework\Tests\Mocks\Providers\MockServiceProvider;
use Quillstack\Framework\Tests\Mocks\Providers\MockServiceProviderRegistry;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestServiceProviders
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    private function app(bool $withProviders = true): App
    {
        MockServiceProvider::$order = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $config = [RouteProviderInterface::class => RouteProvider::class];

        if ($withProviders) {
            $config[ServiceProviderRegistryInterface::class] = MockServiceProviderRegistry::class;
        }

        return new App('', $config);
    }

    /**
     * Everything registers before anything boots, so a provider can count on the services
     * of the ones after it.
     */
    public function registeringHappensBeforeBooting()
    {
        $this->app();

        $this->assertEqual->equal([
            'register: first',
            'register: second',
            'boot: first',
            'sees: 1.0.1',
            'boot: second',
        ], MockServiceProvider::$order);
    }

    public function whatAProviderRegistersReachesTheContainer()
    {
        $app = $this->app();

        $this->assertEqual->equal('yes', $app->container->getConfig()['brought.by.the.first.provider']);
    }

    public function anApplicationWithoutProvidersStillRuns()
    {
        $response = $this->app(false)->run();

        $this->assertEqual->equal(200, $response->getStatusCode());
        $this->assertEqual->equal([], MockServiceProvider::$order);
    }

    public function theApplicationStillAnswersWithProviders()
    {
        $this->assertEqual->equal('{"version":"1.0.0"}', json_encode($this->app()->run()));
    }

    /**
     * A provider brings defaults, not decisions: what the application configured itself is
     * left alone.
     */
    public function theApplicationWinsOverAProvider()
    {
        MockServiceProvider::$order = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $app = new App('', [
            RouteProviderInterface::class => RouteProvider::class,
            ServiceProviderRegistryInterface::class => MockServiceProviderRegistry::class,
            'brought.by.the.first.provider' => 'set by the application',
        ]);

        $this->assertEqual->equal(
            'set by the application',
            $app->container->getConfig()['brought.by.the.first.provider']
        );
    }
}
