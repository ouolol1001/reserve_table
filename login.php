<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows === 1){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            if($user['role'] === 'admin'){
                header("Location: admin_reservations.php");
            } else {
                header("Location: reserve_form.php");
            }
            exit;
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "Account not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login – Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #fff9f0; }
.card { border-radius: 15px; padding: 2rem; }
h2 { color: #ff6f61; text-align: center; margin-bottom: 1.5rem; }
</style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
<div class="card shadow col-md-4">
<h2>Login 🍋</h2>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<form method="POST">
<div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-warning w-100">Login</button>
</form>
<p class="mt-3 text-center">No account? <a href="register.php">Register</a></p>
</div>
</div>
</body>
</html>
