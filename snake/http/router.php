<?php

namespace Snake\Http;

use App\Services\AppService;
use Snake\Http\Route;

class Router
{

    private static $routes = [];

    public static function get($path, $controllerAction)
    {
        self::$routes['GET'][$path] = ['action' => $controllerAction, 'middlewares' => []];
    }

    public static function post($path, $controllerAction)
    {
        self::$routes['POST'][$path] = ['action' => $controllerAction, 'middlewares' => []];
    }

    public static function put($path, $controllerAction)
    {
        self::$routes['PUT'][$path] = ['action' => $controllerAction, 'middlewares' => []];
    }

    public static function delete($path, $controllerAction)
    {
        self::$routes['DELETE'][$path] = ['action' => $controllerAction, 'middlewares' => []];
    }

    public static function patch($path, $controllerAction)
    {
        self::$routes['PATCH'][$path] = ['action' => $controllerAction, 'middlewares' => []];
    }

    public static function middleware($name, $callback)
    {
        if (is_callable($callback)) {
            $grouped_router_instance = new Route([$name]);
            $callback($grouped_router_instance);
            self::$routes = self::mergeRoutes(self::$routes, $grouped_router_instance->getRoutes());
        } else {
            die("Router Error: Middleware class {$name} not found or not callable.");
        }
    }

    private static function mergeRoutes($open_routes, $grouped_routes)
    {
        foreach ($grouped_routes as $method => $url) {
            if(isset($open_routes[$method])) {
                $open_routes[$method] = array_merge($open_routes[$method], $grouped_routes[$method]);
            }else{
                $open_routes[$method] = $grouped_routes[$method];
            }
        }
        return $open_routes;
    }

    public static function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Normalize (remove trailing slash except for root "/")
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        if (!isset(self::$routes[$method])) {
            return self::renderError(['code' => 404, 'message' => "No routes for [{$method}]"]);
        }

        foreach (self::$routes[$method] as $routePattern => $route) {
            // Convert /user/:id/post/:slug → regex
            $regex = preg_replace('#:([\w]+)#', '(?P<$1>[^/]+)', $routePattern);
            $regex = "#^" . $regex . "$#";

            if (preg_match($regex, $uri, $matches)) {
                // Collect params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }

                $route_action = $route['action'];
                $route_middlewares = $route['middlewares'];

                if (count($route_middlewares) > 0) {
                    static::chainMiddlewares($params, $route_middlewares, $route_action);
                } else {
                    // Inject params into request
                    $request = getRequestInstance();
                    $request->addParams($params);
                    static::triggerController($route_action);
                }
                return; // Stop after first match
            }
        }

        self::renderError(['code' => 404, 'message' => "Route not found for [{$method}] {$uri}"]);
    }

    private static function chainMiddlewares($params, $middlewares, $route_action, $index = 0)
    {
        // var_dump($index, ' < ', (count($middlewares)));
        if ($index < count($middlewares)) {
            $middleware = $middlewares[$index];

            $middleware_instance = AppService::middlewares()[$middleware];
            $middleware = new $middleware_instance();
            $request = getRequestInstance();

            $middleware->handle($request, function ($request) use ($route_action, $middlewares, $index, $params) {
                // var_dump($index, ' == ', (count($middlewares)-1));
                if ($index == (count($middlewares) - 1)) {
                    // Inject params into request
                    $request = getRequestInstance();
                    $request->addParams($params);
                    static::triggerController($route_action);
                } else {
                    static::chainMiddlewares($params, $middlewares, $route_action, ++$index);
                }
            });
        }
    }

    private static function triggerController($route_action)
    {
        $request = getRequestInstance();
        $response = getResponseInstance();

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
