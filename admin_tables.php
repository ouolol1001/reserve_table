<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){ die("Access denied"); }

$action_msg = "";

// 添加桌子
if(isset($_POST['add'])){
    $table_number = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $stmt = $conn->prepare("INSERT INTO tables (table_number, capacity) VALUES (?, ?)");
    $stmt->bind_param("ii",$table_number,$capacity);
    $stmt->execute();
    $action_msg = "Table added successfully";
}

// 删除桌子
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tables WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $action_msg = "Table deleted successfully";
}

// 修改桌子
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $table_number = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $stmt = $conn->prepare("UPDATE tables SET table_number=?, capacity=? WHERE id=?");
    $stmt->bind_param("iii",$table_number,$capacity,$id);
    $stmt->execute();
    $action_msg = "Table updated successfully";
}

// 获取所有桌子
$tables = $conn->query("SELECT * FROM tables ORDER BY table_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Tables – Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color:#fff9f0; }
h2 { color:#ff6f61; margin-top:1rem; text-align:center; }
.card { padding:1rem; border-radius:15px; }
</style>
</head>
<body>
<div class="container mt-4">
<h2>Manage Tables 🍋</h2>
<?php if($action_msg): ?><div class="alert alert-success"><?= $action_msg ?></div><?php endif; ?>

<div class="card mb-4">
<h4>Add New Table</h4>
<form method="POST" class="row g-3">
<div class="col-md-4">
<input type="number" name="table_number" placeholder="Table Number" class="form-control" required>
</div>
<div class="col-md-4">
<input type="number" name="capacity" placeholder="Capacity" class="form-control" required>
</div>
<div class="col-md-4">
<button type="submit" name="add" class="btn btn-warning w-100">Add Table</button>
</div>
</form>
</div>

<h4>Existing Tables</h4>
<table class="table table-striped table-bordered">
<thead class="table-warning">
<tr>
<th>ID</th><th>Table Number</th><th>Capacity</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($row=$tables->fetch_assoc()): ?>
<tr>
<form method="POST">
<td><?= $row['id'] ?><input type="hidden" name="id" value="<?= $row['id'] ?>"></td>
<td><input type="number" name="table_number" value="<?= $row['table_number'] ?>" class="form-control" required></td>
<td><input type="number" name="capacity" value="<?= $row['capacity'] ?>" class="form-control" required></td>
<td>
<button type="submit" name="update" class="btn btn-success btn-sm">Update</button>
<a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this table?')">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<a href="admin_reservations.php" class="btn btn-primary">Back to Dashboard</a>
</div>
</body>
</html>
