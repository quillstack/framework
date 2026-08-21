<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Queue;

final class SendEmail
{
    public function __construct(public readonly string $email)
    {
        //
    }
}
