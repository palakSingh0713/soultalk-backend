<?php
session_start();
require_once 'config.php';

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    $result = $conn->query("SELECT DISTINCT user_email FROM chat_history LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_email'] = $row['user_email'];
    }
}

$conversation_id = $_GET['conversation_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? 'test@example.com';

if (!$conversation_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing conversation_id']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT message, sender, created_at 
        FROM chat_history 
        WHERE conversation_id = ? AND user_email = ? 
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("ss", $conversation_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['messages' => $messages]);
    $stmt->close();

} catch (Exception $e) {
    error_log("Load conversation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load conversation']);
}
?>


