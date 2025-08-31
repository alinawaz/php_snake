<?php
include 'config.php';
session_start();

// Approve request
if (isset($_POST['approve'])) {
    $id = $_POST['card_id'];

    // Generate card details
    $cardNumber = str_pad(mt_rand(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
    $expiryMonth = rand(1, 12);
    $expiryYear = date("Y") + rand(3, 5);
    $cvc = rand(100, 999);

    $stmt = $conn->prepare("UPDATE cards SET card_number=?, expiry_month=?, expiry_year=?, cvc=?, status='approved' WHERE id=?");
    $stmt->bind_param("siiii", $cardNumber, $expiryMonth, $expiryYear, $cvc, $id);
    $stmt->execute();
}

$result = $conn->query("SELECT cards.*, users.username FROM cards JOIN users ON cards.user_id = users.id WHERE cards.status='requested'");
?>

<h2>Pending Card Requests</h2>
<table>
  <tr><th>User</th><th>Action</th></tr>
  <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['username'] ?></td>
      <td>
        <form method="post" action="/accounts/pending_cards.php" style="display:inline;">
          <input type="hidden" name="card_id" value="<?= $row['id'] ?>">
          <button type="submit" name="approve">Approve</button>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
