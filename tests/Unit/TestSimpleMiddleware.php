<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Middleware\TestMiddleware;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestSimpleMiddleware
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    public function middleware()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/version';
        $_SERVER['SERVER_PROTOCOL'] = '1.1';

        // Create App instance with config.
        $app = new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ], [
            TestMiddleware::class,
        ]);

        // Run app.
        $response = $app->run();

        $this->assertEqual->equal('{"version":"1.0.0"}', json_encode($response));
        $this->assertEqual->equal('middleware', $response->getHeaderLine('test'));
    }
}
