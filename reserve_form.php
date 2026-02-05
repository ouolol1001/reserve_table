<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='customer'){ die("Access denied"); }

$msg = "";

// 当前客户ID
$customer_id = $_SESSION['user_id'];

// 获取所有桌子
$tables = $conn->query("SELECT * FROM tables ORDER BY table_number ASC");

// 处理预约提交
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $table_id = intval($_POST['table_id']);
    $date = $_POST['reservation_date'];
    $time = $_POST['reservation_time'];
    $guests = intval($_POST['guest_count']);

    // 检查桌子容量
    $stmt_check = $conn->prepare("SELECT capacity FROM tables WHERE id=?");
    $stmt_check->bind_param("i",$table_id);
    $stmt_check->execute();
    $cap = $stmt_check->get_result()->fetch_assoc();

    if($guests > $cap['capacity']){
        $msg = "Selected table cannot accommodate that many guests.";
    } else {
        // 检查桌子是否已经预约
        $stmt_exist = $conn->prepare("SELECT * FROM reservations WHERE table_id=? AND reservation_date=? AND reservation_time=? AND status='approved'");
        $stmt_exist->bind_param("iss",$table_id,$date,$time);
        $stmt_exist->execute();
        if($stmt_exist->get_result()->num_rows>0){
            $msg = "This table is already booked for selected date and time.";
        } else {
            // 插入预约
            $stmt_insert = $conn->prepare("INSERT INTO reservations (user_id, table_id, reservation_date, reservation_time, guest_count, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt_insert->bind_param("iissi",$customer_id,$table_id,$date,$time,$guests);
            $stmt_insert->execute();
            $msg = "Reservation submitted! Waiting for approval.";
        }
    }
}

// 获取当前客户的预约
$res_list = $conn->query("SELECT r.*, t.table_number 
    FROM reservations r 
    JOIN tables t ON r.table_id=t.id 
    WHERE r.user_id=$customer_id 
    ORDER BY r.reservation_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reserve Table – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#fff9f0; }
h2 { color:#ff6f61; text-align:center; margin-top:1rem; }
.card { padding:1rem; border-radius:15px; margin-bottom:1rem; }
</style>
</head>
<body>
<div class="container mt-4">
<h2>Reserve Table 🍋</h2>

<?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>

<div class="card">
<form method="POST" class="row g-3">
<div class="col-md-4">
<label>Table</label>
<select name="table_id" class="form-select" required>
<?php while($table=$tables->fetch_assoc()): ?>
<option value="<?= $table['id'] ?>">Table <?= $table['table_number'] ?> (Seats: <?= $table['capacity'] ?>)</option>
<?php endwhile; ?>
</select>
</div>
<div class="col-md-4">
<label>Date</label>
<input type="date" name="reservation_date" class="form-control" required>
</div>
<div class="col-md-4">
<label>Time</label>
<input type="time" name="reservation_time" class="form-control" required>
</div>
<div class="col-md-4">
<label>Guests</label>
<input type="number" name="guest_count" class="form-control" min="1" required>
</div>
<div class="col-md-4 align-self-end">
<button type="submit" class="btn btn-warning w-100">Reserve</button>
</div>
</form>
</div>

<h4 class="mt-4">My Reservations</h4>
<?php if($res_list->num_rows>0): ?>
<table class="table table-striped table-bordered">
<thead class="table-warning">
<tr>
<th>ID</th><th>Table</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php while($r=$res_list->fetch_assoc()): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= $r['table_number'] ?></td>
<td><?= $r['reservation_date'] ?></td>
<td><?= $r['reservation_time'] ?></td>
<td><?= $r['guest_count'] ?></td>
<td><?= ucfirst($r['status']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>You have no reservations yet.</p>
<?php endif; ?>

<a href="order_menu.php" class="btn btn-primary">Order Food</a>
</div>
</body>
</html>
