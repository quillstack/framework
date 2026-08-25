<?php

declare(strict_types=1);

namespace Quillstack\Framework\Tests\Fixtures\OpenApi;

use Quillstack\Framework\Http\Responses\SerializedResponse;

/**
 * Says nothing about what it carries, which is what every response written before the
 * attribute existed does.
 */
final class UndeclaredResponse extends SerializedResponse
{
}
