<?php
header('Content-Type: application/json');
require_once '../config.php';

// Validate required URL params
$app_id = isset($_GET['app_id']) ? $_GET['app_id'] : 0;
$secret = isset($_GET['secret']) ? $_GET['secret'] : '';

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !$app_id || !$secret) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing app_id or secret']);
    exit;
}

// Validate app
$stmt = $conn->prepare("SELECT * FROM apps WHERE `name` = ? AND secret_key = ?");
$stmt->bind_param("is", $app_id, $secret);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$app) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid app credentials']);
    exit;
}

// Get POST params
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$card_token = isset($input['card_token']) ? $input['card_token'] : '';
$card_token = null;
$card = null;
$request = isset($input['request']) ? $input['request'] : '';

if (!$request) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing request']);
    exit;
}

if($card_token && $request !== 'capture') {

    // Resolve card token to card id
    $stmt = $conn->prepare("SELECT * FROM card_tokens WHERE token = ?");
    $stmt->bind_param("s", $card_token);
    $stmt->execute();
    $card_token = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$card_token) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid card token']);
        exit;
    }

    // Resolve card
    $stmt = $conn->prepare("SELECT * FROM cards WHERE id = ?");
    $stmt->bind_param("s", $card_token['card_id']);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$card) {
        http_response_code(404);
        echo json_encode(['error' => 'Card not found']);
        exit;
    }

}

// Handle requests
if ($request === 'get_information') {
    $card_number = $card['card_number'];
    $masked = str_repeat('*', strlen($card_number) - 4) . substr($card_number, -4);

    // Permissions are stored as JSON in the apps table
    $permissions = [];
    if (!empty($app['permissions'])) {
        $permissions = json_decode($app['permissions'], true);
    }

    echo json_encode([
        'card_number'   => $masked,
        'card_type'     => $card['type'],
        'card_status'   => $card['status'],
        'card_permissions' => $permissions
    ]);
    exit;
}

if ($request === 'charge') {
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid amount']);
        exit;
    }

    // Permissions check for card_payments
    $permissions = [];
    if (!empty($app['permissions'])) {
        $permissions = json_decode($app['permissions'], true);
    }
    if (!in_array('card_payments', $permissions)) {
        http_response_code(403);
        echo json_encode(['error' => 'App does not have permission to charge cards']);
        exit;
    }

    // Find the account connected to the app
    $app_account_id = isset($app['account_id']) ? intval($app['account_id']) : 0;
    if (!$app_account_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App is not linked to any account']);
        exit;
    }

    // Check if card is active and not blocked
    if ($card['status'] !== 'approved') {
        http_response_code(403);
        echo json_encode(['error' => 'Card is not active']);
        exit;
    }

    // Create a transaction: debit from card's account, credit to app's account
    $card_account_id = intval($card['account_id']);

    // Debit from card's account
    $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, message, status) VALUES (?, 'debit', ?, 'Charged by business (" . $app['name'] . ")', 'charged')");
    $stmt->bind_param("id", $card_account_id, $amount);
    $stmt->execute();
    $stmt->close();

    // Credit to app's account
    $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, message, status) VALUES (?, 'credit', ?, 'API credit from app (" . $app['name'] . ")', 'charged')");
    $stmt->bind_param("id", $app_account_id, $amount);
    $stmt->execute();
    $stmt->close();

    // Update balances (recalculate from charged transactions)
    function updateBalanceForAccount($conn, $accountId) {
        $stmt = $conn->prepare("SELECT type, amount FROM transactions WHERE account_id = ? AND status = 'charged'");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $res = $stmt->get_result();
        $balance = 0.0;
        while ($row = $res->fetch_assoc()) {
            if ($row['type'] === 'credit') {
                $balance += floatval($row['amount']);
            } else {
                $balance -= floatval($row['amount']);
            }
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->bind_param("di", $balance, $accountId);
        $stmt->execute();
        $stmt->close();
    }

    updateBalanceForAccount($conn, $card_account_id);
    updateBalanceForAccount($conn, $app_account_id);

    echo json_encode(['success' => true, 'amount' => $amount, 'message' => 'Charge successful']);
    exit;
}

if ($request === 'capture') {
    $card_number = isset($input['card_number']) ? preg_replace('/\s+/', '', $input['card_number']) : '';
    $expiry_date = isset($input['expiry_date']) ? $input['expiry_date'] : '';
    $cvc = isset($input['cvc']) ? $input['cvc'] : '';

    // Validate input
    if (!$card_number || !$expiry_date || !$cvc) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing card_number, expiry_date, or cvc']);
        exit;
    }

    // Parse expiry_date (format MM/YY or MM/YYYY)
    if (!preg_match('/^(\d{2})\/(\d{2,4})$/', $expiry_date, $matches)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid expiry_date format']);
        exit;
    }
    $exp_month = intval($matches[1]);
    $exp_year = intval(strlen($matches[2]) === 2 ? '20' . $matches[2] : $matches[2]);

    // Permissions check for card_captures
    $permissions = [];
    if (!empty($app['permissions'])) {
        $permissions = json_decode($app['permissions'], true);
    }
    if (!in_array('card_captures', $permissions)) {
        http_response_code(403);
        echo json_encode(['error' => 'App does not have permission to capture cards']);
        exit;
    }

    // Find card
    $stmt = $conn->prepare("SELECT * FROM cards WHERE card_number = ? AND expiry_month = ? AND expiry_year = ? AND cvc = ?");
    $stmt->bind_param("siss", $card_number, $exp_month, $exp_year, $cvc);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$card) {
        http_response_code(404);
        echo json_encode(['error' => 'Card not found or invalid details']);
        exit;
    }

    // Check if a token already exists for this card and app user
    $stmt = $conn->prepare("SELECT token FROM card_tokens WHERE card_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card['id'], $app['user_id']);
    $stmt->execute();
    $existingToken = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existingToken && !empty($existingToken['token'])) {
        echo json_encode(['success' => true, 'card_token' => $existingToken['token']]);
        exit;
    }

    // Create card token
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO card_tokens (card_id, user_id, token, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $card['id'], $app['user_id'], $token);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'card_token' => $token]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown request']);