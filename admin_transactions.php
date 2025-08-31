<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$role   = $_SESSION['role'] ?? 'user';
$message = "";

// Function to recalculate and update account balance based on charged transactions
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

// Handle transaction creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_txn'])) {
    $search = $_POST['search'];
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note']);

    // Find account by username, account_number or card_number
    $stmt = $conn->prepare("
        SELECT a.id, a.account_number, u.username 
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN cards c ON c.user_id = a.user_id
        WHERE u.username = ? OR a.account_number = ? OR c.card_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    $stmt->close();

    if ($account) {
        // Insert transaction with pending status
        $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, message, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isss", $account['id'], $type, $amount, $note);
        $stmt->execute();
        $stmt->close();
        $message = "Transaction created and pending approval!";
    } else {
        $message = "Account not found!";
    }
}

// Handle transaction approval
if (isset($_GET['approve_txn']) && is_numeric($_GET['approve_txn'])) {
    $txn_id = intval($_GET['approve_txn']);
    // Get transaction details
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $txn_id);
    $stmt->execute();
    $txn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($txn) {
        // Update transaction status to charged
        $stmt = $conn->prepare("UPDATE transactions SET status = 'charged' WHERE id = ?");
        $stmt->bind_param("i", $txn_id);
        $stmt->execute();
        $stmt->close();

        // Recalculate and update account balance
        updateBalanceForAccount($conn, $txn['account_id']);

        $message = "Transaction approved and account balance updated!";
    } else {
        $message = "Transaction not found or already processed.";
    }
}

// Handle transaction decline
if (isset($_GET['decline_txn']) && is_numeric($_GET['decline_txn'])) {
    $txn_id = intval($_GET['decline_txn']);
    $stmt = $conn->prepare("UPDATE transactions SET status = 'declined' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $txn_id);
    $stmt->execute();
    $stmt->close();
    $message = "Transaction declined.";
}

// Get pending transactions
$pendingTxns = [];
$res = $conn->query("SELECT t.*, a.account_number, u.username FROM transactions t JOIN accounts a ON t.account_id = a.id JOIN users u ON a.user_id = u.id WHERE t.status = 'pending' ORDER BY t.id DESC");
while ($row = $res->fetch_assoc()) {
    $pendingTxns[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Accounts - Bank App</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dash-body">

  <!-- Sidebar -->
  <?php include_once 'menu.php'; ?>

  <!-- Main -->
    <div class="main-content">
        <div class="header">
            <h1>Account - Transactions</h1>
        </div>

        <?php if($message): ?>
            <div class="p-3 mb-4 rounded-lg text-white <?= strpos($message, 'approved') !== false ? 'bg-green-500' : 'bg-red-500' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="create_txn" value="1">
            <div>
                <label class="block text-gray-700">Search Account (Username / Account # / Card #)</label>
                <input type="text" name="search" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-gray-700">Transaction Type</label>
                <select name="type" class="w-full p-2 border rounded-lg">
                    <option value="credit">Deposit (Credit)</option>
                    <option value="debit">Withdraw (Debit)</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700">Amount</label>
                <input type="number" step="0.01" name="amount" required class="w-full p-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-gray-700">Message / Note</label>
                <input type="text" name="note" maxlength="255" class="w-full p-2 border rounded-lg" placeholder="Reason for transaction">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                Submit Transaction
            </button>
        </form>

        <!-- Pending Transactions Table -->
        <h2 style="margin-top:30px;">Pending Transactions</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account #</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Message</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pendingTxns as $txn): ?>
                    <tr>
                        <td><?= htmlspecialchars($txn['id']) ?></td>
                        <td><?= htmlspecialchars($txn['account_number']) ?></td>
                        <td><?= htmlspecialchars($txn['username']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($txn['type'])) ?></td>
                        <td>$<?= number_format($txn['amount'],2) ?></td>
                        <td><?= htmlspecialchars($txn['message']) ?></td>
                        <td>
                            <a href="admin_transactions.php?approve_txn=<?= (int)$txn['id'] ?>" class="approve-btn">Approve</a>
                            <a href="admin_transactions.php?decline_txn=<?= (int)$txn['id'] ?>" class="decline-btn" style="margin-left:8px;">Decline</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="margin-top:10px;"><a href="accounts.php" style="text-decoration:none;color:#203a43;">← Back to Accounts</a></p>
    </div>
</body>
</html>
