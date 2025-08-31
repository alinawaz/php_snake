<?php
// Application bootstrap file
// Homebank - Personal Banking Application php framework
// (c) 2024 Ali Nawaz - MIT License

session_start();

// Loading configuration
$database_config = loadConfiguration('database');
$app_config = loadConfiguration('app');

// Loading MySql Database class & making it globally available
global $db;


class Application
{

    private $database_config;
    private $app_config;

    public function boot() {

        $this->loadConfigs();
        $this->setupDatabase();
        $this->setupRouting();

    }

    public function loadConfigs()
    {
        $this->database_config = loadConfiguration('database');
        $this->app_config = loadConfiguration('app');
    }

    public function setupDatabase()
    {
        global $db;
        $db = loadClass('snake.database.MySQL', $this->database_config);
        if (!$db) {
            die("HomeBank Framework Error: Failed to load database class.");
        }
    }

    public function setupRouting() {
        // Loading router class
        loadFile('snake.http.Router');
        // loading user web routes
        loadFile('app.routes.web');

        // Running routes
        Router::dispatch();
    }
}
