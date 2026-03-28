<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user_email'];

$res = $conn->query(
    "SELECT * FROM stories 
     WHERE user_email = '$user' 
     ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Stories – SoulTalk</title>
<link rel="stylesheet" href="../css/explore.css">
</head>

<body>

<h2 class="page-title">Your Stories 💜</h2>

<div class="card-grid">

<?php if ($res->num_rows === 0): ?>
  <p style="color:#ccc;">You haven’t created any stories yet.</p>
<?php endif; ?>

<?php while ($row = $res->fetch_assoc()): ?>

<div class="card">

    <a href="view_story.php?id=<?php echo $row['id']; ?>" class="card-link">
        <img src="../uploads/<?php echo $row['image'] ?: 'story.png'; ?>">
        <h3><?php echo htmlspecialchars($row['character_name']); ?></h3>
        <p><?php echo htmlspecialchars($row['tagline']); ?></p>
    </a>

    <!-- DELETE BUTTON -->
    <form action="delete_story.php" method="POST"
          onsubmit="return confirm('Delete this story permanently? ');">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <button type="submit" class="delete-btn">🗑 Delete</button>
    </form>

</div>

<?php endwhile; ?>

</div>

</body>
</html>



