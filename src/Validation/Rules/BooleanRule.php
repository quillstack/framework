<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `boolean`. A field which was not sent is left to `required`.
 */
class BooleanRule implements RuleInterface
{
    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        return $value === null || is_bool($value)
            || in_array($value, ['0', '1', 0, 1, 'true', 'false'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to be true or false";
    }
}
