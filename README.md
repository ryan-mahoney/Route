Route
=====

Service wrapper for FastRoute providing a more slim-like interface.

## Background

I love [Slim Framework](http://www.slimframework.com) .  I definitely learned a lot from it and have got a lot of mileage out of it.  That being said, I did eventually think that Slim was not really that slim (that it is actually somewhat bloated) and I was surprised on a recent project with a large budget for performance testing that it was faster.

Also, I needed Slim to allow for multiple routes to be called in one php-run when I consume my own RESTful APIs... and it seems not to work out of the box and be difficult to implement.

I got super excited about the [FastRoute](https://github.com/nikic/FastRoute), but then I longed for the elegant interface of Slim.

So, here is a wrapper for FastRoute which is super fast and light weight, with a more Slim-like interface.

## Usage
Add to your composer:

composer.json
```json
{
"opine/route": "dev-master"
}

composer install
```

## With Dependency Injection
container.yml
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

your front controller, etc:

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

## Without Dependency Injection

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
## Currently, the following methods are defined:

- before: add a before filter
- after: add an after filter
- purgeAfter*: get rid of after filters
- purgeBefore*: get rid of before filters
- get: add a pattern/callback to route for get method
- post: add a pattern/callback to route for post method
- run: dispatch against current URI or supply one

"*" denotes methods not present in Slim.

