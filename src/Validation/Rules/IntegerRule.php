<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `integer`. A field which was not sent is left to `required`.
 */
class IntegerRule implements RuleInterface
{
    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        return $value === null || is_int($value)
            || (is_string($value) && preg_match('/^-?\\d+$/', $value) === 1);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to be an integer";
    }
}
