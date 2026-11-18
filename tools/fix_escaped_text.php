<?php
/**
 * ONE-TIME CLEANUP — run once, from the command line only:
 *
 *   C:\xampp\php\php.exe C:\xampp\htdocs\mysticmind\tools\fix_escaped_text.php
 *
 * Before the security update, user text was HTML-escaped BEFORE being saved,
 * so old entries are stored like "I can&#039;t sleep" and show up on screen
 * as literal "&#039;". The app now stores raw text and escapes on output.
 * This script decodes the old rows so they display correctly.
 *
 * Only run it ONCE: running again would also decode text a user typed on
 * purpose, like "&amp;". Back up your database (phpMyAdmin → Export) first.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$db = getDB();

function decodeText($s) {
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function decodeDeep($v) {
    if (is_array($v)) return array_map('decodeDeep', $v);
    if (is_string($v)) return decodeText($v);
    return $v;
}

$fixed = 0;

// Journal entries
$rows = $db->query("SELECT id, entry_text FROM journal_entries WHERE entry_text LIKE '%&%;%'")->fetch_all(MYSQLI_ASSOC);
$upd  = $db->prepare('UPDATE journal_entries SET entry_text = ? WHERE id = ?');
foreach ($rows as $r) {
    $new = decodeText($r['entry_text']);
    if ($new !== $r['entry_text']) {
        $upd->bind_param('si', $new, $r['id']);
        $upd->execute();
        $fixed++;
    }
}
echo "journal_entries: " . count($rows) . " checked\n";

// Readings (question, summary, and the interpretation JSON)
$rows = $db->query(
    "SELECT id, user_question, summary, interpretation FROM readings
     WHERE user_question LIKE '%&%;%' OR summary LIKE '%&%;%' OR interpretation LIKE '%&%;%'"
)->fetch_all(MYSQLI_ASSOC);
$upd = $db->prepare('UPDATE readings SET user_question = ?, summary = ?, interpretation = ? WHERE id = ?');
foreach ($rows as $r) {
    $q = $r['user_question'] === null ? null : decodeText($r['user_question']);
    $s = $r['summary'] === null ? null : decodeText($r['summary']);

    $interp  = $r['interpretation'];
    $decoded = json_decode($interp, true);
    if (is_array($decoded)) {
        $interp = json_encode(decodeDeep($decoded));
    }

    if ($q !== $r['user_question'] || $s !== $r['summary'] || $interp !== $r['interpretation']) {
        $upd->bind_param('sssi', $q, $s, $interp, $r['id']);
        $upd->execute();
        $fixed++;
    }
}
echo "readings: " . count($rows) . " checked\n";

echo "Done. $fixed row(s) fixed. You can delete this file now.\n";
