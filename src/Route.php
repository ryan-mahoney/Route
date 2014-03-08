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

class Route {
	private $collector;
	private $before = [];
	private $after = [];

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

	private function method ($method, $pattern, callable $callback) {
		$this->collector->addRoute($method, $pattern, $callback);
	}

	public function run ($method=false, $uri=false, &$header=false) {
		if ($method === false) {
			$method = $_SERVER['REQUEST_METHOD'];
		}
		if ($uri === false) {
			$uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
	        if (substr_count($uri, '/') > 0) {
	            $uri = explode('/', trim($uri, '/'))[0];
	        }
		}
    	$dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->collector->getData());
		$route = $dispatcher->dispatch($method, $uri);
		switch ($route[0]) {
		    case \FastRoute\Dispatcher::NOT_FOUND:
		        $header = 404;
		        return false;

		    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
		        $allowedMethods = $route[1];
		        $header = 405;
		        return false;

		    case \FastRoute\Dispatcher::FOUND:
		        $header =  200;
		        ob_start();
		        foreach ($this->before as $before) {
		        	$before();
		        }
		        $route[1]($route[2]);
		        foreach ($this->after as $after) {
		        	$after();
		        }
		        return ob_get_clean();
		}
	}
}