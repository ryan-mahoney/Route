[![Stories in Ready](https://badge.waffle.io/Opine-Org/Route.png?label=ready&title=Ready)](https://waffle.io/Opine-Org/Route)
Opine\Route (SlimFast)
======================

Service wrapper for FastRoute providing a more slim-like interface.

[![Build Status](https://travis-ci.org/Opine-Org/Route.png?branch=master)](https://travis-ci.org/Opine-Org/Route)

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Opine-Org/Route/badges/quality-score.png?s=345960c961c6d6da9788d4238c2f9c2a90a29a84)](https://scrutinizer-ci.com/g/Opine-Org/Route/)

[![Code Coverage](https://scrutinizer-ci.com/g/Opine-Org/Route/badges/coverage.png?s=a8bb5c9fd7b98c7c4debb4d88e1064ee5e48f3c4)](https://scrutinizer-ci.com/g/Opine-Org/Route/)

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
- **get**: add a pattern/callback to route for get method
- **post**: add a pattern/callback to route for post method
- **put**: add a pattern/callback to route for put method
- **delete**: add a pattern/callback to route for delete method
- **patch**: add a pattern/callback to route for patch method
- **before**: add a before filter
- **after**: add an after filter
- **purgeAfter**: get rid of after filters
- **purgeBefore**: get rid of before filters
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
