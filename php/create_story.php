<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

/* ---------- SAVE STORY ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_email = $_SESSION['user_email'];
    $character  = $_POST['character_name'];
    $tagline    = $_POST['tagline'];
    $desc       = $_POST['user_description'];
    $first_message = $_POST['first_message'];

    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $dir = "../uploads/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $image = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image);
    }

    $stmt = $conn->prepare(
        "INSERT INTO stories 
        (user_email, character_name, tagline, description, image, first_message, is_public)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("ssssssi",
        $user_email,
        $character,
        $tagline,
        $desc,
        $image,
        $first_message,
        $is_public
    );

    $stmt->execute();

    header("Location: your_story.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Your Character</title>
<link rel="stylesheet" href="../css/story.css">
</head>

<body class="story-page">

<div class="story-wrapper">

<h1>✨ Create Your Character</h1>
<p class="subtitle">This helps SoulTalk understand *you* better 💜</p>

<form action="create_story.php" method="POST" enctype="multipart/form-data">

<label style="color:#ccc;">
  <input type="checkbox" name="is_public" value="1">
  Share to Explore 🌍
</label>

<label>Character Name</label>
<input type="text" name="character_name" required>

<label>Tagline</label>
<input type="text" name="tagline">

<label>First Message (Character intro)</label>
<textarea name="first_message" placeholder="Hey, I’m Alex. I need your help with a case..." required></textarea>

<label>Describe Yourself</label>
<textarea name="user_description"></textarea>

<label class="upload-box">
📷 Upload Image (optional)
<input type="file" name="image" hidden>
</label>

<button type="submit">💜 Save Character</button>

</form>

</div>
</body>
</html>



