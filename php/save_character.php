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

// ✅ Same auth bypass as other files
if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = 'test@example.com';
}

$user_email = $_SESSION['user_email'];
$input = file_get_contents("php://input");
$character = json_decode($input, true);
 
try {
    $stmt = $conn->prepare("
        INSERT INTO custom_characters 
        (id, creator_email, name, tagline, personality, first_message, is_public, image, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $isPublic = $character['isPublic'] ? 1 : 0;
    
$stmt->bind_param(
    "sssssis",
    $user_email,
    $character['name'],
    $character['tagline'],
    $character['personality'],
    $character['firstMessage'],
    $isPublic,
    $character['image']
);
    
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Save character error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 


