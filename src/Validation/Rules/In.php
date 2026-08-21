<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `in:draft,published`, naming the values which are accepted.
 */
class In implements RuleInterface
{
    /**
     * @param string[] $allowed
     */
    public function __construct(private readonly array $allowed)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        if ($value === null) {
            return true;
        }

        return is_scalar($value) && in_array((string) $value, $this->allowed, true);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to be one of: " . implode(', ', $this->allowed);
    }
}
