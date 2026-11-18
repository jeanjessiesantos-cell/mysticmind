<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

require_csrf_json();

$user = getCurrentUser();
$db   = getDB();
$id   = (int)($_POST['id'] ?? 0);
$type = sanitize($_POST['type'] ?? '');

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID.']);
    exit;
}

if ($type === 'reading') {
    $stmt = $db->prepare('DELETE FROM readings WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);
} elseif ($type === 'journal') {
    $stmt = $db->prepare('DELETE FROM journal_entries WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);
} else {
    echo json_encode(['error' => 'Unknown type.']);
}
