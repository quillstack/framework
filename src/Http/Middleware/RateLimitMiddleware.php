<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Middleware;

use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Quillstack\Clock\SystemClock;
use Quillstack\Framework\Exceptions\Http\TooManyRequestsHttpException;

/**
 * Counts what one caller asks for and refuses the rest once there has been enough of it.
 * The count lives in the cache, so it is shared by however many processes are answering.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param int $limit how many requests one caller may make within the window
     * @param int $window how long the window is, in seconds
     */
    private ClockInterface $clock;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $limit = 60,
        private readonly int $window = 60,
        ?ClockInterface $clock = null
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->keyFor($request);
        $now = $this->clock->now()->getTimestamp();
        $window = $this->readWindow($key, $now);

        if ($window['used'] >= $this->limit) {
            throw new TooManyRequestsHttpException('Too many requests, try again later');
        }

        ++$window['used'];

        // When the window closes is written down and carried, so the request after this one
        // does not push it back. Storing the count alone and leaving the time to the cache
        // meant the second request replaced the entry without one, and the window never
        // closed again.
        $this->cache->set($key, $window, max(1, $window['closesAt'] - $now));

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->limit - $window['used']));
    }

    /**
     * How much of the window is used, and when it closes. A window which has closed starts
     * again from nothing.
     *
     * @return array{used: int, closesAt: int}
     */
    private function readWindow(string $key, int $now): array
    {
        $stored = $this->cache->get($key);

        if (is_array($stored) && isset($stored['used'], $stored['closesAt'])
            && is_int($stored['used']) && is_int($stored['closesAt']) && $stored['closesAt'] > $now) {
            return ['used' => $stored['used'], 'closesAt' => $stored['closesAt']];
        }

        return ['used' => 0, 'closesAt' => $now + $this->window];
    }

    /**
     * Who is asking. The address the request came from is all an API without authentication
     * knows about that.
     */
    private function keyFor(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        $address = $server['REMOTE_ADDR'] ?? 'unknown';

        return 'rate-limit.' . sha1(is_scalar($address) ? (string) $address : 'unknown');
    }
}
