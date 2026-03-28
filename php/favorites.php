<?php
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

session_start();
require_once 'config.php';

try {
    if (!isset($_SESSION['user_email'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }

    $user_email = $_SESSION['user_email'];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT character_id FROM favorite_characters WHERE user_email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row['character_id'];
        }
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        $stmt->close();
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $character_id = $input['character_id'] ?? null;
        if (!$character_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'character_id required']);
            exit();
        }
        $stmt = $conn->prepare("INSERT IGNORE INTO favorite_characters (user_email, character_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $user_email, $character_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Added to favorites']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add favorite']);
        }
        $stmt->close();
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $character_id = $input['character_id'] ?? null;
        if (!$character_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'character_id required']);
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM favorite_characters WHERE user_email = ? AND character_id = ?");
        $stmt->bind_param("ss", $user_email, $character_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove favorite']);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    error_log("Favorites error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>