<?php

declare(strict_types=1);

namespace Framework;

use PHPUnit\Framework\TestCase;
use QuillStack\Framework\App;
use QuillStack\Framework\Interfaces\RouteProviderInterface;
use QuillStack\Mocks\Providers\RouteProvider;

final class NotFoundTest extends TestCase
{
    public function testNotFoundRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/not-found';
        $_SERVER['SERVER_PROTOCOL'] = '1.1';

        // Create App instance with config.
        $app = new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]);

        // Run app.
        $response = $app->run();

        $this->assertEquals('[]', json_encode($response));
    }
}
