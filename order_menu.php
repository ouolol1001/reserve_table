<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='customer'){
    die("Access denied");
}

$customer_id = $_SESSION['user_id'];

// 获取客户已批准的预约
$reservations = $conn->query("
    SELECT * FROM reservations 
    WHERE user_id=$customer_id AND status='approved' 
    ORDER BY reservation_date DESC
");

// 获取菜单
$menu = $conn->query("SELECT * FROM menu ORDER BY name ASC");

// 下单处理
$msg = "";
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['reservation_id'])){
    $reservation_id = intval($_POST['reservation_id']);
    $items = $_POST['quantity'] ?? [];
    $has_items = false;

    foreach($items as $menu_id => $qty){
        $qty = intval($qty);
        if($qty>0){
            $has_items = true;
        }
    }

    if($has_items){
        // 创建订单
        $stmt_order = $conn->prepare("INSERT INTO orders (reservation_id, order_date) VALUES (?, NOW())");
        $stmt_order->bind_param("i",$reservation_id);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        // 添加订单明细，数量最大50
        foreach($items as $menu_id => $qty){
            $qty = intval($qty);
            if($qty>0){
                if($qty>50) $qty = 50; // 最大50份
                $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
                $stmt_item->bind_param("iii",$order_id,$menu_id,$qty);
                $stmt_item->execute();
            }
        }
        $msg = "Order placed successfully!";
    } else {
        $msg = "Please select at least one item.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order Menu – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color:#fff9f0; }
h2 { color:#ff6f61; text-align:center; margin-top:1rem; }
.card { padding:1rem; border-radius:15px; margin-bottom:1rem; }
</style>
</head>
<body>
<div class="container mt-4">
<h2>Order Menu 🍋</h2>
<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

<?php if($reservations->num_rows>0): ?>
<form method="POST">
<div class="mb-3">
<label>Select Reservation:</label>
<select name="reservation_id" class="form-select" required>
<?php while($res = $reservations->fetch_assoc()): ?>
<option value="<?= $res['id'] ?>">
<?= $res['reservation_date'] ?> <?= $res['reservation_time'] ?> | Guests: <?= $res['guest_count'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<h4>Menu</h4>
<table class="table table-striped table-bordered">
<thead class="table-warning"><tr><th>Item</th><th>Price</th><th>Quantity</th></tr></thead>
<tbody>
<?php while($item = $menu->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td>$<?= number_format($item['price'],2) ?></td>
<td>
<input type="number" 
       name="quantity[<?= $item['id'] ?>]" 
       value="0" min="0" max="50" 
       class="form-control" style="width:80px">
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<button type="submit" class="btn btn-warning">Place Order</button>
</form>
<?php else: ?>
<div class="alert alert-info">You have no approved reservations yet. Please make a reservation first.</div>
<a href="reserve_form.php" class="btn btn-primary">Make Reservation</a>
<?php endif; ?>
</div>
</body>
</html>
