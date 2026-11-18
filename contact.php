<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <?= csrf_meta() ?>
  <link rel="icon" href="/mysticmind/favicon.ico?v=5" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="/mysticmind/assets/images/favicon-32.png?v=5">
  <link rel="apple-touch-icon" href="/mysticmind/assets/images/apple-touch-icon.png?v=5">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .contact-hero {
      text-align: center;
      padding: 5rem 1.5rem 3rem;
    }
    .contact-label {
      font-family: 'Cinzel', serif;
      font-size: 0.75rem;
      letter-spacing: 4px;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 1rem;
    }
    .contact-title {
      font-family: 'Cinzel', serif;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
      color: var(--accent);
      margin-bottom: 1.5rem;
    }
    .contact-divider {
      width: 60px;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      margin: 0 auto 2rem;
    }
    .contact-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      max-width: 900px;
      margin: 0 auto 4rem;
    }
    .contact-info {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .contact-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
    }
    .contact-item-icon {
      font-size: 1.3rem;
      color: var(--accent);
      flex-shrink: 0;
      margin-top: 0.1rem;
    }
    .contact-item-text h4 {
      font-family: 'Cinzel', serif;
      font-size: 0.82rem;
      color: var(--accent);
      margin-bottom: 0.3rem;
      letter-spacing: 1px;
    }
    .contact-item-text p {
      color: var(--text-muted);
      font-size: 0.88rem;
      line-height: 1.6;
    }
    @media (max-width: 640px) {
      .contact-grid { grid-template-columns: 1fr; }
    }

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
<script src="/mysticmind/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/">Home</a>
      <a href="/mysticmind/about.php">About</a>
      <a href="/mysticmind/contact.php" class="active">Contact</a>
      <?php if (isLoggedIn()): ?>
        <a href="/mysticmind/home.php" class="btn-nav">My Space</a>
      <?php else: ?>
        <a href="/mysticmind/login.php" class="btn-nav">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container">
  <div class="contact-hero">
    <p class="contact-label">Contact</p>
    <h1 class="contact-title">Book a Reading</h1>
    <div class="contact-divider"></div>
    <p style="color:rgba(232,224,240,0.7);font-size:1.05rem;line-height:1.9;max-width:580px;margin:0 auto 3rem">
      Interested in a face-to-face tarot reading? Reach out and we'll arrange a personal session tailored to your questions and intentions.
    </p>
  </div>

  <div class="contact-grid">
    <div class="contact-info">
      <div class="contact-item">
        <div class="contact-item-icon">✉</div>
        <div class="contact-item-text">
          <h4>Email</h4>
          <p>mysticmind@email.com</p>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-item-icon">📍</div>
        <div class="contact-item-text">
          <h4>Location</h4>
          <p>Paliton Beach, San Juan, Siquijor, Central Visayas, Philippines.</p>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-item-icon">🕐</div>
        <div class="contact-item-text">
          <h4>Availability</h4>
          <p>Sessions by appointment · Monday to Saturday</p>
        </div>
      </div>
    </div>

    <div class="panel">
      <h3 style="font-family:'Cinzel',serif;color:var(--accent);margin-bottom:1.5rem;font-size:1rem">Send a Message</h3>
      <form>
        <div class="form-group">
          <label for="name">Your Name</label>
          <input type="text" id="name" class="form-control" placeholder="e.g. Maria Santos">
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" class="form-control" placeholder="you@example.com">
        </div>
        <div class="form-group">
          <label for="message">Message</label>
          <textarea id="message" class="form-control" rows="4" placeholder="Tell us what you're looking for — a general reading, a specific question, or just curious about a session..."></textarea>
        </div>
        <button type="submit" class="btn btn-gold btn-block">Send Message</button>
      </form>
    </div>
  </div>
</div>

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
          <li><a href="/mysticmind/about.php">About</a></li>
          <li><a href="/mysticmind/contact.php">Contact</a></li>
          <li><a href="/mysticmind/login.php">Sign In</a></li>
          <li><a href="/mysticmind/register.php">Register</a></li>
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
    <p class="footer-copy">© <?= date('Y') ?> <b>MysticMind</b> · All rights reserved</p>
    <p class="footer-tagline-text">Intelligent Tarot · Self-Reflection · Clarity</p>
  </div>
</footer>
</body>
</html>