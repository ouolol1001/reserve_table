<?php
$conn = new mysqli("localhost", "root", "1001ouo", "little_lemon");

if ($conn->connect_error) {
    die("DB connection failed");
}
?>
