<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestNotFound
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    public function notFoundRequest()
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

        $this->assertEqual->equal('[]', json_encode($response));
    }
}
