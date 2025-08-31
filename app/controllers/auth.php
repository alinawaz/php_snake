<?php

require_once __DIR__ . '/../database/db.php';

function login() {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing credentials']);
        exit;
    }

    $username = $input['username'];
    $password = $input['password'];

    $db = new Database();
    $users = $db->query("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
    $result = $users ? $users[0] : null;

    if ($result && password_verify($password, $result['password'])) {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['role'] = $result['role'];
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid username or password!']);
    }
    exit;
}

function signup() {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing credentials']);
        exit;
    }

    $username = $input['username'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);

    $db = new Database();

    // Check if username already exists
    $existing = $db->query("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]);
    if ($existing) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Username already taken']);
        exit;
    }

    $success = $db->create('users', [
        'username' => $username,
        'password' => $password
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => "Signup successful!"]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Signup failed']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action === 'signup') {
        signup();
    } else {
        login();
    }
}