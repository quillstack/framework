<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Requests\UserRequest;
use Quillstack\Framework\Tests\Mocks\Responses\UserResponse;

/**
 * Declares its own request class, and still reads the route parameters off it.
 */
class UserRequestController implements ControllerInterface
{
    public UserResponse $response;
    public UserRequest $request;

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): UserResponse
    {
        return $this->response
            ->setUser((string) $this->request->getAttribute('user'))
            ->setPost((string) $this->request->getAttribute('post'));
    }
}
