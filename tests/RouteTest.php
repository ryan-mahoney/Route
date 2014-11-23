<?php
namespace Opine;

use Opine\Config\Service as Config;
use Opine\Container\Service as Container;

class RouteTest extends \PHPUnit_Framework_TestCase {
    private $route;

    public function setup () {
        $root = __DIR__ . '/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = new Container($root, $config, $root . '/../container.yml');
        $this->route = $container->route;
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

        //$this->route->get('/sample/{input}', 'controller@sampleOutput2');
        //$this->route->cacheSet();
/*
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
        for ($i=0; $i < 1; $i++) {
            $this->route->get('/{' . uniqid() . '}/{' . uniqid() . '}/{' . uniqid() . '}', $callback);
        }
*/
    }
/*
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
*/

    public function testRouteWithStringController () {
        $this->initializeRoutes();
        $response = $this->route->run('GET', '/sample', $header);
        $this->assertTrue($response == 'SAMPLE' && $header == 200);
    }

    public function testRouteWithGroup () {
        $response = $this->route->run('GET', '/api/upload/file/xyz', $header);
        $this->assertTrue($response == 'SAMPLExyz' && $header == 200);
    }

    public function testRouteWithGroupFilters () {
        $response = $this->route->run('GET', '/api2/upload/file/xyz', $header);
        $this->assertTrue($response == 'STARTSAMPLExyzEND' && $header == 200);
    }

    public function testRouteFindNamedRoutes () {
        $this->assertTrue($this->route->runNamed('Sample') == 'SAMPLE');
        $this->assertTrue($this->route->runNamed('SampleParam', ['Ryan']) == 'SAMPLERyan');
        $this->assertTrue($this->route->runNamed('SampleParamAssoc', ['age' => 35, 'location' => 'NY', 'name' => 'Ryan']) == 'Name: Ryan Age: 35 Location: NY');
    }

    public function testRouteRedirect () {
        $this->assertTrue($this->route->run('GET', '/redirect', $header) == 'From Redirect');
    }
}