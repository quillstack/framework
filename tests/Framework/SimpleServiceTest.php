<?php

declare(strict_types=1);

namespace Framework;

use PHPUnit\Framework\TestCase;
use QuillStack\Framework\App;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Mocks\Providers\RouteProvider;

final class SimpleServiceTest extends TestCase
{
    public function testSimpleRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/version/service';
        $_SERVER['SERVER_PROTOCOL'] = '1.1';

        // Create App instance with config.
        $app = new App([
            RouteProviderInterface::class => RouteProvider::class,
        ]);

        // Run app.
        $response = $app->run();

        $this->assertEquals('{"version":"1.0.1"}', $response);
    }
}
