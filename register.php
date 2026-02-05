<?php
include "db.php";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($name === "" || $email === "" || $password === "") {
        $error = "All fields are required";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')"
            );
            $stmt->bind_param("sss", $name, $email, $hashed);
            $stmt->execute();
            $success = "Registration successful. You can login now.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register – Little Lemon</title>
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
        <h2>Register 🍋</h2>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <button class="btn btn-warning w-100">Register</button>
        </form>
        <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>
