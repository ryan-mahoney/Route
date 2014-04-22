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

class Route {
    private $collector;
    private $before = [];
    private $after = [];
    private $dispatcher = false;

    public function __construct ($collector) {
        $this->collector = $collector;
    }

    public function before (callable $callback) {
        $this->before[] = $callback;
        return $this;
    }

    public function after (callable $callback) {
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

    public function get ($pattern, callable $callback) {
        $this->method('GET', $pattern, $callback);
        return $this;
    }

    public function post ($pattern, callable $callback) {
        $this->method('POST', $pattern, $callback);
        return $this;
    }

    public function delete ($pattern, callable $callback) {
        $this->method('DELETE', $pattern, $callback);
        return $this;
    }

    public function patch ($pattern, callable $callback) {
        $this->method('PATCH', $pattern, $callback);
        return $this;
    }

    public function put ($pattern, callable $callback) {
        $this->method('PUT', $pattern, $callback);
        return $this;
    }

    private function method ($method, $pattern, callable $callback) {
        $this->collector->addRoute($method, $pattern, $callback);
    }

    private function dispatcher () {
        if ($this->dispatcher === false) {
            $this->dispatcher = new GroupCountBased($this->collector->getData());
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

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $route[1];
                $header = 405;
                $return = false;

            case \FastRoute\Dispatcher::FOUND:
                $header =  200;
                ob_start();
                foreach ($this->before as $before) {
                    $before();
                }
                call_user_func_array($route[1], $route[2]);
                foreach ($this->after as $after) {
                    $after();
                }
                $return = ob_get_clean();
        }
        if ($getModified) {
            $_GET = $originalGet;
        }
        return $return;
    }

    public function hook ($name, callable $callback) {
        switch ($name) {
            case 'slim.before':
                //This hook is invoked before the Slim application is run and before output buffering is turned on. This hook is invoked once during the Slim application lifecycle.
                break;

            case 'slim.before.router':
                //This hook is invoked after output buffering is turned on and before the router is dispatched. This hook is invoked once during the Slim application lifecycle.
                break;

            case 'slim.before.dispatch':
                $this->before($callback);
                //This hook is invoked before the current matching route is dispatched. Usually this hook is invoked only once during the Slim application lifecycle; however, this hook may be invoked multiple times if a matching route chooses to pass to a subsequent matching route.
                break;

            case 'slim.after.dispatch':
                $this->after($callback);
                //This hook is invoked after the current matching route is dispatched. Usually this hook is invoked only once during the Slim application lifecycle; however, this hook may be invoked multiple times if a matching route chooses to pass to a subsequent matching route.
                break;

            case 'slim.after.router':
                //This hook is invoked after the router is dispatched, before the Response is sent to the client, and after output buffering is turned off. This hook is invoked once during the Slim application lifecycle.
                break;

            case 'slim.after':
                break;

            default:
                throw new \Exception('hook: ' . $name . ' not implemented');
        }
    }

    public function name () {
        //still thinking about this one
        return $this;
    }
}