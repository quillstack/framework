<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Exceptions\Http\UnprocessableContentHttpException;
use Quillstack\Framework\Interfaces\ControllerInterface;

class InvalidInputController implements ControllerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new UnprocessableContentHttpException();
    }
}
