<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation;

use Quillstack\Framework\Validation\Exceptions\UnknownRuleException;
use Quillstack\Framework\Validation\Rules;

/**
 * Turns a rule written as text, `min:18` or `in:draft,published`, into the object which
 * checks it.
 */
class RuleFactory
{
    /**
     * Rules written by name alone.
     *
     * @var array<string, class-string<RuleInterface>>
     */
    public const RULES = [
        'required' => Rules\Required::class,
        'string' => Rules\StringRule::class,
        'integer' => Rules\IntegerRule::class,
        'numeric' => Rules\NumericRule::class,
        'boolean' => Rules\BooleanRule::class,
        'email' => Rules\Email::class,
        'url' => Rules\Url::class,
    ];

    /**
     * Rules written with something after a colon.
     *
     * @var string[]
     */
    public const RULES_WITH_ARGUMENT = ['min', 'max', 'in', 'same'];

    public function create(RuleInterface|string $rule): RuleInterface
    {
        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        $parts = explode(':', $rule, 2);
        $name = $parts[0];
        $argument = $parts[1] ?? '';

        return match ($name) {
            'min' => new Rules\Min((float) $argument),
            'max' => new Rules\Max((float) $argument),
            'in' => new Rules\In(explode(',', $argument)),
            'same' => new Rules\Same($argument),
            default => $this->withoutArgument($name),
        };
    }

    private function withoutArgument(string $name): RuleInterface
    {
        if (!isset(self::RULES[$name])) {
            throw new UnknownRuleException("Unknown validation rule: {$name}");
        }

        $class = self::RULES[$name];

        return new $class();
    }
}
