<?php
// ⭐ ADD THESE CORS HEADERS AT THE TOP
header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$answer = $data['answer'] ?? '';

if (empty($email) || empty($answer)) {
    echo json_encode(['success' => false, 'error' => 'Email and answer are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Case-insensitive comparison
        if (password_verify(strtolower(trim($answer)), $user['security_answer'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Incorrect answer'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>


