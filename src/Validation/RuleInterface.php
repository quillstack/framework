<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation;

interface RuleInterface
{
    /**
     * Tells whether the value is acceptable. A field which was not sent at all arrives as
     * null, so a rule which does not care about that has to let null through and leave it
     * to `required`.
     *
     * @param array<string, mixed> $data everything that was sent, for rules reading another field
     */
    public function passes(mixed $value, array $data): bool;

    /**
     * What the client is told when the value is not acceptable.
     */
    public function message(string $field): string;
}
