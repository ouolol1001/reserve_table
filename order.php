<?php
include "db.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$reservation_id = $_POST['reservation_id'];
$menu_id = $_POST['menu_id'];
$qty = $_POST['quantity'];

/* 创建订单（如果没有） */
$result = $conn->query("
SELECT id FROM orders WHERE reservation_id = $reservation_id
");

if ($result->num_rows == 0) {
    $conn->query("
    INSERT INTO orders (reservation_id)
    VALUES ($reservation_id)
    ");
    $order_id = $conn->insert_id;
} else {
    $order_id = $result->fetch_assoc()['id'];
}

/* 添加食物 */
$stmt = $conn->prepare("
INSERT INTO order_items (order_id, menu_id, quantity)
VALUES (?,?,?)
");

$stmt->bind_param("iii", $order_id, $menu_id, $qty);
$stmt->execute();

echo "Food ordered successfully";
