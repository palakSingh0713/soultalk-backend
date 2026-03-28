<?php
session_start();
require_once 'config.php';

require_login();

$user_email = $_SESSION['user_email'];
$errors = [];
$success = '';

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        
        if (empty($name)) {
            $errors[] = "Name is required.";
        }
        
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, description = ? WHERE email = ?");
            $stmt->bind_param("sss", $name, $description, $user_email);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $success = "Profile updated!";
                $user['name'] = $name;
                $user['description'] = $description;
            } else {
                $errors[] = "Update failed.";
            }
            $stmt->close();
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request.";
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current, $user['password'])) {
            $errors[] = "Current password incorrect.";
        }
        
        if (strlen($new) < 8) {
            $errors[] = "New password must be 8+ characters.";
        }
        
        if ($new !== $confirm) {
            $errors[] = "Passwords don't match.";
        }
        
        if (empty($errors)) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $user_email);
            
            if ($stmt->execute()) {
                $success = "Password changed!";
            } else {
                $errors[] = "Failed to change password.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SoulTalk</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #e4b4ff;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: none;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .btn {
            background: #d77aff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .success {
            background: #4caf50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error {
            background: #f44336;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <a href="home.php" style="color: #d77aff; text-decoration: none;">← Back</a>
    
    <h2 style="color: #e4b4ff; text-align: center;">Your Profile</h2>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <div><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <h3 style="color: #e4b4ff;">Profile Info</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
        </div>
        
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>About</label>
            <textarea name="description"><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" name="update_profile" class="btn">Update</button>
    </form>
    
    <h3 style="color: #e4b4ff; margin-top: 30px;">Change Password</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="8">
        </div>
        
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        
        <button type="submit" name="change_password" class="btn">Change Password</button>
    </form>
</div>

</body>
</html>


