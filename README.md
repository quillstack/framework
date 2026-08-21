# Quillstack Framework

[![Build Status](https://app.travis-ci.com/quillstack/framework.svg?branch=main)](https://app.travis-ci.com/quillstack/framework)
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

### Controllers

Dependencies are injected into public typed properties, so a controller only declares what
it needs:

```php
final class HomeController implements ControllerInterface
{
    public HomeResponse $response;
    public VersionService $versionService;
    public LoggerInterface $logger;
}
```

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
