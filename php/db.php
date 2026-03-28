<?php
// Database credentials
$host = 'localhost';
$dbname = 'soultalk_db';
$username = 'root';
$password = ''; // ⭐ Try with empty password first
$port = 3307; // ⭐ Your custom port

try {
    // Try connection with port 3307
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // If port 3307 fails, try default port 3306
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e2) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Database connection failed. Port 3307: ' . $e->getMessage() . ' | Port 3306: ' . $e2->getMessage()
        ]));
    }
}
?>


