<?php
if (!isset($pendingCards)) {
    $pendingCardsStmt = $conn->prepare("SELECT * FROM cards WHERE status = 'requested' ORDER BY id DESC");
    $pendingCardsStmt->execute();
    $pendingCards = $pendingCardsStmt->get_result();
}
?>
<h2 style="margin-top:22px;">Pending Card Requests</h2>
<div class="table-container">
  <table>
    <thead>
      <tr><th>Card Number</th><th>Expiry</th><th>CVC</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php while($row = $pendingCards->fetch_assoc()): ?>
      <tr>
        <td><?= chunk_split($row['card_number'], 4, ' ') ?></td>
        <td><?= sprintf("%02d/%d", $row['expiry_month'], $row['expiry_year']) ?></td>
        <td><?= htmlspecialchars($row['cvc']) ?></td>
        <td>
          <a href="/accounts/?card_action=approve&id=<?= (int)$row['id'] ?>" class="approve-btn">Approve</a> | 
          <a href="/accounts/?card_action=decline&id=<?= (int)$row['id'] ?>" class="decline-btn">Decline</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>