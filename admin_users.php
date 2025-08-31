<?php 
include './config.php';
if (!isset($_SESSION['user_id'])) { header("Location: /"); exit; }
$page = 'users';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';

// Fetching list of users from database
include_once './models/UserModel.php';
include_once './models/AccountModel.php';
$user_model = new UserModel($conn);
$account_model = new AccountModel($conn);

$users = [];
if($role === 'admin'){
    $users = $user_model->select('id,username,role')->where(['role' => 'user'])->get();
    foreach($users as &$user){
        $user['accounts'] = $account_model->where(['user_id' => $user['id']])->count();
    }
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
        <?php include_once '../menu.php'; ?>
        <div class="main-content">
            <div class="header">
                <h1>Users</h1>
                <p>Manage your customers here</p>
            </div>
            <!-- Users Table -->
            <div class="table-container" style="margin-top:18px;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Accounts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
                    <?php else: foreach($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                            <td><?= htmlspecialchars($user['accounts']) ?></td>
                            <td>
                                <form method="POST" action="admin_users.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <button type="submit" name="create_account" class="btn btn-primary btn-sm">Create Account</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
        </div>
    </body>
</html>