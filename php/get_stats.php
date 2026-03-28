<?php
header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = 'test@example.com';
}

$user_email = $_SESSION['user_email'];

try {
    // Count conversations
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT conversation_id) as total FROM chat_history WHERE user_email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $conversations = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Count messages sent by user
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM chat_history WHERE user_email = ? AND sender = 'user'");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Count unique characters chatted with
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT character_id) as total FROM chat_history WHERE user_email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $characters = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'messages' => $messages,
        'characters' => $characters
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


