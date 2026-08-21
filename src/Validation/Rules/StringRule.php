<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `string`. A field which was not sent is left to `required`.
 */
class StringRule implements RuleInterface
{
    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        return $value === null || is_string($value);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to be a string";
    }
}
