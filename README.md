Route
=====

Service wrapper for FastRoute providing a more slim-like interface.

## Background

I love [Slim Framework](http://www.slimframework.com), but it is not as slim (or fast) as I'd like it to be.

[FastRoute](https://github.com/nikic/FastRoute), is exactly what I need in a router, but I prefer the Slim API.

Opine\Route is a service wrapper for FastRoute which with a more Slim-like interface.  So here is the fastest router with the most popular API.  Enjoy!

## With Dependency Injection
> index.php

```php
$route = $container->route;

$route->get('/', function () {
    echo 'Home';
})->get('/about', function () {
    echo 'About';
})->get('/blog', function () {
    echo 'Blog';
})->post('/form', function () {
    echo 'Received';
});

echo $route->run('GET', '/');
```

> container.yml

```yaml
services:
    route:
        class:     'Opine\Route'
        arguments: ['@fastrouteCollector']
    fastrouteCollector:
        class:     'FastRoute\RouteCollector'
        arguments: ['@fastrouteParser', '@fastrouteGenerator']
    fastrouteParser:
        class:     'FastRoute\RouteParser\Std'
    fastrouteGenerator:
        class:     'FastRoute\DataGenerator\GroupCountBased'
```

## Without Dependency Injection
> index.php

```php
use Opine;
use FastRoute;

$route = new Route(new RouteCollector(new RouteParser\Std(), new DataGenerator\GroupCountBased()));

$route->get('/', function () {
    echo 'Home';
})->get('/about', function () {
    echo 'About';
})->get('/blog', function () {
    echo 'Blog';
})->post('/form', function () {
    echo 'Received';
});

echo $route->run('GET', '/');
```

## Currently, the following methods are defined:
- **before**: add a before filter
- **after**: add an after filter
- **purgeAfter**: get rid of after filters
- **purgeBefore**: get rid of before filters
- **get**: add a pattern/callback to route for get method
- **post**: add a pattern/callback to route for post method
- **run**: dispatch against current URI or supply one

## Installation
> edit composer.json

```json
{
    "require": {
        "opine/route": "dev-master"
    }
}
```

> composer install


## Author

Ryan Mahoney can be reached at ryan@virtuecenter.com or @vcryan on Twitter.