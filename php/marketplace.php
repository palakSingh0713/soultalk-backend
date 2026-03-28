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

require_once 'db.php';

session_start();

// GET - Fetch marketplace characters with stats
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sortBy = $_GET['sort'] ?? 'recent';
    
    try {
        // Build query based on sort
        $orderBy = 'cc.created_at DESC'; // default: recent
        
        if ($sortBy === 'popular') {
            $orderBy = 'cs.downloads DESC, cs.likes DESC';
        } elseif ($sortBy === 'liked') {
            $orderBy = 'cs.likes DESC';
        }
        
        // Get marketplace characters with stats
        $stmt = $pdo->query("
            SELECT 
                cc.id,
                cc.name,
                cc.tagline,
                cc.personality,
                cc.first_message,
                cc.image,
                cc.creator_email,
                u.name as creator,
                COALESCE(cs.likes, 0) as likes,
                COALESCE(cs.downloads, 0) as downloads,
                COALESCE(cs.times_used, 0) as timesUsed,
                cc.created_at
            FROM custom_characters cc
            LEFT JOIN users u ON cc.creator_id = u.id
            LEFT JOIN character_stats cs ON cc.id = cs.character_id
            WHERE cc.share_in_marketplace = 1
            ORDER BY $orderBy
        ");
        
        $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current user's liked characters (if logged in)
        $likedCharacterIds = [];
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT character_id FROM character_likes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $likedCharacterIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        echo json_encode([
            'success' => true,
            'characters' => $characters,
            'likedCharacterIds' => $likedCharacterIds
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// POST - Handle actions (like, download)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $characterId = $data['character_id'] ?? '';
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // LIKE ACTION
        if ($action === 'like') {
            // Check if already liked
            $stmt = $pdo->prepare("
                SELECT id FROM character_likes 
                WHERE character_id = ? AND user_id = ?
            ");
            $stmt->execute([$characterId, $userId]);
            $existingLike = $stmt->fetch();
            
            if ($existingLike) {
                // Unlike - remove the like
                $stmt = $pdo->prepare("
                    DELETE FROM character_likes 
                    WHERE character_id = ? AND user_id = ?
                ");
                $stmt->execute([$characterId, $userId]);
                
                // Decrease like count
                $stmt = $pdo->prepare("
                    UPDATE character_stats 
                    SET likes = GREATEST(likes - 1, 0) 
                    WHERE character_id = ?
                ");
                $stmt->execute([$characterId]);
                
            } else {
                // Like - add the like
                $stmt = $pdo->prepare("
                    INSERT INTO character_likes (character_id, user_id) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$characterId, $userId]);
                
                // Increase like count (create stats row if doesn't exist)
                $stmt = $pdo->prepare("
                    INSERT INTO character_stats (character_id, likes, downloads, times_used)
                    VALUES (?, 1, 0, 0)
                    ON DUPLICATE KEY UPDATE likes = likes + 1
                ");
                $stmt->execute([$characterId]);
            }
            
            echo json_encode(['success' => true]);
            
        // DOWNLOAD ACTION
        } elseif ($action === 'download') {
            // Check if character exists
            $stmt = $pdo->prepare("SELECT id FROM custom_characters WHERE id = ?");
            $stmt->execute([$characterId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Character not found']);
                exit;
            }
            
            // Check if user already downloaded
            $stmt = $pdo->prepare("
                SELECT id FROM user_characters 
                WHERE user_id = ? AND character_id = ?
            ");
            $stmt->execute([$userId, $characterId]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Already in your collection']);
                exit;
            }
            
            // Add to user's collection
            $stmt = $pdo->prepare("
                INSERT INTO user_characters (user_id, character_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $characterId]);
            
            // Increase download count
            $stmt = $pdo->prepare("
                INSERT INTO character_stats (character_id, likes, downloads, times_used)
                VALUES (?, 0, 1, 0)
                ON DUPLICATE KEY UPDATE downloads = downloads + 1
            ");
            $stmt->execute([$characterId]);
            
            echo json_encode(['success' => true]);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Invalid method
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>


