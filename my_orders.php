<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='customer'){ die("Access denied"); }

$customer_id = $_SESSION['user_id'];

// 获取客户订单
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.order_date, r.reservation_date, r.reservation_time
    FROM orders o
    JOIN reservations r ON o.reservation_id=r.id
    WHERE r.user_id=?
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i",$customer_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Orders – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#fff9f0;}
h2{color:#ff6f61;text-align:center;margin-top:1rem;}
.card{padding:1rem;border-radius:15px;margin-bottom:1rem;}
</style>
</head>
<body>
<div class="container mt-4">
<h2>My Orders 🍋</h2>

<?php if($orders->num_rows>0): ?>
<?php while($order=$orders->fetch_assoc()): ?>
<div class="card">
<h5>Order #<?= $order['order_id'] ?></h5>
<p>Reservation: <?= $order['reservation_date'] ?> <?= $order['reservation_time'] ?></p>
<p>Order Date: <?= $order['order_date'] ?></p>

<table class="table table-striped">
<thead class="table-warning"><tr><th>Item</th><th>Quantity</th><th>Price</th></tr></thead>
<tbody>
<?php
$stmt_items = $conn->prepare("
    SELECT m.name, oi.quantity, m.price 
    FROM order_items oi 
    JOIN menu m ON oi.menu_id=m.id 
    WHERE oi.order_id=?
");
$stmt_items->bind_param("i",$order['order_id']);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
while($item = $res_items->fetch_assoc()):
?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td><?= $item['quantity'] ?></td>
<td>$<?= number_format($item['price'],2) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endwhile; ?>
<?php else: ?>
<p>You have no orders yet.</p>
<?php endif; ?>

<a href="reserve_form.php" class="btn btn-primary">Back to Reservations</a>
</div>
</body>
</html>
