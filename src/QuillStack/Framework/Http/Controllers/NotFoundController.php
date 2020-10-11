<?php

declare(strict_types=1);

namespace QuillStack\Framework\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use QuillStack\Framework\Http\Responses\NotFoundResponse;
use QuillStack\Framework\Interfaces\ControllerInterface;

final class NotFoundController implements ControllerInterface
{
    /**
     * @var NotFoundResponse
     */
    public NotFoundResponse $response;

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): NotFoundResponse
    {
        return $this->response;
    }
}
