<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/sentiment.php';
requireLogin();

$user = getCurrentUser();
$db   = getDB();

// View single reading
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewReading = null;
if ($viewId) {
    $vq = $db->prepare('SELECT * FROM readings WHERE id = ? AND user_id = ?');
    $vq->bind_param('ii', $viewId, $user['id']);
    $vq->execute();
    $viewReading = $vq->get_result()->fetch_assoc();
}

// All readings
$allQ = $db->prepare('SELECT * FROM readings WHERE user_id = ? ORDER BY created_at DESC');
$allQ->bind_param('i', $user['id']);
$allQ->execute();
$readings = $allQ->get_result()->fetch_all(MYSQLI_ASSOC);

$spreadLabels = ['single'=>'Single Card','three_card'=>'3-Card Spread','decision'=>'Decision Spread'];
$spreadClass  = ['single'=>'spread-single','three_card'=>'spread-three','decision'=>'spread-decision'];
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
  <title>Reading History — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .site-footer{position:relative;z-index:10;background:#0a0818;border-top:none;padding:0;margin-top:4rem;overflow:hidden}.footer-glow-bar{width:100%;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(201,168,76,0.6) 30%,rgba(160,125,224,0.6) 70%,transparent 100%);box-shadow:0 0 18px 2px rgba(201,168,76,0.18),0 0 32px 4px rgba(124,92,191,0.12)}.footer-noise{position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");background-size:200px 200px;pointer-events:none;opacity:0.35}.footer-blob{position:absolute;border-radius:50%;filter:blur(70px);pointer-events:none;opacity:0.09}.footer-blob-1{width:350px;height:350px;background:#7c5cbf;top:-80px;left:-60px}.footer-blob-2{width:250px;height:250px;background:#c9a84c;bottom:-60px;right:10%}.footer-inner-wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:3.5rem 2rem 0}.footer-grid{display:grid;grid-template-columns:1.8fr 1fr 1fr;gap:3rem;padding-bottom:3rem}.footer-brand-logo{font-family:'Cinzel',serif;font-size:1.5rem;color:#c9a84c;letter-spacing:3px;text-decoration:none;display:inline-block;margin-bottom:0.6rem}.footer-brand-logo span{color:#a07de0}.footer-brand-tagline{font-family:'Cinzel',serif;font-size:0.62rem;letter-spacing:3.5px;text-transform:uppercase;color:rgba(201,168,76,0.5);margin-bottom:1rem}.footer-brand-divider{width:48px;height:1px;background:linear-gradient(90deg,#c9a84c,transparent);margin-bottom:1rem}.footer-brand-desc{color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.8;max-width:270px}.footer-col-title{font-family:'Cinzel',serif;font-size:0.7rem;letter-spacing:3px;text-transform:uppercase;color:#c9a84c;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem}.footer-col-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(201,168,76,0.3),transparent)}.footer-nav-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem}.footer-nav-list li a{color:rgba(155,142,196,0.7);text-decoration:none;font-size:0.88rem;transition:color 0.2s,padding-left 0.2s;display:inline-block}.footer-nav-list li a:hover{color:#c9a84c;padding-left:6px}.footer-contact-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem}.footer-contact-list li{display:flex;align-items:flex-start;gap:0.6rem;color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.5}.footer-contact-list li .contact-icon{font-size:0.9rem;margin-top:1px;flex-shrink:0}.footer-bottom-bar{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:1.25rem 2rem 1.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;border-top:1px solid rgba(201,168,76,0.08)}.footer-copy{color:rgba(155,142,196,0.4);font-size:0.76rem}.footer-copy b{color:rgba(201,168,76,0.6);font-weight:400}.footer-tagline-text{color:rgba(155,142,196,0.25);font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;font-family:'Cinzel',serif}@media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:2rem}.footer-bottom-bar{justify-content:center;text-align:center}}
  </style>
  <link rel="stylesheet" href="/mysticmind/assets/css/theme.css?v=<?= @filemtime(__DIR__ . '/assets/css/theme.css') ?>">
</head>
<body>
<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/home">Home</a>
      <a href="/mysticmind/dashboard">Dashboard</a>
      <a href="/mysticmind/reading">New Reading</a>
      <a href="/mysticmind/history" class="active">History</a>
      <a href="/mysticmind/journal">Journal</a>
      <a href="/mysticmind/logout" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="page-title">📜 Reading History</h1>
  <p class="page-subtitle">Revisit your past readings and track your growth</p>

  <?php if ($viewReading): ?>
  <!-- Single reading detail view -->
  <div style="margin-bottom:1.5rem">
    <a href="/mysticmind/history" class="btn btn-outline btn-sm">← Back to History</a>
  </div>
  <div class="panel">
    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem">
      <h3 style="font-family:'Cinzel',serif;color:var(--accent)">
        <?= $spreadLabels[$viewReading['spread_type']] ?? $viewReading['spread_type'] ?>
      </h3>
      <span class="spread-badge <?= $spreadClass[$viewReading['spread_type']] ?? '' ?>">
        <?= $spreadLabels[$viewReading['spread_type']] ?? '' ?>
      </span>
      <span class="sentiment-badge sentiment-<?= $viewReading['sentiment'] ?>">
        <?= getSentimentLabel($viewReading['sentiment']) ?>
      </span>
      <span style="color:var(--text-muted);font-size:0.85rem;margin-left:auto">
        <?= date('F d, Y g:i A', strtotime($viewReading['created_at'])) ?>
      </span>
    </div>

    <?php if ($viewReading['user_question']): ?>
    <div class="interpretation-section" style="margin-bottom:1.5rem">
      <h4>Your Question</h4>
      <p><?= htmlspecialchars($viewReading['user_question']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Cards drawn -->
    <?php
    $cards = json_decode($viewReading['cards_drawn'], true) ?? [];
    ?>
    <div class="cards-display">
      <?php foreach ($cards as $i => $card):
        $positions = ['single'=>[''],'three_card'=>['Past','Present','Future'],'decision'=>['Option A','Option B','Outcome']];
        $pos = $positions[$viewReading['spread_type']][$i] ?? '';
      ?>
      <div class="tarot-card">
        <?php if ($pos): ?><div class="card-position"><?= htmlspecialchars($pos) ?></div><?php endif; ?>
        <?php
          $imgFile = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $card['name'])) . '.jpg';
          $imgFile = preg_replace('/-+/', '-', trim($imgFile, '-'));
          $rotateStyle = !empty($card['reversed']) ? 'transform:rotate(180deg)' : '';
        ?>
        <div class="card-image-wrap">
          <img class="card-image" src="/mysticmind/assets/images/tarot/<?= htmlspecialchars($imgFile) ?>"
               alt="<?= htmlspecialchars($card['name']) ?>" style="<?= $rotateStyle ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="card-placeholder" style="display:none">
            <div class="card-placeholder-symbol"><?= htmlspecialchars($card['symbol'] ?? '🃏') ?></div>
            <div class="card-placeholder-label"><?= htmlspecialchars($card['name']) ?></div>
          </div>
        </div>
        <div class="card-name"><?= htmlspecialchars($card['name']) ?></div>
        <div class="card-suit"><?= htmlspecialchars($card['suit']) ?></div>
        <span class="card-orientation <?= $card['reversed'] ? 'reversed' : 'upright' ?>">
          <?= $card['reversed'] ? 'Reversed' : 'Upright' ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Interpretation -->
    <?php
    $interp = json_decode($viewReading['interpretation'], true) ?? [];
    if (is_string($viewReading['interpretation'])) {
        // Try to decode; if it's a plain string, show it
        $decoded = json_decode($viewReading['interpretation'], true);
        $interp  = is_array($decoded) ? $decoded : ['reading' => $viewReading['interpretation']];
    }
    ?>
    <?php if (isset($interp['reading'])): ?>
      <div class="interpretation-section"><h4>Interpretation</h4><p><?= htmlspecialchars($interp['reading']) ?></p></div>
    <?php endif; ?>
    <?php foreach (['past','present','future'] as $pos): ?>
      <?php if (isset($interp[$pos])): ?>
        <div class="interpretation-section"><h4><?= ucfirst($pos) ?></h4><p><?= htmlspecialchars($interp[$pos]) ?></p></div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php foreach (['option_a'=>'Option A','option_b'=>'Option B','outcome'=>'Overall Outcome'] as $key=>$label): ?>
      <?php if (isset($interp[$key])): ?>
        <div class="interpretation-section"><h4><?= $label ?></h4><p><?= htmlspecialchars($interp[$key]) ?></p></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!empty($interp['summary'])): ?>
    <div class="summary-box">
      <h4>✨ Key Message</h4>
      <p><?= htmlspecialchars($interp['summary']) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($interp['reflection_prompts'])): ?>
    <div class="reflection-prompts">
      <h4>🌙 Reflection Prompts</h4>
      <?php foreach ($interp['reflection_prompts'] as $p): ?>
        <div class="prompt-item"><?= htmlspecialchars($p) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <hr class="divider">
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
      <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $viewReading['id'] ?>, 'reading')">Delete Reading</button>
      <a href="/mysticmind/history" class="btn btn-outline btn-sm">← Back</a>
    </div>
  </div>

  <?php else: ?>
  <!-- History list -->
  <div class="search-bar">
    <input type="text" id="history-search" class="form-control" placeholder="🔍 Search by question or spread type...">
  </div>

  <div class="panel">
    <?php if (empty($readings)): ?>
      <div style="text-align:center;padding:3rem">
        <div style="font-size:3rem;margin-bottom:1rem">📜</div>
        <p style="color:var(--text-muted);margin-bottom:1.5rem">No readings yet. Start your first one!</p>
        <a href="/mysticmind/reading" class="btn btn-gold">Begin a Reading</a>
      </div>
    <?php else: ?>
    <table class="history-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Spread</th>
          <th>Question</th>
          <th>Mood</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="history-tbody">
        <?php foreach ($readings as $r): ?>
        <tr>
          <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
          <td><span class="spread-badge <?= $spreadClass[$r['spread_type']] ?? '' ?>"><?= $spreadLabels[$r['spread_type']] ?? $r['spread_type'] ?></span></td>
          <td><?= htmlspecialchars(mb_strimwidth($r['user_question'] ?? '—', 0, 60, '…')) ?></td>
          <td><span class="sentiment-badge sentiment-<?= $r['sentiment'] ?>"><?= getSentimentLabel($r['sentiment']) ?></span></td>
          <td style="white-space:nowrap">
            <a href="/mysticmind/history?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $r['id'] ?>, 'reading')">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<script src="/mysticmind/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
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
