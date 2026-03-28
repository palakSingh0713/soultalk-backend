<?php
header('Access-Control-Allow-Origin: https://soultalk-app.netlify.app');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

session_start();
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$securityQuestion = $input['securityQuestion'] ?? '';
$securityAnswer = $input['securityAnswer'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Name, email and password are required']);
    exit();
}

if (empty($securityQuestion) || empty($securityAnswer)) {
    echo json_encode(['success' => false, 'error' => 'Security question and answer are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit();
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit();
    }
    $stmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Hash security answer
    $hashedAnswer = password_hash(strtolower(trim($securityAnswer)), PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, security_question, security_answer, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $securityQuestion, $hashedAnswer);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Registration error: ' . $e->getMessage()]);
}
?>


