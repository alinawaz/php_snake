<?php 
include '../config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
$page = 'accounts';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';

// --- FUNCTIONS ---
/** Fetch a single account (with owner username) */
function getAccountById($conn, $accountId) {
    $stmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.id = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/** Get transactions result for an account */
function getTransactionsForAccount($conn, $accountId) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE account_id = ? ORDER BY created_at DESC, id DESC");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    return $stmt->get_result();
}

/** Get account balance computed from transactions (credit +, debit -) */
function getAccountBalance($conn, $accountId) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END), 0) AS balance
        FROM transactions
        WHERE account_id = ? and status = 'charged'
    ");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (float) ($res['balance'] ?? 0.0);
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Accounts - <?php echo $app_name; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body class="dash-body">
        <?php include_once '../menu.php'; ?>
        <div class="main-content">
            <div class="header">
                <a href="/accounts" style="text-decoration:none;color:#203a43;">⤺ Back</a>
            </div>

            <!-- TRANSACTION HISTORY VIEW -->
            <?php
            if (isset($_GET['a'])):
                $accId = intval($_GET['a']);
                $accRow = getAccountById($conn, $accId);
                if (!$accRow) {
                    echo "<p class='error-msg'>Account not found.</p>";
                } elseif ($role !== 'admin' && (int)$accRow['user_id'] !== $userId) {
                    echo "<p class='error-msg'>You are not allowed to view this account's transactions.</p>";
                } else {
                    // get transactions
                    $txns = getTransactionsForAccount($conn, $accId);
                    $accBalance = getAccountBalance($conn, $accId);
            ?>
                <h2 style="margin-top:22px;">Transaction History — Account <?= htmlspecialchars($accRow['account_number']) ?></h2>
                <p style="margin-bottom:8px;">Owner: <?= htmlspecialchars($accRow['username']) ?> | Balance: $<?= number_format($accBalance,2) ?></p>

                <div class="table-container">
                <table>
                    <thead>
                    <tr><th>ID</th><th>Type</th><th>Amount</th><th>Desc.</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while($t = $txns->fetch_assoc()): ?>
                        <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><?= ucfirst(htmlspecialchars($t['type'])) ?></td>
                        <td><?= ($t['type'] === 'credit' ? '+' : '-') . '$' . number_format($t['amount'],2) ?></td>
                        <td><?= htmlspecialchars($t['message']) ?></td>
                        <td><?= htmlspecialchars($t['created_at']) ?></td>
                        <td><?= htmlspecialchars($t['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php if ($role === 'admin'): ?>
                <p style="margin-top:10px;"><a href="/admin_transactions.php" style="text-decoration:none;color:#203a43;">+ Transaction</a></p>
                <?php endif; ?>

            <?php
                } // end permission check
            endif; // view_txn
            ?>

        </div>
    </body>
</html>