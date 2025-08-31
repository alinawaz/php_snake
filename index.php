<?php

/**
 * Snake php framework
 */

/** Global Root path */
global $root_path;
$root_path = __DIR__;

/** Auto loading */
require __DIR__ . '/vendor/autoload.php';

/** Session */
session_start();

/* Global helper file */
include_once './snake/global_helpers.php';

/** Application bootstrap file */
// include_once './snake/Application.php';

use Snake\Application;

$app = new Application();

$app->boot();