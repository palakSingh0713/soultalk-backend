<?php
session_start();

header("Content-Type: application/json");

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($data["message"] ?? "");

// Simple bot replies
$replies = [
    "hi" => "Hi 💜 I’m here with you.",
    "hello" => "Hello 🌸 Tell me what’s on your mind.",
    "default" => "I’m listening 💜 You can talk freely here."
];

// Intro message
if ($userMessage === "__intro__") {
    echo json_encode([
        "reply" => "Hi 💜 I’m SoulTalk. Your soft safe space 🌸"
    ]);
    exit;
}

// Normal message
$reply = $replies[strtolower($userMessage)] ?? $replies["default"];

echo json_encode([
    "reply" => $reply
]);
exit;



