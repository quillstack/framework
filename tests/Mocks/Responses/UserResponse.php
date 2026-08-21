<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Responses;

use Quillstack\Response\Response;

class UserResponse extends Response
{
    private string $user = '';
    private string $post = '';

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setPost(string $post): self
    {
        $this->post = $post;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [
            'user' => $this->user,
            'post' => $this->post,
        ];
    }
}
