<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Auth;

use Quillstack\Auth\Identity;
use Quillstack\Auth\IdentityProviderInterface;

class Users implements IdentityProviderInterface
{
    public const TOKEN = 'a-token-nobody-would-guess-000000000000000000000000000000';

    /**
     * {@inheritDoc}
     */
    public function findByToken(string $token): ?Identity
    {
        return $token === self::TOKEN
            ? new Identity(42, ['support'], ['email' => 'ada@example.com'])
            : null;
    }
}
