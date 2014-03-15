<?php
namespace Opine;

class RouteTest extends \PHPUnit_Framework_TestCase {
    private $route;

    public function setup () {
        date_default_timezone_set('UTC');
        $root = getcwd();
        $container = new Container($root, $root . '/container.yml');
        $this->route = $container->route;
    }

    private function initializeRoutes () {
        $this->route->get('/', function () {
            echo 'Home';
        })->get('/about', function () {
            echo 'About';
        })->get('/blog', function () {
            echo 'Blog';
        })->get('/hello/{name}', function ($name) {
            echo $name;
        })->post('/form', function () {
            echo 'Received';
        })->get('/outer', function () {
            echo 'Outer';
            echo $this->route->run('GET', '/inner');
        })->get('/inner', function () {
            echo 'Inner';
        });
        $callback = function () {
            echo 'OK';
        };
        for ($i=0; $i < 1000; $i++) {
            $this->route->get('/{' . uniqid() . '}/{' . uniqid() . '}/{' . uniqid() . '}', $callback);
        }
    }

    public function testRouteGetFirstMatch () {
        $this->initializeRoutes();
        $response = $this->route->run('GET', '/');
        $this->assertTrue($response === 'Home');
    }

    public function testRouteGetSecondMatch () {
        $response = $this->route->run('GET', '/blog');
        $this->assertTrue($response === 'Blog');
    }

    public function testRouteGetMatchParamater () {
        $response = $this->route->run('GET', '/hello/Ryan');
        $this->assertTrue($response === 'Ryan');
    }

    public function testRoutePostMatch () {
        $response = $this->route->run('POST', '/form');
        $this->assertTrue($response === 'Received');
    }

    public function testRouteGetNoMatch () {
        $header = false;
        $this->route->run('GET', '/nothing', $header);
        $this->assertTrue($header === 404);
    }

    public function testRoutePostNoMatch () {
        $header = false;
        $this->route->run('POST', '/nothing', $header);
        $this->assertTrue($header === 404);
    }

    public function testRouteGetMatchBefore () {
        $expected = 'BeforeHome';
        $this->route->before(function () {
            echo 'Before';
        });
        $response = $this->route->run('GET', '/');
        $this->assertTrue($response === $expected);   
    }

    public function testRouteGetMatchAfter () {
        $expected = 'BeforeHomeAfter';
        $this->route->after(function () {
            echo 'After';
        });
        $response = $this->route->run('GET', '/');
        $this->assertTrue($response === $expected);   
    }

    public function testRoutePurgeBeforeAfter () {
        $this->route->purgeBefore()->purgeAfter();
        $response = $this->route->run('GET', '/');
        $this->assertTrue($response === 'Home');
    }

    public function testRouteAndReRoute () {
        $response = $this->route->run('GET', '/');
        $this->assertTrue($response === 'Home');
        $response = $this->route->run('GET', '/about');
        $this->assertTrue($response === 'About');
    }

    public function testRouteWithinRoute () {
        $response = $this->route->run('GET', '/outer');
        $this->assertTrue($response === 'OuterInner');   
    }
}