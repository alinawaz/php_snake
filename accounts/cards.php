<?php 
include '../config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
$page = 'accounts';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';
$accId = 0;
if (isset($_GET['a'])){
    $accId = intval($_GET['a']);
}

$accRow = getAccountById($conn, $accId);
$accBalance = getAccountBalance($conn, $accId);

// --- FUNCTIONS ---

// ---------- FETCH ALL USER CARDS ----------
$userCards = [];
$cardStmt = $conn->prepare("SELECT * FROM cards WHERE account_id = ? ORDER BY id DESC");
$cardStmt->bind_param("i", $accId);
$cardStmt->execute();
$cardRes = $cardStmt->get_result();
while ($row = $cardRes->fetch_assoc()) {
    $userCards[] = $row;
}

/** Fetch a single account (with owner username) */
function getAccountById($conn, $accountId) {
    $stmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.id = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/** Get account balance computed from transactions (credit +, debit -) */
function getAccountBalance($conn, $accountId) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END), 0) AS balance
        FROM transactions
        WHERE account_id = ?
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
            <h2 style="margin-top: 2px;">Available Cards — Account <?= htmlspecialchars($accRow['account_number']) ?></h2>
            <p style="margin-bottom:8px;">Owner: <?= htmlspecialchars($accRow['username']) ?> | Balance: $<?= number_format($accBalance,2) ?></p>

            <!-- Card Section (User) -->
            <div class="account-section">
            <?php if (!$userCards): ?>
                <p style="margin-bottom:8px;">No card found for your user (requests appear here after you request).</p>
                <p style="font-size:13px;color:#666;">To request a card, create an account first below and then request a card from that account (cards attach to accounts).</p>
            <?php else: ?>
                <?php 
                    foreach ($userCards as $firstCard):
                ?>
                <div class="credit-card" style="margin-top:12px;">
                    <div class="chip"></div>
                    <div class="card-number" id="card-number"><?= chunk_split($firstCard['card_number'], 4, ' ') ?></div>
                    <div class="card-details">
                        <span id="card-expiry">EXP: <?= sprintf("%02d/%d", $firstCard['expiry_month'], $firstCard['expiry_year']) ?></span>
                        <span id="card-cvc">CVC: <?= $firstCard['cvc'] ?></span>
                        <span id="type"><?= $firstCard['type'] ?></span>
                    </div>
                    <?php if($role == 'user') { ?>
                    <div class="card-holder" id="card-holder"><?= strtoupper($_SESSION['username']) ?></div>
                    <?php } else { ?>
                    <div class="card-holder" id="card-holder"><?= strtoupper($firstCard['status']) ?></div>
                    <?php } ?>
                </div>
                <?php 
                endforeach;
                endif; 
                ?>
            </div>
            <!-- Card Request Section (User) -->

        </div>
    </body>
</html>