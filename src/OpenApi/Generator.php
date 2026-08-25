<?php

declare(strict_types=1);

namespace Quillstack\Framework\OpenApi;

use Quillstack\Framework\Http\Responses\SerializedResponse;
use Psr\Http\Message\ResponseInterface;
use Quillstack\Framework\Validation\Validator;
use Quillstack\Response\StatusCode;
use Quillstack\Router\GuardedRouteInterface;
use Quillstack\Router\RouteInterface;
use Quillstack\Router\RouterInterface;
use ReflectionClass;
use ReflectionNamedType;

/**
 * An OpenAPI document, worked out from what the application already says.
 *
 * Nothing here is written twice. The paths and methods are the routes; what a request may hold
 * is the `#[Accepts]` the validator reads; what comes back is the return type of `handle()`
 * and, where that is a `SerializedResponse`, the class it says it carries and the fields that
 * class exposes. Whether an endpoint needs a token is what the route was told.
 *
 * Where something is not declared it is left out rather than guessed at. A document which is
 * partly invented is worse than a short one, because there is no way to tell which half is
 * which.
 */
final class Generator
{
    /**
     * @var array<string, array{class: class-string, groups: string[]}>
     */
    private array $schemas = [];

    /**
     * @param string[] $servers where the API answers, which nothing in the code knows
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $title = 'API',
        private readonly string $version = '1.0.0',
        private readonly array $servers = []
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $this->schemas = [];
        $paths = [];

        foreach ($this->router->getRoutes() as $route) {
            $path = self::pathOf($route);
            $paths[$path][strtolower($route->getMethod())] = $this->operation($route);
        }

        ksort($paths);

        $document = [
            'openapi' => '3.1.0',
            'info' => ['title' => $this->title, 'version' => $this->version],
        ];

        if ($this->servers !== []) {
            $document['servers'] = array_map(static fn (string $url): array => ['url' => $url], $this->servers);
        }

        $document['paths'] = $paths;

        $components = $this->components();

        if ($components !== []) {
            $document['components'] = $components;
        }

        return $document;
    }

    /**
     * `:id` is how a route says it, `{id}` is how OpenAPI does.
     */
    private static function pathOf(RouteInterface $route): string
    {
        return preg_replace('/:([A-Za-z_][A-Za-z0-9_]*)/', '{$1}', $route->getPath()) ?? $route->getPath();
    }

    /**
     * @return array<string, mixed>
     */
    private function operation(RouteInterface $route): array
    {
        $controller = $route->getController();
        $operation = [];

        if ($route->getName() !== '') {
            $operation['operationId'] = $route->getName();
        }

        $summary = self::summaryOf($controller);

        if ($summary !== null) {
            $operation['summary'] = $summary;
        }

        $parameters = [];

        foreach ($route->getParameterNames() as $name) {
            $parameters[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        if ($parameters !== []) {
            $operation['parameters'] = $parameters;
        }

        $body = $this->requestBody($controller);

        if ($body !== null) {
            $operation['requestBody'] = $body;
        }

        $operation['responses'] = $this->responses($route, $controller);

        $operation['security'] = $route instanceof GuardedRouteInterface && $route->requiresAuthentication()
            ? [['bearerAuth' => $route->getRequiredRoles()]]
            // An empty list is how OpenAPI says an endpoint needs nothing, which is a
            // different statement from saying nothing about it.
            : [];

        return $operation;
    }

    /**
     * What the controller's docblock says it does, which is a sentence somebody already wrote
     * next to the code rather than a second one to keep in step.
     */
    private static function summaryOf(string $controller): ?string
    {
        if (!class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);
        $comment = $reflection->hasMethod('handle')
            ? $reflection->getMethod('handle')->getDocComment()
            : false;

        $comment = $comment === false ? $reflection->getDocComment() : $comment;

        if ($comment === false) {
            return null;
        }

        foreach (explode("\n", $comment) as $line) {
            $line = trim(ltrim(trim($line), '/*'));

            // The first sentence, and nothing that is a tag or the inherit marker.
            if ($line !== '' && !str_starts_with($line, '@') && !str_contains($line, '{@inherit')) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function requestBody(string $controller): ?array
    {
        if (!class_exists($controller)) {
            return null;
        }

        /** @var class-string $controller */

        $rules = Validator::rulesOf($controller);

        if ($rules === []) {
            return null;
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => ['schema' => Rules::asSchema($rules)],
            ],
        ];
    }

    /**
     * A status is written as a string and PHP keeps it as an integer, which is the same
     * thing once it is JSON.
     *
     * @return array<int|string, mixed>
     */
    private function responses(RouteInterface $route, string $controller): array
    {
        [$status, $schema] = $this->success($controller);

        $answer = ['description' => StatusCode::reasonPhrase($status)];

        // A 204 says there is no body, and describing one would contradict the status.
        if ($schema !== null && $status !== StatusCode::NO_CONTENT) {
            $answer['content'] = ['application/json' => ['schema' => $schema]];
        }

        $responses = [(string) $status => $answer];

        // What the handler says it throws. A DELETE on something which is not there answers
        // 404, and the controller already says so — this reads it rather than asking for it
        // to be said again.
        if (class_exists($controller)) {
            $reflection = new ReflectionClass($controller);

            if ($reflection->hasMethod('handle')) {
                foreach (Throws::of($reflection->getMethod('handle')) as $thrown => $means) {
                    $responses[(string) $thrown] = ['description' => $means];
                }
            }
        }

        if (class_exists($controller) && Validator::rulesOf($controller) !== []) {
            // Anything taking a body can be sent one that is not JSON, and the middleware
            // which reads it answers 400 rather than passing on an empty body.
            $responses['400'] = ['description' => 'The request body is not valid JSON'];
            $responses['422'] = ['description' => 'The given data was invalid'];
        }

        if ($route instanceof GuardedRouteInterface && $route->requiresAuthentication()) {
            $responses['401'] = ['description' => 'Not authenticated'];

            if ($route->getRequiredRoles() !== []) {
                $responses['403'] = ['description' => 'Not authorised'];
            }
        }

        ksort($responses);

        return $responses;
    }

    /**
     * What a handler answers with when it works: the status its response was built to carry,
     * and the schema where it carries one.
     *
     * The status is read rather than assumed. `EmptyResponse` was built to answer 204, and a
     * document saying 200 would be describing an answer nobody gets.
     *
     * @return array{0: int, 1: ?array<string, mixed>}
     */
    private function success(string $controller): array
    {
        $response = self::responseOf($controller);

        if ($response === null) {
            return [StatusCode::OK, null];
        }

        return [self::statusOf($response), $this->responseSchema($controller)];
    }

    /**
     * The status a response class was built to carry, where it can be built to ask.
     *
     * @param class-string $response
     */
    private static function statusOf(string $response): int
    {
        $reflection = new ReflectionClass($response);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            return StatusCode::OK;
        }

        $built = $reflection->newInstance();

        return $built instanceof ResponseInterface ? $built->getStatusCode() : StatusCode::OK;
    }

    /**
     * The response class a handler returns, where it returns one.
     *
     * @return ?class-string
     */
    private static function responseOf(string $controller): ?string
    {
        if (!class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);

        if (!$reflection->hasMethod('handle')) {
            return null;
        }

        $type = $reflection->getMethod('handle')->getReturnType();

        if (!$type instanceof ReflectionNamedType || !class_exists($type->getName())) {
            return null;
        }

        /** @var class-string $name */
        $name = $type->getName();

        return $name;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function responseSchema(string $controller): ?array
    {
        if (!class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);

        if (!$reflection->hasMethod('handle')) {
            return null;
        }

        $type = $reflection->getMethod('handle')->getReturnType();

        if (!$type instanceof ReflectionNamedType || !class_exists($type->getName())) {
            return null;
        }

        $response = $type->getName();

        if (!is_subclass_of($response, SerializedResponse::class)) {
            return null;
        }

        /** @var ?class-string $carries */
        $carries = $response::serializes();

        if ($carries === null) {
            return null;
        }

        $groups = self::groupsOf($response);
        $name = Schema::nameOf($carries, $groups);

        $this->schemas[$name] = ['class' => $carries, 'groups' => $groups];

        return ['$ref' => '#/components/schemas/' . $name];
    }

    /**
     * The audiences a response serves. `groups()` is protected because nothing outside the
     * response has any business changing it — reading it to describe the response is a
     * different matter.
     *
     * @param class-string<SerializedResponse> $response
     *
     * @return string[]
     */
    private static function groupsOf(string $response): array
    {
        $reflection = new ReflectionClass($response);
        $method = $reflection->getMethod('groups');

        /** @var string[] $groups */
        $groups = $method->invoke($reflection->newInstanceWithoutConstructor());

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    private function components(): array
    {
        $components = [];

        if ($this->schemas !== []) {
            $schemas = [];

            foreach ($this->schemas as $name => $schema) {
                $schemas[$name] = Schema::of($schema['class'], $schema['groups']);
            }

            ksort($schemas);
            $components['schemas'] = $schemas;
        }

        foreach ($this->router->getRoutes() as $route) {
            if ($route instanceof GuardedRouteInterface && $route->requiresAuthentication()) {
                $components['securitySchemes'] = [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
                ];

                break;
            }
        }

        return $components;
    }
}
