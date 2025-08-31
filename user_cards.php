<?php
require_once 'database/db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page = 'cards';
$role   = $_SESSION['role'] ?? 'user';

$db = new Database();

// Handle Create Card
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $card_type = $_POST['card_type'];
    $account_id = intval($_POST['account_id']);

    // Validate card type and account
    if (($card_type === 'Credit Card' || $card_type === 'Debit Card') && $account_id > 0) {
        // Check card limits for this account
        $cards = $db->query("SELECT type FROM cards WHERE account_id = ?", [$account_id]);
        $debit_count = 0;
        $credit_count = 0;
        foreach ($cards as $card) {
            if ($card['type'] === 'Debit Card') $debit_count++;
            if ($card['type'] === 'Credit Card') $credit_count++;
        }
        $can_create = false;
        if ($card_type === 'Debit Card' && $debit_count < 2) $can_create = true;
        if ($card_type === 'Credit Card' && $credit_count < 1) $can_create = true;

        if ($can_create) {
            // Generate a random 16-digit card number
            $card_number = '';
            for ($i = 0; $i < 4; $i++) {
                $card_number .= str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            }
            $db->create('cards', [
                'user_id' => $user_id,
                'account_id' => $account_id,
                'card_number' => $card_number,
                'expiry_month' => rand(1, 12),
                'expiry_year' => date("Y") + rand(3, 5),
                'cvc' => str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT),
                'type' => $card_type == 'Credit Card' ? 'credit' : 'debit',
                'status' => 'requested'
            ]);
            $success = "Card application submitted!";
        } else {
            $error = "Card limit reached for this account.";
        }
    } else {
        $error = "Invalid card type or account.";
    }
}

// Get accounts of user
$accounts = $db->query("SELECT id, account_number FROM accounts WHERE user_id = ?", [$user_id]);

// Get user cards
$cards = $db->query("SELECT * FROM cards WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Cards - Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dash-body">
  <!-- Sidebar -->
  <?php include_once 'menu.php'; ?>

  <div class="main-content">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">My Cards</h2>

    <!-- Create Card Form -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Apply for New Card</h3>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-2 rounded mb-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <input type="hidden" name="create" value="1">
            <div>
                <label class="block text-gray-700">Applying for</label>
                <select name="card_type" class="w-full p-2 border rounded-lg" required>
                    <option value="">-- Select Card Type --</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700">Linked Account</label>
                <select name="account_id" class="w-full p-2 border rounded-lg" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>">Account #<?= htmlspecialchars($acc['account_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Apply for Card</button>
        </form>
    </div>

    <!-- Cards List -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">My Cards</h3>
        <?php if (count($cards) > 0): ?>
        <table class="w-full border-collapse">
            <thead>
                <tr style="background-color: #2563eb; color: #fff;">
                    <th class="p-2 border">Card Number</th>
                    <th class="p-2 border">Type</th>
                    <th class="p-2 border">Status</th>
                    <th class="p-2 border">Linked Account</th>
                    <th class="p-2 border">Applied On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cards as $card): ?>
                <tr>
                    <td class="p-2 border">
                        <?php
                        // Format card number with space after every 4 digits
                        echo htmlspecialchars(trim(chunk_split($card['card_number'], 4, ' ')));
                        ?>
                    </td>
                    <td class="p-2 border"><?= htmlspecialchars($card['type']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($card['status']) ?></td>
                    <td class="p-2 border">
                        <?php
                        foreach($accounts as $acc) {
                            if ($acc['id'] == $card['account_id']) {
                                echo "Account #" . htmlspecialchars($acc['account_number']);
                                break;
                            }
                        }
                        ?>
                    </td>
                    <td class="p-2 border"><?= isset($card['created_at']) ? htmlspecialchars($card['created_at']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-gray-600">No cards applied yet.</p>
        <?php endif; ?>
    </div>
  </div>
</body>
</html>