<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\Http\Message\ResponseInterface;
use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestMethodNotAllowed
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    /**
     * @param array<string, string> $server
     */
    private function run(array $server): ResponseInterface
    {
        $_SERVER = $server + [
            'HTTP_HOST' => 'localhost',
            'SERVER_PROTOCOL' => '1.1',
        ];

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]))->run();
    }

    /**
     * A known path asked the wrong way used to answer 404, which tells a client the resource
     * does not exist rather than that it does not answer to that.
     */
    public function aKnownPathAskedTheWrongWay()
    {
        $response = $this->run([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/version',
        ]);

        $this->assertEqual->equal(405, $response->getStatusCode());
        $this->assertEqual->equal('Method Not Allowed', $response->getReasonPhrase());
    }

    /**
     * A 405 which does not say what is allowed leaves the client guessing, so the `Allow`
     * header is part of the answer rather than an extra.
     */
    public function theAllowedMethodsAreNamed()
    {
        $response = $this->run([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/users/1/posts/2',
        ]);

        $this->assertEqual->equal(405, $response->getStatusCode());
        $this->assertEqual->equal('GET, DELETE', $response->getHeaderLine('Allow'));
        $this->assertEqual->equal(
            '{"error":{"status":405,"message":"Method Not Allowed","allowed":["GET, DELETE"]}}',
            json_encode($response)
        );
    }

    /**
     * An unknown path is still nothing found, whatever it was asked with.
     */
    public function anUnknownPathIsStillNotFound()
    {
        $response = $this->run([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/nothing-here',
        ]);

        $this->assertEqual->equal(404, $response->getStatusCode());
    }
}
