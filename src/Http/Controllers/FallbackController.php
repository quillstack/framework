<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Http\Responses\MethodNotAllowedResponse;
use Quillstack\Framework\Http\Responses\NotFoundResponse;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Middleware\Defaults\RoutingMiddleware;

/**
 * Answers whatever no route matched. There are two ways to match nothing, and they mean
 * different things: the path is unknown, or the path is known and the method is not.
 */
class FallbackController implements ControllerInterface
{
    public function __construct(
        private readonly NotFoundResponse $notFound,
        private readonly MethodNotAllowedResponse $methodNotAllowed
    ) {
        //
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $allowed = $request->getAttribute(RoutingMiddleware::ALLOWED_METHODS);

        if (!is_array($allowed) || $allowed === []) {
            return $this->notFound;
        }

        /** @var string[] $allowed */
        return $this->methodNotAllowed->withAllowedMethods($allowed);
    }
}
