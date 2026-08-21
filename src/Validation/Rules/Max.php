<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Rules;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Written in a rule list as `max:255`, where the number is the largest a number may be, or the longest a string.
 */
class Max implements RuleInterface
{
    public function __construct(private readonly float $limit)
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

        return $this->sizeOf($value) <= $this->limit;
    }

    /**
     * A number is compared by its value, a string by its length and a list by how many it
     * holds. Everything sent over HTTP arrives as a string, so a string of digits is a
     * number here rather than two characters.
     */
    private function sizeOf(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            return (float) mb_strlen($value);
        }

        return is_countable($value) ? (float) count($value) : 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $field): string
    {
        $limit = $this->limit == (int) $this->limit ? (string) (int) $this->limit : (string) $this->limit;

        return "The {$field} field has to be at most {$limit}";
    }
}
