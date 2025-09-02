@layout(customer.layout.header)

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

@layout(customer.layout.footer)