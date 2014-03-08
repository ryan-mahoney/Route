Route
=====

Service wrapper for FastRoute providing a more slim-like interface.

# Background

I love Slim.  I definitely learned a lot from it and have got a lot of mileage out of it.  That beig said, I did eventually think that Slim was not really that slim (that it has gotten somewhat bloated) and I was surprised on a recent project with a large budget for performance testing that it was not a bit faster.  Also, I needed Slim to allow for multiple routes to be called in one php-run... and it seems not to work out of the box and be difficult to implemnt.

I got excited about the FastRoute for PHP, but then I longed for the elegant interface of Slim.

So, here is a wrapper for FastRoute which is super fast and light weight, with a more Slim-like approach.

# Usage

Add to your composer:

```
"opine/route": "dev-master"
```

# With Dependency Injection

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
}
echo $route->run('GET', '/');
```

# Without Dependency Injection

```php
use Opine;
use FastRoute;
$route = new Route(new RouteCollector(new RouteParser\Std(), new DataGenerator\GroupCountBased));
$route->get('/', function () {
    echo 'Home';
})->get('/about', function () {
    echo 'About';
})->get('/blog', function () {
    echo 'Blog';
})->post('/form', function () {
    echo 'Received';
});
}
echo $route->run('GET', '/');
```


