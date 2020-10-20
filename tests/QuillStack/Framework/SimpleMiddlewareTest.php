<?php

declare(strict_types=1);

namespace QuillStack\Framework;

use Monolog\Test\TestCase;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Mocks\Middleware\TestMiddleware;
use QuillStack\Mocks\Providers\RouteProvider;

final class SimpleMiddlewareTest extends TestCase
{
    public function testMiddleware()
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

        $this->assertEquals('{"version":"1.0.0"}', json_encode($response));
        $this->assertEquals('middleware', $response->getHeaderLine('test'));
    }
}
