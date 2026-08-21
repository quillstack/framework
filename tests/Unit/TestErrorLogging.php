<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\Log\LoggerInterface;
use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Logger\MockLogger;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestErrorLogging
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    private function respond(string $uri, MockLogger $logger)
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => $uri,
            'SERVER_PROTOCOL' => '1.1',
        ];
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
            LoggerInterface::class => $logger,
        ]))->run();
    }

    public function anErrorOfTheApplicationIsLogged()
    {
        $logger = new MockLogger();
        $this->respond('/broken', $logger);

        $this->assertEqual->equal(1, count($logger->entries));
        $this->assertEqual->equal('error', $logger->entries[0]['level']);
        $this->assertEqual->equal('database is down', $logger->entries[0]['message']);
        $this->assertEqual->equal('RuntimeException', $logger->entries[0]['context']['exception']);
    }

    /**
     * A client error says the request was wrong, not the application, so it is answered
     * and not written to the log.
     */
    public function aClientErrorIsNotLogged()
    {
        $logger = new MockLogger();
        $this->respond('/missing', $logger);

        $this->assertEqual->equal(0, count($logger->entries));
    }

    /**
     * Nothing breaks when no logger was configured, which is the default.
     */
    public function theErrorIsAnsweredWithoutALogger()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/broken',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $response = (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]))->run();

        $this->assertEqual->equal(500, $response->getStatusCode());
    }
}
