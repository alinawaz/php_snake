<?php include 'config.php';
if ($_SESSION['role'] !== 'admin') { die("Unauthorized"); }

$id = intval($_GET['id']);
$conn->query("UPDATE accounts SET status='approved' WHERE id=$id");

header("Location: accounts.php");
exit;
