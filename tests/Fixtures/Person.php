<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures;

use Quillstack\Serializer\Attributes\Exposed;

final class Person
{
    public function __construct(
        #[Exposed] public int $id = 0,
        #[Exposed] public string $name = '',
        #[Exposed(name: 'email_address')] public string $email = '',
        #[Exposed(groups: ['admin'])] public string $note = '',
        public string $password = ''
    ) {
        //
    }
}
