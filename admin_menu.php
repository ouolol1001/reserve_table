<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){ die("Access denied"); }

$msg = "";

// 添加菜品
if(isset($_POST['add'])){
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $stmt = $conn->prepare("INSERT INTO menu (name, price) VALUES (?, ?)");
    $stmt->bind_param("sd",$name,$price);
    $stmt->execute();
    $msg = "Menu item added";
}

// 删除菜品
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM menu WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $msg = "Menu item deleted";
}

// 更新菜品
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $stmt = $conn->prepare("UPDATE menu SET name=?, price=? WHERE id=?");
    $stmt->bind_param("sdi",$name,$price,$id);
    $stmt->execute();
    $msg = "Menu item updated";
}

// 获取菜单
$menu = $conn->query("SELECT * FROM menu ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Menu – Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#fff9f0;}h2{color:#ff6f61;text-align:center;margin-top:1rem;} .card{padding:1rem;border-radius:15px;}</style>
</head>
<body>
<div class="container mt-4">
<h2>Manage Menu 🍋</h2>
<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

<div class="card mb-4">
<h4>Add New Item</h4>
<form method="POST" class="row g-3">
<div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Item Name" required></div>
<div class="col-md-4"><input type="number" name="price" class="form-control" placeholder="Price" step="0.01" required></div>
<div class="col-md-2"><button type="submit" name="add" class="btn btn-warning w-100">Add</button></div>
</form>
</div>

<h4>Existing Menu</h4>
<table class="table table-striped table-bordered">
<thead class="table-warning"><tr><th>ID</th><th>Name</th><th>Price</th><th>Actions</th></tr></thead>
<tbody>
<?php while($row=$menu->fetch_assoc()): ?>
<tr>
<form method="POST">
<td><?= $row['id'] ?><input type="hidden" name="id" value="<?= $row['id'] ?>"></td>
<td><input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required></td>
<td><input type="number" name="price" value="<?= $row['price'] ?>" step="0.01" class="form-control" required></td>
<td>
<button type="submit" name="update" class="btn btn-success btn-sm">Update</button>
<a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this item?')">Delete</a>
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
