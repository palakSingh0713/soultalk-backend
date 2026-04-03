<?php
header("Access-Control-Allow-Origin: https://soultalk-app.netlify.app");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once 'config.php';

$input = file_get_contents("php://input");
$body = json_decode($input, true);

$user_email = $_SESSION['user_email'] ?? $body['user_email'] ?? null;

if (!$user_email) {
    echo json_encode(['success' => false, 'error' => 'No email', 'conversations' => []]);
    exit();
}

$query = "SELECT conversation_id, character_id, MAX(CASE WHEN sender = 'bot' THEN message END) as last_message, MAX(created_at) as last_updated, COUNT(*) as message_count FROM chat_history WHERE user_email = ? GROUP BY conversation_id, character_id ORDER BY last_updated DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'conversations' => $conversations,
    'total' => count($conversations),
    'debug_email' => $user_email
]);
?>