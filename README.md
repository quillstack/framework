# Quillstack Framework

[![Tests](https://github.com/quillstack/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/framework/actions/workflows/tests.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/framework.svg)](https://packagist.org/packages/quillstack/framework)
[![StyleCI](https://github.styleci.io/repos/302737962/shield?branch=main)](https://github.styleci.io/repos/302737962?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/framework/badge)](https://www.codefactor.io/repository/github/quillstack/framework)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_framework&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_framework)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/quillstack/framework)
![Packagist License](https://img.shields.io/packagist/l/quillstack/framework)

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

A request matching no route is answered with 404.

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
