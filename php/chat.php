<?php

session_start();

include "config.php";

/* ────────────── AUTH CHECK ────────────── */
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}
 
/* ────────────── BOT SELECTION ────────────── */
$bot = $_GET['bot'] ?? 'default';
$_SESSION['bot'] = $bot;

echo "<script>sessionStorage.removeItem('intro_sent');</script>";

/* ────────────── LOAD STORY CHARACTER DATA ────────────── */
if (isset($_GET['story'])) {

    $storyId = (int)$_GET['story'];

    $stmt = $conn->prepare("
        SELECT character_name, tagline, description, first_message 
        FROM stories 
        WHERE id = ?
    ");

    $stmt->bind_param("i", $storyId);
    $stmt->execute();

    $res = $stmt->get_result();
    $story = $res->fetch_assoc();

    if($story){

        // 🔥 SAVE FULL CHARACTER DATA INTO SESSION
        $_SESSION['character_name'] = $story['character_name'];
        $_SESSION['tagline'] = $story['tagline'];
        $_SESSION['character_description'] = $story['description'];
        $_SESSION['first_message'] = $story['first_message'];
    }
}

/* Username */
$username = $_SESSION['username'] ?? "Friend";

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SoulTalk 💜</title>
<link rel="stylesheet" href="../css/chat.css">
</head>

<body>

<div class="chat-container">

<!-- HEADER -->
<div class="chat-header">
<h2>SoulTalk 💜</h2>
<p class="subtitle">your soft safe space</p>
</div>

<!-- CHAT BOX -->
<div id="chatbox" class="chat-box"></div>

<!-- INPUT AREA -->
<div class="input-area">

<input
type="text"
id="userInput"
placeholder="Type your feelings here…"
autocomplete="off"
>

<button id="sendBtn">➤</button>

</div>

</div>

<script src="../js/chat.js"></script>

</body>
</html>



