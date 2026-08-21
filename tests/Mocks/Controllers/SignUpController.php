<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quillstack\Framework\Interfaces\ControllerInterface;
use Quillstack\Framework\Tests\Mocks\Responses\UserResponse;
use Quillstack\Framework\Validation\Validator;

class SignUpController implements ControllerInterface
{
    public function __construct(
        private readonly UserResponse $response,
        private readonly Validator $validator
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->check((array) $request->getParsedBody(), [
            'email' => ['required', 'email'],
            'age' => ['required', 'integer', 'min:18'],
            'plan' => ['required', 'in:free,pro'],
        ]);

        return $this->response->setUser($data['email']);
    }
}
