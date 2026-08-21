<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Responses\UserResponse;

/**
 * Reads the route parameters from the request handed over by the routing middleware.
 */
class UserPostController implements ControllerInterface
{
    public function __construct(private readonly UserResponse $response)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): UserResponse
    {
        return $this->response
            ->setUser((string) $request->getAttribute('user'))
            ->setPost((string) $request->getAttribute('post'));
    }
}
