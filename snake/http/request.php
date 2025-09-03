<?php
namespace Snake\Http;

use Snake\Http\Validation;

class Request {

    public $body;
    public $header;
    private $method;
    private $path;
    public $user;

    public function __construct() {
        $this->refresh();
    }

    public function refresh() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->header = getallheaders();
        $this->body = $this->parseBody();
    }

    public function validate(array $rules) {
        return (new Validation($this))->validate($rules);
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

    /**
     * Add route params into $this->body
     */
    public function addParams(array $params) {
        $current = (array) $this->body;
        $merged = array_merge($current, $params);
        $this->body = (object) $merged;
    }

}