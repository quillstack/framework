<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Quillstack\Serializer\Attributes\Exposed;

/**
 * Has a field which may be null, which is the one thing OpenAPI 3.1 writes differently from
 * 3.0 and so the one thing worth a fixture of its own.
 */
final class Note
{
    public function __construct(
        #[Exposed] public int $id = 0,
        #[Exposed] public ?string $title = null,
        #[Exposed] public string $body = ''
    ) {
        //
    }
}
