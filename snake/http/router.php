<?php

/**
 * Router class to handle HTTP routing
 * Homebank - Personal Banking Application php framework
 * (c) 2024 Ali Nawaz - MIT License
 * Uses a simple routing mechanism to map URLs to controller actions
 */

class Router
{

    private static $routes = [];
    private static $request = null;

    public static function get($path, $controllerAction)
    {
        self::$routes['GET'][$path] = $controllerAction;
    }

    public static function post($path, $controllerAction)
    {
        self::$routes['POST'][$path] = $controllerAction;
    }

    public static function put($path, $controllerAction)
    {
        self::$routes['PUT'][$path] = $controllerAction;
    }

    public static function delete($path, $controllerAction)
    {
        self::$routes['DELETE'][$path] = $controllerAction;
    }

    public static function patch($path, $controllerAction)
    {
        self::$routes['PATCH'][$path] = $controllerAction;
    }

    /**
     * @method middleware
     * @param $middleware_class string
     * @param $callback callable
     * Adds middleware to the router, callback will be given with instance of Router class to create group of routes with middleware
     * Middleware_class should implement handle($request, $next) method, will be auto loaded from ./app/middlewares/ accordingly
     * Example:
     * $router->middleware('AuthMiddleware', function($router) {
     *   $router->get('/dashboard', 'DashboardController@index');
     * });
     */
    public static function middleware($middleware_class, $callback)
    {
        $request = self::getRequestSingletonInstance();
        $middleware = loadClass("app.middlewares.{$middleware_class}");
        $router_static_class = self::class;
        if ($middleware && is_callable($callback)) {
            $middleware->handle($request, function($request) use($callback, $router_static_class) {
                $callback($router_static_class);
            });
        } else {
            die("Router Error: Middleware class {$middleware_class} not found or not callable.");
        }
    }

    private static function getRequestSingletonInstance() {
        if(self::$request === NULL) {
            $request = loadClass('snake.http.Request');
            self::$request = $request;
        }
        return self::$request;
    }

    public static function dispatch()
    {
        // Detect request method (GET, POST, PUT, DELETE, PATCH)
        $method = $_SERVER['REQUEST_METHOD'];

        // Detect request URI path (strip query string if present)
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Normalize (remove trailing slash except for root "/")
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        // var_dump($method, $uri);

        // Check if route exists
        if (isset(self::$routes[$method][$uri])) {
            list($controllerName, $action) = explode('@', self::$routes[$method][$uri]);

            // Load controller
            $controller = loadClass("app.controllers.{$controllerName}");

            if ($controller && method_exists($controller, $action)) {
                $request = self::getRequestSingletonInstance();
                $response = loadClass('snake.http.Response');
                echo call_user_func([$controller, $action], $request, $response);
                exit();
            } else {
                self::renderError(['code' => 500, 'message' => "Controller or method not found: {$controllerName}@{$action}"]);
            }
        } else {
            self::renderError(['code' => 404, 'message' => "Route not found for [{$method}] {$uri}"]);
        }
    }

    private static function renderError($data)
    {

        global $root_path;

        // Globalizing the passed data variables
        if ($data != NULL) {
            foreach ($data as $var => $val) {
                $$var = $val;
            }
        }

        // Reading view file
        $view_file = $root_path . '/homebank/http/templates/error.php';
        include_once $view_file;
        die();
    }
}
