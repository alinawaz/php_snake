<?php 
include '../config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
$page = 'accounts';

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';


?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Accounts - <?php echo $app_name; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body class="dash-body">
        <?php include_once '../menu.php'; ?>
        <div class="main-content">
            <div class="header">
            <h1>Accounts</h1>
            <p>Manage your accounts here</p>
            </div>
        </div>
    </body>
</html>