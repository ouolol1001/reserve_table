<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "db.php";

$id = $_GET['id'];
$conn->query("UPDATE reservations SET status='rejected' WHERE id=$id");

echo "Rejected";
