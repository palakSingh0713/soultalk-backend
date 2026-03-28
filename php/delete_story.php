<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['id'])) {
    header("Location: your_story.php");
    exit();
}

$id = (int)$_POST['id'];
$user = $_SESSION['user_email'];

$stmt = $conn->prepare("DELETE FROM stories WHERE id = ? AND user_email = ?");
$stmt->bind_param("is", $id, $user);
$stmt->execute();

header("Location: your_story.php");
exit();



