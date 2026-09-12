<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/sentiment.php';
requireLogin();

$user = getCurrentUser();
$db   = getDB();

// Stats
$statsQ = $db->prepare('SELECT COUNT(*) as total FROM readings WHERE user_id = ?');
$statsQ->bind_param('i', $user['id']);
$statsQ->execute();
$totalReadings = $statsQ->get_result()->fetch_assoc()['total'];

$journalQ = $db->prepare('SELECT COUNT(*) as total FROM journal_entries WHERE user_id = ?');
$journalQ->bind_param('i', $user['id']);
$journalQ->execute();
$totalJournals = $journalQ->get_result()->fetch_assoc()['total'];

$streakQ = $db->prepare('SELECT COUNT(DISTINCT DATE(created_at)) as days FROM readings WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$streakQ->bind_param('i', $user['id']);
$streakQ->execute();
$streakDays = $streakQ->get_result()->fetch_assoc()['days'];

// Last sentiment
$sentQ = $db->prepare('SELECT sentiment FROM readings WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$sentQ->bind_param('i', $user['id']);
$sentQ->execute();
$lastSentiment = $sentQ->get_result()->fetch_assoc()['sentiment'] ?? null;

// Recent readings (last 5)
$recentQ = $db->prepare('SELECT * FROM readings WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$recentQ->bind_param('i', $user['id']);
$recentQ->execute();
$recentReadings = $recentQ->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent journal entries (last 3)
$recentJournalQ = $db->prepare('SELECT * FROM journal_entries WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$recentJournalQ->bind_param('i', $user['id']);
$recentJournalQ->execute();
$recentJournals = $recentJournalQ->get_result()->fetch_all(MYSQLI_ASSOC);

$spreadLabels = ['single' => 'Single Card', 'three_card' => '3-Card Spread', 'decision' => 'Decision Spread'];
$spreadClass  = ['single' => 'spread-single', 'three_card' => 'spread-three', 'decision' => 'spread-decision'];

$hour = (int)date('G');
if ($hour >= 5 && $hour < 12)      $greeting = 'Good Morning';
elseif ($hour >= 12 && $hour < 17) $greeting = 'Good Afternoon';
elseif ($hour >= 17 && $hour < 21) $greeting = 'Good Evening';
else                                $greeting = 'Good Night';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css">
  <style>
    .welcome-banner {
      background: linear-gradient(135deg, rgba(124,92,191,0.2), rgba(201,168,76,0.1));
      border: 1px solid var(--purple);
      border-radius: var(--radius-lg);
      padding: 2rem 2.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1.5rem;
    }
    .welcome-text h2 {
      font-family: 'Cinzel', serif;
      font-size: 1.6rem;
      color: var(--accent);
      margin-bottom: 0.3rem;
    }
    .welcome-text p {
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    .welcome-name { color: var(--purple-light); }

    .quick-actions {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.25rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .section-header h3 {
      font-family: 'Cinzel', serif;
      color: var(--accent);
      font-size: 1rem;
    }

    .empty-state {
      text-align: center;
      padding: 2.5rem 1rem;
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    .empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

    .journal-preview {
      background: var(--bg-panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1rem 1.25rem;
      margin-bottom: 0.75rem;
      transition: border-color 0.2s;
    }
    .journal-preview:hover { border-color: var(--purple); }
    .journal-preview-meta {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.4rem;
      flex-wrap: wrap;
    }
    .journal-preview-date { color: var(--text-muted); font-size: 0.78rem; }
    .journal-preview-text { color: var(--text-main); font-size: 0.9rem; line-height: 1.6; }

    .two-col {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      gap: 1.5rem;
      margin-top: 2rem;
    }
    @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

    /* Footer */
    .site-footer{position:relative;z-index:10;background:#0a0818;border-top:none;padding:0;margin-top:4rem;overflow:hidden}
    .footer-glow-bar{width:100%;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(201,168,76,0.6) 30%,rgba(160,125,224,0.6) 70%,transparent 100%);box-shadow:0 0 18px 2px rgba(201,168,76,0.18),0 0 32px 4px rgba(124,92,191,0.12)}
    .footer-noise{position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");background-size:200px 200px;pointer-events:none;opacity:0.35}
    .footer-blob{position:absolute;border-radius:50%;filter:blur(70px);pointer-events:none;opacity:0.09}
    .footer-blob-1{width:350px;height:350px;background:#7c5cbf;top:-80px;left:-60px}
    .footer-blob-2{width:250px;height:250px;background:#c9a84c;bottom:-60px;right:10%}
    .footer-inner-wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:3.5rem 2rem 0}
    .footer-grid{display:grid;grid-template-columns:1.8fr 1fr 1fr;gap:3rem;padding-bottom:3rem}
    .footer-brand-logo{font-family:'Cinzel',serif;font-size:1.5rem;color:#c9a84c;letter-spacing:3px;text-decoration:none;display:inline-block;margin-bottom:0.6rem}
    .footer-brand-logo span{color:#a07de0}
    .footer-brand-tagline{font-family:'Cinzel',serif;font-size:0.62rem;letter-spacing:3.5px;text-transform:uppercase;color:rgba(201,168,76,0.5);margin-bottom:1rem}
    .footer-brand-divider{width:48px;height:1px;background:linear-gradient(90deg,#c9a84c,transparent);margin-bottom:1rem}
    .footer-brand-desc{color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.8;max-width:270px}
    .footer-col-title{font-family:'Cinzel',serif;font-size:0.7rem;letter-spacing:3px;text-transform:uppercase;color:#c9a84c;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem}
    .footer-col-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(201,168,76,0.3),transparent)}
    .footer-nav-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem}
    .footer-nav-list li a{color:rgba(155,142,196,0.7);text-decoration:none;font-size:0.88rem;transition:color 0.2s,padding-left 0.2s;display:inline-block}
    .footer-nav-list li a:hover{color:#c9a84c;padding-left:6px}
    .footer-contact-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem}
    .footer-contact-list li{display:flex;align-items:flex-start;gap:0.6rem;color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.5}
    .footer-contact-list li .contact-icon{font-size:0.9rem;margin-top:1px;flex-shrink:0}
    .footer-bottom-bar{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:1.25rem 2rem 1.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;border-top:1px solid rgba(201,168,76,0.08)}
    .footer-copy{color:rgba(155,142,196,0.4);font-size:0.76rem}
    .footer-copy b{color:rgba(201,168,76,0.6);font-weight:400}
    .footer-tagline-text{color:rgba(155,142,196,0.25);font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;font-family:'Cinzel',serif}
    @media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:2rem}.footer-bottom-bar{justify-content:center;text-align:center}}
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/home.php">Home</a>
      <a href="/mysticmind/dashboard.php" class="active">Dashboard</a>
      <a href="/mysticmind/reading.php">New Reading</a>
      <a href="/mysticmind/history.php">History</a>
      <a href="/mysticmind/journal.php">Journal</a>
      <a href="/mysticmind/logout.php" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="container">

  <!-- Welcome Banner -->
  <div class="welcome-banner">
    <div class="welcome-text">
      <h2><?= $greeting ?>, <span class="welcome-name"><?= htmlspecialchars($user['username']) ?></span> ✨</h2>
      <p><?= date('l, F j, Y') ?> · Your mystic journey continues</p>
      <?php if ($lastSentiment): ?>
        <p style="margin-top:0.4rem;font-size:0.82rem">
          Last reading mood:
          <span class="sentiment-badge sentiment-<?= $lastSentiment ?>" style="margin-left:0.25rem">
            <?= getSentimentLabel($lastSentiment) ?>
          </span>
        </p>
      <?php endif; ?>
    </div>
    <div class="quick-actions">
      <a href="/mysticmind/reading.php" class="btn btn-gold">🔮 New Reading</a>
      <a href="/mysticmind/journal.php" class="btn btn-outline">📝 Journal</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="dash-grid">
    <div class="stat-card">
      <div class="stat-icon">🔮</div>
      <div class="stat-num"><?= $totalReadings ?></div>
      <div class="stat-label">Total Readings</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📝</div>
      <div class="stat-num"><?= $totalJournals ?></div>
      <div class="stat-label">Journal Entries</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🌙</div>
      <div class="stat-num" style="font-size:2rem">
        <?= $lastSentiment ? ucfirst($lastSentiment) : '—' ?>
      </div>
      <div class="stat-label">Recent Mood</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✨</div>
      <div class="stat-num"><?= $totalReadings + $totalJournals ?></div>
      <div class="stat-label">Total Reflections</div>
    </div>
  </div>

  <!-- Spread shortcuts -->
  <div class="suggestion-box">
    <h3>🌟 What would you like to explore today?</h3>
    <p style="margin-bottom:1rem">Choose a spread to begin your reading, or revisit your past insights below.</p>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
      <a href="/mysticmind/reading.php?spread=single" class="btn btn-outline btn-sm">🃏 Single Card</a>
      <a href="/mysticmind/reading.php?spread=three_card" class="btn btn-outline btn-sm">🌙 Past · Present · Future</a>
      <a href="/mysticmind/reading.php?spread=decision" class="btn btn-outline btn-sm">⚖️ Decision Spread</a>
    </div>
  </div>

  <!-- Two column: recent readings + recent journal -->
  <div class="two-col">

    <!-- Recent Readings -->
    <div>
      <div class="section-header">
        <h3>📜 Recent Readings</h3>
        <a href="/mysticmind/history.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <div class="panel panel-sm">
        <?php if (empty($recentReadings)): ?>
          <div class="empty-state">
            <div class="empty-icon">🔮</div>
            <p>No readings yet.<br>Start your first one above!</p>
          </div>
        <?php else: ?>
          <table class="history-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Spread</th>
                <th>Mood</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentReadings as $r): ?>
              <tr>
                <td style="white-space:nowrap;font-size:0.82rem"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                <td><span class="spread-badge <?= $spreadClass[$r['spread_type']] ?? '' ?>"><?= $spreadLabels[$r['spread_type']] ?? $r['spread_type'] ?></span></td>
                <td><span class="sentiment-badge sentiment-<?= $r['sentiment'] ?>"><?= getSentimentLabel($r['sentiment']) ?></span></td>
                <td><a href="/mysticmind/history.php?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Journal -->
    <div>
      <div class="section-header">
        <h3>📝 Recent Journal</h3>
        <a href="/mysticmind/journal.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if (empty($recentJournals)): ?>
        <div class="panel panel-sm">
          <div class="empty-state">
            <div class="empty-icon">📝</div>
            <p>No journal entries yet.<br>Write your first reflection!</p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($recentJournals as $j): ?>
        <div class="journal-preview">
          <div class="journal-preview-meta">
            <span class="journal-preview-date"><?= date('M d, Y', strtotime($j['created_at'])) ?></span>
            <span class="sentiment-badge sentiment-<?= $j['sentiment'] ?>"><?= getSentimentLabel($j['sentiment']) ?></span>
          </div>
          <p class="journal-preview-text"><?= htmlspecialchars(mb_strimwidth($j['entry_text'], 0, 100, '…')) ?></p>
        </div>
        <?php endforeach; ?>
        <a href="/mysticmind/journal.php" class="btn btn-outline btn-sm" style="margin-top:0.25rem">See all entries →</a>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="/mysticmind/assets/js/main.js"></script>

<footer class="site-footer">
  <div class="footer-glow-bar"></div>
  <div class="footer-blob footer-blob-1"></div>
  <div class="footer-blob footer-blob-2"></div>
  <div class="footer-noise"></div>
  <div class="footer-inner-wrap">
    <div class="footer-grid">
      <div>
        <a href="/mysticmind/" class="footer-brand-logo">Mystic<span>Mind</span></a>
        <p class="footer-brand-tagline">Intelligent Tarot · Self-Reflection · Clarity</p>
        <div class="footer-brand-divider"></div>
        <p class="footer-brand-desc">An intelligent tarot reading system blending ancient wisdom with modern AI. Discover clarity, reflect deeply, and let the cards guide your path.</p>
      </div>
      <div>
        <p class="footer-col-title">Navigate</p>
        <ul class="footer-nav-list">
          <li><a href="/mysticmind/home.php">Home</a></li>
          <li><a href="/mysticmind/dashboard.php">Dashboard</a></li>
          <li><a href="/mysticmind/reading.php">New Reading</a></li>
          <li><a href="/mysticmind/history.php">History</a></li>
          <li><a href="/mysticmind/journal.php">Journal</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-col-title">Contact</p>
        <ul class="footer-contact-list">
          <li><span class="contact-icon">✉</span><span>mysticmind@email.com</span></li>
          <li><span class="contact-icon">📍</span><span>In-person · by appointment</span></li>
          <li><span class="contact-icon">🕐</span><span>Mon – Sat</span></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom-bar">
    <p class="footer-copy">© <?= date('Y') ?> <b>MysticMind</b> · All rights reserved</p>
    <p class="footer-tagline-text">Intelligent Tarot · Self-Reflection · Clarity</p>
  </div>
</footer>

</body>
</html>