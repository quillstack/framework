<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Quillstack\Framework\Exceptions\Http\BadRequestHttpException;

/**
 * Reads a JSON request body into the parsed body.
 *
 * PHP fills `$_POST` from a form-encoded body and from nothing else, so a framework built for
 * APIs was handed nothing at all by every client that sent it JSON — which is every client an
 * API has. The body was not rejected, it was simply absent, and what came back was a
 * validation error naming fields the caller had in fact sent.
 *
 * Broken JSON is a `400` rather than an empty body, for the same reason: an answer saying the
 * `email` field is missing, to somebody who sent an `email` field and a stray comma, is an
 * answer that sends them looking in the wrong place.
 */
class JsonBodyMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($this->withJsonBody($request));
    }

    private function withJsonBody(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!self::isJson($request)) {
            return $request;
        }

        $body = (string) $request->getBody();

        if (trim($body) === '') {
            return $request;
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('The request body is not valid JSON: ' . json_last_error_msg());
        }

        // A body which is a list or a bare value is valid JSON and not something a parsed body
        // can hold, so it is left where it is rather than forced into one.
        return is_array($decoded) && !array_is_list($decoded)
            ? $request->withParsedBody($decoded)
            : $request;
    }

    private static function isJson(ServerRequestInterface $request): bool
    {
        $type = $request->getHeaderLine('Content-Type');

        // `application/json`, and everything shaped like `application/vnd.thing+json`.
        return preg_match('#^application/([\w.+-]+\+)?json#i', trim($type)) === 1;
    }
}
