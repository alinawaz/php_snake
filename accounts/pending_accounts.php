<?php
if (!isset($pendingAccounts)) {
    $pendingAccountsStmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.id DESC");
    $pendingAccountsStmt->execute();
    $pendingAccounts = $pendingAccountsStmt->get_result();
}
?>
<h2 style="margin-top:22px;">Pending Account Requests</h2>
<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Account Number</th>
        <th>User</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $pendingAccounts->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['account_number']) ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
        <td>
          <a href="/accounts/?account_action=approve&id=<?= (int)$row['id'] ?>" class="approve-btn">Approve</a> | 
          <a href="/accounts/?account_action=decline&id=<?= (int)$row['id'] ?>" class="decline-btn">Decline</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>