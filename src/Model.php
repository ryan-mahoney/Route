<?php
/**
 * Opine\Route\Model
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
namespace Opine\Route;
use Symfony\Component\Yaml\Yaml;

class Model {
    private $root;
    private $route;
    private $bundleModel;

    public function __construct ($root, $route, $bundleModel=NULL) {
        $this->root = $root;
        $this->route = $route;
        $this->bundleModel = $bundleModel;
    }

    public function build () {
        $routes = glob($this->root . '/../config/routes/*.yml');
        if ($routes === FALSE) {
            $routes = [];
        }
        $this->bundleRoutes();
        foreach ($routes as $route) {
            $this->yaml($route);
        }
    }

    private function bundleRoutes (&$routes) {
        $bundles = $this->bundleModel->bundles();
        if (empty($bundles)) {
            return;
        }
        foreach ($bundles as $bundle) {
            if (!isset($bundle['routeFiles']) || empty($bundle['routeFiles'])) {
                continue;
            }
            foreach ($bundle['routeFiles'] as $file) {
                $routes[] = $file;
            }
        }
    }

    public function yaml ($file) {
        try {
            if (function_exists('yaml_parse_file')) {
                $routes = yaml_parse_file($file);
            }
            $routes = Yaml::parse(file_get_contents($file));
        } catch (Exception $e) {
            throw new Exception('Can not parse file: ' . $file . ', ' . $e->getMessage());
        }
        foreach ($routes['routes'] as $method => $paths) {
            $this->paths($method, $paths);
        }
    }

    private function paths ($method, Array $paths) {
        foreach ($paths as $pattern => $path) {
            $this->path($method, $pattern, $path);
        }
    }

    private function path ($method, $pattern, $callback) {
        $options = [];
        $count = 0;
        if (is_array($callback)) {
            $count = count($callback);
        }
        if (is_array($callback) && $count == 2) {
            $options = $callback[1];
            $callback = $callback[0];
        }
        if (is_array($callback) && $count == 1) {
            $callback = $callback[0];
        }
        if (is_array($callback) && ($count == 0 || $count > 2)) {
            throw new Exception('Invalid callback');
        }
        $this->route->method($method, $pattern, $callback, $options);
    }
}