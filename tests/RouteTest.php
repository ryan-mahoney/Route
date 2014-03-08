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
        })->post('/form', function () {
            echo 'Received';
        });
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
}