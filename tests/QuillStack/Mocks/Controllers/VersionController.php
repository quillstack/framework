<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QuillStack\Framework\Interfaces\ControllerInterface;
use QuillStack\Mocks\Responses\VersionResponse;

final class VersionController implements ControllerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new VersionResponse();
    }
}
