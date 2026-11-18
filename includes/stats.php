<?php
/**
 * Dashboard statistics: mood trend, streaks, card stats, Card of the Day.
 */
require_once __DIR__ . '/tarot_data.php';

/** Image filename for a card name — same rule as getCardImageTag() in main.js. */
function cardImageFile($name) {
    $f = strtolower($name);
    $f = preg_replace("/['\\s]+/", '-', $f);
    $f = preg_replace('/[^a-z0-9-]/', '', $f);
    return '/mysticmind/assets/images/tarot/' . $f . '.jpg';
}

/**
 * Mood trend for the last $days days, combining readings and journal entries.
 * positive = +1, neutral = 0, negative = -1, averaged per day.
 * Days with no activity are null (the chart leaves a gap).
 */
function getMoodTrend($db, $userId, $days = 30) {
    $sql = "
        SELECT DATE(created_at) AS d, sentiment FROM readings
          WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        UNION ALL
        SELECT DATE(created_at) AS d, sentiment FROM journal_entries
          WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
    $daysBack = $days - 1;
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iiii', $userId, $daysBack, $userId, $daysBack);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $value  = ['positive' => 1, 'neutral' => 0, 'negative' => -1];
    $byDay  = [];
    $counts = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
    foreach ($rows as $r) {
        $s = $r['sentiment'] ?? 'neutral';
        if (!isset($value[$s])) $s = 'neutral';
        $byDay[$r['d']][] = $value[$s];
        $counts[$s]++;
    }

    $labels = []; $values = [];
    for ($i = $daysBack; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $labels[] = date('M j', strtotime($d));
        $values[] = isset($byDay[$d]) ? round(array_sum($byDay[$d]) / count($byDay[$d]), 2) : null;
    }

    return ['labels' => $labels, 'values' => $values, 'counts' => $counts, 'total' => array_sum($counts)];
}

/**
 * Streaks: consecutive days with at least one reading OR journal entry.
 * The current streak stays alive until the end of today (if you were active yesterday).
 */
function getStreaks($db, $userId) {
    $stmt = $db->prepare("
        SELECT DISTINCT d FROM (
            SELECT DATE(created_at) AS d FROM readings WHERE user_id = ?
            UNION
            SELECT DATE(created_at) AS d FROM journal_entries WHERE user_id = ?
        ) t ORDER BY d DESC");
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $dates = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'd');

    if (!$dates) return ['current' => 0, 'longest' => 0, 'active_today' => false];

    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Current streak
    $current = 0;
    if ($dates[0] === $today || $dates[0] === $yesterday) {
        $expected = $dates[0];
        foreach ($dates as $d) {
            if ($d !== $expected) break;
            $current++;
            $expected = date('Y-m-d', strtotime("$expected -1 day"));
        }
    }

    // Longest streak
    $longest = 1; $run = 1;
    for ($i = 1; $i < count($dates); $i++) {
        $prevMinusOne = date('Y-m-d', strtotime($dates[$i - 1] . ' -1 day'));
        if ($dates[$i] === $prevMinusOne) { $run++; $longest = max($longest, $run); }
        else $run = 1;
    }

    return ['current' => $current, 'longest' => max($longest, $current), 'active_today' => $dates[0] === $today];
}

/** Card statistics from every reading's cards_drawn JSON. */
function getCardStats($db, $userId) {
    $stmt = $db->prepare('SELECT cards_drawn FROM readings WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $byCard = []; $bySuit = []; $total = 0; $reversed = 0; $major = 0;
    while ($row = $res->fetch_assoc()) {
        $cards = json_decode($row['cards_drawn'], true);
        if (!is_array($cards)) continue;
        foreach ($cards as $c) {
            if (empty($c['name'])) continue;
            $total++;
            $name = $c['name'];
            $byCard[$name] = ($byCard[$name] ?? 0) + 1;
            $suit = $c['suit'] ?? 'Unknown';
            $bySuit[$suit] = ($bySuit[$suit] ?? 0) + 1;
            if (!empty($c['reversed'])) $reversed++;
            if ($suit === 'Major Arcana') $major++;
        }
    }
    arsort($byCard);
    arsort($bySuit);

    $top = [];
    foreach (array_slice($byCard, 0, 5, true) as $name => $count) {
        $top[] = ['name' => $name, 'count' => $count, 'image' => cardImageFile($name)];
    }

    return [
        'total'        => $total,
        'unique'       => count($byCard),
        'top'          => $top,
        'suits'        => $bySuit,
        'reversed_pct' => $total ? round($reversed / $total * 100) : 0,
        'major_pct'    => $total ? round($major / $total * 100) : 0,
    ];
}

/**
 * Card of the Day: the same card for a user all day, a new one tomorrow.
 * Picked from a seed (user id + date), so no database storage is needed.
 */
function getCardOfTheDay($userId) {
    $deck = getTarotDeck();
    $seed = crc32('mysticmind-cotd-' . $userId . '-' . date('Y-m-d'));
    mt_srand($seed);
    $card     = $deck[mt_rand(0, count($deck) - 1)];
    $reversed = mt_rand(0, 3) === 0;   // 1 in 4 chance of reversed
    mt_srand();                          // restore normal randomness

    $messages = [
        'new_start'  => 'Today invites a fresh beginning — take one small brave step.',
        'action'     => 'You already hold the tools you need. Act with intention today.',
        'intuition'  => 'Pause and listen inward today; your intuition knows the way.',
        'abundance'  => 'Nurture what you love today, and watch it grow.',
    ];
    $msg = $messages[$card['theme'] ?? ''] ??
        ($reversed
            ? 'Notice what feels blocked today, and be gentle with yourself as you work through it.'
            : 'Carry this card\'s energy with you today and notice where it shows up.');

    return [
        'name'     => $card['name'],
        'suit'     => $card['suit'],
        'symbol'   => $card['symbol'] ?? '✦',
        'reversed' => $reversed,
        'meaning'  => $reversed ? $card['reversed'] : $card['upright'],
        'keywords' => array_slice($card['keywords'] ?? [], 0, 3),
        'message'  => $msg,
        'image'    => cardImageFile($card['name']),
    ];
}
