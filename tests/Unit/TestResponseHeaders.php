<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Middleware\HeadersMiddleware;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestResponseHeaders
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    private function respond()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ], [
            HeadersMiddleware::class,
        ]))->run();
    }

    /**
     * PSR-7 keeps a list of values per header, which is what lets one header be sent on
     * several lines.
     */
    public function aHeaderCanHoldSeveralValues()
    {
        $response = $this->respond();

        $this->assertEqual->equal(['first=1', 'second=2'], $response->getHeader('Set-Cookie'));
        $this->assertEqual->equal('first=1, second=2', $response->getHeaderLine('Set-Cookie'));
    }

    /**
     * A value carrying commas used to be cut into pieces, which broke every date sent.
     */
    public function aValueWithCommasStaysWhole()
    {
        $response = $this->respond();

        $this->assertEqual->equal(
            ['Fri, 11 Sep 2020 20:46:34 GMT'],
            $response->getHeader('Last-Modified')
        );
    }

    public function getHeadersReturnsAListPerHeader()
    {
        $headers = $this->respond()->getHeaders();

        $this->assertEqual->equal(['first=1', 'second=2'], $headers['Set-Cookie']);
        $this->assertEqual->equal(['text/json'], $headers['Content-Type']);
    }
}
