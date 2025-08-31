<?php

// Sync all acccount balances based on "charged" transactions
require_once './config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Function to recalculate and update account balance based on charged transactions
function updateBalanceForAccount($conn, $accountId)
{
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

$accounts = $conn->query("SELECT id FROM accounts");
$updated_count = 0;
while ($acc = $accounts->fetch_assoc()) {
    updateBalanceForAccount($conn, $acc['id']);
    $updated_count++;
}
echo "<b> ✓ Updating account balances</b><br>";
echo "⌛ Updated balances for $updated_count accounts.";
echo "<br>";
echo "✓ All done.";

// Applying bank charges by scanning all accounts, cards with transactions
// and applying charges if applicable
// Needs to check last cycle ran from table bank_charge_cycle: id, total_amount, last_run
echo "<br><br>";
echo "<b>✓ Applying bank charges...</b><br>";
$bank_charges = $conn->query("SELECT * FROM bank_charges");
$applied_count = 0;
$total_charge_amount = 0.0;
$low_balance_threshold = 20.0; // Example threshold for low balance charge

// Fetching list of bank_charge_cycle to see last run
$last_cycle = $conn->query("SELECT * FROM bank_charge_cycle ORDER BY last_run DESC LIMIT 1")->fetch_assoc();
if ($last_cycle) {
    echo "➡️ Last bank charge cycle ran on: " . $last_cycle['last_run'] . " with total amount: " . $last_cycle['total_amount'] . "<br>";
} else {
    echo "➡️ No previous bank charge cycle found. This is the first run.<br>";
}
// if last ran cycle is today, skip running again
if ($last_cycle && date('Y-m-d', strtotime($last_cycle['last_run'])) === date('Y-m-d')) {
    echo "✓ Bank charges already applied today. Skipping.<br>";
    exit;
} else {
    echo "➡️ Proceeding with bank charges for today.<br>";
}
// Known charg types: 
// 1. card_transaction: charge % on each transaction made with card i.e. (account => card => transaction of type debit)
// 2. account_mentenance: charge fixed amount daily basis for each account of user, based on date from last cycle
// 3. card_maintenance: charge fixed amount daily basis for each card of user, based on date from last cycle
// 4. low_balance: charge fixed amount if account balance goes below certain threshold ($low_balance_threshold)
// Schema of bank_charges: id, type, percentage_amount (percentage), reason, account_id (comma separated or empty for all), created_at
while ($charge = $bank_charges->fetch_assoc()) {
    $charge_type = $charge['type'];
    $percentage_amount = floatval($charge['percentage_amount']);
    $reason = $charge['reason'];
    $applicable_account_ids = [];
    if ($charge['account_id'] === 'all' || empty($charge['account_id'])) {
        // All accounts applicable
        $acc_res = $conn->query("SELECT id FROM accounts");
        while ($a = $acc_res->fetch_assoc()) {
            $applicable_account_ids[] = $a['id'];
        }
    } else {
        // Specific accounts
        $applicable_account_ids = array_map('intval', explode(',', $charge['account_id']));
    }

    foreach ($applicable_account_ids as $acc_id) {
        // Fetch account details
        $account = $conn->query("SELECT * FROM accounts WHERE id=$acc_id LIMIT 1")->fetch_assoc();
        if (!$account) continue;

        if ($charge_type === 'card_transaction') {
            // Find all cards linked to this account's user
            $cards_res = $conn->query("SELECT * FROM cards WHERE user_id=" . intval($account['user_id']));
            while ($card = $cards_res->fetch_assoc()) {
                // Find all debit transactions made with this card since last cycle
                if ($last_cycle) {
                    $txn_res = $conn->query("SELECT * FROM transactions WHERE account_id=" . intval($account['id']) . " AND type='debit' AND status='charged' AND created_at > '" . $last_cycle['last_run'] . "'");
                } else {
                    // If no last cycle, consider all transactions
                    $txn_res = $conn->query("SELECT * FROM transactions WHERE account_id=" . intval($account['id']) . " AND type='debit' AND status='charged'");
                }
                while ($txn = $txn_res->fetch_assoc()) {
                    // Apply charge on this transaction
                    $charge_amount = ($percentage_amount / 100.0) * floatval($txn['amount']);
                    if ($charge_amount > 0) {
                        // Insert charge transaction
                        $stmt = $conn->prepare("INSERT INTO transactions (account_id, returned_account_id, type, amount, message, status) VALUES (?, ?, 'debit', ?, ?, 'charged')");
                        $message = "Bank charge applied: $reason on transaction ID " . $txn['id'];
                        $stmt->bind_param("iids", $account['id'], $account['id'], $charge_amount, $message);
                        $stmt->execute();
                        $stmt->close();
                        $total_charge_amount += $charge_amount;
                        $applied_count++;
                        echo "Applied charge of $charge_amount on account " . $account['account_number'] . " for transaction ID " . $txn['id'] . "<br>";
                    }
                }
            }
        } elseif ($charge_type === 'account_maintenance') {
            // Charge fixed amount for account maintenance
            $charge_amount = $percentage_amount; // Here percentage_amount is treated as fixed amount
            if ($charge_amount > 0) {
                $stmt = $conn->prepare("INSERT INTO transactions (account_id, returned_account_id, type, amount, message, status) VALUES (?, ?, 'debit', ?, ?, 'charged')");
                $message = "Bank charge applied: $reason for account maintenance.";
                $stmt->bind_param("iids", $account['id'], $account['id'], $charge_amount, $message);
                $stmt->execute();
                $stmt->close();
                $total_charge_amount += $charge_amount;
                $applied_count++;
                echo "Applied account maintenance charge of $charge_amount on account " . $account['account_number'] . "<br>";
            }
        } elseif ($charge_type === 'card_maintenance') {
            // Charge fixed amount for each card linked to this account's user
            $cards_res = $conn->query("SELECT * FROM cards WHERE user_id=" . intval($account['user_id']));
            while ($card = $cards_res->fetch_assoc()) {
                $charge_amount = $percentage_amount; // Here percentage_amount is treated as fixed amount
                if ($charge_amount > 0) {
                    $stmt = $conn->prepare("INSERT INTO transactions (account_id, returned_account_id, type, amount, message, status) VALUES (?, ?, 'debit', ?, ?, 'charged')");
                    $message = "Bank charge applied: $reason for card maintenance (Card ending " . substr($card['card_number'], -4) . ").";
                    $stmt->bind_param("iids", $account['id'], $account['id'], $charge_amount, $message);
                    $stmt->execute();
                    $stmt->close();
                    $total_charge_amount += $charge_amount;
                    $applied_count++;
                    echo "Applied card maintenance charge of $charge_amount on account " . $account['account_number'] . " for card ending " . substr($card['card_number'], -4) . "<br>";
                }
            }
        } elseif ($charge_type === 'low_balance') {
            // Charge if account balance is below threshold
            if (floatval($account['balance']) < $low_balance_threshold) {
                $charge_amount = $percentage_amount; // Here percentage_amount is treated as fixed amount
                if ($charge_amount > 0) {
                    $stmt = $conn->prepare("INSERT INTO transactions (account_id, returned_account_id, type, amount, message, status) VALUES (?, ?, 'debit', ?, ?, 'charged')");
                    $message = "Bank charge applied: $reason for low balance (Balance: " . number_format($account['balance'], 2) . ").";
                    $stmt->bind_param("iids", $account['id'], $account['id'], $charge_amount, $message);
                    $stmt->execute();
                    $stmt->close();
                    $total_charge_amount += $charge_amount;
                    $applied_count++;
                    echo "Applied low balance charge of $charge_amount on account " . $account['account_number'] . " (Balance: " . number_format($account['balance'], 2) . ")<br>";
                }
            }
        }
    }
}

echo "✓ Applied $applied_count bank charges.<br>";

// Create new entry for cycle run
$stmt = $conn->prepare("INSERT INTO bank_charge_cycle (total_amount, last_run) VALUES (?, NOW())");
$stmt->bind_param("i", $total_charge_amount);
$stmt->execute();
$stmt->close();

// Account number for bank admin: ACCT39853
// Transfer all collected charges to this account
$admin_account = $conn->query("SELECT * FROM accounts WHERE account_number='ACCT39853' LIMIT 1")->fetch_assoc();
if ($admin_account && $total_charge_amount > 0) {
    $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, message, status) VALUES (?, 'credit', ?, 'Total bank charges collected for the day.', 'charged')");
    $stmt->bind_param("id", $admin_account['id'], $total_charge_amount);
    $stmt->execute();
    $stmt->close();
    echo "✓ Transferred total collected charges of $total_charge_amount to bank admin account " . $admin_account['account_number'] . "<br>";
}



echo "\n<a href='/dashboard.php'>Back to Dashboard</a>";
exit;
