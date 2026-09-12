<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tarot_data.php';
require_once __DIR__ . '/../includes/sentiment.php';
require_once __DIR__ . '/../includes/interpreter.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$user       = getCurrentUser();
$spreadType = sanitize($_POST['spread_type'] ?? 'single');
$question   = sanitize($_POST['user_question'] ?? '');
$optionA    = sanitize($_POST['option_a'] ?? '');
$optionB    = sanitize($_POST['option_b'] ?? '');

$validSpreads = ['single', 'three_card', 'decision'];
if (!in_array($spreadType, $validSpreads)) {
    echo json_encode(['error' => 'Invalid spread type.']);
    exit;
}

// Build full question context for decision spread
if ($spreadType === 'decision' && ($optionA || $optionB)) {
    $question = trim($question . ' ' . $optionA . ' vs ' . $optionB);
}

// Draw cards
$cardCount = ['single' => 1, 'three_card' => 3, 'decision' => 3];
$count     = $cardCount[$spreadType];
$cards     = drawCards($count);

// Sentiment analysis
$sentiment      = analyzeSentiment($question);
$sentimentLabel = getSentimentLabel($sentiment);

// Generate interpretation
$interpretation = generateInterpretation($cards, $question, $spreadType, $sentiment);

// Prepare cards for storage and response
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

// Save to DB
$db          = getDB();
$interpJson  = json_encode($interpretation);
$cardsJson   = json_encode($cardsForStorage);
$summary     = $interpretation['summary'] ?? '';

$stmt = $db->prepare(
    'INSERT INTO readings (user_id, spread_type, user_question, cards_drawn, interpretation, sentiment, summary) VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('issssss', $user['id'], $spreadType, $question, $cardsJson, $interpJson, $sentiment, $summary);
$stmt->execute();
$readingId = $stmt->insert_id;

// Update user preferences
$upd = $db->prepare(
    'INSERT INTO user_preferences (user_id, preferred_spread, total_readings, last_sentiment)
     VALUES (?, ?, 1, ?)
     ON DUPLICATE KEY UPDATE preferred_spread = ?, total_readings = total_readings + 1, last_sentiment = ?'
);
$upd->bind_param('issss', $user['id'], $spreadType, $sentiment, $spreadType, $sentiment);
$upd->execute();

// Build response cards with meanings
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
