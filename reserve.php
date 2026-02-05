<?php
include "db.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


/* 防止直接访问 */
if (!isset($_POST['date'], $_POST['time'], $_POST['guests'])) {
    die("Please submit reservation form first.");
}

$user_id = 1; // 模拟已登录用户
$date = $_POST['date'];
$time = $_POST['time'];
$guests = (int)$_POST['guests'];

/* 查找可用餐桌 */
$sql = "
SELECT * FROM tables
WHERE capacity >= ?
AND id NOT IN (
    SELECT table_id FROM reservations
    WHERE reservation_date = ?
    AND reservation_time = ?
    AND status = 'approved'
)
ORDER BY capacity ASC
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $guests, $date, $time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No table available for selected time.");
}

$table = $result->fetch_assoc();

/* 插入预约 */
$stmt = $conn->prepare("
INSERT INTO reservations (user_id, table_id, reservation_date, reservation_time, guest_count)
VALUES (?,?,?,?,?)
");

$stmt->bind_param(
    "iissi",
    $user_id,
    $table['id'],
    $date,
    $time,
    $guests
);

$stmt->execute();

echo "Reservation submitted successfully. Waiting for admin approval.";
