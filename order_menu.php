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

$msg = "";

// 当前订单相关
$current_order_id = null;
$current_items = [];

// 如果选了 reservation，先查有没有 order
if (isset($_POST['reservation_id'])) {
    $rid = intval($_POST['reservation_id']);

    $stmt = $conn->prepare("SELECT id FROM orders WHERE reservation_id=?");
    $stmt->bind_param("i", $rid);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        $current_order_id = $order['id'];

        $stmt_items = $conn->prepare("
            SELECT menu_id, quantity 
            FROM order_items 
            WHERE order_id=?
        ");
        $stmt_items->bind_param("i", $current_order_id);
        $stmt_items->execute();
        $res_items = $stmt_items->get_result();

        while ($row = $res_items->fetch_assoc()) {
            $current_items[$row['menu_id']] = $row['quantity'];
        }
    }
}

// 下单处理
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['reservation_id'])){
    $reservation_id = intval($_POST['reservation_id']);
    $items = $_POST['quantity'] ?? [];
    $has_items = false;

    foreach($items as $qty){
        if(intval($qty) > 0){
            $has_items = true;
            break;
        }
    }

    if($has_items){

        // 如果还没有 order，才创建
        if(!$current_order_id){
            $stmt_order = $conn->prepare("
                INSERT INTO orders (reservation_id, order_date) 
                VALUES (?, NOW())
            ");
            $stmt_order->bind_param("i",$reservation_id);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
        } else {
            $order_id = $current_order_id;
            // 先清空旧明细，重新存
            $conn->query("DELETE FROM order_items WHERE order_id=$order_id");
        }

        // 插入订单明细
        foreach($items as $menu_id => $qty){
            $qty = intval($qty);
            if($qty > 0){
                if($qty > 50) $qty = 50;
                $stmt_item = $conn->prepare("
                    INSERT INTO order_items (order_id, menu_id, quantity) 
                    VALUES (?, ?, ?)
                ");
                $stmt_item->bind_param("iii",$order_id,$menu_id,$qty);
                $stmt_item->execute();
            }
        }

        $msg = "Order saved successfully!";
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

<?php if($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<?php if($reservations->num_rows>0): ?>
<form method="POST">

<div class="mb-3">
<label>Select Reservation:</label>
<select name="reservation_id" class="form-select" required onchange="this.form.submit()">
<?php while($res = $reservations->fetch_assoc()): ?>
<option value="<?= $res['id'] ?>"
    <?= (isset($_POST['reservation_id']) && $_POST['reservation_id']==$res['id'])?'selected':'' ?>>
<?= $res['reservation_date'] ?> <?= $res['reservation_time'] ?> | Guests: <?= $res['guest_count'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<h4>Menu</h4>
<table class="table table-striped table-bordered">
<thead class="table-warning">
<tr><th>Item</th><th>Price</th><th>Quantity</th></tr>
</thead>
<tbody>
<?php while($item = $menu->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td>$<?= number_format($item['price'],2) ?></td>
<td>
<input type="number"
       name="quantity[<?= $item['id'] ?>]"
       value="<?= $current_items[$item['id']] ?? 0 ?>"
       min="0" max="50"
       class="form-control" style="width:80px">
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<button type="submit" class="btn btn-warning">Save Order</button>
</form>

<?php else: ?>
<div class="alert alert-info">
You have no approved reservations yet. Please make a reservation first.
</div>
<a href="reserve_form.php" class="btn btn-primary">Make Reservation</a>
<?php endif; ?>

</div>
</body>
</html>
