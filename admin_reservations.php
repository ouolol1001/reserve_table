<?php
include "db.php";
include "auth.php";

// 检查角色
check_role('admin');

// 获取预约，包括 guest
$stmt = $conn->prepare("
SELECT r.id, r.reservation_date, r.reservation_time, r.guest_count, r.status, t.table_number,
       COALESCE(u.name, r.guest_name) AS customer_name
FROM reservations r
LEFT JOIN users u ON r.user_id = u.id
JOIN tables t ON r.table_id = t.id
ORDER BY r.id DESC
");
$stmt->execute();
$reservations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #fff9f0; }
h2 { color:#ff6f61; text-align:center;margin-top:1rem; }
.table-container { padding: 2rem; }
</style>
</head>
<body>
<div class="container table-container">
<h2>Reservations 🍋</h2>
<table class="table table-striped table-bordered">
<thead class="table-warning">
<tr>
<th>ID</th>
<th>Customer</th>
<th>Date</th>
<th>Time</th>
<th>Guests</th>
<th>Table</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($row = $reservations->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['customer_name']) ?></td>
<td><?= $row['reservation_date'] ?></td>
<td><?= $row['reservation_time'] ?></td>
<td><?= $row['guest_count'] ?></td>
<td><?= $row['table_number'] ?></td>
<td><?= ucfirst($row['status']) ?></td>
<td>
<?php if($row['status']=='pending'): ?>
<a href="approve_reservation.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
<a href="reject_reservation.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
<?php elseif($row['status']=='approved'): ?>
<span class="text-success">Approved</span>
<?php else: ?>
<span class="text-danger">Rejected</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<a href="admin_tables.php" class="btn btn-primary">Manage Tables</a>
<a href="admin_menu.php" class="btn btn-primary">Manage Menu</a>
<a href="admin_orders.php" class="btn btn-primary">View Orders</a>
</div>
</body>
</html>
