<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Quillstack\Framework\Http\Responses\Attributes\Serializes;
use Quillstack\Framework\Http\Responses\SerializedResponse;
use Quillstack\Framework\Tests\Fixtures\Person;

/**
 * The same entity, a different audience, and so a different set of fields.
 */
#[Serializes(Person::class)]
final class AdminPersonResponse extends SerializedResponse
{
    /**
     * {@inheritDoc}
     */
    protected function groups(): array
    {
        return ['admin'];
    }
}
