<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Requests\UserRequest;
use Quillstack\Framework\Tests\Mocks\Responses\UserResponse;

/**
 * Kept on property injection on purpose: dependencies go through the constructor now, and
 * this proves filling public properties still works for anyone who wants it. The request
 * has to be a property, since the routing middleware replaces it with the one carrying the
 * route parameters.
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
