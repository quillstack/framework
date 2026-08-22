<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Mocks\Entities;

use Quillstack\Orm\Attributes\Column;
use Quillstack\Orm\Attributes\Id;
use Quillstack\Orm\Attributes\Table;

#[Table('notes')]
final class Note
{
    public function __construct(
        #[Id] public ?int $id = null,
        #[Column] public string $body = ''
    ) {
        //
    }
}
