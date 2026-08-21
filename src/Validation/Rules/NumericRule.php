<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `numeric`. A field which was not sent is left to `required`.
 */
class NumericRule implements RuleInterface
{
    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        return $value === null || is_numeric($value);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to be a number";
    }
}
