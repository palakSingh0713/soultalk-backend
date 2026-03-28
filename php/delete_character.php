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
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $user_email = $_SESSION['user_email'];
    
    // Read input
    $input = json_decode(file_get_contents('php://input'), true);
    $character_id = $input['character_id'] ?? null;

    if (!$character_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing character_id']);
        exit();
    }

    // Check if custom_characters table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'custom_characters'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Check if character exists and belongs to user
        $stmt = $conn->prepare("
            SELECT id FROM custom_characters 
            WHERE character_id = ? AND user_email = ?
        ");
        $stmt->bind_param("ss", $character_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            http_response_code(403);
            echo json_encode(['error' => 'You can only delete characters you created']);
            exit();
        }
        $stmt->close();

        // Delete character
        $stmt = $conn->prepare("
            DELETE FROM custom_characters 
            WHERE character_id = ? AND user_email = ?
        ");
        $stmt->bind_param("ss", $character_id, $user_email);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        // Also delete all conversations with this character
        $stmt = $conn->prepare("
            DELETE FROM chat_history 
            WHERE character_id = ? AND user_email = ?
        ");
        $stmt->bind_param("ss", $character_id, $user_email);
        $stmt->execute();
        $messagesDeleted = $stmt->affected_rows;
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Character and conversations deleted successfully',
            'characters_deleted' => $deleted,
            'messages_deleted' => $messagesDeleted
        ]);
    } else {
        // Table doesn't exist - just return success (localStorage only)
        echo json_encode([
            'success' => true,
            'message' => 'Character deleted from local storage'
        ]);
    }

} catch (Exception $e) {
    error_log("Delete character error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete character'
    ]);
}
?>


