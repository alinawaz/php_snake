<?php


// // Handle transaction approval
// if (isset($_GET['approve_txn']) && is_numeric($_GET['approve_txn'])) {
//     $txn_id = intval($_GET['id']);
//     // Update transaction status to 'charged'
//     $stmt = $conn->prepare("UPDATE transactions SET status = 'charged' WHERE id = ? AND status = 'pending'");
//     $stmt->bind_param("i", $txn_id);
//     $stmt->execute();
//     $stmt->close();

//     // Recalculate and update account balance
//     updateBalanceForAccount($conn, $txn_id);

//     header("Location: /dashboard.php"); exit;
// }

// // Handle transaction decline
// if (isset($_GET['decline_txn']) && is_numeric($_GET['decline_txn'])) {
//     $txn_id = intval($_GET['id']);
//     $stmt = $conn->prepare("UPDATE transactions SET status = 'declined' WHERE id = ? AND status = 'pending'");
//     $stmt->bind_param("i", $txn_id);
//     $stmt->execute();
//     $stmt->close();

//     // Fetch transaction to get returned_account_id for crediting back to returned account
//     $stmt = $conn->prepare("SELECT returned_account_id, amount FROM transactions WHERE id = ?");
//     $stmt->bind_param("i", $txn_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $txn = $result->fetch_assoc();
//     $stmt->close();

//     if ($txn && !empty($txn['returned_account_id'])) {

//         // Crediting back to returned account
//         $returned_account_id = intval($txn['returned_account_id']);
//         $amount = floatval($txn['amount']);
//         $stmt = $conn->prepare("INSERT INTO transactions (`account_id`, `type`, `amount`, `message`, `status`) VALUES (" . $returned_account_id . ", 'credit', " . $amount . ", 'Charge reversed as declined by issuer bank.', 'charged')");
//         $stmt->execute();
//         $stmt->close();

//     }

//     header("Location: /dashboard.php"); exit;
// }

// // Get accounts for user (or all if admin)
// if ($role === 'admin') {
//     $accounts = $db->query("SELECT * FROM accounts");
// } else {
//     $accounts = $db->query("SELECT * FROM accounts WHERE user_id = ?", [$user_id]);
// }

// // Get pending transactions for listing
// $pending_txns = $db->query("SELECT t.*, a.account_number, u.username 
//                             FROM transactions t
//                             JOIN accounts a ON t.account_id = a.id
//                             JOIN users u ON a.user_id = u.id
//                             WHERE t.status = 'pending'
//                             ORDER BY t.created_at DESC");

// // Function to recalculate and update account balance based on charged transactions
// function updateBalanceForAccount($conn, $accountId) {
//     $stmt = $conn->prepare("SELECT type, amount FROM transactions WHERE account_id = ? AND status = 'charged'");
//     $stmt->bind_param("i", $accountId);
//     $stmt->execute();
//     $res = $stmt->get_result();
//     $balance = 0.0;
//     while ($row = $res->fetch_assoc()) {
//         if ($row['type'] === 'credit') {
//             $balance += floatval($row['amount']);
//         } else {
//             $balance -= floatval($row['amount']);
//         }
//     }
//     $stmt->close();

//     $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
//     $stmt->bind_param("di", $balance, $accountId);
//     $stmt->execute();
//     $stmt->close();
// }
?>
@layout(admin.layout.header)

<div class="accounts-list">
  <h2>Your Accounts</h2>
  <?php if (empty($accounts)): ?>
    <p>No accounts found.</p>
  <?php else: ?>
    <table class="accounts-table">
      <thead>
        <tr>
          <th>Account #</th>
          <th>Type</th>
          <th>Status</th>
          <th>Balance</th>
          <th>Cards</th>
          <th>Apps</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($accounts as $account): ?>
          <tr>
            <td><?= htmlspecialchars($account->account_number) ?></td>
            <td><?= htmlspecialchars($account->type) ?></td>
            <td><?= htmlspecialchars($account->status) ?></td>
            <td><?= number_format($account->balance, 2) ?></td>
            <td>
              <?php
              if ($account->cards) {
                foreach ($account->cards as $card) {
                  // Format card number with space after every 4 digits
                  $formatted_card = trim(chunk_split($card->card_number, 4, ' '));
                  echo "💳" . htmlspecialchars($formatted_card) . " (" . htmlspecialchars($card->type) . ")<br>";
                }
              } else {
                echo "No cards";
              }
              ?>
            </td>
            <td>
              <?php
              if ($account->apps) {
                foreach ($account->apps as $app) {
                  echo "💻" . htmlspecialchars($app->name);
                  if (!empty($app->type)) {
                    echo " (" . htmlspecialchars($app->type) . ")";
                  }
                  echo "<br>";
                }
              } else {
                echo "No apps";
              }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Pending Transactions for Admin -->
<div class="accounts-list">
  <h2>Pending trx</h2>
  <?php if (empty($pending_txns)): ?>
    <p>No trx found.</p>
  <?php else: ?>
    <table class="accounts-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Acc #</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Desc.</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action(s)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending_txns as $trx): ?>
          <tr>
            <td><?= htmlspecialchars($trx->id) ?></td>
            <td><?= htmlspecialchars($trx->account->user->username) ?></td>
            <td><?= htmlspecialchars($trx->account->account_number) ?></td>
            <td><?= htmlspecialchars($trx->type) ?></td>
            <td><?= number_format($trx->amount, 2) ?></td>
            <td><?= htmlspecialchars($trx->message) ?></td>
            <td><?= htmlspecialchars($trx->created_at) ?></td>
            <td><?= htmlspecialchars($trx->status) ?></td>
            <td>
              <form method="POST" action="/admin/transactions/approve">
                <input type="hidden" name="id" value="<?php echo $trx->id; ?>">
                <button class="approve-btn">Approve</button>
              </form>
              <form method="POST" action="/admin/transactions/decline">
                <input type="hidden" name="id" value="<?php echo $trx->id; ?>">
                <button class="decline-btn">Decline</button>
              </form>
            <td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<!-- End of Pending Transactions for Admin -->

@layout(admin.layout.footer)