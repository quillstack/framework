<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Exceptions\Http\NotFoundHttpException;
use Quillstack\Framework\Interfaces\ControllerInterface;

class MissingThingController implements ControllerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new NotFoundHttpException('No user with that id');
    }
}
