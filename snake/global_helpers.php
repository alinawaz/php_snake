<?php
// Global helper functions for Homebank application
// These functions are avaiable throughout the application

global $__models;
$__models = [];

global $__services;
$__services = [];


// handy method to fetch any model in singleton way
function model($model_name) {

    global $root_path;
    global $__models;

    foreach($__models as $name => $model) {
        if($name == $model_name) return $model;
    }

    $model_file = $root_path . "/app/models/{$model_name}.php";
    if (file_exists($model_file)) {
        require_once $model_file;
        if (class_exists($model_name)) {
            $model = new $model_name();
            $__models[$model_name] = $model;
            return $model;
        }
    }

    die('Unble to resolve model with name: ' . $model_name);
}

// handy method to fetch any service in singleton way
function service($service_name) {

    global $root_path;
    global $__services;

    foreach($__services as $name => $service) {
        if($name == $service_name) return $service;
    }

    $service_file = $root_path . "/app/services/{$service_name}.php";
    if (file_exists($service_file)) {
        require_once $service_file;
        if (class_exists($service_name)) {
            $service = new $service_name();
            $__services[$service_name] = $service;
            return $service;
        }
    }

    die('Unble to resolve service with name: ' . $service_name);
}

// handy method to get db instance
function db() {

    global $db;

    return $db;
}

function attachView($name) {

    global $root_path;
    $name = str_replace('.', '/', $name);
    $view_file = $root_path . "/app/views/{$name}.php";

    include_once $view_file;
}


/**
 * @method loadModel
 * @param string $modelName
 * @return object|null
 */
function loadModel($modelName)
{
    global $root_path;
    $modelFile = $root_path . "/app/models/{$modelName}.php";
    if (file_exists($modelFile)) {
        require_once $modelFile;
        if (class_exists($modelName)) {
            return new $modelName();
        }
    }
    return null;
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
        $shortClassName = $shortClassName[count($shortClassName)-1];
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
    }else{
        die('snake.helper.loadFile: Unable to load file {' . $dot_notation_path . '}');
    }
    return;
}
