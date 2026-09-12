<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tarot_data.php';
require_once __DIR__ . '/../includes/sentiment.php';
require_once __DIR__ . '/../includes/interpreter.php';

header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Not authenticated.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'Invalid request.']); exit; }

$action = $_POST['action'] ?? 'message';

// ── ACTION: chat message ──────────────────────────────────────────────────────
if ($action === 'message') {
    $userMsg  = sanitize($_POST['message'] ?? '');
    $history  = json_decode($_POST['history'] ?? '[]', true);
    $turnCount = (int)($_POST['turn'] ?? 0);

    if (!$userMsg) { echo json_encode(['error' => 'Empty message.']); exit; }

    $sentiment  = analyzeSentiment($userMsg);
    $allText    = $userMsg . ' ' . implode(' ', array_column($history, 'content'));
    $sentiment  = analyzeSentiment($allText);

    $reply      = generateOracleReply($userMsg, $history, $sentiment, $turnCount);
    $readyToPick = $reply['ready'];

    echo json_encode([
        'reply'       => $reply['text'],
        'sentiment'   => $sentiment,
        'ready'       => $readyToPick,
        'spread'      => $reply['spread'] ?? 'single',
        'question'    => $reply['question'] ?? $userMsg,
    ]);
    exit;
}

// ── ACTION: do reading after card pick ────────────────────────────────────────
if ($action === 'reading') {
    $spreadType = sanitize($_POST['spread_type'] ?? 'single');
    $question   = sanitize($_POST['question'] ?? '');
    $sentiment  = sanitize($_POST['sentiment'] ?? 'neutral');

    $validSpreads = ['single', 'three_card', 'decision'];
    if (!in_array($spreadType, $validSpreads)) $spreadType = 'single';

    $cardCount = ['single' => 1, 'three_card' => 3, 'decision' => 3];
    $cards     = drawCards($cardCount[$spreadType]);

    $interpretation = generateInterpretation($cards, $question, $spreadType, $sentiment);
    $sentimentLabel = getSentimentLabel($sentiment);

    $cardsForStorage = array_map(function($c) {
        return [
            'id'              => $c['id'],
            'name'            => $c['name'],
            'suit'            => $c['suit'],
            'symbol'          => $c['symbol'],
            'reversed'        => $c['reversed'],
            'upright_meaning' => $c['upright_meaning'],
            'reversed_meaning'=> $c['reversed_meaning'],
        ];
    }, $cards);

    $user       = getCurrentUser();
    $db         = getDB();
    $interpJson = json_encode($interpretation);
    $cardsJson  = json_encode($cardsForStorage);
    $summary    = $interpretation['summary'] ?? '';

    $stmt = $db->prepare(
        'INSERT INTO readings (user_id, spread_type, user_question, cards_drawn, interpretation, sentiment, summary) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issssss', $user['id'], $spreadType, $question, $cardsJson, $interpJson, $sentiment, $summary);
    $stmt->execute();
    $readingId = $stmt->insert_id;

    $upd = $db->prepare(
        'INSERT INTO user_preferences (user_id, preferred_spread, total_readings, last_sentiment)
         VALUES (?, ?, 1, ?)
         ON DUPLICATE KEY UPDATE preferred_spread=?, total_readings=total_readings+1, last_sentiment=?'
    );
    $upd->bind_param('issss', $user['id'], $spreadType, $sentiment, $spreadType, $sentiment);
    $upd->execute();

    $responseCards = array_map(function($c) {
        return [
            'name'             => $c['name'],
            'suit'             => $c['suit'],
            'symbol'           => $c['symbol'],
            'reversed'         => $c['reversed'],
            'upright_meaning'  => $c['upright_meaning'],
            'reversed_meaning' => $c['reversed_meaning'],
        ];
    }, $cards);

    echo json_encode([
        'success'        => true,
        'reading_id'     => $readingId,
        'spread_type'    => $spreadType,
        'cards'          => $responseCards,
        'interpretation' => $interpretation,
        'sentiment'      => ['value' => $sentiment, 'label' => $sentimentLabel],
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);

// ── Oracle reply logic ────────────────────────────────────────────────────────
function generateOracleReply($msg, $history, $sentiment, $turn) {
    $msgLower = strtolower($msg);
    $turnCount = count($history);

    // Detect themes from message
    $themes = [];
    $themeMap = [
        'love'         => ['love','relationship','partner','boyfriend','girlfriend','marriage','breakup','heart'],
        'career'       => ['job','career','work','business','promotion','study','school','course'],
        'finance'      => ['money','debt','savings','financial','income','bills'],
        'health'       => ['health','sick','anxiety','stress','mental','healing','tired','exhausted'],
        'purpose'      => ['purpose','direction','lost','meaning','path','future','goal','life'],
        'decision'     => ['decide','decision','choice','choose','option','should i','or'],
        'family'       => ['family','parent','mother','father','sibling','child','home'],
    ];
    foreach ($themeMap as $theme => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($msgLower, $kw) !== false) { $themes[] = $theme; break; }
        }
    }

    // Determine spread based on themes
    $spread = 'single';
    if (in_array('decision', $themes) || strpos($msgLower, 'or') !== false || strpos($msgLower, 'should') !== false) {
        $spread = 'decision';
    } elseif ($turnCount >= 1 || strlen($msg) > 60) {
        $spread = 'three_card';
    }

    // Supportive opener for negative sentiment
    $opener = '';
    if ($sentiment === 'negative') {
        $openers = [
            "I hear the weight in your words, and I want you to know — you are not alone in this. ",
            "Thank you for trusting me with something so tender. The cards hold space for all of it. ",
            "What you're carrying sounds heavy. Let's sit with it together and see what the cards reveal. ",
        ];
        $opener = $openers[array_rand($openers)];
    } elseif ($sentiment === 'positive') {
        $openers = [
            "There is a beautiful energy in what you've shared. ",
            "I sense clarity and intention in your words — that is a powerful place to read from. ",
        ];
        $opener = $openers[array_rand($openers)];
    }

    // First turn — acknowledge and ask a follow-up
    if ($turnCount === 0) {
        $followUps = [
            'love'     => "Love and connection run deep in what you've shared. Before we draw the cards — is there a specific aspect of this relationship you most want clarity on? The feelings you carry, the direction it's heading, or a decision you're facing?",
            'career'   => "Your path and purpose are calling for attention. To help the cards speak more precisely — are you seeking clarity on where you are now, where you're headed, or a specific choice you need to make?",
            'finance'  => "Material concerns can weigh heavily on the spirit. Tell me more — is this about a current struggle, a decision around money, or understanding the energy you hold around abundance?",
            'health'   => "Your wellbeing matters deeply. Are you looking for guidance on your emotional state, your physical energy, or the path toward healing?",
            'purpose'  => "The search for meaning is one of the soul's deepest journeys. What feels most unclear right now — your direction, your identity, or a choice that lies ahead?",
            'decision' => "A crossroads. The cards are well-suited for this. Can you tell me a little more about the two paths you're weighing? Even a few words about each option will help the reading speak directly to your situation.",
            'family'   => "Family carries so much — love, history, and sometimes pain. What aspect of this would you like the cards to illuminate?",
        ];

        $theme = $themes[0] ?? null;
        if ($theme && isset($followUps[$theme])) {
            return ['text' => $opener . $followUps[$theme], 'ready' => false, 'spread' => $spread, 'question' => $msg];
        }

        // Generic follow-up
        $generic = [
            "Thank you for sharing that with me. To help the cards speak more clearly to your situation — could you tell me a little more? What outcome or understanding are you hoping this reading will bring?",
            "I sense there is more beneath the surface of what you've shared. What is the heart of what you're seeking — clarity, direction, or perhaps peace of mind?",
            "The cards respond to intention. Before we draw — what would feel like a meaningful answer for you right now?",
        ];
        return ['text' => $opener . $generic[array_rand($generic)], 'ready' => false, 'spread' => $spread, 'question' => $msg];
    }

    // Second turn — summarize and invite card selection
    $spreadLabels = [
        'single'     => 'a single card to offer you a focused message',
        'three_card' => 'three cards — one for your past, present, and the path ahead',
        'decision'   => 'three cards to illuminate both paths and the likely outcome',
    ];
    $spreadLabel = $spreadLabels[$spread];

    $readyPhrases = [
        "I have heard you. The cards are ready to speak. I will draw {$spreadLabel}. Take a breath, hold your question in your heart, and choose your cards from the deck below.",
        "What you've shared gives the cards a clear channel to work through. I'll lay out {$spreadLabel}. Breathe, focus, and when you feel ready — select your cards.",
        "The Oracle understands. Let us draw {$spreadLabel}. Clear your mind, feel the weight of your question, and choose the cards that call to you.",
    ];

    // Combine all user messages as the question
    $allUserMsgs = array_filter($history, fn($h) => $h['role'] === 'user');
    $question = implode(' ', array_column(array_values($allUserMsgs), 'content')) . ' ' . $msg;

    return [
        'text'     => $readyPhrases[array_rand($readyPhrases)],
        'ready'    => true,
        'spread'   => $spread,
        'question' => trim($question),
    ];
}
