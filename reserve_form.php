<?php
session_start();
include "db.php";

$msg = "";

// 判断身份
$customer_id = $_SESSION['user_id'] ?? null;
$guest_name = $_SESSION['guest_name'] ?? ""; 

// 欢迎信息
if($customer_id){
    $welcome_name = $_SESSION['user_name'] ?? "Customer"; 
    $identity = "You are logged in with your account.";
    $is_guest = false;
} else if($guest_name){
    $welcome_name = $guest_name;
    $identity = "You are browsing as a guest.";
    $is_guest = true;
} else {
    $welcome_name = "Guest";
    $identity = "You are browsing as a guest.";
    $is_guest = true;
}

// 获取所有桌子
$tables = $conn->query("SELECT * FROM tables ORDER BY table_number ASC");

// 处理预约提交
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $table_id = intval($_POST['table_id']);
    $date = $_POST['reservation_date'];
    $time = $_POST['reservation_time'];
    $guests = intval($_POST['guest_count']);
    $guest_name = trim($_POST['guest_name'] ?? $guest_name);

    $stmt_check = $conn->prepare("SELECT capacity FROM tables WHERE id=?");
    $stmt_check->bind_param("i",$table_id);
    $stmt_check->execute();
    $cap = $stmt_check->get_result()->fetch_assoc();

    if($guests > $cap['capacity']){
        $msg = "Selected table cannot accommodate that many guests.";
    } else {
        $stmt_exist = $conn->prepare("SELECT * FROM reservations WHERE table_id=? AND reservation_date=? AND reservation_time=? AND status='approved'");
        $stmt_exist->bind_param("iss",$table_id,$date,$time);
        $stmt_exist->execute();
        if($stmt_exist->get_result()->num_rows>0){
            $msg = "This table is already booked for selected date and time.";
        } else {
            $stmt_insert = $conn->prepare("
                INSERT INTO reservations (user_id, guest_name, table_id, reservation_date, reservation_time, guest_count, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $uid = $customer_id ?? null;
            $stmt_insert->bind_param("isissi",$uid,$guest_name,$table_id,$date,$time,$guests);
            $stmt_insert->execute();

            if(!$customer_id){
                $_SESSION['guest_name'] = $guest_name;
            }

            $msg = "Reservation submitted! Waiting for approval.";
        }
    }
}

// 获取当前用户预约
$where_clause = $customer_id ? "r.user_id=$customer_id" : "r.guest_name='$guest_name'";
$res_list = $conn->query("SELECT r.*, t.table_number FROM reservations r JOIN tables t ON r.table_id=t.id WHERE $where_clause ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Little Lemon – Reserve Table</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#fff9f0; font-family: 'Segoe UI', sans-serif; margin:0; padding:0;}
.hero {
    background: url('restaurant-bg.jpg') center/cover no-repeat;
    color: white;
    text-align: center;
    padding: 6rem 1rem 3rem 1rem;
    border-radius: 0 0 30px 30px;
    margin-bottom: 3rem;
    position: relative;
}
.hero h1 { font-size: 2.8rem; font-weight: bold; margin-bottom: 0.5rem; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);}
.hero p { font-size: 1.2rem; max-width: 700px; margin: 0 auto; text-shadow: 1px 1px 4px rgba(0,0,0,0.4); }
.hero .identity { margin-top:0.5rem; font-size:1.1rem; color:#ffd9b3; }
.hero .hero-btns { margin-top:1.5rem; }
.hero .hero-btns a { margin:0 0.5rem; }

/* 餐厅介绍 */
.intro { padding:4rem 1rem; background-color:#fff3e0; border-radius:20px; margin-bottom:3rem; text-align:center; }
.intro h2 { color:#ff6f61; font-weight:bold; margin-bottom:1rem; }
.intro p { font-size:1.1rem; max-width:800px; margin:0 auto; }

/* 表单与表格美化 */
.card { padding:2rem; border-radius:20px; margin-bottom:2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
h2.section-title { color:#ff6f61; text-align:center; margin-top:4rem; margin-bottom:2rem; }
.table-warning { background-color:#ffecd5 !important; }
.btn-warning { background-color:#ff6f61; border-color:#ff6f61; transition: 0.3s; }
.btn-warning:hover { background-color:#ff4a3b; border-color:#ff4a3b; color:white; }
.btn-primary { background-color:#ff8c66; border-color:#ff8c66; }
.btn-primary:hover { background-color:#ff6f61; color:white; }
.table { border-radius:10px; overflow:hidden; }
.table-striped > tbody > tr:nth-of-type(odd) { background-color: #fff2e6; }
.table-hover tbody tr:hover { background-color: #ffe0cc; }
.text-center .btn-lg { min-width: 200px; }
</style>
</head>
<body>

<!-- Hero Section -->
<section class="hero">
    <h1>Hello <?= htmlspecialchars($welcome_name) ?>! 🍋</h1>
    <p><?= $identity ?></p>
    <div class="hero-btns">
        <?php if($is_guest): ?>
            <a href="login.php" class="btn btn-primary">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        <?php else: ?>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        <?php endif; ?>
        <a href="orders.php" class="btn btn-primary">View Orders 🍽️</a>
    </div>
</section>

<!-- 餐厅介绍 -->
<section class="intro container">
    <h2>Welcome to Little Lemon</h2>
    <p>At Little Lemon, we bring the finest Mediterranean flavors to your table. Enjoy a cozy atmosphere, fresh ingredients, and a friendly dining experience. Reserve your table now and taste the difference!</p>
</section>

<div class="container">

<h2 class="section-title">Reserve Your Table</h2>
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
<?php if($is_guest): ?>
<div class="col-md-4">
<label>Your Name (for guest)</label>
<input type="text" name="guest_name" class="form-control" value="<?= htmlspecialchars($guest_name) ?>" required>
</div>
<?php endif; ?>
<div class="col-md-4 align-self-end">
<button type="submit" class="btn btn-warning w-100">Reserve</button>
</div>
</form>
</div>

<h2 class="section-title">My Reservations</h2>
<?php if($res_list->num_rows>0): ?>
<table class="table table-striped table-bordered table-hover">
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
<p class="text-center">You have no reservations yet.</p>
<?php endif; ?>

<!-- 底部 View Orders 按钮 -->
<div class="text-center my-4">
    <a href="order.php" class="btn btn-primary btn-lg">View Orders 🍽️</a>
</div>

</div>
</body>
</html>
