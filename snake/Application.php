<?php
// Application bootstrap file
// Homebank - Personal Banking Application php framework
// (c) 2024 Ali Nawaz - MIT License

namespace Snake;

use Snake\Http\Router;
use Snake\Database\MySQL;

use Doctrine\Inflector\InflectorFactory;


class Application
{

    private $database_config;
    private $app_config;

    public function boot()
    {

        $this->loadConfigs();
        $this->setupDatabase();
        $this->packages();
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
        $db = new MySQL($this->database_config);
        if (!$db) {
            die("HomeBank Framework Error: Failed to load database class.");
        }
    }

    public function setupRouting()
    {

        // loading user web routes
        loadFile('app.routes.web');

        // Running routes
        Router::dispatch();
    }

    public function packages()
    {
        global $inflector;
        $inflector = InflectorFactory::create()->build();
    }
}
