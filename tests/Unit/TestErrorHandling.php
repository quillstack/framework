<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertArray;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * An exception thrown by a controller used to end the request as a fatal error, with the
 * stack trace going out to the client and nothing written anywhere.
 */
class TestErrorHandling
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertArray $assertArray,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    private function respond(string $uri, string $env = 'production')
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => $uri,
            'SERVER_PROTOCOL' => '1.1',
        ];
        $_ENV['APP_ENV'] = $env;
        $_SERVER['APP_ENV'] = $env;

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]))->run();
    }

    public function anUnexpectedExceptionAnswersWithFiveHundred()
    {
        $response = $this->respond('/broken');

        $this->assertEqual->equal(500, $response->getStatusCode());
        $this->assertEqual->equal('Internal Server Error', $response->getReasonPhrase());
    }

    public function productionIsToldNothingAboutTheInternals()
    {
        $body = $this->respond('/broken')->send();

        $this->assertEqual->equal(
            ['error' => ['status' => 500, 'message' => 'Internal Server Error']],
            $body
        );
    }

    public function outsideProductionTheExceptionIsDescribed()
    {
        $body = $this->respond('/broken', 'develop')->send();

        $this->assertEqual->equal(500, $body['error']['status']);
        $this->assertEqual->equal('RuntimeException', $body['error']['exception']);
        $this->assertArray->hasKey('file', $body['error']);
        $this->assertArray->hasKey('line', $body['error']);
        $this->assertArray->hasKey('trace', $body['error']);
    }

    public function anHttpExceptionAnswersWithItsOwnStatus()
    {
        $response = $this->respond('/missing');

        $this->assertEqual->equal(404, $response->getStatusCode());
        $this->assertEqual->equal(
            ['error' => ['status' => 404, 'message' => 'No user with that id']],
            $response->send()
        );
    }

    public function anHttpExceptionWithoutAMessageUsesTheReasonPhrase()
    {
        $response = $this->respond('/invalid');

        $this->assertEqual->equal(422, $response->getStatusCode());
        $this->assertEqual->equal(
            ['error' => ['status' => 422, 'message' => 'Unprocessable Content']],
            $response->send()
        );
    }

    /**
     * Outside production every error is described, a client error included: knowing where
     * a 404 was thrown from is exactly what is wanted while the application is being
     * worked on. Production is told nothing either way.
     */
    public function aClientErrorIsDescribedOutsideProductionOnly()
    {
        $inDevelop = $this->respond('/missing', 'develop')->send();
        $inProduction = $this->respond('/missing')->send();

        $this->assertBoolean->isTrue(isset($inDevelop['error']['trace']));
        $this->assertEqual->equal('No user with that id', $inDevelop['error']['message']);

        $this->assertBoolean->isFalse(isset($inProduction['error']['trace']));
    }

    public function theErrorIsStillJson()
    {
        $this->assertEqual->equal(
            ['text/json'],
            $this->respond('/broken')->getHeader('Content-Type')
        );
    }
}
