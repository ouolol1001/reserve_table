<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    die("Access denied");
}

// 先获取所有订单 + 顾客 + 预约信息
$stmt = $conn->prepare("
SELECT o.id as order_id, o.order_date, r.reservation_date, r.reservation_time, u.name AS customer_name
FROM orders o
JOIN reservations r ON o.reservation_id = r.id
JOIN users u ON r.user_id = u.id
ORDER BY o.order_date DESC
");
$stmt->execute();
$orders_result = $stmt->get_result();

$all_orders = [];
$order_ids = [];

// 收集 order_id
while($order = $orders_result->fetch_assoc()){
    $all_orders[] = $order;
    $order_ids[] = $order['order_id'];
}

// 获取所有订单明细，一次性查出
$order_items_map = [];
if(count($order_ids) > 0){
    $in = implode(",", $order_ids);
    $items_result = $conn->query("
        SELECT oi.order_id, m.name, oi.quantity, m.price
        FROM order_items oi
        JOIN menu m ON oi.menu_id = m.id
        WHERE oi.order_id IN ($in)
    ");

    while($row = $items_result->fetch_assoc()){
        $order_items_map[$row['order_id']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Orders – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#fff9f0; }
h2 { color:#ff6f61; text-align:center; margin-top:1rem; }
.card { padding:1rem; border-radius:15px; }
</style>
</head>
<body>
<div class="container mt-4">
<h2>All Orders 🍋</h2>

<?php if(count($all_orders) > 0): ?>
    <?php foreach($all_orders as $order): ?>
        <div class="card mb-3">
            <h5>Order #<?= $order['order_id'] ?> - <?= htmlspecialchars($order['customer_name']) ?></h5>
            <p>Reservation: <?= $order['reservation_date'] ?> <?= $order['reservation_time'] ?></p>
            <p>Order Date: <?= $order['order_date'] ?></p>

            <table class="table table-striped">
            <thead class="table-warning">
                <tr><th>Item</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
            <?php 
            $items = $order_items_map[$order['order_id']] ?? [];
            $total = 0;
            foreach($items as $item): 
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'],2) ?></td>
                <td>$<?= number_format($subtotal,2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="3">Total</th>
                <th>$<?= number_format($total,2) ?></th>
            </tr>
            </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-info">No orders yet.</div>
<?php endif; ?>

<a href="admin_reservations.php" class="btn btn-primary">Back to Dashboard</a>
</div>
</body>
</html>
