<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) { header('Location: /mysticmind/dashboard'); exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    $errors[] = 'Your session expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be 3–50 characters.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, and underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $errors[] = 'Password must contain at least one special character (e.g. !@#$%).';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $ins->bind_param('sss', $username, $email, $hash);
            if ($ins->execute()) {
                $userId = $ins->insert_id;
                $pref = $db->prepare('INSERT INTO user_preferences (user_id) VALUES (?)');
                $pref->bind_param('i', $userId);
                $pref->execute();
                $success = 'Account created! You can now log in.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
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
  <title>Register — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .auth-back {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: var(--text-muted);
      font-size: 0.82rem;
      text-decoration: none;
      margin-bottom: 1.25rem;
      transition: color 0.2s;
    }
    .auth-back:hover { color: var(--accent); }
    .auth-back svg { width: 14px; height: 14px; }

    /* ── Aesthetic Footer ── */
    .site-footer {
      position: relative;
      z-index: 10;
      background: #0a0818;
      border-top: none;
      padding: 0;
      margin-top: 4rem;
      overflow: hidden;
    }
    .footer-glow-bar {
      width: 100%;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(201,168,76,0.6) 30%, rgba(160,125,224,0.6) 70%, transparent 100%);
      box-shadow: 0 0 18px 2px rgba(201,168,76,0.18), 0 0 32px 4px rgba(124,92,191,0.12);
    }
    .footer-noise {
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      background-size: 200px 200px;
      pointer-events: none;
      opacity: 0.35;
    }
    .footer-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(70px);
      pointer-events: none;
      opacity: 0.09;
    }
    .footer-blob-1 { width: 350px; height: 350px; background: #7c5cbf; top: -80px; left: -60px; }
    .footer-blob-2 { width: 250px; height: 250px; background: #c9a84c; bottom: -60px; right: 10%; }
    .footer-inner-wrap {
      position: relative;
      z-index: 1;
      max-width: 1100px;
      margin: 0 auto;
      padding: 3.5rem 2rem 0;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 1.8fr 1fr 1fr;
      gap: 3rem;
      padding-bottom: 3rem;
    }
    .footer-brand-logo {
      font-family: 'Cinzel', serif;
      font-size: 1.5rem;
      color: #c9a84c;
      letter-spacing: 3px;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 0.6rem;
    }
    .footer-brand-logo span { color: #a07de0; }
    .footer-brand-tagline {
      font-family: 'Cinzel', serif;
      font-size: 0.62rem;
      letter-spacing: 3.5px;
      text-transform: uppercase;
      color: rgba(201,168,76,0.5);
      margin-bottom: 1rem;
    }
    .footer-brand-divider {
      width: 48px;
      height: 1px;
      background: linear-gradient(90deg, #c9a84c, transparent);
      margin-bottom: 1rem;
    }
    .footer-brand-desc {
      color: rgba(155,142,196,0.7);
      font-size: 0.85rem;
      line-height: 1.8;
      max-width: 270px;
    }
    .footer-col-title {
      font-family: 'Cinzel', serif;
      font-size: 0.7rem;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: #c9a84c;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .footer-col-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: linear-gradient(90deg, rgba(201,168,76,0.3), transparent);
    }
    .footer-nav-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }
    .footer-nav-list li a {
      color: rgba(155,142,196,0.7);
      text-decoration: none;
      font-size: 0.88rem;
      transition: color 0.2s, padding-left 0.2s;
      display: inline-block;
    }
    .footer-nav-list li a:hover { color: #c9a84c; padding-left: 6px; }
    .footer-contact-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .footer-contact-list li {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      color: rgba(155,142,196,0.7);
      font-size: 0.85rem;
      line-height: 1.5;
    }
    .footer-contact-list li .contact-icon { font-size: 0.9rem; margin-top: 1px; flex-shrink: 0; }
    .footer-bottom-bar {
      position: relative;
      z-index: 1;
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.25rem 2rem 1.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
      border-top: 1px solid rgba(201,168,76,0.08);
    }
    .footer-copy { color: rgba(155,142,196,0.4); font-size: 0.76rem; }
    .footer-copy b { color: rgba(201,168,76,0.6); font-weight: 400; }
    .footer-tagline-text {
      color: rgba(155,142,196,0.25);
      font-size: 0.72rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      font-family: 'Cinzel', serif;
    }
    @media (max-width: 768px) {
      .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
      .footer-bottom-bar { justify-content: center; text-align: center; }
    }
  </style>
  <link rel="stylesheet" href="/mysticmind/assets/css/theme.css?v=<?= @filemtime(__DIR__ . '/assets/css/theme.css') ?>">
</head>
<body>
<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/login">Login</a>
      <a href="/mysticmind/register" class="btn-nav active">Register</a>
    </div>
  </div>
</nav>

<div class="auth-wrap">
  <div class="auth-box">

    <a href="/mysticmind/" class="auth-back">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
      Back
    </a>

    <div class="auth-logo">
      <img src="/mysticmind/assets/images/logofinalnatalagamm.png" alt="MysticMind Logo" style="width:64px;height:64px;object-fit:contain;filter:drop-shadow(0 0 12px rgba(201,168,76,0.4))">
      MysticMind
    </div>
    <div class="auth-tagline">Begin your journey of self-discovery</div>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control"
               placeholder="mystic_seeker" maxlength="50"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <span id="username-status" style="font-size:0.8rem;margin-top:0.25rem;display:block"></span>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div style="position:relative">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Min 8 chars, upper, lower, number, symbol" required style="padding-right:2.5rem">
          <button type="button" onclick="togglePw('password', this)" aria-label="Toggle password visibility"
                  style="position:absolute;right:0.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;display:flex;align-items:center">
            <svg id="icon-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.563-4.318M6.53 6.53A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.293 5.411M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/>
            </svg>
          </button>
        </div>
        <div id="strength-bar-wrap" style="margin-top:0.5rem;display:none">
          <div style="height:5px;border-radius:4px;background:var(--bg-panel);overflow:hidden">
            <div id="strength-bar" style="height:100%;width:0;border-radius:4px;transition:width 0.3s,background 0.3s"></div>
          </div>
          <span id="strength-label" style="font-size:0.78rem;margin-top:0.3rem;display:block"></span>
        </div>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <div style="position:relative">
          <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                 placeholder="Repeat your password" required style="padding-right:2.5rem">
          <button type="button" onclick="togglePw('confirm_password', this)" aria-label="Toggle password visibility"
                  style="position:absolute;right:0.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;display:flex;align-items:center">
            <svg id="icon-confirm_password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.563-4.318M6.53 6.53A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.293 5.411M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/>
            </svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-gold btn-block">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="/mysticmind/login">Sign in</a>
    </div>
  </div>
</div>

<script src="/mysticmind/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script>
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById('icon-' + inputId);
  const show  = input.type === 'password';
  input.type  = show ? 'text' : 'password';
  icon.innerHTML = show
    ? `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`
    : `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.563-4.318M6.53 6.53A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.293 5.411M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/>`;
}
(function () {
  const input  = document.getElementById('password');
  const bar    = document.getElementById('strength-bar');
  const label  = document.getElementById('strength-label');
  const wrap   = document.getElementById('strength-bar-wrap');
  const levels = [
    { label: 'Very Weak',   color: '#e74c3c', width: '20%' },
    { label: 'Weak',        color: '#e67e22', width: '40%' },
    { label: 'Fair',        color: '#f1c40f', width: '60%' },
    { label: 'Strong',      color: '#2ecc71', width: '80%' },
    { label: 'Very Strong', color: '#27ae60', width: '100%' },
  ];
  function score(pw) {
    let s = 0;
    if (pw.length >= 8)   s++;
    if (/[A-Z]/.test(pw)) s++;
    if (/[a-z]/.test(pw)) s++;
    if (/[0-9]/.test(pw)) s++;
    if (/[\W_]/.test(pw)) s++;
    return s - 1;
  }
  input.addEventListener('input', function () {
    const pw = this.value;
    if (!pw) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    const idx = Math.max(0, score(pw));
    bar.style.width      = levels[idx].width;
    bar.style.background = levels[idx].color;
    label.style.color    = levels[idx].color;
    label.textContent    = levels[idx].label;
  });
})();
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
          <li><a href="/mysticmind/">Home</a></li>
          <li><a href="/mysticmind/about">About</a></li>
          <li><a href="/mysticmind/contact">Contact</a></li>
          <li><a href="/mysticmind/login">Sign In</a></li>
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