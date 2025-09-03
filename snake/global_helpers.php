<?php
// Global helper functions for Homebank application
// These functions are avaiable throughout the application

global $__models;
$__models = [];

global $__services;
$__services = [];

global $__request;
global $__response;

use Snake\Http\Request;
use Snake\Http\Response;

function getRequestInstance()
{
    global $__request;
    if ($__request === NULL) {
        $__request = new Request();
    }
    // $__request->refresh();
    return $__request;
}

function getResponseInstance()
{
    global $__response;
    if ($__response === NULL) {
        $__response = new Response();
    }
    return $__response;
}

function url()
{
    $request = getRequestInstance();
    return $request->path();
}

function auth()
{
    $request = getRequestInstance();
    $stdClass = new \StdClass;
    $stdClass->user = $request->user;
    return $stdClass;
}


// handy method to get db instance
function db()
{

    global $db;

    return $db;
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<pre style="background:#282c34;color:#abb2bf;padding:15px;border-radius:8px;font-size:13px;font-family:Consolas,Monaco,monospace;">';

        foreach ($vars as $var) {
            ob_start();
            var_dump($var);
            $dump = ob_get_clean();
            echo htmlspecialchars($dump) . "\n";
        }

        echo '</pre>';
        exit;
    }
}

function attachView($name)
{

    global $root_path;
    $name = str_replace('.', '/', $name);
    $view_file = $root_path . "/app/views/{$name}.php";

    include_once $view_file;
}

/**
 * @method loadConfiguration
 * @param string $configFilename
 * @return array
 */
function loadConfiguration($configFilename)
{
    $configFile = __DIR__ . "/../configs/{$configFilename}.php";
    if (file_exists($configFile)) {
        return include $configFile;
    }
    return [];
}

/**
 * @method loadClass
 * @param string $className
 * @return object|null
 * Loads a class from the using dot notation for directories starting from root directory
 * i.e. 'snake.database.MySQL' will load 'snake/database/Database.php'
 */
function loadClass($className, ...$args)
{
    $path = str_replace('.', '/', $className);
    $classFile = __DIR__ . "/../{$path}.php";
    if (file_exists($classFile)) {
        require_once $classFile;
        $shortClassName = explode('.', $className);
        $shortClassName = $shortClassName[count($shortClassName) - 1];
        if (class_exists($shortClassName)) {
            if ((!empty($args))) {
                return new $shortClassName($args);
            } else {
                return new $shortClassName();
            }
        }
    }
    return null;
}

/**
 * @method loadFile - Loads any PHP file given its relative path from root directory
 * @param string $dot_notation_path
 * @return void
 * Loads a class from the using dot notation for directories starting from root directory
 * i.e. 'snake.database.MySqlTable' will load 'snake/database/mysql_table.php'
 */
function loadFile($dot_notation_path)
{
    $path = str_replace('.', '/', $dot_notation_path);
    $file = __DIR__ . "/../{$path}.php";
    if (file_exists($file)) {
        require_once $file;
    } else {
        die('snake.helper.loadFile: Unable to load file {' . $dot_notation_path . '}');
    }
    return;
}
