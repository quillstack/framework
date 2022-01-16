<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Requests\VersionRequest;
use Quillstack\Framework\Tests\Mocks\Responses\VersionResponse;

class VersionController implements ControllerInterface
{
    public VersionResponse $response;
    public VersionRequest $request;

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): VersionResponse
    {
        return $this->response->setVersion('1.0.0');
    }
}
