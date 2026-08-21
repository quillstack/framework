<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Unit;

use Psr\SimpleCache\CacheInterface;
use Quillstack\Cache\ArrayCache;
use Quillstack\Cache\Clock\FrozenClock;
use Quillstack\Framework\App;
use Quillstack\Framework\Http\Middleware\RateLimitMiddleware;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\Tests\Mocks\Providers\RouteProvider;
use Quillstack\UnitTests\AssertEqual;

class TestRateLimit
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    private function respond(CacheInterface $cache, int $limit = 3, string $address = '10.0.0.1', ?FrozenClock $clock = null)
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
            'REMOTE_ADDR' => $address,
        ];
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';

        return (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
            RateLimitMiddleware::class => new RateLimitMiddleware($cache, $limit, 60, $clock),
        ], [
            RateLimitMiddleware::class,
        ]))->run();
    }

    public function whatIsLeftIsCountedDown()
    {
        $cache = new ArrayCache();

        $first = $this->respond($cache);

        $this->assertEqual->equal(['3'], $first->getHeader('X-RateLimit-Limit'));
        $this->assertEqual->equal(['2'], $first->getHeader('X-RateLimit-Remaining'));
        $this->assertEqual->equal(['1'], $this->respond($cache)->getHeader('X-RateLimit-Remaining'));
        $this->assertEqual->equal(['0'], $this->respond($cache)->getHeader('X-RateLimit-Remaining'));
    }

    /**
     * Over the limit the request is refused, and the error middleware answers 429 without
     * knowing anything about rate limiting.
     */
    public function pastTheLimitTheRequestIsRefused()
    {
        $cache = new ArrayCache();

        for ($i = 0; $i < 3; $i++) {
            $this->respond($cache);
        }

        $response = $this->respond($cache);

        $this->assertEqual->equal(429, $response->getStatusCode());
        $this->assertEqual->equal(
            ['error' => ['status' => 429, 'message' => 'Too many requests, try again later']],
            $response->send()
        );
    }

    /**
     * The window starts with the first request of it and is not pushed back by the ones
     * after, so a caller who never stops is still let through once it has passed.
     */
    public function theWindowIsNotPushedBackByFurtherRequests()
    {
        $clock = new FrozenClock();
        $cache = new ArrayCache($clock);

        // Three requests at the start of the window, then one more thirty seconds in.
        for ($i = 0; $i < 3; $i++) {
            $this->respond($cache, 3, '10.0.0.1', $clock);
        }

        $clock->sleep(30);
        $this->assertEqual->equal(429, $this->respond($cache, 3, '10.0.0.1', $clock)->getStatusCode());

        // The window closes sixty seconds after the first request, not after the last.
        $clock->sleep(31);
        $this->assertEqual->equal(200, $this->respond($cache, 3, '10.0.0.1', $clock)->getStatusCode());
    }

    public function callersAreCountedApart()
    {
        $cache = new ArrayCache();

        for ($i = 0; $i < 3; $i++) {
            $this->respond($cache, 3, '10.0.0.1');
        }

        $this->assertEqual->equal(429, $this->respond($cache, 3, '10.0.0.1')->getStatusCode());
        $this->assertEqual->equal(200, $this->respond($cache, 3, '10.0.0.2')->getStatusCode());
    }

    /**
     * Nothing says who is asking when there is no address, and they are all counted as one.
     */
    public function withoutAnAddressEverybodyIsOneCaller()
    {
        $cache = new ArrayCache();
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/version',
            'SERVER_PROTOCOL' => '1.1',
        ];

        $response = (new App('', [
            RouteProviderInterface::class => RouteProvider::class,
            RateLimitMiddleware::class => new RateLimitMiddleware($cache, 1, 60),
        ], [
            RateLimitMiddleware::class,
        ]))->run();

        $this->assertEqual->equal(['0'], $response->getHeader('X-RateLimit-Remaining'));
    }
}
