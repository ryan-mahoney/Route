[![Stories in Ready](https://badge.waffle.io/Opine-Org/Route.png?label=ready&title=Ready)](https://waffle.io/Opine-Org/Route)
Opine\Route (SlimFast)
======================

Service wrapper for FastRoute providing a more slim-like interface.

[![Build Status](https://travis-ci.org/Opine-Org/Route.png?branch=master)](https://travis-ci.org/Opine-Org/Route)

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Opine-Org/Route/badges/quality-score.png?s=345960c961c6d6da9788d4238c2f9c2a90a29a84)](https://scrutinizer-ci.com/g/Opine-Org/Route/)

[![Code Coverage](https://scrutinizer-ci.com/g/Opine-Org/Route/badges/coverage.png?s=a8bb5c9fd7b98c7c4debb4d88e1064ee5e48f3c4)](https://scrutinizer-ci.com/g/Opine-Org/Route/)

## Background

[FastRoute](https://github.com/nikic/FastRoute) is an extremely fast PHP routing library.

Opine\Route is a service wrapper that makes it easy to define routes via a YAML file, cache routes and execute them.

## Route Sample

```yaml
routes:
    GET:
        /sample:                            controller@sampleOutput
        /api/add:                           controller@sampleOutput
        /api/edit:                          controller@sampleOutput
        /api/list:                          controller@sampleOutput
        /api/upload:                        controller@sampleOutput
        /api/upload/file:                   controller@sampleOutput
        /api/upload/file/{name}:            controller@sampleOutput2
        /api2/add:                          [controller@sampleOutput, {before: controller@beforeFilter, after: controller@afterFilter}]
        /api2/edit:                         [controller@sampleOutput, {before: controller@beforeFilter, after: controller@afterFilter}]
        /api2/list:                         [controller@sampleOutput, {before: controller@beforeFilter, after: controller@afterFilter}]
        /api2/upload:                       [controller@sampleOutput, {before: controller@beforeFilter, after: controller@afterFilter}]
        /api2/upload/file:                  [controller@sampleOutput, {before: controller@beforeFilter, after: controller@afterFilter}]
        /api2/upload/file/{name}:           [controller@sampleOutput2, {before: controller@beforeFilter, after: controller@afterFilter}]
        /sample2:                           [controller@sampleOutput, {name: Sample}]
        /sample3/{name}:                    [controller@sampleOutput2, {name: SampleParam}]
        /sample3/{name}/{age}/{location}:   [controller@sampleOutput3, {name: SampleParamAssoc}]
        /redirect:                          controller@sampleRedirect
```

## Load / Execute Routes

```php
$routeFile = '/var/www/project/config/routes/route.yml';
$containerFile = '/var/www/project/config/containers/container.yml';
$webroot = '/var/www/project/public';
$config = new \Opine\Config\Service($webroot);
$config->cacheSet();
$container = \Opine\Container\Service::instance($webroot, $config, $containerFile);
$routeService = new Opine\Route\Service($webroot, $container);
$routeModel = new Opine\Route\Model($webroot, $routeService);
$routeModel->yaml($routeFile);
$response = $this->route->run('GET', '/sample');
var_dump($response);
```


## Installation
```sh
composer require "opine/route:dev-master"
composer install
```

## Author

Ryan Mahoney can be reached at ryan@virtuecenter.com or @vcryan on Twitter.