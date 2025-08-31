<?php 
include '../config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
$page = 'accounts';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';

// ---------- HELPERS ----------
function generateCardNumber() {
    return str_pad(mt_rand(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
}
function generateExpiry() {
    $month = rand(1, 12);
    $year  = date("Y") + rand(3, 5);
    return [$month, $year];
}
function generateCVC() {
    return str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
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

// ---------- USER: CREATE ACCOUNT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account']) && $role === 'admin') {
    $accNum = "ACCT" . rand(10000,99999);
    $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("is", $userId, $accNum);
    $stmt->execute();
    header("Location: accounts.php"); exit;
}

// ---------- USER: REQUEST CARD ----------
if (isset($_POST['request_card']) && $role === 'user') {
    $account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
    // validate account belongs to user
    $accChk = $conn->prepare("SELECT id, user_id FROM accounts WHERE id = ? LIMIT 1");
    $accChk->bind_param("i", $account_id);
    $accChk->execute();
    $accRes = $accChk->get_result()->fetch_assoc();
    if (!$accRes || (int)$accRes['user_id'] !== $userId) {
        $error = "Invalid account selected.";
    } else {
        // prevent duplicate card request for same account
        $chk = $conn->prepare("SELECT id FROM cards WHERE account_id = ? LIMIT 1");
        $chk->bind_param("i", $account_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "A card is already requested/exists for this account.";
        } else {
            $cardNum = generateCardNumber();
            [$expM, $expY] = generateExpiry();
            $cvc = generateCVC();
            $ins = $conn->prepare("INSERT INTO cards (user_id, account_id, card_number, expiry_month, expiry_year, cvc, status) VALUES (?, ?, ?, ?, ?, ?, 'requested')");
            $ins->bind_param("iisiis", $userId, $account_id, $cardNum, $expM, $expY, $cvc);
            if ($ins->execute()) {
                $message = "Your card request has been submitted. Please wait for admin approval.";
            } else {
                $error = "Failed to request card: " . $conn->error;
            }
        }
    }
}

// ---------- ADMIN: APPROVE / DECLINE CARD ----------
if ($role === 'admin' && isset($_GET['card_action']) && isset($_GET['id'])) {
    $cardId = intval($_GET['id']);
    if ($_GET['card_action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE cards SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $cardId);
        $stmt->execute();
    } elseif ($_GET['card_action'] === 'decline') {
        $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
        $stmt->bind_param("i", $cardId);
        $stmt->execute();
    }
    header("Location: /accounts"); exit;
}

// ---------- ADMIN: APPROVE / DECLINE ACCOUNT ----------
if ($role === 'admin' && isset($_GET['account_action']) && isset($_GET['id'])) {
    $accountId = intval($_GET['id']);
    if ($_GET['account_action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE accounts SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
    } elseif ($_GET['account_action'] === 'decline') {
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
    }
    header("Location: /accounts"); exit;
}

// ---------- FETCH ACCOUNTS (with computed balance) ----------
if ($role === 'admin') {
    $sql = "SELECT a.*, u.username,
            (SELECT COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount ELSE -t.amount END),0) FROM transactions t WHERE t.account_id = a.id) AS balance
            FROM accounts a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.id DESC";
} else {
    $uid = $userId;
    $sql = "SELECT a.*,
            (SELECT COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount ELSE -t.amount END),0) FROM transactions t WHERE t.account_id = a.id) AS balance
            FROM accounts a
            WHERE a.user_id = $uid
            ORDER BY a.id DESC";
}
$result = $conn->query($sql);

// ---------- FETCH PENDING CARDS FOR ADMIN ----------
if ($role === 'admin') {
    $pendingCardsStmt = $conn->prepare("SELECT * FROM cards WHERE status = 'requested' ORDER BY id DESC");
    $pendingCardsStmt->execute();
    $pendingCards = $pendingCardsStmt->get_result();
}

// ---------- FETCH PENDING ACCOUNTS FOR ADMIN ----------
if ($role === 'admin') {
    $pendingAccountsStmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.id DESC");
    $pendingAccountsStmt->execute();
    $pendingAccounts = $pendingAccountsStmt->get_result();
}

// ---------- FETCH ALL USER CARDS ----------
$userCards = [];
if ($role !== 'admin') {
    $cardStmt = $conn->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY id DESC");
    $cardStmt->bind_param("i", $userId);
    $cardStmt->execute();
    $cardRes = $cardStmt->get_result();
    while ($row = $cardRes->fetch_assoc()) {
        $userCards[] = $row;
    }
}

// Optional: user's total balance across their accounts (useful top summary)
if ($role !== 'admin') {
    $totStmt = $conn->prepare("
      SELECT COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount ELSE -t.amount END), 0) AS total
      FROM transactions t
      JOIN accounts a ON a.id = t.account_id
      WHERE a.user_id = ?
    ");
    $totStmt->bind_param("i", $userId);
    $totStmt->execute();
    $totalBalanceRow = $totStmt->get_result()->fetch_assoc();
    $totalBalance = (float) ($totalBalanceRow['total'] ?? 0.0);
}

// ---------- FETCH ALL CARDS FOR ADMIN ----------
$allCards = [];
if ($role === 'admin') {
    $cardStmt = $conn->prepare("SELECT * FROM cards ORDER BY id DESC");
    $cardStmt->execute();
    $cardRes = $cardStmt->get_result();
    while ($row = $cardRes->fetch_assoc()) {
        $allCards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Accounts - Bank App</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dash-body">

  <?php include_once '../menu.php'; ?>

  <div class="main-content">
    <div class="header">
      <h1>Accounts</h1>
      <p>Manage your accounts here</p>
      <?php if ($role !== 'admin'): ?>
        <p style="margin-top:8px;font-weight:bold;">Total balance across all your accounts: $<?= number_format($totalBalance,2) ?></p>
      <?php endif; ?>
    </div>


    <!-- Accounts Table -->
    <div class="table-container" style="margin-top:18px;">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Account Number</th>
            <th>Balance</th>
            <th>Status</th>
            <?php if ($role==='admin') echo "<th>User</th>"; ?>
            <th>Transactions</th>
            <th>Card</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()):
            $balanceVal = isset($row['balance']) ? (float)$row['balance'] : getAccountBalance($conn, $row['id']);
            // Find card for this account
            $cardList = ($role === 'admin') ? $allCards : $userCards;
            $accountCards = [];
            foreach ($cardList as $ucard) {
                if ($ucard['account_id'] == $row['id']) {
                    $accountCards[] = $ucard;
                }
            }
        ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['account_number']) ?></td>
            <td>$<?= number_format($balanceVal,2) ?></td>
            <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
            <?php if ($role==='admin'): ?><td><?= htmlspecialchars($row['username']) ?></td><?php endif; ?>
            <td><a href="../accounts/transactions.php?a=<?= (int)$row['id'] ?>" class="txn-link">View</a></td>
            <td>
              <?php
                $cardCount = count($accountCards);
                if ($cardCount > 0) {
                    echo "<a href='/accounts/cards.php?a=" . $row['id'] . "'><span style='font-size:13px;color:#2563eb;'>$cardCount Card" . ($cardCount > 1 ? "s" : "") . "</span></a>";
                } else {
                    echo "<span style='font-size:13px;color:#666;'>No Card</span>";
                }
              ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Pending Cards Table (Admin) -->
    <?php if ($role === 'admin') include 'pending_cards.php'; ?>

    <!-- Pending Accounts Table (Admin) -->
    <?php if ($role === 'admin') include 'pending_accounts.php'; ?>
    
  </div>
</body>
</html>