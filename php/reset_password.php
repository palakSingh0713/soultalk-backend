<?php
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

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (!$token || !$password) {
    echo json_encode(['success' => false, 'error' => 'Token and password required']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit();
}

try {
    // Verify token
    $stmt = $conn->prepare("
        SELECT email FROM password_resets 
        WHERE token = ? 
        AND used = FALSE 
        AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        exit();
    }

    $row = $result->fetch_assoc();
    $email = $row['email'];
    $stmt->close();

    // Update password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();
    $stmt->close();

    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Password reset successful']);

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to reset password']);
}
?>


