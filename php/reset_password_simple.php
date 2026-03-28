<?php
header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (empty($email) || empty($newPassword)) {
    echo json_encode(['success' => false, 'error' => 'Email and new password are required']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

try {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // ⭐ FIXED: Changed password_hash to password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $success = $stmt->execute([$hashedPassword, $email]);

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update password or user not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>


