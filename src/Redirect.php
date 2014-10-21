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
use Exception;

class Redirect {
	private $mode = false;
	private $routeName;
	private $parameters;
	private $location;
	private $action;
	private $route;

	public function __construct ($route) {
		$this->route = $route;
	}

	public function to ($location) {
		$this->mode = 'redirect';
		$this->location = $location;
		return $this;
	}

	public function route ($routeName, Array $parameters=[]) {
		$this->mode = 'named';
		$this->routeName = $routeName;
		$this->parameters = $parameters;
		return $this;
	}

	public function action ($action, Array $parameters=[]) {
		$this->mode = 'action';
		$this->action = $action;
		$this->parameters = $parameters;
		return $this;
	}

	public function with ($key, $value) {
		$_SESSION[$key] = $value;
		return $this;
	}

	public function execute () {
		switch ($this->mode) {
			case 'redirect':
				header('Location: ' . $this->location);
				break;

			case 'named':
				$this->route->runNamed($this->routeName, $this->parameters);
				break;

			case 'action':
				if (is_string($this->action)) {
					$this->route->stringToCallback($this->action);
				}
				$this->route->execute($this->action, $this->parameters);
				break;
		}
		return false;
	}
}