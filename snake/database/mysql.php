<?php
/**
 * MySQL database connection and operations class
 * Homebank - Personal Banking Application php framework
 * (c) 2024 Ali Nawaz - MIT License
 * Uses Mysqli for connection and basic CRUD operations
 */
class MySQL {

    private $conn;

    public function __construct($config) {
        $this->connect($config);
    }

    private function connect($config) {
        $config = $config[0];
        $this->conn = new mysqli(
            $config['host'], 
            $config['username'], 
            $config['password'], 
            $config['dbname']
        );

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset($config['charset'] ?? 'utf8mb4');
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        $objects = [];
        while ($row = $result->fetch_object()) {
            $objects[] = $row;
        }

        return $objects;
    }

    public function close() {
        $this->conn->close();
    }
    
}