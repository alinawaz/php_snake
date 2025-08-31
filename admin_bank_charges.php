<?php
require_once 'database/db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = $_SESSION['user_id'];
$page = 'bank_charges';
$role = $_SESSION['role'] ?? 'user';

// Check if role is admin
if ($role !== 'admin') {
    header("Location: /");
    exit;
}

$db = new Database();

// Handle bank_charge creation
// Form posted data: type textbox, percentage_amount textbox, reason textarea, account_ids textbox (checboxes for all accounts or specific account ids as array)
// DB Schema: id, type (string), percentage_amount, reason, account_id(string comma separated account_ids or "all"), created_at
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_charge'])) {
    $type = $_POST['type'];
    $percentage_amount = floatval($_POST['percentage_amount']);
    $reason = trim($_POST['reason']);
    $account_ids = isset($_POST['account_ids']) ? $_POST['account_ids'] : [];

    if ($type && $percentage_amount > 0 && $reason) {
        // If "all" is selected, set account_ids to "all"
        if (in_array('all', $account_ids)) {
            $account_ids_str = 'all';
        } else {
            // Validate and join account IDs
            $valid_account_ids = [];
            foreach ($account_ids as $acc_id) {
                if (is_numeric($acc_id) && intval($acc_id) > 0) {
                    $valid_account_ids[] = intval($acc_id);
                }
            }
            if (count($valid_account_ids) == 0) {
                $error = "Please select at least one valid account.";
            } else {
                $account_ids_str = implode(',', $valid_account_ids);
            }
        }

        if (!isset($error)) {
            // Insert bank charge record
            $db->create('bank_charges', [
                'type' => $type,
                'percentage_amount' => $percentage_amount,
                'reason' => $reason,
                'account_id' => $account_ids_str,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            $success = "Bank charge created successfully!";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle delete charge
if (isset($_GET['delete_charge']) && is_numeric($_GET['delete_charge'])) {
    $charge_id = intval($_GET['delete_charge']);
    var_dump($charge_id);
    // Delete the charge
    $db->delete('bank_charges', ['id' => $charge_id]);
    header("Location: /admin_bank_charges.php");
    exit;
}

// Get list of bank charges
$charges = $db->query("SELECT * FROM bank_charges ORDER BY created_at DESC");
// Get all accounts for selection
$accounts = $db->query("SELECT id, account_number FROM accounts");

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
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Bank Charges</h2>

    <!-- Create Card Form -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Create New Bank Charge Schedule</h3>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-2 rounded mb-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <input type="hidden" name="create_charge" value="1">
            <div>
                <label class="block text-gray-700">Charge Type</label>
                <input type="text" name="type" class="w-full p-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-gray-700">Percentage Amount (%)</label>
                <input type="number" step="0.01" name="percentage_amount" class="w-full p-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-gray-700">Reason</label>
                <textarea name="reason" class="w-full p-2 border rounded-lg" required></textarea>
            </div>
            <div>
                <label class="block text-gray-700">Accounts</label>
                <select name="account_ids[]" class="w-full p-2 border rounded-lg" multiple required>
                    <option value="all">-- All Accounts --</option>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>">Account #<?= htmlspecialchars($acc['account_number']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500">Hold Ctrl (Cmd on Mac) to select multiple accounts.</p>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Create</button>
        </form>
    </div>

    <!-- Bank Charges List -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Bank Scheduled Charge List <?php echo date('Y'); ?></h3>
        <?php if (count($charges) > 0): ?>
        <table class="w-full border-collapse">
            <thead>
                <tr style="background-color: #2563eb; color: #fff;">
                    <th class="p-2 border">Type</th>
                    <th class="p-2 border">Percentage Amount (%)</th>
                    <th class="p-2 border">Reason</th>
                    <th class="p-2 border">Accounts</th>
                    <th class="p-2 border">Created At</th>
                    <th class="p-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($charges as $card): ?>
                <tr>
                    <td class="p-2 border"><?= htmlspecialchars($card['type']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($card['percentage_amount']) ?>%</td>
                    <td class="p-2 border"><?= htmlspecialchars($card['reason']) ?></td>
                    <td class="p-2 border">
                        <?php
                        if ($card['account_id'] === 'all') {
                            echo "All Accounts";
                        } else {
                            $acc_ids = explode(',', $card['account_id']);
                            $acc_names = [];
                            foreach ($acc_ids as $id) {
                                foreach ($accounts as $acc) {
                                    if ($acc['id'] == $id) {
                                        $acc_names[] = htmlspecialchars($acc['account_number']);
                                        break;
                                    }
                                }
                            }
                            echo implode(', ', $acc_names);
                        }
                        ?>
                    </td>
                    <td class="p-2 border"><?= isset($card['created_at']) ? htmlspecialchars($card['created_at']) : '-' ?></td>
                    <td class="p-2 border">
                        <a href="admin_bank_charges.php?delete_charge=<?= (int)$card['id'] ?>" class="bg-red-600 text-white px-2 py-1 rounded-lg" onclick="return confirm('Are you sure you want to delete this charge?');">Delete</a>
                    </td>
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