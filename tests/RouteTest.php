<?php
namespace Opine;

class RouteTest extends \PHPUnit_Framework_TestCase {
    private $route;

    public function setup () {
        date_default_timezone_set('UTC');
        $root = __DIR__ . '/../public';
        $container = new Container($root, $root . '/../container.yml');
        $this->route = $container->route;
    }

    private function initializeRoutes () {
        $this->route->get('/sample', '\Opine\RouteTest@sampleOutput');
        $this->route->get('/api', [
            '/add' => '\Opine\RouteTest@sampleOutput',
            '/edit' => '\Opine\RouteTest@sampleOutput',
            '/list' => '\Opine\RouteTest@sampleOutput',
            '/upload' => [
                '' => '\Opine\RouteTest@sampleOutput',
                '/file' => '\Opine\RouteTest@sampleOutput',
                '/file/{name}' => '\Opine\RouteTest@sampleOutput2'
            ]
        ]);
        $this->route->get(
            '\Opine\RouteTest@beforeFilter', 
            '/api2', [
                '/add' => '\Opine\RouteTest@sampleOutput',
                '/edit' => '\Opine\RouteTest@sampleOutput',
                '/list' => '\Opine\RouteTest@sampleOutput',
                '/upload' => [
                    '' => '\Opine\RouteTest@sampleOutput',
                    '/file' => '\Opine\RouteTest@sampleOutput',
                    '/file/{name}' => '\Opine\RouteTest@sampleOutput2'
                ]
            ],
            '\Opine\RouteTest@afterFilter'
        );
        //$this->route->get('/sample/{input}', '\Opine\RouteTest@sampleOutput2');
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

/*
    public function testRouteWithStringWithVarPathController () {
        $response = $this->route->run('GET', '/sample/abc', $header);
        $this->assertTrue($response == 'SAMPLEabc' && $header == 200);
    }
*/
    public function sampleOutput () {
        echo 'SAMPLE';
    }

    public function sampleOutput2 ($data) {
        echo 'SAMPLE' . $data;
    }

    public function beforeFilter () {
        echo 'START';
    }

    public function afterFilter () {
        echo 'END';
    }
}