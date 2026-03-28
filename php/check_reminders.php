 <!-- <?php
session_start();
include "config.php";
header("Content-Type: application/json");

$user = $_SESSION['user_email'] ?? null;
if (!$user) {
    echo json_encode([]);
    exit();
}

$now = date("Y-m-d H:i:s");

$res = $conn->query(
    "SELECT id, message FROM reminders
     WHERE user_email='$user'
     AND remind_at <= '$now'
     AND triggered=0"
);

$list = [];

while ($row = $res->fetch_assoc()) {
    $list[] = $row;
    $conn->query(
        "UPDATE reminders SET triggered=1 WHERE id={$row['id']}"
    );
}

echo json_encode($list);  -->



