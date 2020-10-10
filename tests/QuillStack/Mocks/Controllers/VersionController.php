<?php

declare(strict_types=1);

namespace QuillStack\Mocks\Controllers;

use QuillStack\Framework\Interfaces\ControllerInterface;
use QuillStack\Mocks\Requests\VersionRequest;
use QuillStack\Mocks\Responses\VersionResponse;

final class VersionController implements ControllerInterface
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
     * {@inheritDoc}
     */
    public function handle(): VersionResponse
    {
        return $this->response->setVersion('1.0.0');
    }
}
