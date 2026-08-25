<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Quillstack\Framework\Http\Responses\Attributes\Serializes;
use Quillstack\Framework\Http\Responses\SerializedResponse;
use Quillstack\Framework\Tests\Fixtures\Person;

#[Serializes(Person::class)]
final class PersonResponse extends SerializedResponse
{
}
