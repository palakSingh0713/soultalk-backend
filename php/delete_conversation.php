<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'config.php';

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
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
    
    // Read input
    $input = json_decode(file_get_contents('php://input'), true);
    $conversation_id = $input['conversation_id'] ?? null;

    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing conversation_id']);
        exit();
    }

    // Delete all messages in this conversation
    $stmt = $conn->prepare("
        DELETE FROM chat_history 
        WHERE conversation_id = ? AND user_email = ?
    ");
    $stmt->bind_param("ss", $conversation_id, $user_email);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'message' => 'Conversation deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete conversation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete conversation'
    ]);
}
?>


