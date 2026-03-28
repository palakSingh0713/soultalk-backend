<?php
session_start();
require_once 'config.php';

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if (!isset($_SESSION['user_email'])) {
        $result = $conn->query("SELECT DISTINCT user_email FROM chat_history LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_email'] = $row['user_email'];
        } else {
            $_SESSION['user_email'] = 'test@example.com';
        }
    }

    $user_email = $_SESSION['user_email'];
    $input = json_decode(file_get_contents('php://input'), true);

    $conversation_id = $input['conversation_id'] ?? null;
    $character_id    = $input['character_id'] ?? null;
    $message         = $input['message'] ?? null;

    if (!$conversation_id || !$character_id || !$message) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    // Check if first message already saved for this conversation
    $stmt = $conn->prepare("
        SELECT id FROM chat_history 
        WHERE conversation_id = ? AND sender = 'bot' 
        LIMIT 1
    ");
    $stmt->bind_param("s", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Only save if no bot message exists yet
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("
            INSERT INTO chat_history 
            (conversation_id, character_id, user_email, message, sender, created_at)
            VALUES (?, ?, ?, ?, 'bot', NOW())
        ");
        $stmt->bind_param("ssss", $conversation_id, $character_id, $user_email, $message);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'saved' => true]);
    } else {
        echo json_encode(['success' => true, 'saved' => false, 'reason' => 'already exists']);
    }

} catch (Exception $e) {
    error_log("Save first message error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save first message']);
}
?>


