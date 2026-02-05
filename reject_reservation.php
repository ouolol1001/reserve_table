<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){ die("Access denied"); }
if(!isset($_GET['id'])) die("Invalid request");

$id = intval($_GET['id']);
$status = basename($_SERVER['PHP_SELF'], ".php")==='approve_reservation' ? 'approved' : 'rejected';

$stmt = $conn->prepare("UPDATE reservations SET status=? WHERE id=?");
$stmt->bind_param("si",$status,$id);
$stmt->execute();

header("Location: admin_reservations.php");
exit;
