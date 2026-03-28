<?php
session_start();
require_once 'config.php';

header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// ✅ Auth bypass like other files
if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = 'test@example.com';
}

$user_email = $_SESSION['user_email'];

try {
    // ✅ Fetch ALL user's characters (public + private)
    $stmt = $conn->prepare("
        SELECT * FROM custom_characters 
        WHERE creator_email = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    $characters = [];
    while ($row = $result->fetch_assoc()) {
        $characters[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'tagline' => $row['tagline'],
            'personality' => $row['personality'],
            'firstMessage' => $row['first_message'],
            'image' => $row['image'],
            'isPublic' => (bool)$row['is_public'],
            'isCustom' => true,
        ];
    }

    // ✅ Added success: true
    echo json_encode(['success' => true, 'characters' => $characters]);
    $stmt->close();

} catch (Exception $e) {
    error_log("Get characters error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load characters']);
}
?>


