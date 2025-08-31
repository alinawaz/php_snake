<?php

/**
 * Request handling class for router
 * Homebank - Personal Banking Application php framework
 * (c) 2024 Ali Nawaz - MIT License
 * Request class for handling HTTP requests and extracting data
 * i.e. $request->body-> (all kind of data from request get/put/patch/post/delete & json data from request body, in short it will have all data sent by client in object form)
 * $request->header-> (all headers)
 * $request->method() (GET, POST, PUT, DELETE, PATCH)
 * $request->path() (URI path)
 * $request->$user will contain current logged in user info if session is active
 */

class Request {

    public $body;
    public $header;
    private $method;
    private $path;
    public $user;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->header = getallheaders();
        $this->body = $this->parseBody();
    }

    private function parseBody() {
        $data = [];
        if ($this->method === 'GET') {
            $data = $_GET;
        } elseif (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true) ?? [];
            } else {
                $data = $_POST;
                if (empty($data)) {
                    parse_str(file_get_contents('php://input'), $data);
                }
            }
        }
        return (object)$data; // Convert to object for easier access
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function method() {
        return $this->method;
    }

    public function path() {
        return $this->path;
    }

}