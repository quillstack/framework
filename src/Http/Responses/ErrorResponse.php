<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses;

use Quillstack\HeaderBag\HeaderBag;
use Quillstack\Response\Response;
use Quillstack\Response\StatusCode;
use Throwable;

/**
 * What the client is told when something went wrong. Outside production the exception is
 * described as well, so the answer says what actually happened while it is being worked on.
 */
class ErrorResponse extends Response
{
    private string $error = '';
    /**
     * @var array<string, mixed>
     */
    private array $details = [];

    public function __construct(int $code = StatusCode::INTERNAL_SERVER_ERROR, ?HeaderBag $headerBag = null)
    {
        parent::__construct($code, '', $headerBag ?? new HeaderBag());
    }

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Adds what the client needs to act on the error, such as which fields were wrong.
     *
     * @param array<string, mixed> $details
     */
    public function addDetails(array $details): self
    {
        $this->details += $details;

        return $this;
    }

    /**
     * Describes the exception, which only happens when the application is not in production.
     */
    public function describe(Throwable $throwable): self
    {
        $this->details = [
            'exception' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => explode("\n", $throwable->getTraceAsString()),
        ];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): array
    {
        return [
            'error' => [
                'status' => $this->getStatusCode(),
                'message' => $this->error !== '' ? $this->error : $this->getReasonPhrase(),
            ] + $this->details,
        ];
    }
}
