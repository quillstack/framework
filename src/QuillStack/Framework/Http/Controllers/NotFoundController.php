<?php

declare(strict_types=1);

namespace QuillStack\Framework\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuillStack\Framework\Http\Responses\NotFoundResponse;

final class NotFoundController implements RequestHandlerInterface
{
    public NotFoundResponse $response;

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
