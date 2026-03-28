<?php
session_start();
require_once "config.php";

require_login();

$stmt = $conn->prepare("
    SELECT s.*, u.name as creator_name 
    FROM stories s
    LEFT JOIN users u ON s.user_email = u.email
    WHERE s.is_public = 1 
    ORDER BY s.created_at DESC
");
$stmt->execute();
$stories = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - SoulTalk</title>
    <link rel="stylesheet" href="../css/explore.css">
</head>
<body>

<a href="home.php" style="display: inline-block; margin: 20px; color: #d77aff; text-decoration: none;">← Back to Home</a>

<h2 style="text-align: center; color: #e4b4ff; padding: 20px;">Explore SoulTalk 💜</h2>

<div class="card-grid">

<a href="chat.php?bot=perfect_family" class="card-link">
    <div class="card">
        <img src="../assets/family.png" alt="Family">
        <h3>The Perfect Family</h3>
    </div>
</a>

<a href="chat.php?bot=aira_listener" class="card-link">
    <div class="card">
        <img src="../assets/aira.png" alt="Aira">
        <h3>Aira – The Calm Listener</h3>
    </div>
</a>

<a href="chat.php?bot=noah_study" class="card-link">
    <div class="card">
        <img src="../assets/noah.png" alt="Noah">
        <h3>Noah – The Study Buddy</h3>
    </div>
</a>

<a href="chat.php?bot=eli_friend" class="card-link">
    <div class="card">
        <img src="../assets/eli.png" alt="Eli">
        <h3>Eli – The Thoughtful Friend</h3>
    </div>
</a>

<a href="chat.php?bot=paranormal_detective" class="card-link">
    <div class="card">
        <img src="../assets/paranormal.png" alt="Detective">
        <h3>Paranormal Detective</h3>
    </div>
</a>

<a href="chat.php?bot=night_shift" class="card-link">
    <div class="card">
        <img src="../assets/night.png" alt="Night">
        <h3>The Night Shift</h3>
    </div>
</a>

<a href="chat.php?bot=man_knows_too_much" class="card-link">
    <div class="card">
        <img src="../assets/man.png" alt="Man">
        <h3>Man Who Knows Too Much</h3>
    </div>
</a>

<a href="chat.php?bot=future_person" class="card-link">
    <div class="card">
        <img src="../assets/future.png" alt="Future">
        <h3>Someone From the Future</h3>
    </div>
</a>

<a href="chat.php?bot=stranger_train" class="card-link">
    <div class="card">
        <img src="../assets/stranger.png" alt="Stranger">
        <h3>The Stranger on the Train</h3>
    </div>
</a>

<?php while ($row = $stories->fetch_assoc()): ?>
<a href="chat.php?story_id=<?php echo $row['id']; ?>" class="card-link">
    <div class="card">
        <img src="../uploads/<?php echo htmlspecialchars($row['image'] ?: 'default.png'); ?>" alt="Story">
        <h3><?php echo htmlspecialchars($row['character_name']); ?></h3>
        <p><?php echo htmlspecialchars($row['tagline']); ?></p>
    </div>
</a>
<?php endwhile; ?>

</div>

</body>
</html>


