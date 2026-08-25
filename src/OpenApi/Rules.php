<?php

declare(strict_types=1);

namespace Quillstack\Framework\OpenApi;

use Quillstack\Framework\Validation\RuleInterface;

/**
 * Validation rules, read as a schema.
 *
 * Only what a rule actually says is written down. `min:18` on an integer is a minimum and
 * `min:3` on a string is a length, and where the rules do not say which, neither does this.
 */
final class Rules
{
    /**
     * @param array<string, array<int, RuleInterface|string>> $rules
     *
     * @return array<string, mixed>
     */
    public static function asSchema(array $rules): array
    {
        $properties = [];
        $required = [];

        foreach ($rules as $field => $list) {
            $properties[$field] = self::field($list);

            if (in_array('required', $list, true)) {
                $required[] = $field;
            }
        }

        $schema = ['type' => 'object', 'properties' => $properties];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param array<int, RuleInterface|string> $rules
     *
     * @return array<string, mixed>
     */
    private static function field(array $rules): array
    {
        $described = [];
        $type = null;

        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            $parts = explode(':', $rule, 2);
            $name = $parts[0];
            $argument = $parts[1] ?? null;

            switch ($name) {
                case 'integer':
                    $type = 'integer';
                    break;
                case 'numeric':
                    $type = 'number';
                    break;
                case 'boolean':
                    $type = 'boolean';
                    break;
                case 'string':
                    $type = 'string';
                    break;
                case 'email':
                    $type = 'string';
                    $described['format'] = 'email';
                    break;
                case 'url':
                    $type = 'string';
                    $described['format'] = 'uri';
                    break;
                case 'in':
                    $described['enum'] = $argument === null ? [] : explode(',', $argument);
                    break;
                case 'min':
                case 'max':
                    $described[$name] = $argument;
                    break;
            }
        }

        // `min` and `max` mean a bound on a number and a length on a string, and which one
        // depends on the type the other rules gave. Without a type they mean neither, so
        // nothing is written rather than the wrong one.
        foreach (['min' => ['minimum', 'minLength'], 'max' => ['maximum', 'maxLength']] as $bound => $names) {
            if (!isset($described[$bound])) {
                continue;
            }

            $value = $described[$bound];
            unset($described[$bound]);

            if ($type === 'integer' || $type === 'number') {
                $described[$names[0]] = $type === 'integer' ? (int) $value : (float) $value;
            } elseif ($type === 'string') {
                $described[$names[1]] = (int) $value;
            }
        }

        if ($type !== null) {
            $described = ['type' => $type] + $described;
        }

        return $described;
    }
}
