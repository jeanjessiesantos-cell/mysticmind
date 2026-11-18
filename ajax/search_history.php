<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sentiment.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['html' => '']);
    exit;
}

require_csrf_json();

$user = getCurrentUser();
$db   = getDB();
$q    = sanitize($_POST['q'] ?? '');

$spreadLabels = ['single'=>'Single Card','three_card'=>'3-Card Spread','decision'=>'Decision Spread'];
$spreadClass  = ['single'=>'spread-single','three_card'=>'spread-three','decision'=>'spread-decision'];

if ($q === '') {
    $stmt = $db->prepare('SELECT * FROM readings WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user['id']);
} else {
    // Prepared statements already handle quoting; only escape LIKE wildcards (% and _)
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $stmt = $db->prepare(
        'SELECT * FROM readings WHERE user_id = ? AND (user_question LIKE ? OR spread_type LIKE ?) ORDER BY created_at DESC'
    );
    $stmt->bind_param('iss', $user['id'], $like, $like);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">No readings found.</td></tr>';
} else {
    foreach ($rows as $r) {
        $label = $spreadLabels[$r['spread_type']] ?? $r['spread_type'];
        $cls   = $spreadClass[$r['spread_type']] ?? '';
        $sent  = getSentimentLabel($r['sentiment']);
        $q_esc = htmlspecialchars(mb_strimwidth($r['user_question'] ?? '—', 0, 60, '…'));
        $date  = date('M d, Y', strtotime($r['created_at']));
        echo "<tr>
          <td>{$date}</td>
          <td><span class='spread-badge {$cls}'>{$label}</span></td>
          <td>{$q_esc}</td>
          <td><span class='sentiment-badge sentiment-{$r['sentiment']}'>{$sent}</span></td>
          <td style='white-space:nowrap'>
            <a href='/mysticmind/history.php?view={$r['id']}' class='btn btn-outline btn-sm'>View</a>
            <button class='btn btn-danger btn-sm' onclick='confirmDelete({$r['id']}, \"reading\")'>Delete</button>
          </td>
        </tr>";
    }
}
$html = ob_get_clean();

echo json_encode(['html' => $html]);
