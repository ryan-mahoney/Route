<?php
namespace Opine;

use Opine\Config\Service as Config;
use Opine\Container\Service as Container;
use PHPUnit_Framework_TestCase;

class RouteTest extends PHPUnit_Framework_TestCase {
    private $route;

    public function setup () {
        $root = __DIR__ . '/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = Container::instance($root, $config, $root . '/../container.yml');
        $this->route = $container->get('route');
    }

    private function initializeRoutes () {
        $this->route->get('/sample', 'controller@sampleOutput');

        $this->route->get('/api', [
            '/add' => 'controller@sampleOutput',
            '/edit' => 'controller@sampleOutput',
            '/list' => 'controller@sampleOutput',
            '/upload' => [
                '' => 'controller@sampleOutput',
                '/file' => 'controller@sampleOutput',
                '/file/{name}' => 'controller@sampleOutput2'
            ]
        ]);

        $this->route->get(
            'controller@beforeFilter',
            '/api2', [
                '/add' => 'controller@sampleOutput',
                '/edit' => 'controller@sampleOutput',
                '/list' => 'controller@sampleOutput',
                '/upload' => [
                    '' => 'controller@sampleOutput',
                    '/file' => 'controller@sampleOutput',
                    '/file/{name}' => 'controller@sampleOutput2'
                ]
            ],
            'controller@afterFilter'
        );

        $this->route->get('/sample2', 'controller@sampleOutput', 'Sample');

        $this->route->get('/sample3/{name}', 'controller@sampleOutput2', 'SampleParam');

        $this->route->get('/sample3/{name}/{age}/{location}', 'controller@sampleOutput3', 'SampleParamAssoc');

        $this->route->get('/redirect', 'controller@sampleRedirect');
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
}