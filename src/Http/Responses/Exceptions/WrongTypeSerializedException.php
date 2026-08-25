<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses\Exceptions;

use Quillstack\Framework\QuillstackException;

/**
 * A response was handed an object it did not say it carries.
 */
class WrongTypeSerializedException extends QuillstackException
{
}
