<?php

declare(strict_types=1);

namespace Quillstack\Framework\OpenApi;

use Quillstack\Serializer\Fields;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * What a class looks like on the wire, as an OpenAPI schema.
 *
 * The fields are the ones the serializer would send and no others, because they are read from
 * the same place it reads them: adding a column nobody exposed does not add it here, for the
 * same reason it does not add it to a response.
 */
final class Schema
{
    /**
     * @param class-string $class
     * @param string[] $groups the audiences the response serves, as the serializer takes them
     *
     * @return array<string, mixed>
     */
    public static function of(string $class, array $groups = []): array
    {
        $reflection = new ReflectionClass($class);
        $properties = [];
        $required = [];

        foreach (Fields::of($class) as $property => $exposed) {
            // A field for administrators is not in a response which does not serve them, and
            // describing it here would be describing an answer nobody gets.
            if (!$exposed->isFor($groups)) {
                continue;
            }

            $name = $exposed->name ?? $property;
            $type = self::typeOf($reflection, $property);

            $properties[$name] = self::describe($type);

            if ($type !== null && !$type->allowsNull()) {
                $required[] = $name;
            }
        }

        $schema = ['type' => 'object', 'properties' => $properties];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * The name a schema is filed under in the document.
     *
     * The groups are part of it, because the same entity serves different audiences different
     * fields, and two of those under one name would describe neither.
     *
     * @param class-string $class
     * @param string[] $groups
     */
    public static function nameOf(string $class, array $groups = []): string
    {
        $parts = explode('\\', $class);
        $name = (string) end($parts);

        foreach ($groups as $group) {
            $name .= ucfirst(preg_replace('/[^A-Za-z0-9]/', '', $group) ?? '');
        }

        return $name;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private static function typeOf(ReflectionClass $reflection, string $property): ?ReflectionNamedType
    {
        // A promoted constructor parameter is a property, and is where an entity written the
        // usual way here declares its type.
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            if ($parameter->getName() === $property) {
                $type = $parameter->getType();

                return $type instanceof ReflectionNamedType ? $type : null;
            }
        }

        if (!$reflection->hasProperty($property)) {
            return null;
        }

        $type = (new ReflectionProperty($reflection->getName(), $property))->getType();

        return $type instanceof ReflectionNamedType ? $type : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function describe(?ReflectionNamedType $type): array
    {
        if ($type === null) {
            return [];
        }

        $described = match ($type->getName()) {
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => []],
            'string' => ['type' => 'string'],
            // Anything else is a class, and saying `object` is more honest than guessing at
            // its shape from here — it may not be exposed at all.
            default => ['type' => 'object'],
        };

        // OpenAPI 3.1 says a nullable type by listing null among the types. `nullable: true`
        // is how 3.0 said it, and a 3.1 document containing it is not a valid one — which a
        // validator says and a reader does not.
        if ($type->allowsNull()) {
            $described['type'] = [$described['type'], 'null'];
        }

        return $described;
    }
}
