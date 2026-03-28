<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $user_email = $_SESSION['user_email'];
    $character  = $_POST['character_name'];
    $tagline    = $_POST['tagline'];
    $desc       = $_POST['user_description'];
    $first_message = $_POST['first_message'];

    // private by default
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $dir = "../uploads/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $image = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image);
    }

    $stmt = $conn->prepare(
        "INSERT INTO stories (user_email, character_name, tagline, description, image, first_message)
         VALUES (?, ?, ?, ?, ?, ?)"
      );
      
      $stmt->bind_param("ssssss",
        $user_email,
        $character,
        $tagline,
        $desc,
        $image,
        $first_message
      );
      

    $stmt->execute();

    header("Location: your_story.php");
    exit();
}



