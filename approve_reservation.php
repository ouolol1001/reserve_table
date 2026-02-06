<?php
session_start();
include "db.php";

// 只允许 admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$reservation_id = intval($_GET['id']);

// 更新状态为 approved
$stmt = $conn->prepare("UPDATE reservations SET status='approved' WHERE id=?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();

// 回到 admin dashboard
header("Location: admin_reservations.php");
exit;
