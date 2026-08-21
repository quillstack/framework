<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Responses\VersionResponse;
use Quillstack\Framework\Tests\Mocks\Services\VersionService;

class ServiceVersionController implements ControllerInterface
{
    public function __construct(
        private readonly VersionResponse $response,
        private readonly VersionService $versionService
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): VersionResponse
    {
        return $this->response->setVersion(
            $this->versionService->getVersion()
        );
    }
}
