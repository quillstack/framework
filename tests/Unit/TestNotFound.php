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

    /**
     * A request matching no route used to answer 200 with an empty body, telling every
     * client that finding nothing had gone well.
     */
    public function notFoundRequest()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/not-found',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $response = (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
        ]))->run();

        $this->assertEqual->equal(404, $response->getStatusCode());
        $this->assertEqual->equal('Not Found', $response->getReasonPhrase());
        $this->assertEqual->equal(
            '{"error":{"status":404,"message":"Not Found"}}',
            json_encode($response)
        );
    }
}
