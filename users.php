<?php

include './config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./index.php");
    exit;
}

if(!in_array($_SESSION['role'], ['admin'])){
    header("Location: ./dashboard.php");
    exit;
}

$page = 'users';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';

// ---------- USER: CREATE ACCOUNT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account']) && $role === 'admin') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : $userId;
    $accNum = "ACCT" . rand(10000,99999);
    $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("is", $userId, $accNum);
    $stmt->execute();
    header("Location: /users.php"); exit;
}

include_once './models/UserModel.php';
include_once './models/AccountModel.php';

UserModel::setConnection($conn);
$users = UserModel::select('id,username,role')->where(['role' => 'user'])->get();

AccountModel::setConnection($conn);
foreach($users as &$user){
    $user['accounts'] = AccountModel::where(['user_id' => $user['id']])->get();
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users - <?php echo $app_name; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="dash-body">
    <?php include_once './menu.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1>Users</h1>
            <p>Manage your users here</p>
        </div>

        <!-- Accounts Table -->
        <div class="table-container" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Account</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as &$user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <?php if (empty($user['accounts'])): ?>
                                    <!-- User Account Create Form -->
                                    <form method="post" class="create-form" action="/users.php">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" name="create_account">➕ Create Account</button>
                                    </form>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach($user['accounts'] as $acc): ?>
                                            <li>
                                                <?= htmlspecialchars($acc['account_number']) ?> 
                                                (<?= htmlspecialchars($acc['type']) ?> - <?= htmlspecialchars($acc['status']) ?>)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <!-- User Account Create Form -->
                                    <form method="post" class="create-form" action="/users.php">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" name="create_account">➕ Create Account</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>