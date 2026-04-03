<?php
session_start();
// 🔍 DEBUG - add this temporarily
$raw = file_get_contents('php://input');
error_log("RAW INPUT: " . $raw);
file_put_contents(__DIR__ . '/memory_debug.txt', $raw);
require_once 'config.php';

ini_set('display_errors', 1); error_reporting(E_ALL);

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if (!isset($_SESSION['user_email'])) {
        $_SESSION['user_email'] = 'test@example.com';
    }

   $user_email = $_SESSION['user_email'] 
    ?? $input['user_email'] 
    ?? 'guest@soultalk.app';
    $method = $_SERVER['REQUEST_METHOD'];

    // GET - Retrieve conversation memory
    if ($method === 'GET') {
        $conversation_id = $_GET['conversation_id'] ?? '';

        if ($conversation_id) {
            $stmt = $conn->prepare("
                SELECT memory_summary, key_topics, user_preferences, emotional_tone
                FROM conversation_memory
                WHERE conversation_id = ? AND user_email = ?
            ");
            $stmt->bind_param("ss", $conversation_id, $user_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'memory' => [
                        'summary' => $row['memory_summary'],
                        'keyTopics' => json_decode($row['key_topics'] ?? '[]'),
                        'userPreferences' => json_decode($row['user_preferences'] ?? '{}'),
                        'emotionalTone' => $row['emotional_tone']
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'memory' => null]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'conversation_id required']);
        }
    }

    // POST - Save/Update memory
    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $conversation_id = $input['conversation_id'] ?? '';
        $character_id = $input['character_id'] ?? '';
        $character_id = preg_replace('/[^0-9]/', '', $character_id) ?: '0';
        $memory_summary = $input['memory_summary'] ?? '';
        $key_topics = json_encode($input['key_topics'] ?? []);
        $user_preferences = json_encode($input['user_preferences'] ?? []);
        $emotional_tone = $input['emotional_tone'] ?? 'neutral';

        if (!$conversation_id || !$character_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }

        // Insert or update
        $stmt = $conn->prepare("
            INSERT INTO conversation_memory 
            (conversation_id, character_id, user_email, memory_summary, key_topics, user_preferences, emotional_tone)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            memory_summary = VALUES(memory_summary),
            key_topics = VALUES(key_topics),
            user_preferences = VALUES(user_preferences),
            emotional_tone = VALUES(emotional_tone)
        ");
        
        $stmt->bind_param(
            "sssssss",
            $conversation_id,
            $character_id,
            $user_email,
            $memory_summary,
            $key_topics,
            $user_preferences,
            $emotional_tone
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Memory saved']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save memory']);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    error_log("Memory error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>


