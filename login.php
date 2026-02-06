<?php
session_start();
include "db.php";

$error = "";

// 登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['guest_login'])){
        // Guest 登录
        $_SESSION['user_id'] = null;          // Guest 没有 user_id
        $_SESSION['role'] = 'guest';
        $_SESSION['guest_name'] = trim($_POST['guest_name']); // 可选访客名
        header("Location: reserve_form.php");
        exit;
    } else {
        // 正常用户登录
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result && $result->num_rows === 1){
            $user = $result->fetch_assoc();
            if(password_verify($password, $user['password'])){
                session_regenerate_id(true);
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
        
        <!-- 正常用户登录 -->
        <form method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
            </div>
            <button type="submit" class="btn btn-warning w-100 mb-2">Login</button>
        </form>

        <hr>

        <!-- Guest 登录 -->
        <form method="POST">
            <div class="mb-3">
                <label>Your Name (Guest)</label>
                <input type="text" name="guest_name" class="form-control" required>
            </div>
            <button type="submit" name="guest_login" class="btn btn-secondary w-100">Continue as Guest</button>
        </form>

        <p class="mt-3 text-center">No account? <a href="register.php">Register</a></p>
    </div>
</div>
</body>
</html>
