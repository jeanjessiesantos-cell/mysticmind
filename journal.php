<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/sentiment.php';
requireLogin();

$user = getCurrentUser();
$db   = getDB();

$error   = '';
$success = '';

// Handle new journal entry (standalone)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_text'])) {
    $text = trim($_POST['entry_text'] ?? '');
    if (strlen($text) < 5) {
        $error = 'Entry is too short.';
    } else {
        $text      = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $sentiment = analyzeSentiment($text);
        $stmt = $db->prepare('INSERT INTO journal_entries (user_id, entry_text, sentiment) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user['id'], $text, $sentiment);
        if ($stmt->execute()) {
            // Update user preferences
            $upd = $db->prepare('UPDATE user_preferences SET last_sentiment = ? WHERE user_id = ?');
            $upd->bind_param('si', $sentiment, $user['id']);
            $upd->execute();
            $success = 'Journal entry saved.';
        } else {
            $error = 'Failed to save entry.';
        }
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'], $_POST['edit_text'])) {
    $editId   = (int)$_POST['edit_id'];
    $editText = htmlspecialchars(trim($_POST['edit_text']), ENT_QUOTES, 'UTF-8');
    if (strlen($editText) >= 5) {
        $sentiment = analyzeSentiment($editText);
        $upd = $db->prepare('UPDATE journal_entries SET entry_text = ?, sentiment = ? WHERE id = ? AND user_id = ?');
        $upd->bind_param('ssii', $editText, $sentiment, $editId, $user['id']);
        $upd->execute();
        $success = 'Entry updated.';
    }
}

// Fetch entries
$q = $db->prepare('SELECT je.*, r.spread_type FROM journal_entries je LEFT JOIN readings r ON je.reading_id = r.id WHERE je.user_id = ? ORDER BY je.created_at DESC');
$q->bind_param('i', $user['id']);
$q->execute();
$entries = $q->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Journal — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css">
  <style>
    .site-footer{position:relative;z-index:10;background:#0a0818;border-top:none;padding:0;margin-top:4rem;overflow:hidden}.footer-glow-bar{width:100%;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(201,168,76,0.6) 30%,rgba(160,125,224,0.6) 70%,transparent 100%);box-shadow:0 0 18px 2px rgba(201,168,76,0.18),0 0 32px 4px rgba(124,92,191,0.12)}.footer-noise{position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");background-size:200px 200px;pointer-events:none;opacity:0.35}.footer-blob{position:absolute;border-radius:50%;filter:blur(70px);pointer-events:none;opacity:0.09}.footer-blob-1{width:350px;height:350px;background:#7c5cbf;top:-80px;left:-60px}.footer-blob-2{width:250px;height:250px;background:#c9a84c;bottom:-60px;right:10%}.footer-inner-wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:3.5rem 2rem 0}.footer-grid{display:grid;grid-template-columns:1.8fr 1fr 1fr;gap:3rem;padding-bottom:3rem}.footer-brand-logo{font-family:'Cinzel',serif;font-size:1.5rem;color:#c9a84c;letter-spacing:3px;text-decoration:none;display:inline-block;margin-bottom:0.6rem}.footer-brand-logo span{color:#a07de0}.footer-brand-tagline{font-family:'Cinzel',serif;font-size:0.62rem;letter-spacing:3.5px;text-transform:uppercase;color:rgba(201,168,76,0.5);margin-bottom:1rem}.footer-brand-divider{width:48px;height:1px;background:linear-gradient(90deg,#c9a84c,transparent);margin-bottom:1rem}.footer-brand-desc{color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.8;max-width:270px}.footer-col-title{font-family:'Cinzel',serif;font-size:0.7rem;letter-spacing:3px;text-transform:uppercase;color:#c9a84c;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem}.footer-col-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(201,168,76,0.3),transparent)}.footer-nav-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem}.footer-nav-list li a{color:rgba(155,142,196,0.7);text-decoration:none;font-size:0.88rem;transition:color 0.2s,padding-left 0.2s;display:inline-block}.footer-nav-list li a:hover{color:#c9a84c;padding-left:6px}.footer-contact-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem}.footer-contact-list li{display:flex;align-items:flex-start;gap:0.6rem;color:rgba(155,142,196,0.7);font-size:0.85rem;line-height:1.5}.footer-contact-list li .contact-icon{font-size:0.9rem;margin-top:1px;flex-shrink:0}.footer-bottom-bar{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:1.25rem 2rem 1.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;border-top:1px solid rgba(201,168,76,0.08)}.footer-copy{color:rgba(155,142,196,0.4);font-size:0.76rem}.footer-copy b{color:rgba(201,168,76,0.6);font-weight:400}.footer-tagline-text{color:rgba(155,142,196,0.25);font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;font-family:'Cinzel',serif}@media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:2rem}.footer-bottom-bar{justify-content:center;text-align:center}}
  </style>
</head>
<body>
<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/home.php">Home</a>
      <a href="/mysticmind/dashboard.php">Dashboard</a>
      <a href="/mysticmind/reading.php">New Reading</a>
      <a href="/mysticmind/history.php">History</a>
      <a href="/mysticmind/journal.php" class="active">Journal</a>
      <a href="/mysticmind/logout.php" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="page-title">📝 My Journal</h1>
  <p class="page-subtitle">A private space for your reflections and insights</p>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- New entry form -->
  <div class="panel" style="margin-bottom:2rem">
    <h3 style="font-family:'Cinzel',serif;color:var(--accent);margin-bottom:1rem">New Entry</h3>
    <form method="POST" action="">
      <div class="form-group">
        <textarea name="entry_text" class="form-control" rows="5"
          placeholder="What's on your mind? Write freely — this is your space for reflection..."></textarea>
      </div>
      <button type="submit" class="btn btn-gold">Add Journal</button>
    </form>
  </div>

  <!-- Entries list -->
  <?php if (empty($entries)): ?>
    <div class="panel" style="text-align:center;padding:3rem">
      <div style="font-size:3rem;margin-bottom:1rem">📝</div>
      <p style="color:var(--text-muted)">No journal entries yet. Start writing your reflections above.</p>
    </div>
  <?php else: ?>
  <div class="search-bar">
    <input type="text" id="journal-search" class="form-control" placeholder="🔍 Search by entry text or mood...">
  </div>

  <div class="panel">
    <table class="history-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Entry</th>
          <th>Mood</th>
          <th>Linked Reading</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="journal-tbody">
        <?php foreach ($entries as $entry): ?>
        <tr id="row-<?= $entry['id'] ?>">
          <td style="white-space:nowrap"><?= date('M d, Y', strtotime($entry['created_at'])) ?></td>
          <td>
            <span id="text-<?= $entry['id'] ?>"><?= htmlspecialchars(mb_strimwidth($entry['entry_text'], 0, 80, '…')) ?></span>
            <form method="POST" id="edit-form-<?= $entry['id'] ?>" style="display:none;margin-top:0.5rem">
              <input type="hidden" name="edit_id" value="<?= $entry['id'] ?>">
              <textarea name="edit_text" class="form-control" rows="3"><?= htmlspecialchars($entry['entry_text']) ?></textarea>
              <div style="display:flex;gap:0.5rem;margin-top:0.4rem">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleEdit(<?= $entry['id'] ?>)">Cancel</button>
              </div>
            </form>
          </td>
          <td><span class="sentiment-badge sentiment-<?= $entry['sentiment'] ?>"><?= getSentimentLabel($entry['sentiment']) ?></span></td>
          <td>
            <?php if ($entry['reading_id']): ?>
              <a href="/mysticmind/history.php?view=<?= $entry['reading_id'] ?>" class="btn btn-outline btn-sm">🔮 View</a>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:0.82rem">—</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit(<?= $entry['id'] ?>)">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $entry['id'] ?>, 'journal')">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script src="/mysticmind/assets/js/main.js"></script>
<script>
function toggleEdit(id) {
  const form = document.getElementById('edit-form-' + id);
  const text = document.getElementById('text-' + id);
  if (form.style.display === 'none') {
    form.style.display = 'block';
    text.style.display = 'none';
  } else {
    form.style.display = 'none';
    text.style.display = 'inline';
  }
}

const searchInput = document.getElementById('journal-search');
if (searchInput) {
  searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#journal-tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
</script>
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
