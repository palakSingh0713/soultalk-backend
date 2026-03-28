<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: explore.php");
    exit();
}

$id = (int)$_GET['id'];

$res = $conn->query("SELECT * FROM stories WHERE id = $id");
$story = $res->fetch_assoc();

if (!$story) {
    header("Location: explore.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Story – SoulTalk</title>
<link rel="stylesheet" href="../css/story.css">
</head>

<body class="story-page">

<div class="story-wrapper">

<h1>💜 Your Story</h1>

<?php if (!empty($story['image'])): ?>
    <img src="../uploads/<?php echo htmlspecialchars($story['image']); ?>"
         style="width:120px;border-radius:16px;margin-bottom:15px;">
<?php endif; ?>

<h3><?php echo htmlspecialchars($story['character_name']); ?></h3>
<p style="opacity:0.8;"><?php echo htmlspecialchars($story['tagline']); ?></p>

<hr style="margin:20px 0;opacity:0.3;">

<p style="white-space:pre-line;">
<?php echo htmlspecialchars($story['description']); ?>
</p>

<div style="margin-top:30px;text-align:center;">
    <a href="chat.php?story=<?php echo $story['id']; ?>" style="color:#c084fc;">Start Chat 💬</a> |
    <a href="explore.php" style="color:#aaa;">Back</a>
</div>

</div>

</body>
</html>



