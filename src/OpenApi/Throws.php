<?php

declare(strict_types=1);

namespace Quillstack\Framework\OpenApi;

use Quillstack\Framework\Exceptions\Http\HttpException;
use ReflectionClass;
use ReflectionMethod;

/**
 * The statuses a handler says it can answer with, read from its `@throws`.
 *
 * A `DELETE` on something that is not there is a `404`, and the controller already says so by
 * throwing `NotFoundHttpException` — and, where somebody wrote the tag, by declaring it. This
 * reads what is written rather than asking for it to be written again somewhere else.
 */
final class Throws
{
    /**
     * @return array<int, string> status to what it means, in the order they were declared
     */
    public static function of(ReflectionMethod $method): array
    {
        $comment = $method->getDocComment();

        if ($comment === false) {
            return [];
        }

        preg_match_all('/@throws\s+([\\\\A-Za-z0-9_]+)/', $comment, $matches);

        $statuses = [];
        $namespace = $method->getDeclaringClass()->getNamespaceName();
        $imports = self::importsOf($method->getDeclaringClass());

        foreach ($matches[1] as $name) {
            $class = self::resolve($name, $namespace, $imports);

            if ($class === null || !is_subclass_of($class, HttpException::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            // The base carries the status as an argument, so a subclass which still wants one
            // has not decided what it means and there is nothing to read off it.
            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $exception = $reflection->newInstance();
            $statuses[$exception->getStatusCode()] = $exception->getMessage();
        }

        return $statuses;
    }

    /**
     * A short name in a docblock means whatever the file's `use` statements say it means, and
     * failing that something in the same namespace.
     *
     * @param array<string, class-string> $imports
     *
     * @return ?class-string
     */
    private static function resolve(string $name, string $namespace, array $imports): ?string
    {
        if (str_starts_with($name, '\\')) {
            $name = substr($name, 1);

            return class_exists($name) ? $name : null;
        }

        if (isset($imports[$name])) {
            return $imports[$name];
        }

        $sameNamespace = $namespace === '' ? $name : $namespace . '\\' . $name;

        if (class_exists($sameNamespace)) {
            return $sameNamespace;
        }

        return class_exists($name) ? $name : null;
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return array<string, class-string>
     */
    private static function importsOf(ReflectionClass $class): array
    {
        $file = $class->getFileName();
        $contents = $file === false ? false : file_get_contents($file);

        if (!is_string($contents)) {
            return [];
        }

        preg_match_all(
            '/^use\s+([\\\\A-Za-z0-9_]+)(?:\s+as\s+([A-Za-z0-9_]+))?\s*;/mi',
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        $imports = [];

        foreach ($matches as $match) {
            $full = ltrim($match[1], '\\');

            if (!class_exists($full)) {
                continue;
            }

            $parts = explode('\\', $full);
            $alias = $match[2] ?? end($parts);
            $imports[$alias] = $full;
        }

        return $imports;
    }
}
