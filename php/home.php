<?php
session_start();
if(!isset($_SESSION['user_email'])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SoulTalk - Home</title>
<link rel="stylesheet" href="../css/home.css">
</head>
<body>

<nav class="navbar">
    <h2 class="logo">SoulTalk</h2>
    <ul>
    <!-- <li><a href="explore.php">Explore</a></li> -->
    <a href="your_story.php">Your Story</a></li>
<li><a href="create_story.php">Create Story</a></li>
<li><a href="home.php">Home</a></li>
        <li><a href="chat.php">Chat</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>


<section class="hero">
    <h1>Welcome to SoulTalk 💜</h1>
    <p>Your safe space to speak your feelings without judgement.</p>
    <button onclick="window.location.href='explore.php'"> explore here</button>
</section>

</body>
</html>



