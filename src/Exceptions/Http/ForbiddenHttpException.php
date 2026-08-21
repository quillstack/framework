<?php

declare(strict_types=1);

namespace Quillstack\Framework\Exceptions\Http;

use Quillstack\Response\StatusCode;
use Throwable;

class ForbiddenHttpException extends HttpException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct(StatusCode::FORBIDDEN, $message, $previous);
    }
}
