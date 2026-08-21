<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `same:password`, naming the field this one has to match.
 */
class Same implements RuleInterface
{
    public function __construct(private readonly string $other)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function passes(mixed $value, array $data): bool
    {
        return $value === null || $value === ($data[$this->other] ?? null);
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        return "The {$field} field has to match {$this->other}";
    }
}
