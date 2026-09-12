<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sentiment.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$user      = getCurrentUser();
$text      = htmlspecialchars(trim($_POST['entry_text'] ?? ''), ENT_QUOTES, 'UTF-8');
$readingId = isset($_POST['reading_id']) ? (int)$_POST['reading_id'] : null;

if (strlen($text) < 3) {
    echo json_encode(['error' => 'Entry too short.']);
    exit;
}

$sentiment = analyzeSentiment($text);
$db        = getDB();

// Verify reading belongs to user if provided
if ($readingId) {
    $chk = $db->prepare('SELECT id FROM readings WHERE id = ? AND user_id = ?');
    $chk->bind_param('ii', $readingId, $user['id']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) $readingId = null;
}

$stmt = $db->prepare('INSERT INTO journal_entries (user_id, reading_id, entry_text, sentiment) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiss', $user['id'], $readingId, $text, $sentiment);

if ($stmt->execute()) {
    // Update last sentiment
    $upd = $db->prepare('UPDATE user_preferences SET last_sentiment = ? WHERE user_id = ?');
    $upd->bind_param('si', $sentiment, $user['id']);
    $upd->execute();
    echo json_encode(['success' => true, 'sentiment' => $sentiment]);
} else {
    echo json_encode(['error' => 'Failed to save.']);
}
