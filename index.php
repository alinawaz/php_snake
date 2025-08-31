<?php

/**
 * Snake php framework
 */

/** Global Root path */
global $root_path;
$root_path = __DIR__;

/* Global helper file */
include_once './snake/global_helpers.php';

/** Application bootstrap file */
include_once './snake/Application.php';

$app = new Application();

$app->boot();