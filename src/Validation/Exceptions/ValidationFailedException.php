<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Exceptions;

use Quillstack\Framework\Exceptions\Http\UnprocessableContentHttpException;
use Quillstack\ValidatorInterface\ValidationExceptionInterface;

/**
 * What was sent could not be accepted. It carries which fields were wrong and why, and it
 * is an HTTP exception, so the error middleware answers 422 without anything else knowing
 * about validation.
 */
class ValidationFailedException extends UnprocessableContentHttpException implements ValidationExceptionInterface
{
    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The given data was invalid');
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
