# Quillstack Framework

[![Tests](https://github.com/quillstack/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/framework/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/framework.svg)](https://packagist.org/packages/quillstack/framework)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/framework.svg)](https://packagist.org/packages/quillstack/framework)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/framework)](https://packagist.org/packages/quillstack/framework)
[![StyleCI](https://github.styleci.io/repos/302737962/shield?branch=main)](https://github.styleci.io/repos/302737962?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/framework/badge)](https://www.codefactor.io/repository/github/quillstack/framework)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![License](https://img.shields.io/packagist/l/quillstack/framework)](https://github.com/quillstack/framework/blob/main/LICENSE)

The Quillstack Framework, a light and simple micro-framework to build
the API.

## Why this exists

Two frameworks are pleasant to build an API with, and both make you take a great deal you did
not ask for. Two are rigorous about their internals, and both make you read a book first. This
was written to find out whether that trade is real.

What holds it together is that **every part of it works without the rest**. The router, the
container, the ORM, the HTTP client — each is a package with its own tests, usable in somebody
else's application, and none of them depends on this one. What this adds is the wiring: a kernel,
a middleware stack, error handling that never lets a fatal reach the client, and a console.

The whole stack contains **no third-party implementations** — the only outside code anywhere in
it is the PSR interfaces. That is not a boast about invented wheels; it is what lets an N+1
query be impossible rather than discouraged, and a guarded route refuse to boot rather than
quietly let everyone through.

## Requirements

- PHP 8.1 or newer

## Installation

The quickest way to start is the application skeleton:

```shell
composer create-project quillstack/quillstack my-api
cd my-api
composer serve
```

To add the framework to an existing project:

```shell
composer require quillstack/framework
```

## Usage

### An application

`App` takes the path to the `.env` file and the container configuration, and returns
a PSR-7 response:

```php
use App\Providers\RouteProvider;
use Quillstack\Framework\App;
use Quillstack\Framework\Interfaces\RouteProviderInterface;

require __DIR__ . '/../vendor/autoload.php';

$app = new App(__DIR__ . '/../.env', [
    RouteProviderInterface::class => RouteProvider::class,
]);

echo json_encode($app->run());
```

### Routes

A route provider registers the routes of the application:

```php
final class RouteProvider implements RouteProviderInterface
{
    public function setRoutes(Router $router): void
    {
        $router->get('/', HomeController::class)->name('home');
        $router->get('/users/:id', UserController::class)->name('users.show');
        $router->delete('/users/{id}', DeleteUserController::class);
    }
}
```

`get()`, `post()`, `put()`, `patch()`, `delete()`, `options()` and `head()` register a single
method, `match(['PUT', 'PATCH'], ...)` registers a few of them, and `any()` registers them all.

A path segment written as `:id` or as `{id}` is a parameter. Matched parameters are put on the
request as attributes, and a query string never takes part in the matching:

```php
final class UserController implements ControllerInterface
{
    public UserResponse $response;

    public function handle(ServerRequestInterface $request): UserResponse
    {
        return $this->response->setId(
            (string) $request->getAttribute('id')
        );
    }
}
```

### Providers

A provider brings a piece of the application with it. Everything registers before anything
boots, so a provider can count on the services of the ones after it:

```php
final class CacheProvider extends ServiceProvider
{
    public function register(): array
    {
        return [
            CacheInterface::class => new FileCache(new LocalStorage(), __DIR__ . '/../var/cache'),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        $container->get(ListenerProvider::class)->listen(
            UserRegistered::class,
            fn (UserRegistered $event) => $container->get(CacheInterface::class)->delete('users.count')
        );
    }
}
```

The application lists them the way it lists its routes:

```php
$app = new App(__DIR__ . '/../.env', [
    RouteProviderInterface::class => RouteProvider::class,
    ServiceProviderRegistryInterface::class => ServiceProviders::class,
]);
```

What the application configured itself wins, so a provider brings defaults rather than
decisions.

### Middleware

`CorsMiddleware` answers what a browser asks before it will let a page read an API on
another host. Without it every request from a browser is refused before the application sees
it:

```php
$app = new App($env, [
    CorsMiddleware::class => new CorsMiddleware(
        origins: ['https://quillstack.com'],
        credentials: true,
    ),
], [
    CorsMiddleware::class,
]);
```

A preflight is answered there and never reaches the application.

`RateLimitMiddleware` counts what one caller asks for and refuses the rest once there has
been enough of it. The count lives in a PSR-16 cache, so it is shared by however many
processes are answering:

```php
RateLimitMiddleware::class => new RateLimitMiddleware($cache, limit: 60, window: 60),
```

Past the limit the request is answered with 429, and `X-RateLimit-Limit` and
`X-RateLimit-Remaining` say where the caller stands.

### Database

Entities describe the tables, so there are no migration files to write and none to keep in
order. A relation is already a declaration that one column holds another table's id, which is
all the information needed to index it and constrain it — so nobody writes an index and nobody
writes a foreign key.

```php
#[Table('posts')]
final class Post
{
    public function __construct(
        #[Id] public ?int $id = null,
        #[Column('user_id')] public ?int $userId = null,
        #[Column] public string $title = '',
        #[BelongsTo(User::class, 'user_id')] public readonly Reference $user = new Reference(),
    ) {
    }
}
```

`src/Providers/EntityRegistry.php` says which entities there are, and the command shows up
once it does:

```shell
./bin/quill db:migrate --pretend    # what it would run
./bin/quill db:migrate              # run it
```

Nothing is ever removed: a column the entities no longer mention is reported and left alone,
because a renamed property looks identical to a deleted one.

Reading is where [quillstack/orm](https://github.com/quillstack/orm) earns its keep — touching
one entity's relation loads it for every entity read beside it, so this is three queries and
not sixty-one:

```php
foreach ($users->all() as $user) {
    foreach ($user->posts as $post) {
        foreach ($post->comments as $comment) { … }
    }
}
```

### Queues

Work which does not have to happen while somebody is waiting goes on a queue. A message
carries what it is about, and a handler does something with it:

```php
$queue->push(new SendWelcomeEmail($user->email));
```

The command shows up once the application has configured a queue:

```shell
./bin/quill queue:work                    # everything due now, then stop
./bin/quill queue:work emails             # a queue of its own
./bin/quill queue:work --keep-running     # wait for more
```

A message which fails is tried again a few times and then set aside, and written to the log
when a PSR-3 logger is configured. See [quillstack/queue](https://github.com/quillstack/queue)
for the rest.

### Commands

The command line is [quillstack/cli](https://github.com/quillstack/cli): a command is a class,
and what it needs it asks for in the constructor.



`Console` is what `App` is for a request: the same container, the same configuration, and
the same way of registering what the application brings.

```php
#!/usr/bin/env php
<?php

use App\Providers\CommandProvider;
use Quillstack\Framework\Console;
use Quillstack\Framework\Console\CommandProviderInterface;

require __DIR__ . '/../vendor/autoload.php';

$console = new Console(__DIR__ . '/../.env', [
    CommandProviderInterface::class => CommandProvider::class,
]);

exit($console->run($argv));
```

A command asks for what it needs through the constructor, the way a controller does, and
returns the exit code:

```php
final class VersionCommand implements CommandInterface
{
    public function __construct(private readonly VersionService $versionService)
    {
    }

    public function getName(): string
    {
        return 'app:version';
    }

    public function getDescription(): string
    {
        return 'Shows which version of the application this is';
    }

    public function run(Input $input, OutputInterface $output): int
    {
        $output->writeln("Version <green>{$this->versionService->getVersion()}</green>");

        return 0;
    }
}
```

`Input` reads what was typed: `$input->getArgument(0)` for a value standing on its own, and
`$input->getOption('force')` for `--force`, `--target=prod` or `-f`. Typing nothing lists the
commands there are, and a name nobody knows says so and exits with 1.

Colours are written only when the output is a terminal, so a command piped into a file
writes the text alone. A command which throws is reported the way a request is: the message,
and outside production the exception with it.

### Validation

Ask the validator what the request may hold. What comes back is only the fields there were
rules for, so nothing else that happened to be sent reaches the application:

```php
use Quillstack\Framework\Validation\Validator;

public function __construct(
    private readonly UserResponse $response,
    private readonly Validator $validator
) {
}

public function handle(ServerRequestInterface $request): UserResponse
{
    $data = $this->validator->check((array) $request->getParsedBody(), [
        'email' => ['required', 'email'],
        'age' => ['required', 'integer', 'min:18'],
        'plan' => ['required', 'in:free,pro'],
    ]);

    return $this->response->setEmail($data['email']);
}
```

Everything wrong is reported at once, and the answer says which field and why:

```json
{"error": {"status": 422, "message": "The given data was invalid",
           "fields": {"email": ["The email field has to be an email address"],
                      "age": ["The age field has to be at least 18"]}}}
```

`required`, `string`, `integer`, `numeric`, `boolean`, `email` and `url` are written by
name. `min:18`, `max:255`, `in:free,pro` and `same:password` take what follows the colon.
`min` and `max` read a number by its value, a string by its length and a list by how many it
holds, so a field sent over HTTP as `"30"` is thirty and not two characters.

A rule of your own is any class implementing `RuleInterface`, and goes into the list next to
the written ones:

```php
'slug' => ['required', new UniqueSlug($this->articles)],
```

A field which was not sent at all is only `required`'s business: every other rule lets it
through, so `['email']` alone accepts a missing field and rejects a malformed one.

### Authentication

A route says what reaching it requires, and one place enforces it — a rule kept in each
controller instead is a rule which is one day not kept:

```php
$router->get('/orders', OrdersController::class)->requireAuthentication();
$router->delete('/orders/:id', DeleteOrderController::class)->requireAuthentication('admin');
```

The application says who anybody is, because only it knows where the tokens are:

```php
use Quillstack\Auth\IdentityProviderInterface;

$app = new App(__DIR__ . '/../.env', [
    IdentityProviderInterface::class => Users::class,
]);
```

Once it does, the middleware is added and every guarded route is enforced. A request from
nobody is answered `401`; one from somebody without the role is answered `403`.

```php
use Quillstack\Auth\Middleware\AuthenticationMiddleware;

$identity = AuthenticationMiddleware::identityOf($request);
```

**A guarded route in an application which has said nothing about identities is refused at
boot**, before a single request is served — such a route would be open while reading as
guarded, which is the one failure the whole arrangement exists to prevent.

There is no authorisation middleware in the default stack any more. There was one, and it let
everything through. See [quillstack/auth](https://github.com/quillstack/auth).

### Describing the API

An OpenAPI document is usually a second description of the application, kept beside the first
one and true for about a week. This one is worked out from what the application already says:

```shell
./bin/quill openapi:generate --title="Orders" --server=https://api.example.com --out=public/openapi.json
```

| In the document | Where it came from |
| --- | --- |
| paths and methods | the routes |
| `{id}` parameters | the `:id` in the path |
| `operationId` | the route's name |
| `summary` | the sentence above `handle()` |
| security, and `401`/`403` | `requireAuthentication()` on the route |
| the request body, and `422` | the `#[Accepts]` the validator reads |
| the response schema | what the response says it carries, and what that class exposes |

Two of those have to be said, because the code did not say them anywhere before. A response
declares what it carries:

```php
use Quillstack\Framework\Http\Responses\Attributes\Serializes;

#[Serializes(User::class)]
final class UserResponse extends SerializedResponse
{
}
```

and a handler declares what it accepts:

```php
use Quillstack\Framework\Validation\Attributes\Accepts;

/**
 * Takes a person and keeps them.
 */
#[Accepts([
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', 'min:18'],
    'plan' => ['required', 'in:free,pro'],
])]
public function handle(ServerRequestInterface $request): UserResponse
{
    $data = $this->validator->of($request, $this);

    // …
}
```

**Both are load-bearing, which is the point.** `with()` refuses an object the response did not
say it carries, and `of()` validates against the rules the document describes — so there is one
list, and it is the one that decides. An attribute which only documented would be wrong the
first time somebody changed one and not the other, and then it is worse than nothing, because
it is believed.

What is not declared is left out rather than guessed at. A response which says nothing about
what it carries gets a `200` with no schema; a handler with no `#[Accepts]` gets no request
body. A document which is partly invented is worse than a short one, because there is no way to
tell which half is which.

The fields are the ones the serializer would send, read from where it reads them — so a
`password` column nobody exposed is absent from the document for the same reason it is absent
from the answer. A response for administrators gets a schema of its own, because it is a
different set of fields.

The output passes [Redocly](https://redocly.com/docs/cli)'s full recommended ruleset with
nothing to report, which is checked rather than claimed.

### Errors

Nothing thrown by the application reaches the client as a fatal error. Throw an HTTP
exception and it is answered with the status it carries:

```php
use Quillstack\Framework\Exceptions\Http\NotFoundHttpException;

public function handle(ServerRequestInterface $request): UserResponse
{
    $user = $this->users->find($request->getAttribute('id'));

    if ($user === null) {
        throw new NotFoundHttpException('No user with that id');
    }

    return $this->response->setUser($user);
}
```

```json
{"error": {"status": 404, "message": "No user with that id"}}
```

`BadRequestHttpException`, `UnauthorizedHttpException`, `ForbiddenHttpException`,
`NotFoundHttpException`, `MethodNotAllowedHttpException`, `ConflictHttpException`,
`UnprocessableContentHttpException`, `TooManyRequestsHttpException` and
`ServiceUnavailableHttpException` are there, and `HttpException` takes any status.

Anything else is an error of the application: it is answered with 500 and written to the
log, when the application configured a PSR-3 logger under `LoggerInterface`. In production
the client is told the status and nothing else. Anywhere else, `APP_ENV` being something
other than `production`, the answer describes the exception:

```json
{"error": {"status": 500, "message": "Internal Server Error",
           "exception": "RuntimeException", "file": "...", "line": 16, "trace": ["..."]}}
```

There are two ways to match no route, and they are answered differently. An unknown path is
404. A known path asked with a method it is not registered for is 405, naming what it does
answer to:

```
$ curl -i -X POST http://localhost:8000/users/42
HTTP/1.1 405 Method Not Allowed
Allow: GET, HEAD, DELETE

{"error": {"status": 405, "message": "Method Not Allowed", "allowed": ["GET, HEAD, DELETE"]}}
```

`HEAD` is in there because a path registered for `GET` answers it: the same controller runs,
and the server sends the headers without the body.

### Controllers

Dependencies are asked for through the constructor, so what a controller needs is written
in one place and cannot be swapped out from the outside:

```php
final class HomeController implements ControllerInterface
{
    public function __construct(
        private readonly HomeResponse $response,
        private readonly VersionService $versionService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(ServerRequestInterface $request): HomeResponse
    {
        return $this->response->setVersion(
            $this->versionService->getVersion()
        );
    }
}
```

The container also fills public typed properties, which is how a controller can be handed
a request class of its own, since the routing middleware replaces it with the one carrying
the route parameters:

```php
final class UserController implements ControllerInterface
{
    public UserRequest $request;
}
```

Anything else belongs in the constructor.

## Benchmark

Measured with [quillstack/benchmark](https://github.com/quillstack/benchmark) on sixteen routes,
answering `GET /projects/42` with a JSON body, once per process — which is what a PHP request
does. All three return the same status and the same body. Runs are interleaved and unconcurrent,
each figure is the median of five, and PHP is 8.5.7.

| | Version |
| --- | --- |
| quillstack/framework | v0.13.0 |
| slim/slim | 4.15.2 |
| mezzio/mezzio | 3.28.1 |

| | Per request | Relative | Files loaded | Memory |
| --- | --- | --- | --- | --- |
| mezzio/mezzio | 2.38 ms | 0.69× | 44 | 544 kB |
| slim/slim | 3.36 ms | 0.98× | 66 | 750 kB |
| **quillstack/framework** | **3.44 ms** | — | 73 | 773 kB |

**This one is third**, and level with Slim to within three per cent.

The comparison is not quite like for like, and in this one's favour it should be said which way:
the Mezzio pipeline here is a router and a dispatcher and nothing else, and the Slim application
has no error middleware. The Quillstack figure is a whole `App` — reading configuration,
building a container, assembling the middleware stack, and wiring error handling that turns
anything thrown into a response. **Take those away and the numbers would move; they are in
because that is what booting this framework is.**

A millisecond between frameworks is not what decides an API's response time — a single database
query costs more. What these numbers are good for is knowing that none of the three is doing
anything foolish.

## Tests

Run tests using a command:

```
phpdbg -qrr ./vendor/bin/unit-tests
```

## The rest of Quillstack

Every one of these works without this framework, and this framework is what wires them together.

- [quillstack/router](https://github.com/quillstack/router) — matching a request to a controller
- [quillstack/di](https://github.com/quillstack/di) — building what a controller asks for
- [quillstack/orm](https://github.com/quillstack/orm) — where an N+1 query cannot be written
- [quillstack/quillstack](https://github.com/quillstack/quillstack) — the skeleton to start from

## License

MIT — see [LICENSE](https://github.com/quillstack/framework/blob/main/LICENSE).
