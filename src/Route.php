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
 * The above copyright notice and this permission notice shall be included in
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
use FastRoute\Dispatcher\GroupCountBased;
use Exception;

class Route {
    private $collector;
    private $before = [];
    private $after = [];
    private $dispatcher = false;
    private $root;
    private $cache = false;
    private $container;
    private $cachePath;

    public function __construct ($root, $collector, $container=false) {
        $this->root = $root;
        $this->cachePath = $this->root . '/../cache/routes.php';
        $this->collector = $collector;
        $this->container = $container;
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
            'group' => false
        ];
        if (is_string($arguments[0]) && substr_count($arguments[0], '@') == 1) {
            $parsed['before'] = array_shift($arguments);
        }
        $parsed['pattern'] = array_shift($arguments);
        if (count($arguments) == 2) {
            $parsed['after'] = array_pop($arguments);
        }
        $parsed['callback'] = $arguments[0];
        $this->patternNested($parsed);
        $this->filtersGraft($parsed);
        return $parsed;
    }

    private function patternNested (Array &$arguments) {
        if (!is_array($arguments['callback'])) {
            return;
        }
        $arguments['group'] = [];
        $this->patternNestedCallback($arguments, $arguments['pattern'], $arguments['callback']);
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

    private function stringToCallback (&$callback) {
        if (!is_string($callback)) {
            return;
        }
        if (substr_count($callback, '@') == 1) {
            $callback = explode('@', $callback);
        } else {
            throw new RouteException('Invalid callback: ' . $callback);
        }
    }

    private function arrayToService (&$callback) {
        if (!is_array($callback) || $this->container === false) {
            return;
        }
        $service = $this->container->{$callback[0]};
        if (is_object($service)) {
            $callback[0] = $service;
        }
    }

    private function method ($method, $arguments) {
        if ($arguments['group'] == false) {
            $this->stringToCallback($arguments['callback']);
            $this->collector->addRoute($method, $arguments['pattern'], $arguments['callback']);
            return;
        }
        foreach ($arguments['group'] as $group) {
            $this->stringToCallback($group['callback']);
            $this->collector->addRoute($method, $group['pattern'], $group['callback']);
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

    private function filterParse (Array &$callable) {
        //[ClassBBBBmethodbbbbClass, methodAAAAClassaaaamethod]
        if (substr_count($callable[0], 'BBBB') == 1 && substr_count($callable[0], 'bbbb') == 1) {
            $parts = preg_split('/(BBBB|bbbb)/', $callable[0]);
            $callable[0] = $parts[2];
            $this->before([$parts[0], $parts[1]]);
        }
        if (substr_count($callable[1], 'AAAA') == 1 && substr_count($callable[1], 'aaaa') == 1) {
            $parts = preg_split('/(AAAA|aaaa)/', $callable[1]);
            $callable[1] = $parts[0];
            $this->after([$parts[1], $parts[2]]);
        }
    }

    public function run ($method=false, $uri=false, &$header=false) {
        $originalGet = $_GET;
        $getModified = false;
        $debug = false;
        if ($method === false) {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        if ($uri === false) {
            $uri = $_SERVER['REQUEST_URI'];
            if (substr_count($uri, '?') > 0) {
                $uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri);
            }
        } else {
            if (substr_count($uri, '?') > 0) {
                $parts = explode('?', $uri, 2);
                parse_str($parts[1], $_GET);
                $uri = $parts[0];
                $getModified = true;
            }
        }
        $dispatcher = $this->dispatcher();
        $route = $dispatcher->dispatch($method, $uri);
        switch ($route[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $header = 404;
                $return = false;
                break;

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $header = 405;
                $return = false;
                break;

            case \FastRoute\Dispatcher::FOUND:
                $this->filterParse($route[1]);
                $header =  200;
                ob_start();
                foreach ($this->before as $before) {
                    if (!is_object($before[0])) {
                        $before[0] = new $before[0]();
                    }
                    $before($route[2]);
                }
                $this->arrayToService($route[1]);
                if (!is_object($route[1][0])) {
                    $route[1][0] = new $route[1][0]();
                }
                call_user_func_array($route[1], $route[2]);
                foreach ($this->after as $after) {
                    if (!is_object($after[0])) {
                        $after[0] = new $after[0]();
                    }
                    $after($route[2]);
                }
                $return = ob_get_clean();
                break;
        }
        if ($getModified) {
            $_GET = $originalGet;
        }
        http_response_code($header);
        return $return;
    }

    public function getData () {
        return $this->collector->getData();
    }

    public function cacheSet ($data) {
        $this->cache = $data;
    }

    public function cacheGenerate () {
        file_put_contents(
            $this->cachePath,
            '<?php return ' . var_export($this->collector->getData(), true) . ';'
        );
        return $this->collector->getData();
    }
}

class RouteException extends Exception {}