<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestRouteParameters
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    private function run(string $method, string $uri): string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['SERVER_PROTOCOL'] = '1.1';

        $app = new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]);

        return json_encode($app->run());
    }

    public function parametersReachTheController()
    {
        $this->assertEqual->equal(
            '{"user":"13","post":"7"}',
            $this->run('GET', '/users/13/posts/7')
        );
    }

    public function parametersSurviveAQueryString()
    {
        $this->assertEqual->equal(
            '{"user":"13","post":"7"}',
            $this->run('GET', '/users/13/posts/7?draft=1&sort=date')
        );
    }

    public function parametersReachACustomRequestClass()
    {
        $this->assertEqual->equal(
            '{"user":"42","post":"5"}',
            $this->run('DELETE', '/users/42/posts/5')
        );
    }

    public function aTrailingSlashMatchesTheSameRoute()
    {
        $this->assertEqual->equal(
            '{"user":"13","post":"7"}',
            $this->run('GET', '/users/13/posts/7/')
        );
    }
}
