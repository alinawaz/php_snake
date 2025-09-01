<?php

namespace Snake\Http;

use App\Services\AppService;
use Snake\Http\Request;
use Snake\Http\Response;
use Snake\Http\ProtectedRouter;

class Router
{

    private static $routes = [];
    private static $request = null;
    private static $response = null;

    public static function get($path, $controllerAction)
    {
        self::$routes['GET'][$path] = ['action' => $controllerAction, 'middleware' => NULL];
    }

    public static function post($path, $controllerAction)
    {
        self::$routes['POST'][$path] = ['action' => $controllerAction, 'middleware' => NULL];
    }

    public static function put($path, $controllerAction)
    {
        self::$routes['PUT'][$path] = ['action' => $controllerAction, 'middleware' => NULL];
    }

    public static function delete($path, $controllerAction)
    {
        self::$routes['DELETE'][$path] = ['action' => $controllerAction, 'middleware' => NULL];
    }

    public static function patch($path, $controllerAction)
    {
        self::$routes['PATCH'][$path] = ['action' => $controllerAction, 'middleware' => NULL];
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
    public static function middleware($middleware_name, $callback)
    {
        if (is_callable($callback)) {
            $grouped_router_instance = new ProtectedRouter($middleware_name);
            $callback($grouped_router_instance);
            self::$routes = self::mergeRoutes(self::$routes, $grouped_router_instance->getRoutes());
            // dd(self::$routes);
        } else {
            die("Router Error: Middleware class {$middleware_name} not found or not callable.");
        }
    }

    private static function mergeRoutes($open_routes, $grouped_routes)
    {
        foreach ($grouped_routes as $method => $url) {
            $open_routes[$method] = array_merge($open_routes[$method], $grouped_routes[$method]);
        }
        return $open_routes;
    }

    private static function getRequestSingletonInstance()
    {
        if (self::$request === NULL) {
            $request = new Request();
            self::$request = $request;
        }
        return self::$request;
    }

    private static function getResponseSingletonInstance()
    {
        if (self::$response === NULL) {
            $response = new Response();
            self::$response = $response;
        }
        return self::$response;
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

            $request = self::getRequestSingletonInstance();

            $route_action = self::$routes[$method][$uri]['action'];
            $route_middleware = self::$routes[$method][$uri]['middleware'];

            if ($route_middleware) {
                $middleware_instance = AppService::middlewares()[$route_middleware];
                $middleware = new $middleware_instance();
                if ($middleware) {
                    $middleware->handle($request, function ($request) use ($route_action) {
                        static::triggerController($route_action);
                    });
                } else {
                    die("Router Error: Middleware class {$route_middleware} not found or not callable.");
                }
            } else {

                static::triggerController($route_action);
            }
        } else {
            self::renderError(['code' => 404, 'message' => "Route not found for [{$method}] {$uri}"]);
        }
    }

    private static function triggerController($route_action)
    {
        $request = self::getRequestSingletonInstance();
        $response = self::getResponseSingletonInstance();

        list($controller_name, $action) = explode('@', $route_action);

        // Load controller
        $controller_namespace = 'App\\Controllers\\' . $controller_name;
        $controller = new $controller_namespace;

        if ($controller && method_exists($controller, $action)) {
            echo call_user_func([$controller, $action], $request, $response);
            exit();
        } else {
            self::renderError(['code' => 500, 'message' => "Controller or method not found: {$controller_name}@{$action}"]);
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
        $view_file = $root_path . '/snake/http/templates/error.php';
        include_once $view_file;
        die();
    }
}
