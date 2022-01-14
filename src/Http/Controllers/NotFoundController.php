<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Http\Responses\NotFoundResponse;
use Quillstack\Framework\Interfaces\ControllerInterface;

class NotFoundController implements ControllerInterface
{
    public NotFoundResponse $response;

    public function handle(ServerRequestInterface $request): NotFoundResponse
    {
        return $this->response;
    }
}
