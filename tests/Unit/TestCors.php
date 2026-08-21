<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Http\Middleware\CorsMiddleware;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestCors
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $cors
     */
    private function respond(string $method, array $headers = [], array $cors = [])
    {
        $_SERVER = [
            'REQUEST_METHOD' => $method,
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
            CorsMiddleware::class => $cors === [] ? new CorsMiddleware() : new CorsMiddleware(...$cors),
        ], [
            CorsMiddleware::class,
        ]))->run();
    }

    public function apageIsToldWhichHostsMayReadTheAnswer()
    {
        $response = $this->respond('GET', ['Origin' => 'https://quillstack.com']);

        $this->assertEqual->equal(['*'], $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEqual->equal(['Origin'], $response->getHeader('Vary'));
        $this->assertEqual->equal(200, $response->getStatusCode());
    }

    /**
     * A request with no Origin is not a browser asking, so nothing is added to it.
     */
    public function withoutAnOriginNothingIsAdded()
    {
        $this->assertEqual->equal([], $this->respond('GET')->getHeader('Access-Control-Allow-Origin'));
    }

    public function ahostWhichIsNotAllowedIsToldNothing()
    {
        $response = $this->respond('GET', ['Origin' => 'https://somewhere.else'], [
            'origins' => ['https://quillstack.com'],
        ]);

        $this->assertEqual->equal([], $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function anAllowedHostIsNamed()
    {
        $response = $this->respond('GET', ['Origin' => 'https://quillstack.com'], [
            'origins' => ['https://quillstack.com'],
        ]);

        $this->assertEqual->equal(['https://quillstack.com'], $response->getHeader('Access-Control-Allow-Origin'));
    }

    /**
     * The question a browser asks before the real request is answered here, and the
     * application never sees it.
     */
    public function apreflightIsAnsweredWithoutReachingTheApplication()
    {
        $response = $this->respond('OPTIONS', [
            'Origin' => 'https://quillstack.com',
            'Access-Control-Request-Method' => 'POST',
        ]);

        $this->assertEqual->equal(204, $response->getStatusCode());
        $this->assertEqual->equal([], $response->send());
        $this->assertBoolean->isTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertBoolean->isTrue($response->hasHeader('Access-Control-Allow-Headers'));
        $this->assertEqual->equal(['86400'], $response->getHeader('Access-Control-Max-Age'));
    }

    /**
     * An OPTIONS request which is not a preflight is a request like any other.
     */
    public function optionsWithoutTheQuestionIsARequestLikeAnyOther()
    {
        $response = $this->respond('OPTIONS', ['Origin' => 'https://quillstack.com']);

        $this->assertBoolean->isFalse($response->hasHeader('Access-Control-Allow-Methods'));
    }

    /**
     * A browser refuses a wildcard together with credentials, so the host which asked is
     * named instead.
     */
    public function withCredentialsTheHostIsNamedRatherThanStarred()
    {
        $response = $this->respond('GET', ['Origin' => 'https://quillstack.com'], [
            'origins' => ['*'],
            'credentials' => true,
        ]);

        $this->assertEqual->equal(['https://quillstack.com'], $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEqual->equal(['true'], $response->getHeader('Access-Control-Allow-Credentials'));
    }

    public function headersThePageMayReadAreNamed()
    {
        $response = $this->respond('GET', ['Origin' => 'https://quillstack.com'], [
            'exposed' => ['X-Total-Count', 'X-Page'],
        ]);

        $this->assertEqual->equal(
            ['X-Total-Count, X-Page'],
            $response->getHeader('Access-Control-Expose-Headers')
        );
    }
}
