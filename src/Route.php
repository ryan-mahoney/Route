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

    public function __construct ($root, $collector) {
        $this->root = $root;
        $this->cachePath = $this->root . '/../cache/routes.php';
        $this->collector = $collector;
        $this->container = container();
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

    public function get ($pattern, $callback) {
        $this->method('GET', $pattern, $callback);
        return $this;
    }

    public function post ($pattern, $callback) {
        $this->method('POST', $pattern, $callback);
        return $this;
    }

    public function delete ($pattern, $callback) {
        $this->method('DELETE', $pattern, $callback);
        return $this;
    }

    public function patch ($pattern, $callback) {
        $this->method('PATCH', $pattern, $callback);
        return $this;
    }

    public function put ($pattern, $callback) {
        $this->method('PUT', $pattern, $callback);
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
        if (!is_array($callback)) {
            return;
        }
        $service = $this->container->{$callback[0]};
        if (is_object($service)) {
            $callback[0] = $service;
        }
    }

    private function method ($method, $pattern, $callback) {
        $this->stringToCallback($callback);
        $this->collector->addRoute($method, $pattern, $callback);
    }

    private function dispatcher () {
        if ($this->dispatcher === false) {
            if (is_array($this->cache) == true) {
                $this->dispatcher = new GroupCountBased($this->cache);
            } else {
                $this->dispatcher = new GroupCountBased($this->collector->getData());
            }
        }
        return $this->dispatcher;
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
                $header =  200;
                ob_start();
                foreach ($this->before as $before) {
                    $before();
                }
                $this->arrayToService($route[1]);
                call_user_func_array($route[1], $route[2]);
                foreach ($this->after as $after) {
                    $after();
                }
                $return = ob_get_clean();
                break;
        }
        if ($getModified) {
            $_GET = $originalGet;
        }
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