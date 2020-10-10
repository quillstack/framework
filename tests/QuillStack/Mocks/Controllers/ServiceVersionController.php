<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Controllers;

use QuillStack\Framework\Interfaces\ControllerInterface;
use QuillStack\Mocks\Requests\VersionRequest;
use QuillStack\Mocks\Responses\VersionResponse;
use QuillStack\Mocks\Services\VersionService;

final class ServiceVersionController implements ControllerInterface
{
    /**
     * @var VersionResponse
     */
    public VersionResponse $response;

    /**
     * @var VersionRequest
     */
    public VersionRequest $request;

    /**
     * @var VersionService
     */
    public VersionService $versionService;

    /**
     * {@inheritDoc}
     */
    public function handle(): VersionResponse
    {
        return $this->response->setVersion(
            $this->versionService->getVersion()
        );
    }
}
