<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Quillstack\Framework\Http\Responses\EmptyResponse;
use Quillstack\HttpRequest\HttpRequest;
use Quillstack\Response\StatusCode;

/**
 * Answers what a browser asks before it will let a page read an API on another host. Without
 * this every request from a browser is refused before the application sees it.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param string[] $origins the hosts allowed to read the answer, or `['*']` for any
     * @param string[] $methods
     * @param string[] $headers
     * @param string[] $exposed headers the page is allowed to read off the answer
     * @param int $maxAge how long a browser may remember this, in seconds
     */
    public function __construct(
        private readonly array $origins = ['*'],
        private readonly array $methods = HttpRequest::AVAILABLE_METHODS,
        private readonly array $headers = ['Content-Type', 'Authorization'],
        private readonly array $exposed = [],
        private readonly int $maxAge = 86400,
        private readonly bool $credentials = false
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        // A preflight is answered here and never reaches the application: it asks what is
        // allowed, and the application has nothing to say about that.
        $response = $this->isPreflight($request)
            ? new EmptyResponse(StatusCode::NO_CONTENT)
            : $handler->handle($request);

        if ($origin === '' || !$this->allows($origin)) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin($origin))
            ->withHeader('Vary', 'Origin');

        if ($this->credentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->exposed !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->exposed));
        }

        if (!$this->isPreflight($request)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->methods))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->headers))
            ->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
    }

    private function isPreflight(ServerRequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === HttpRequest::METHOD_OPTIONS
            && $request->hasHeader('Access-Control-Request-Method');
    }

    private function allows(string $origin): bool
    {
        return in_array('*', $this->origins, true) || in_array($origin, $this->origins, true);
    }

    /**
     * A browser refuses `*` together with credentials, so the origin which asked is named.
     */
    private function allowedOrigin(string $origin): string
    {
        return in_array('*', $this->origins, true) && !$this->credentials ? '*' : $origin;
    }
}
