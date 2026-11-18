<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/sentiment.php';
require_once __DIR__ . '/includes/stats.php';
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

// Streaks, mood trend, card stats, Card of the Day (see includes/stats.php)
$streaks   = getStreaks($db, $user['id']);
$mood      = getMoodTrend($db, $user['id'], 30);
$cardStats = getCardStats($db, $user['id']);
$cotd      = getCardOfTheDay($user['id']);

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
  <?= csrf_meta() ?>
  <link rel="icon" href="/mysticmind/favicon.ico?v=5" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="/mysticmind/assets/images/favicon-32.png?v=5">
  <link rel="apple-touch-icon" href="/mysticmind/assets/images/apple-touch-icon.png?v=5">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
  <link rel="stylesheet" href="/mysticmind/assets/css/theme.css?v=<?= @filemtime(__DIR__ . '/assets/css/theme.css') ?>">
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/home">Home</a>
      <a href="/mysticmind/dashboard" class="active">Dashboard</a>
      <a href="/mysticmind/reading">New Reading</a>
      <a href="/mysticmind/history">History</a>
      <a href="/mysticmind/journal">Journal</a>
      <a href="/mysticmind/logout" class="btn-nav">Logout</a>
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
      <a href="/mysticmind/reading" class="btn btn-gold">🔮 New Reading</a>
      <a href="/mysticmind/journal" class="btn btn-outline">📝 Journal</a>
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
    <div class="stat-card stat-streak<?= $streaks['current'] > 0 ? ' is-lit' : '' ?>">
      <div class="stat-icon">🔥</div>
      <div class="stat-num"><?= $streaks['current'] ?></div>
      <div class="stat-label">Day Streak · Best <?= $streaks['longest'] ?></div>
      <?php if ($streaks['current'] > 0 && !$streaks['active_today']): ?>
        <div class="stat-hint">Do a reading or journal today to keep it!</div>
      <?php endif; ?>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✨</div>
      <div class="stat-num"><?= $totalReadings + $totalJournals ?></div>
      <div class="stat-label">Total Reflections</div>
    </div>
  </div>

  <!-- Card of the Day + Mood Trend -->
  <div class="dash-feature-grid">

    <div class="panel cotd-panel">
      <div class="section-header"><h3>✦ Card of the Day</h3><span class="cotd-date"><?= date('M j') ?></span></div>
      <div class="cotd-body">
        <div class="cotd-card" id="cotd-card" data-key="cotd-<?= (int)$user['id'] ?>-<?= date('Y-m-d') ?>" role="button" tabindex="0" aria-label="Reveal your card of the day">
          <div class="cotd-inner">
            <div class="cotd-face cotd-back"><div class="card-back-art"></div><span class="cotd-tap">Tap to reveal</span></div>
            <div class="cotd-face cotd-front">
              <img src="<?= e($cotd['image']) ?>" alt="<?= e($cotd['name']) ?>" style="<?= $cotd['reversed'] ? 'transform:rotate(180deg)' : '' ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="cotd-fallback" style="display:none"><span><?= e($cotd['symbol']) ?></span><?= e($cotd['name']) ?></div>
            </div>
          </div>
        </div>
        <div class="cotd-info">
          <h4 class="cotd-name"><?= e($cotd['name']) ?></h4>
          <div class="cotd-meta">
            <span><?= e($cotd['suit']) ?></span>
            <span class="card-orientation <?= $cotd['reversed'] ? 'reversed' : 'upright' ?>"><?= $cotd['reversed'] ? 'Reversed' : 'Upright' ?></span>
          </div>
          <p class="cotd-meaning"><?= e($cotd['meaning']) ?></p>
          <p class="cotd-message">“<?= e($cotd['message']) ?>”</p>
          <?php if ($cotd['keywords']): ?>
          <div class="cotd-keywords"><?php foreach ($cotd['keywords'] as $k): ?><span>#<?= e($k) ?></span><?php endforeach; ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="panel mood-panel">
      <div class="section-header"><h3>🌙 Mood Trend</h3><span class="cotd-date">Last 30 days</span></div>
      <?php if ($mood['total'] === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">📈</div>
          <p>Your mood chart appears here after your first reading or journal entry.</p>
        </div>
      <?php else: ?>
        <div class="mood-chart-wrap"><canvas id="moodChart" aria-label="Mood trend chart for the last 30 days"></canvas></div>
        <div class="mood-legend">
          <span class="sentiment-badge sentiment-positive">✨ <?= $mood['counts']['positive'] ?> positive</span>
          <span class="sentiment-badge sentiment-neutral">🌙 <?= $mood['counts']['neutral'] ?> neutral</span>
          <span class="sentiment-badge sentiment-negative">🌧 <?= $mood['counts']['negative'] ?> needs support</span>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Spread shortcuts -->
  <div class="suggestion-box">
    <h3>🌟 What would you like to explore today?</h3>
    <p style="margin-bottom:1rem">Choose a spread to begin your reading, or revisit your past insights below.</p>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
      <a href="/mysticmind/reading?spread=single" class="btn btn-outline btn-sm">🃏 Single Card</a>
      <a href="/mysticmind/reading?spread=three_card" class="btn btn-outline btn-sm">🌙 Past · Present · Future</a>
      <a href="/mysticmind/reading?spread=decision" class="btn btn-outline btn-sm">⚖️ Decision Spread</a>
    </div>
  </div>

  <!-- Card statistics -->
  <?php if ($cardStats['total'] > 0): ?>
  <div class="panel cardstats-panel">
    <div class="section-header"><h3>🃏 Your Card Patterns</h3><span class="cotd-date"><?= $cardStats['total'] ?> cards drawn · <?= $cardStats['unique'] ?> unique</span></div>
    <div class="cardstats-grid">
      <div>
        <h5 class="cardstats-sub">Most drawn cards</h5>
        <div class="top-cards">
          <?php $maxCount = $cardStats['top'][0]['count'] ?? 1; foreach ($cardStats['top'] as $i => $tc): ?>
          <div class="top-card">
            <span class="top-rank"><?= $i + 1 ?></span>
            <img src="<?= e($tc['image']) ?>" alt="" onerror="this.style.visibility='hidden'">
            <div class="top-card-info">
              <div class="top-card-name"><?= e($tc['name']) ?></div>
              <div class="bar"><span style="width:<?= round($tc['count'] / $maxCount * 100) ?>%"></span></div>
            </div>
            <span class="top-count">×<?= $tc['count'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h5 class="cardstats-sub">By suit</h5>
        <?php $suitIcons = ['Major Arcana'=>'✦','Cups'=>'🏆','Wands'=>'🔥','Swords'=>'⚔️','Pentacles'=>'🪙'];
              foreach ($cardStats['suits'] as $suit => $count): $pct = round($count / $cardStats['total'] * 100); ?>
        <div class="suit-row">
          <span class="suit-name"><?= $suitIcons[$suit] ?? '•' ?> <?= e($suit) ?></span>
          <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
          <span class="suit-pct"><?= $pct ?>%</span>
        </div>
        <?php endforeach; ?>
        <div class="cardstats-facts">
          <div><strong><?= $cardStats['major_pct'] ?>%</strong><span>Major Arcana</span></div>
          <div><strong><?= $cardStats['reversed_pct'] ?>%</strong><span>Reversed</span></div>
        </div>
        <?php if ($cardStats['major_pct'] >= 40): ?>
          <p class="cardstats-insight">Many Major Arcana cards — the universe may be pointing to big life lessons right now.</p>
        <?php elseif ($cardStats['reversed_pct'] >= 50): ?>
          <p class="cardstats-insight">Lots of reversed cards — a sign to look inward and release what's holding you back.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Two column: recent readings + recent journal -->
  <div class="two-col">

    <!-- Recent Readings -->
    <div>
      <div class="section-header">
        <h3>📜 Recent Readings</h3>
        <a href="/mysticmind/history" class="btn btn-outline btn-sm">View All</a>
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
                <td><a href="/mysticmind/history?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
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
        <a href="/mysticmind/journal" class="btn btn-outline btn-sm">View All</a>
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
        <a href="/mysticmind/journal" class="btn btn-outline btn-sm" style="margin-top:0.25rem">See all entries →</a>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="/mysticmind/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<?php if ($mood['total'] > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  initMoodChart(<?= json_encode(['labels' => $mood['labels'], 'values' => $mood['values']]) ?>);
</script>
<?php endif; ?>
<script>initCardOfTheDay();</script>

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
          <li><a href="/mysticmind/home">Home</a></li>
          <li><a href="/mysticmind/dashboard">Dashboard</a></li>
          <li><a href="/mysticmind/reading">New Reading</a></li>
          <li><a href="/mysticmind/history">History</a></li>
          <li><a href="/mysticmind/journal">Journal</a></li>
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
  <?php include __DIR__ . '/includes/disclaimer.php'; ?>
  <div class="footer-bottom-bar">
    <p class="footer-copy">© <?= date('Y') ?> <b>MysticMind</b> · All rights reserved · <span class="footer-dev">Developed by <em>Jessie Jean Santos</em></span></p>
    <p class="footer-tagline-text">Intelligent Tarot · Self-Reflection · Clarity</p>
  </div>
</footer>

</body>
</html>