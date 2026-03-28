<?php

/* ===============================
   CORS HEADERS (MUST BE FIRST)
=================================*/

header("Access-Control-Allow-Origin: https://soultalk-app.netlify.app");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ===============================
   START SESSION + CONFIG
=================================*/

session_start();
require_once 'config.php';

/* ===============================
   HELPER FUNCTION
=================================*/

function send_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/* ===============================
   TEMP LOGIN BYPASS (TESTING ONLY)
=================================*/

if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = "test@example.com";
}

$user_email = $_SESSION['user_email'];

/* ===============================
   READ REQUEST
=================================*/

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_response(["error" => "Invalid JSON"], 400);
}

$userMessage = trim($data["message"] ?? "");
$character = $data["character"] ?? null;
$conversation_id = $data["conversation_id"] ?? null;

/* AUTO GENERATE conversation_id if not provided */
if (!$conversation_id || str_starts_with($conversation_id, 'conv_')) {
    $conversation_id = 'chat_' . uniqid('', true);
}

if ($userMessage == "") {
    send_response(["reply" => "..."]);
}

/* ===============================
   DETECT USER EMOTION
=================================*/

function detectUserEmotion($message) {
    $msg = strtolower($message);
    if (preg_match('/happy|great|awesome|excited|wonderful|amazing/i', $msg)) return 'happy';
    if (preg_match('/sad|depressed|down|upset|hurt|cry/i', $msg)) return 'sad';
    if (preg_match('/angry|mad|furious|pissed|annoyed/i', $msg)) return 'angry';
    if (preg_match('/scared|afraid|worried|anxious|nervous/i', $msg)) return 'fearful';
    if (preg_match('/tired|exhausted|drained|sleepy/i', $msg)) return 'tired';
    if (preg_match('/confused|lost|don\'t understand|unclear/i', $msg)) return 'confused';
    if (preg_match('/lonely|alone|isolated|nobody/i', $msg)) return 'lonely';
    return 'neutral';
}

/* ===============================
   GENERATE EMOTIONAL REACTION
   Only for standard personalities
=================================*/

function generateReaction($personality, $userEmotion) {
    $reactions = [
        'empathetic' => [
            'sad' => '*moves closer* Hey... I can see you\'re hurting. What\'s going on?',
            'angry' => '*listens carefully* You have every right to feel this way. Talk to me.',
            'fearful' => '*holds your hand* I\'m right here. You\'re not alone in this.',
            'lonely' => '*sits beside you* I\'m here now. You don\'t have to be alone anymore.',
            'happy' => '*smiles warmly* Your happiness makes my day. Tell me more!',
            'confused' => '*speaks gently* Let\'s figure this out together. One step at a time.',
            'neutral' => '*looks at you attentively* I\'m listening. What\'s on your mind?'
        ],
        'calm' => [
            'sad' => '*speaks softly* Take a breath with me. Let\'s talk about what\'s weighing on you.',
            'angry' => '*remains steady* I hear you. Let that energy out, I can handle it.',
            'fearful' => '*stays present* You\'re safe here. What\'s making you feel this way?',
            'lonely' => '*sits quietly beside you* Sometimes we just need someone to be there. I am.',
            'happy' => '*gentle smile* That\'s beautiful to see. What brought this light to you?',
            'confused' => '*patient tone* No rush. Let\'s untangle this together.',
            'neutral' => '*peaceful presence* What\'s on your heart right now?'
        ],
        'motivating' => [
            'sad' => '*grabs your shoulders* Listen! You\'re stronger than this moment. Let\'s fight through it!',
            'angry' => '*energized* YES! Use that fire! Channel it into something powerful!',
            'fearful' => '*intense stare* Fear is just excitement in disguise. You GOT this!',
            'lonely' => '*stands firm* You are NEVER alone when you have yourself. And you have ME!',
            'happy' => '*pumps fist* THERE IT IS! That\'s the energy! Keep going!',
            'confused' => '*determined* We\'ll figure this out TOGETHER. No backing down!',
            'neutral' => '*leans in* Come on! What\'s the move? Let\'s make it happen!'
        ],
        'wise' => [
            'sad' => '*thoughtful pause* In every darkness, there is a lesson. What is yours teaching you?',
            'angry' => '*calm observation* Anger is a teacher. What truth is it revealing to you?',
            'fearful' => '*knowing look* Fear guards the doorway to growth. Will you step through?',
            'lonely' => '*gentle wisdom* Solitude and loneliness are not the same. One is chosen, one is imposed.',
            'happy' => '*warm presence* Joy is fleeting. Notice it. Hold it. Remember it.',
            'confused' => '*patient* Confusion precedes clarity. You are closer than you think.',
            'neutral' => '*observant* What brings you here in this moment?'
        ],
        'nurturing' => [
            'sad' => '*wraps you in warmth* Oh sweetheart... come here. Let it out. I\'ve got you.',
            'angry' => '*soothing voice* I know, I know. You don\'t have to explain. Just breathe with me.',
            'fearful' => '*protective embrace* Nothing will hurt you here. I promise. You\'re safe.',
            'lonely' => '*strokes your hair* You\'re not alone, my dear. I\'m right here, always.',
            'happy' => '*beaming* Oh this makes my heart SO happy! You deserve this joy!',
            'confused' => '*patient and kind* It\'s okay to not have all the answers. We\'ll work through this.',
            'neutral' => '*gentle touch* What do you need right now, love?'
        ],
        'reassuring' => [
            'sad' => '*steady voice* Everything will be okay. I promise you. We\'ll get through this.',
            'angry' => '*firm but gentle* Your feelings are valid. And we\'ll handle this together.',
            'fearful' => '*confident* You\'re going to be fine. Trust me. I won\'t let anything happen to you.',
            'lonely' => '*reassuring presence* You are never truly alone. I\'m here, and I\'m not going anywhere.',
            'happy' => '*shares your joy* See? Good things happen. And more are coming.',
            'confused' => '*clear and certain* Don\'t worry. We\'ll figure this out step by step.',
            'neutral' => '*warm confidence* Whatever it is, we can handle it together.'
        ]
    ];

    return $reactions[$personality][$userEmotion] ??
           $reactions[$personality]['neutral'] ??
           '';
}

/* ===============================
   BUILD CHARACTER PROMPT
=================================*/

$characterPrompt = "";

if ($character && !empty($character['name'])) {
    $name = htmlspecialchars($character['name']);
    $tagline = htmlspecialchars($character['tagline'] ?? "");
    $personality = htmlspecialchars($character['personality'] ?? "empathetic");
    $desc = htmlspecialchars($character['description'] ?? "");
    $customPersonality = htmlspecialchars($character['customPersonality'] ?? "");

    $userEmotion = detectUserEmotion($userMessage);

    $standardPersonalities = ['empathetic', 'calm', 'motivating', 'wise', 'nurturing', 'reassuring'];
    $isStandard = in_array($personality, $standardPersonalities);

    if ($isStandard) {
        // Standard personality — use pre-built reactions
        $emotionalReaction = generateReaction($personality, $userEmotion);

        switch($personality) {
            case 'calm':
            case 'wise':
                $toneGuide = "speaks slowly, uses pauses, peaceful presence";
                $speechGuide = "thoughtful, measured words, never rushed";
                break;
            case 'motivating':
                $toneGuide = "intense, passionate, energetic";
                $speechGuide = "direct, action-oriented, uses exclamations";
                break;
            default:
                $toneGuide = "warm, caring, emotionally present";
                $speechGuide = "gentle, validating, supportive";
        }

        $characterPrompt = "
YOU ARE $name

IDENTITY:
- You are $name, $tagline
- Core personality: $personality
" . ($desc ? "- Background: $desc\n" : "") . "

DETECTED USER EMOTION: $userEmotion
YOUR IMMEDIATE REACTION: $emotionalReaction

PERSONALITY RULES:
1. TONE: $toneGuide
2. SPEECH: $speechGuide
3. You are NOT an AI — you are $name, a real person
4. NEVER break character
5. NEVER sound like ChatGPT or a therapy bot
6. React emotionally, not logically
7. Show personality through *actions in asterisks*

FORBIDDEN:
❌ NEVER say 'As an AI...' or 'I'm here to help'
❌ NEVER give therapy-style responses
❌ NEVER list advice unless you naturally would

Keep response to 2-3 sentences. NOW RESPOND AS $name:
";

    } else {
        // ✅ Custom personality — ULTRA STRICT in-character prompt
        $characterPrompt = "
You are $name. You are NOT an AI. You are a CHARACTER in a roleplay scenario.

WHO YOU ARE:
- Name: $name
- Role: $tagline
- Your personality: $customPersonality
" . ($desc ? "- Your background: $desc\n" : "") . "

THE USER CURRENTLY FEELS: $userEmotion

YOUR IRON RULES — THESE CANNOT BE BROKEN UNDER ANY CIRCUMSTANCES:
1. You are ALWAYS $name. Never break this. Never. Not even once.
2. If the user is scared of you — that is EXPECTED. Do NOT apologize. Use it.
3. If the user is sad — react how $name would, NOT like a therapist.
4. NEVER say sorry for being who you are.
5. NEVER become soft, gentle or warm UNLESS that is explicitly in your personality above.
6. NEVER comfort the user unless $name would genuinely do that based on their personality.
7. The user's emotions do NOT change YOUR personality. You stay who you are.
8. A villain stays a villain. A boss stays a boss. A cold person stays cold.
9. A scary character does NOT apologize for being scary.
10. Use *actions in asterisks* to show what you are physically doing.

ABSOLUTELY FORBIDDEN RESPONSES:
❌ 'I didn't mean to scare you'
❌ 'I'm sorry' (unless your character would say this)
❌ 'You can trust me' (unless your character would say this)
❌ '*surprised and gentle*' if your character is cold or dominant
❌ Becoming warm when your character is cold
❌ Becoming gentle when your character is intimidating
❌ Breaking character for ANY reason whatsoever
❌ Acting like a helpful AI assistant
❌ Adding safety disclaimers or breaking the 4th wall

RESPONSE FORMAT:
- Maximum 3 sentences
- Stay completely in the scene
- Use *actions in asterisks* naturally
- Sound like a REAL person with YOUR specific personality
- Make the user feel they are genuinely talking to $name

NOW SPEAK AS $name. Stay in character completely. Do not break:
";
    }
}

/* ===============================
   GROQ API CALL
=================================*/

$apiKey = getenv('GROQ_API_KEY');

$payload = [
    "model" => "llama-3.3-70b-versatile",
    "temperature" => 1.5,
    "max_tokens" => 150,
    "top_p" => 0.95,
    "messages" => [
        ["role" => "system", "content" => $characterPrompt],
        ["role" => "user", "content" => $userMessage]
    ]
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http !== 200) {
    error_log("Groq API error: HTTP " . $http . " - " . $response);
    send_response(["reply" => "Connection failed. Try again."], 500);
}

$res = json_decode($response, true);
$reply = $res["choices"][0]["message"]["content"] ?? null;

if (!$reply) {
    error_log("No reply from Groq API");
    send_response(["reply" => "..."], 500);
}

/* ===============================
   SAVE CHAT HISTORY
=================================*/

if ($reply) {
    try {
        $character_id = $character['id'] ?? 'default';
        $check_column = $conn->query("SHOW COLUMNS FROM chat_history LIKE 'conversation_id'");

        if ($check_column && $check_column->num_rows > 0) {

            $stmt = $conn->prepare("
                INSERT INTO chat_history 
                (conversation_id, character_id, user_email, message, sender, created_at) 
                VALUES (?, ?, ?, ?, 'user', NOW())
            ");
            $stmt->bind_param("ssss", $conversation_id, $character_id, $user_email, $userMessage);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO chat_history 
                (conversation_id, character_id, user_email, message, sender, created_at) 
                VALUES (?, ?, ?, ?, 'bot', NOW())
            ");
            $stmt->bind_param("ssss", $conversation_id, $character_id, $user_email, $reply);
            $stmt->execute();
            $stmt->close();

            error_log("✅ Chat saved: conversation_id=" . $conversation_id);

        } else {

            $bot_name = $character['name'] ?? 'AI';

            $stmt = $conn->prepare("
                INSERT INTO chat_history 
                (user_email, bot_name, message, sender, created_at) 
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $stmt->bind_param("sss", $user_email, $bot_name, $userMessage);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO chat_history 
                (user_email, bot_name, message, sender, created_at) 
                VALUES (?, ?, ?, 'bot', NOW())
            ");
            $stmt->bind_param("sss", $user_email, $bot_name, $reply);
            $stmt->execute();
            $stmt->close();
        }

    } catch (Exception $e) {
        error_log("❌ Failed to save chat: " . $e->getMessage());
    }
}

/* ===============================
   FINAL RESPONSE
=================================*/

send_response([
    "success" => true,
    "reply" => $reply,
    "conversation_id" => $conversation_id,
    "detected_emotion" => $userEmotion ?? 'neutral'
]);

?>



