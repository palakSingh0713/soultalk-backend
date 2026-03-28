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
$email = trim($input['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Valid email required']);
    exit();
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Don't reveal if email exists or not (security)
        echo json_encode(['success' => true, 'message' => 'If email exists, reset link sent']);
        exit();
    }
    $stmt->close();

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Save token
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();
    $stmt->close();

    // Send email via EmailJS
    $reset_link = "https://soultalk-app.netlify.app/reset-password?token=" . $token;
    
    // Return token for EmailJS to send
    echo json_encode([
        'success' => true,
        'message' => 'Reset link sent',
        'email' => $email,
        'reset_link' => $reset_link,
        'token' => $token
    ]);

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to process request']);
}
?>



