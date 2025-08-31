<?php

require 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page = 'apps';
$role   = $_SESSION['role'] ?? 'user';

// Handle Create App
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['name']);
    $account_id = intval($_POST['account_id']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (!empty($name) && $account_id > 0) {
        $secret_key = bin2hex(random_bytes(32)); // 64-char secret key
        $permissions_json = json_encode($permissions);

        $stmt = $conn->prepare("INSERT INTO apps (user_id, account_id, name, secret_key, permissions) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $account_id, $name, $secret_key, $permissions_json);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $app_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM apps WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $app_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get accounts for user
// If logged in user is admin, get all accounts (type=user)
// If logged in user is normal user, get only their accounts
// Also get username information with both queries, so dropdown can display account number + username
$accounts = [];
if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id=u.id WHERE u.role='user' ORDER BY a.account_number ASC");
} else {
    $stmt = $conn->prepare("SELECT a.*, u.username FROM accounts a JOIN users u ON a.user_id=u.id WHERE a.user_id=? ORDER BY a.account_number ASC");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $accounts[] = $row;
}
$stmt->close();

// Get user apps
$apps = [];
$stmt = $conn->prepare("SELECT * FROM apps WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $apps[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apps - <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dash-body">
  <!-- Sidebar -->
  <?php include_once 'menu.php'; ?>

  <div class="main-content">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">My Apps</h2>

    <!-- Create App Form -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Create New App</h3>
        <form method="post" class="space-y-4">
            <input type="hidden" name="create" value="1">
            <div>
                <label class="block text-gray-700">App Name</label>
                <input type="text" name="name" class="w-full p-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-gray-700">Select Account</label>
                <select name="account_id" class="w-full p-2 border rounded-lg" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>">Acc#<?= $acc['account_number'] ?> | <?= $acc['username'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700">Permissions</label>
                <div class="space-y-2">
                    <label><input type="checkbox" name="permissions[]" value="card_payments"> Card Payments</label><br>
                    <label><input type="checkbox" name="permissions[]" value="card_captures"> Card Captures</label><br>
                    <label><input type="checkbox" name="permissions[]" value="otp"> Allow OTP</label>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Create App</button>
        </form>
    </div>

    <!-- Apps List -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">My Apps</h3>
        <?php if (count($apps) > 0): ?>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="p-2 border">App Name</th>
                    <th class="p-2 border">Secret Key</th>
                    <th class="p-2 border">Account #</th>
                    <th class="p-2 border">Permissions</th>
                    <th class="p-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($apps as $app): ?>
                <tr>
                    <td class="p-2 border"><?= htmlspecialchars($app['name']) ?></td>
                    <td class="p-2 border text-sm text-gray-600"><?= htmlspecialchars($app['secret_key']) ?></td>
                    <td class="p-2 border">
                        <?php
                            // Find account number from $accounts array
                            $acc_num = '';
                            foreach ($accounts as $acc) {
                                if ($acc['id'] == $app['account_id']) {
                                    $acc_num = $acc['account_number'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($acc_num);
                        ?>
                    </td>
                    <td class="p-2 border">
                        <?php 
                            $perms = json_decode($app['permissions'], true);
                            echo implode(", ", $perms);
                        ?>
                    </td>
                    <td class="p-2 border">
                        <a href="?delete=<?= $app['id'] ?>" class="text-red-600" onclick="return confirm('Delete this app?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-gray-600">No apps created yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
