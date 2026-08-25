<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Quillstack\Framework\Http\Responses\Attributes\Serializes;
use Quillstack\Framework\Http\Responses\SerializedResponse;

#[Serializes(Note::class)]
final class NoteResponse extends SerializedResponse
{
}
