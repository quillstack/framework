<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

class Required implements RuleInterface
{
    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null && $value !== [];
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field is required";
    }
}
