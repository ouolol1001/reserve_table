<?php
include "db.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$conn->query("UPDATE reservations SET status='approved' WHERE id=$id");

echo "Approved";
