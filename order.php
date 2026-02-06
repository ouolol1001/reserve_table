<?php
session_start();
include "db.php";

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$msg = "";

// 只在表单提交时处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservation_id = $_POST['reservation_id'] ?? null;
    $menu_id        = $_POST['menu_id'] ?? null;
    $qty            = $_POST['quantity'] ?? null;

    if (!$reservation_id || !$menu_id || !$qty) {
        $msg = "Please select a reservation, menu item, and quantity.";
    } else {

        // 检查订单是否存在
        $stmt_check = $conn->prepare("SELECT id FROM orders WHERE reservation_id = ?");
        $stmt_check->bind_param("i", $reservation_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows == 0) {
            // 创建订单
            $stmt_insert_order = $conn->prepare("INSERT INTO orders (reservation_id) VALUES (?)");
            $stmt_insert_order->bind_param("i", $reservation_id);
            $stmt_insert_order->execute();
            $order_id = $conn->insert_id;
        } else {
            $order_id = $result->fetch_assoc()['id'];
        }

        // 添加食物到 order_items
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
        $stmt_item->bind_param("iii", $order_id, $menu_id, $qty);
        $stmt_item->execute();

        $msg = "Food ordered successfully!";
    }
}

// 获取当前用户的预约
$reservations = $conn->query("SELECT id, reservation_date, reservation_time FROM reservations WHERE user_id = ".$_SESSION['user_id']);

// 获取菜单
$menu_items = $conn->query("SELECT id, name FROM menu");

// 获取客户所有订单及对应菜品
$order_summary = $conn->query("
    SELECT o.id as order_id, r.reservation_date, r.reservation_time, m.name as menu_name, oi.quantity
    FROM orders o
    JOIN reservations r ON o.reservation_id = r.id
    JOIN order_items oi ON oi.order_id = o.id
    JOIN menu m ON m.id = oi.menu_id
    WHERE r.user_id = ".$_SESSION['user_id']."
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order Food – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#fff9f0; font-family:'Segoe UI', sans-serif; }
h2 { color:#ff6f61; text-align:center; margin-top:1rem; margin-bottom:2rem; }
.card { padding:2rem; border-radius:20px; box-shadow:0 8px 20px rgba(0,0,0,0.1); margin-bottom:2rem; }
.table-striped > tbody > tr:nth-of-type(odd) { background-color:#fff2e6; }
.table-hover tbody tr:hover { background-color:#ffe0cc; }
.table-warning { background-color:#ffecd5 !important; }
</style>
</head>
<body>
<div class="container mt-5">
    <h2>Order Food 🍋</h2>

    <?php if($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- 下单表单 -->
    <div class="card">
        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <label>Reservation</label>
                <select name="reservation_id" class="form-select" required>
                    <option value="">Select Reservation</option>
                    <?php while($r = $reservations->fetch_assoc()): ?>
                        <option value="<?= $r['id'] ?>">#<?= $r['id'] ?> – <?= $r['reservation_date'] ?> <?= $r['reservation_time'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label>Menu Item</label>
                <select name="menu_id" class="form-select" required>
                    <option value="">Select Menu Item</option>
                    <?php while($m = $menu_items->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="1" class="form-control" required>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-warning w-100">Place Order</button>
            </div>
        </form>
    </div>

    <!-- 订单列表 -->
    <h2 class="mt-5">My Orders</h2>
    <?php if($order_summary->num_rows > 0): ?>
        <table class="table table-striped table-bordered table-hover">
            <thead class="table-warning">
                <tr>
                    <th>Reservation</th>
                    <th>Menu Item</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while($o = $order_summary->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $o['order_id'] ?> – <?= $o['reservation_date'] ?> <?= $o['reservation_time'] ?></td>
                        <td><?= htmlspecialchars($o['menu_name']) ?></td>
                        <td><?= $o['quantity'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">You have not ordered any food yet.</p>
    <?php endif; ?>

</div>
</body>
</html>
