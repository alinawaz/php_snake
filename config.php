<?php
session_start();

$app_name = 'HomeBank';

$db_host = "localhost";
$db_name   = "homebank";
$db_user = "root";
$db_pass = "1234";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>
