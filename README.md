# Quillstack Framework

[![Tests](https://github.com/quillstack/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/framework/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/framework.svg)](https://packagist.org/packages/quillstack/framework)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/framework.svg)](https://packagist.org/packages/quillstack/framework)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/framework)](https://packagist.org/packages/quillstack/framework)
[![StyleCI](https://github.styleci.io/repos/302737962/shield?branch=main)](https://github.styleci.io/repos/302737962?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/framework/badge)](https://www.codefactor.io/repository/github/quillstack/framework)
[![License](https://img.shields.io/packagist/l/quillstack/framework)](https://github.com/quillstack/framework/blob/main/LICENSE)

The Quillstack Framework, a light and simple micro-framework to build
the API.

### Getting started

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

### Unit tests

Run tests using a command:

```
phpdbg -qrr ./vendor/bin/unit-tests
```

### Docker

```shell
$ docker-compose up -d
$ docker exec -w /var/www/html -it quillstack_framework sh
```
