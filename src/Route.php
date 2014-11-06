<?php
/**
 * Opine\Route
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyreadDiskCacheright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine;
use RouteException;
use Redirect;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\BadRouteException;
use ReflectionClass;

class Route {
    private $collector;
    private $before = [];
    private $after = [];
    private $dispatcher = false;
    private $root;
    private $cache = false;
    private $container;
    private $cachePath;
    private $path = false;
    private $queryString = false;
    private $get = false;
    private $namedRoutes = [];
    private $testMode = false;
    private $knownRoutes = [];

    public function __construct ($root, $collector, $container=false) {
        $this->root = $root;
        $this->cachePath = $this->root . '/../cache/routes.php';
        $this->collector = $collector;
        $this->container = $container;
    }

    public function testMode() {
        $this->testMode = true;
    }

    public function pathGet () {
        if ($this->path === false) {
            $this->pathDetermine();
        }
        return $this->path;
    }

    public function queryStringGet () {
        if ($this->queryString === false) {
            $this->pathDetermine();
        }
        return $this->queryString;
    }

    public function getGet () {
        if ($this->get === false) {
            $this->pathDetermine();
        }
        return $this->get;   
    }

    public function cachePathSet ($path) {
        $this->cachePath = $path;
        return $this;
    }

    public function before ($callback) {
        $this->stringToCallback($callback);
        $this->arrayToService($callback);
        $this->before[] = $callback;
        return $this;
    }

    public function after ($callback) {
        $this->stringToCallback($callback);
        $this->arrayToService($callback);
        $this->after[] = $callback;
        return $this;
    }

    private function actionPrepare($callback) {
        $this->stringToCallback($callback);
        $this->arrayToService($callback);
        return $callback;
    }

    public function purgeAfter() {
        $this->after = [];
        return $this;
    }

    public function purgeBefore () {
        $this->before = [];
        return $this;
    }

    private function variableMethodArgs (Array $arguments) {
        $parsed = [
            'before' => false,
            'pattern' => '',
            'callback' => '',
            'after' => false,
            'group' => false,
            'name' => false
        ];
        if (is_string($arguments[0]) && substr_count($arguments[0], '@') == 1) {
            $parsed['before'] = array_shift($arguments);
        }
        $parsed['pattern'] = array_shift($arguments);
        if (count($arguments) == 2) {
            if (substr_count($arguments[1], '@') == 1) {
                $parsed['after'] = array_pop($arguments);
            } else {
                $parsed['name'] = array_pop($arguments);
            }
        }
        if (count($arguments) == 3) {
            $parsed['after'] = array_pop($arguments);
            $parsed['name'] = array_pop($arguments);
        }
        $parsed['callback'] = $arguments[0];
        $this->patternNested($parsed);
        $this->filtersGraft($parsed);
        return $parsed;
    }

    private function patternNested (Array &$arguments) {
        if (!is_array($arguments['callback'])) {
            return false;
        }
        $arguments['group'] = [];
        $this->patternNestedCallback($arguments, $arguments['pattern'], $arguments['callback']);
        return true;
    }

    private function patternNestedCallback (Array &$arguments, $prefix, Array $group) {
        foreach ($group as $pattern => $callback) {
            if (is_string($callback)) {
                $arguments['group'][] = [
                    'pattern' => $prefix . $pattern,
                    'callback' => $callback
                ];
            } elseif (is_array($callback)) {
                $this->patternNestedCallback($arguments, $prefix . $pattern, $callback);
            }
        }
    }

    private function filtersGraft (Array &$arguments) {
        $prefix = $suffix = '';
        if ($arguments['before'] !== false) {
            $prefix = str_replace('@', 'BBBB', $arguments['before']) . 'bbbb';
        }
        if ($arguments['after'] !== false) {
            $suffix = 'AAAA' . str_replace('@', 'aaaa', $arguments['after']);
        }
        if ($prefix == '' && $suffix == '') {
            return;
        }
        if (is_string($arguments['callback'])) {
            $arguments['callback'] = $prefix . $arguments['callback'] . $suffix;
        }
        if (is_array($arguments['group'])) {
            foreach ($arguments['group'] as &$group) {
                $group['callback'] = $prefix . $group['callback'] . $suffix;
            }
        }
    }

    public function get () {
        $arguments = $this->variableMethodArgs(func_get_args());
        $this->method('GET', $arguments);
        return $this;
    }

    public function post ($pattern, $callback) {
        $arguments = $this->variableMethodArgs(func_get_args());
        $this->method('POST', $arguments);
        return $this;
    }

    public function delete ($pattern, $callback) {
        $arguments = $this->variableMethodArgs(func_get_args());
        $this->method('DELETE', $arguments);
        return $this;
    }

    public function patch ($pattern, $callback) {
        $arguments = $this->variableMethodArgs(func_get_args());
        $this->method('PATCH', $arguments);
        return $this;
    }

    public function put ($pattern, $callback) {
        $arguments = $this->variableMethodArgs(func_get_args());
        $this->method('PUT', $arguments);
        return $this;
    }

    public function stringToCallback (&$callback) {
        if (!is_string($callback)) {
            return;
        }
        if (substr_count($callback, '@') == 1) {
            $callback = explode('@', $callback);
        } else {
            throw new RouteException('Invalid callback: ' . $callback);
        }
    }

    public function arrayToService (&$callback) {
        if (!is_array($callback) || $this->container === false) {
            return;
        }
        $service = $this->container->{$callback[0]};
        if (is_object($service)) {
            $callback[0] = $service;
        }
    }

    public function show () {
        return $this->knownRoutes;
    }

    private function method ($method, $arguments) {
        if (is_string($arguments['callback'])) {
            $this->knownRoutes[$method][$arguments['pattern']] = $arguments['callback'];
        }
        if ($arguments['group'] == false) {
            $this->stringToCallback($arguments['callback']);
            try {
                $this->collector->addRoute($method, $arguments['pattern'], $arguments['callback']);
            } catch (BadRouteException $e) {
                if (!$this->testMode) {
                    throw $e;
                }
            }
            if (!empty($arguments['name'])) {
                $this->namedRoutes[$arguments['name']] = $arguments['callback'];
            }
            return;
        }
        foreach ($arguments['group'] as $group) {
            $this->knownRoutes[$method][$group['pattern']] = $group['callback'];
            $this->stringToCallback($group['callback']);
            try {
                $this->collector->addRoute($method, $group['pattern'], $group['callback']);
            } catch (BadRouteException $e) {
                if (!$this->testMode) {
                    throw $e;
                }
            }
        }
    }

    private function dispatcher () {
        if ($this->dispatcher === false) {
            if (is_array($this->cache) == true) {
                $this->dispatcher = new GroupCountBased($this->cache);
            } else {
                if (file_exists($this->cachePath)) {
                    $data = require $this->cachePath;
                    $this->dispatcher = new GroupCountBased($data);
                } else {
                    $this->dispatcher = new GroupCountBased($this->collector->getData());
                }
            }
        }
        return $this->dispatcher;
    }

    private function filterParse (Array &$callable, &$beforeActions=[], &$afterActions=[]) {
        //[ClassBBBBmethodbbbbClass, methodAAAAClassaaaamethod]
        if (substr_count($callable[0], 'BBBB') == 1 && substr_count($callable[0], 'bbbb') == 1) {
            $parts = preg_split('/(BBBB|bbbb)/', $callable[0]);
            $callable[0] = $parts[2];
            $beforeActions[] = $this->actionPrepare([$parts[0], $parts[1]]);
        }
        if (substr_count($callable[1], 'AAAA') == 1 && substr_count($callable[1], 'aaaa') == 1) {
            $parts = preg_split('/(AAAA|aaaa)/', $callable[1]);
            $callable[1] = $parts[0];
            $afterActions[] = $this->actionPrepare([$parts[1], $parts[2]]);
        }
    }

    private function pathDetermine ($path=false, &$getModified=false) {
        $this->queryString = '';
        $this->get = [];
        if ($path === false) {
            $this->path = $_SERVER['REQUEST_URI'];
            if (substr_count($this->path, '?') > 0) {
                $this->path = str_replace('?' . $_SERVER['QUERY_STRING'], '', $this->path);
                $this->queryString = $_SERVER['QUERY_STRING'];
            }
        } else {
            $this->path = $path;
            if (substr_count($path, '?') > 0) {
                $parts = explode('?', $path, 2);
                parse_str($parts[1], $_GET);
                $this->queryString = $parts[1];
                $this->path = $parts[0];
                $getModified = true;
            }
        }
        $this->get = $_GET;
    }

    public function execute (Array $callable, Array $parameters=[], $beforeActionsIn=[], $afterActionsIn=[]) {
        $beforeActions = array_merge($this->before, $beforeActionsIn);
        $afterActions = array_merge($this->after, $afterActionsIn);
        $this->filterParse($callable, $beforeActions, $afterActions);
        foreach ($beforeActions as $before) {
            if (!is_object($before[0])) {
                $before[0] = new $before[0]();
            }
            if ($this->response(call_user_func_array($before, $parameters)) === false) {
                return false;
            }
        }
        $this->purgeBefore();
        $this->arrayToService($callable);
        if (!is_object($callable[0])) {
            $callable[0] = new $callable[0]();
        }
        if ($this->response(call_user_func_array($callable, $parameters)) === false) {
            return;
        }
        foreach ($afterActions as $after) {
            if (!is_object($after[0])) {
                $after[0] = new $after[0]();
            }
            if ($this->response($after($parameters)) === false) {
                return;
            }
        }
        $this->purgeAfter();
        return;
    }

    public function run ($method=false, $path=false, &$code=false) {
        $originalGet = $_GET;
        $getModified = false;
        $debug = false;
        if ($method === false) {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        $this->pathDetermine($path, $getModified);
        $dispatcher = $this->dispatcher();
        $route = $dispatcher->dispatch($method, $this->path);
        switch ($route[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                http_response_code(404);
                $output = false;
                break;

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                $output = false;
                break;

            case \FastRoute\Dispatcher::FOUND:
                try {
                    http_response_code(200);
                    ob_start();
                    $response = $this->execute($route[1], $route[2]);
                    if ($response === false) {
                        $output = false;    
                    } else {
                        $output = ob_get_clean();
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    $output = false;
                }
                break;

            default:
                http_response_code(404);
                $output = false;
        }
        if ($getModified) {
            $_GET = $originalGet;
        }
        $code = http_response_code();
        return $output;
    }

    public function runNamed ($name, Array $parameters=[]) {
        if (!array_key_exists($name, $this->namedRoutes)) {
            http_response_code(404);
            return false;
        }
        ob_start();
        $beforeActions = [];
        $afterActions = [];
        $action = $this->namedRoutes[$name];
        $this->filterParse($action, $beforeActions, $afterActions);
        if (count($parameters) == 0) {
            $result = $this->execute($action, $parameters, $beforeActions, $afterActions);
        } else {
            if ($parameters !== array_values($parameters)) {
                $reflector = new ReflectionClass($action[0]);
                $method = $reflector->getMethod($action[1]);
                $reflectedParameters = $method->getParameters();
                $newParameters = [];
                foreach ($reflectedParameters as $parameter) {
                    $parameter = $parameter->name;
                    if (!array_key_exists($parameter, $parameters)) {
                        $newParameters[] = null;
                        continue;
                    }
                    $newParameters[] = $parameters[$parameter];
                }
                $result = $this->execute($action, $newParameters);
            } else {
                $result = $this->execute($action, $parameters);
            }
        }
        return ob_get_clean();
    }

    private function response ($response) {
//NOTE: should we look into  support for streams? (like Slim)
        if ($response === '') {
            return true;
        } elseif ($response === false) {
            return false;
        } elseif (is_string($response)) {
            echo $response;
            return true;
        } elseif (is_array($response)) {
            echo json_encode($response);
            return true;
        } elseif (is_object($response)) {
            $class = get_class($response);
            if ($class == 'Opine\Redirect') {
                $response->execute();
                return false;
            } elseif (method_exists($response, '__toString')) {
                echo (string)$response;
                return true;
            } else {
                return false;
            }
        } elseif (is_integer($response)) {
            http_response_code($response);
            return false;
        } else {
            return true;
        }
    }

    public function namedRoutesGet () {
        return $this->namedRoutes;
    }

    public function collectorDataGet () {
        return $this->collector->getData();
    }

    public function cacheSet ($data) {
        if (empty($data)) {
            if (!file_exists($this->cachePath)) {
                return;
            }
            $this->cache = include $this->cachePath;
            return;
        }
        $this->cache = $data;
    }

    public function cacheGenerate () {
        file_put_contents(
            $this->cachePath,
            '<?php return ' . var_export($this->collector->getData(), true) . ';'
        );
        return $this->collector->getData();
    }

    public function redirect () {
        return new \Opine\Redirect($this);
    }
}