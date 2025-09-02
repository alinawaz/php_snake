<?php

namespace Snake\Http;


class Route
{

    private $routes = [];
    private $middlewares = [];
    private $group = NULL;

    public function __construct($middlewares = [], $group = NULL)
    {
        if(!empty($middlewares)) $this->middlewares = $middlewares;
        if($group != NULL) $this->group = $group;
    }

    public function get($path, $controllerAction)
    {
        $this->routes['GET'][($this->group ? $this->group : '') . $path] = ['action' => $controllerAction, 'middlewares' => $this->middlewares];
    }

    public function post($path, $controllerAction)
    {
        $this->routes['POST'][($this->group ? $this->group : '') . $path] = ['action' => $controllerAction, 'middlewares' => $this->middlewares];
    }

    public function put($path, $controllerAction)
    {
        $this->routes['PUT'][($this->group ? $this->group : '') . $path] = ['action' => $controllerAction, 'middlewares' => $this->middlewares];
    }

    public function delete($path, $controllerAction)
    {
        $this->routes['DELETE'][($this->group ? $this->group : '') . $path] = ['action' => $controllerAction, 'middlewares' => $this->middlewares];
    }

    public function patch($path, $controllerAction)
    {
        $this->routes['PATCH'][($this->group ? $this->group : '') . $path] = ['action' => $controllerAction, 'middlewares' => $this->middlewares];
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public static function middleware($name, $callback)
    {
        if (is_callable($callback)) {
            $route_instance = new Route($name);
            $callback($route_instance);
            self::$routes = self::mergeRoutes(self::$routes, $route_instance->getRoutes());
            // dd(self::$routes);
        } else {
            die("Router Error: Middleware class {$name} not found or not callable.");
        }
    }

    public function group($path, $callback) {
        if (is_callable($callback)) {
            $route_instance = new Route($this->middlewares, $path);
            $callback($route_instance);
            $this->routes = $this->mergeRoutes($this->routes, $route_instance->getRoutes());
            // dd($this->routes);
        } else {
            die("Router Error: Group should be callable.");
        }
    }

    private function mergeRoutes($open_routes, $grouped_routes)
    {
        // dd($open_routes);
        foreach ($grouped_routes as $method => $url) {
            if(isset($open_routes[$method])) {
                $open_routes[$method] = array_merge($open_routes[$method], $grouped_routes[$method]);
            }else {
                $open_routes[$method] = $grouped_routes[$method];
            }
        }
        return $open_routes;
    }

}
