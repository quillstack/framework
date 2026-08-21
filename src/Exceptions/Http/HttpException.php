<?php

declare(strict_types=1);

namespace Quillstack\Framework\Exceptions\Http;

use Quillstack\Framework\QuillstackException;
use Quillstack\Response\StatusCode;
use Throwable;

/**
 * An error the client is told about, carrying the status to answer with. Throwing one of
 * these from anywhere in the application is enough: the error middleware turns it into a
 * response.
 */
class HttpException extends QuillstackException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message !== '' ? $message : StatusCode::reasonPhrase($statusCode),
            $statusCode,
            $previous
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
