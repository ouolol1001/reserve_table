<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$reservation_id = intval($_GET['id']);

$stmt = $conn->prepare("UPDATE reservations SET status='rejected' WHERE id=?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();

header("Location: admin_reservations.php");
exit;
