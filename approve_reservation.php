<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    die("Access denied");
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("UPDATE reservations SET status='approved' WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

// 重定向回 admin 页面，避免刷新重复提交
header("Location: admin_reservations.php");
exit;
