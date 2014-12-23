<?php
namespace Opine;

use Opine\Config\Service as Config;
use Opine\Container\Service as Container;
use Opine\Route\Exception as RouteException;
use PHPUnit_Framework_TestCase;

class RouteTest extends PHPUnit_Framework_TestCase {
    private $route;
    private $routeModel;

    public function setup () {
        $root = __DIR__ . '/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = Container::instance($root, $config, $root . '/../config/container.yml');
        $this->route = $container->get('route');
        $this->routeModel = $container->get('routeModel');
    }

    private function initializeRoutes () {
        $this->routeModel->build();
        //$this->routeModel->yaml(__DIR__ . '/routes.yml');
    }

    public function testRouteWithStringController () {
        $header = '';
        $this->initializeRoutes();
        $response = $this->route->run('GET', '/sample', $header);
        $this->assertTrue($response == 'SAMPLE' && $header == 200);
    }

    public function testRouteWithGroup () {
        $header = '';
        $response = $this->route->run('GET', '/api/upload/file/xyz', $header);
        $this->assertTrue($response == 'SAMPLExyz' && $header == 200);
    }

    public function testRouteWithGroupFilters () {
        $header = '';
        $response = $this->route->run('GET', '/api2/upload/file/xyz', $header);
        $this->assertTrue($response == 'STARTSAMPLExyzEND' && $header == 200);
    }

    public function testRouteFindNamedRoutes () {
        $this->assertTrue($this->route->runNamed('Sample') === 'SAMPLE');
        $this->assertTrue($this->route->runNamed('SampleParam', ['Ryan']) === 'SAMPLERyan');
        $this->assertTrue($this->route->runNamed('SampleParamAssoc', ['age' => 35, 'location' => 'NY', 'name' => 'Ryan']) == 'Name: Ryan Age: 35 Location: NY');
    }

    public function testRouteRedirect () {
        $header = '';
        $this->assertTrue($this->route->run('GET', '/redirect', $header) === 'From Redirect');
    }

    public function testRouteServiceWrapper () {
        $this->assertTrue('Controller' === get_class($this->route->service('controller')));
    }

    public function testRouteServiceMethodWrapper () {
        $this->assertTrue('success: A' === $this->route->serviceMethod('controller@check', 'A'));
    }

    public function testRouteServiceMethodWrapperFail () {
        $success = true;
        try {
            $this->route->serviceMethod('controller:check', 'A');
        } catch (RouteException $e) {
            $success = false;
        }
        $this->assertFalse($success);
    }
}