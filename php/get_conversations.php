<?php
// CORS headers FIRST
header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

session_start();
require_once 'config.php';

try {
    // Auth check - same pattern as ai.php
    if (!isset($_SESSION['user_email'])) {
        $_SESSION['user_email'] = 'test@example.com';
    }

    $user_email = $_SESSION['user_email'];

    // Get conversations
    $query = "
        SELECT 
            conversation_id,
            character_id,
            MAX(CASE WHEN sender = 'bot' THEN message END) as last_message,
            MAX(created_at) as last_updated,
            COUNT(*) as message_count
        FROM chat_history
        WHERE user_email = ? AND conversation_id IS NOT NULL
        GROUP BY conversation_id, character_id
        ORDER BY last_updated DESC
        LIMIT 50
    ";

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
        'total' => count($conversations)
    ]);

} catch (Exception $e) {
    error_log("Get conversations error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


