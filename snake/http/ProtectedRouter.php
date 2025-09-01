<?php

namespace Snake\Http;


class ProtectedRouter
{

    private $routes = [];
    private $middleware = '';

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    public function get($path, $controllerAction)
    {
        $this->routes['GET'][$path] = ['action' => $controllerAction, 'middleware' => $this->middleware];
    }

    public function post($path, $controllerAction)
    {
        $this->routes['POST'][$path] = ['action' => $controllerAction, 'middleware' => $this->middleware];
    }

    public function put($path, $controllerAction)
    {
        $this->routes['PUT'][$path] = ['action' => $controllerAction, 'middleware' => $this->middleware];
    }

    public function delete($path, $controllerAction)
    {
        $this->routes['DELETE'][$path] = ['action' => $controllerAction, 'middleware' => $this->middleware];
    }

    public function patch($path, $controllerAction)
    {
        $this->routes['PATCH'][$path] = ['action' => $controllerAction, 'middleware' => $this->middleware];
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
