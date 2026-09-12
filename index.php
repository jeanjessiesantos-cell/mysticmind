<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: /mysticmind/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MysticMind — Intelligent Tarot Reading</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css">
  <style>
    /* ── Landing page overrides ── */
    body { overflow-x: hidden; }

    .landing-bg {
      position: fixed;
      inset: 0;
      z-index: 0;
      background: url('/mysticmind/assets/images/landing-bg.jpg') center center / cover no-repeat;
    }
    .landing-bg::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(
        160deg,
        rgba(13,11,30,0.82) 0%,
        rgba(28,18,60,0.75) 40%,
        rgba(13,11,30,0.88) 100%
      );
    }

    .landing-wrap {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
      text-align: center;
    }

    .landing-logo {
      font-family: 'Cinzel', serif;
      font-size: clamp(2.8rem, 8vw, 5rem);
      color: var(--accent);
      letter-spacing: 6px;
      margin-bottom: 0.25rem;
      text-shadow: 0 0 40px rgba(201,168,76,0.4);
    }
    .landing-logo span { color: var(--purple-light); }

    .landing-tagline {
      font-family: 'Cinzel', serif;
      font-size: clamp(0.75rem, 2vw, 0.95rem);
      color: var(--text-muted);
      letter-spacing: 4px;
      text-transform: uppercase;
      margin-bottom: 1.5rem;
    }

    .landing-divider {
      width: 80px;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      margin: 0 auto 2rem;
    }

    .landing-desc {
      max-width: 480px;
      color: rgba(232,224,240,0.75);
      font-size: 1rem;
      line-height: 1.8;
      margin-bottom: 2.5rem;
    }

    .landing-btns {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 3rem;
    }
    .landing-btns .btn {
      min-width: 160px;
      justify-content: center;
      font-family: 'Cinzel', serif;
      letter-spacing: 1px;
      font-size: 0.9rem;
    }
    .btn-ghost {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(201,168,76,0.4);
      color: var(--accent);
      backdrop-filter: blur(6px);
    }
    .btn-ghost:hover {
      background: rgba(201,168,76,0.12);
      border-color: var(--accent);
      transform: translateY(-1px);
    }

    .landing-features {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      justify-content: center;
      max-width: 600px;
    }
    .feature-pill {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(201,168,76,0.15);
      border-radius: 30px;
      padding: 0.4rem 1rem;
      font-size: 0.78rem;
      color: var(--text-muted);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      pointer-events: none;
      z-index: 0;
      opacity: 0.18;
    }
    .orb-1 {
      width: 400px; height: 400px;
      background: var(--purple);
      top: -100px; left: -100px;
      animation: orbFloat 8s ease-in-out infinite;
    }
    .orb-2 {
      width: 300px; height: 300px;
      background: var(--accent);
      bottom: -80px; right: -80px;
      animation: orbFloat 10s ease-in-out infinite reverse;
    }
    @keyframes orbFloat {
      0%,100% { transform: translate(0,0); }
      50%      { transform: translate(30px, 20px); }
    }

    .landing-symbol {
      font-size: 3.5rem;
      margin-bottom: 1.25rem;
      animation: float 4s ease-in-out infinite;
      filter: drop-shadow(0 0 20px rgba(201,168,76,0.5));
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

    /* Top golden divider line with glow */
    .footer-glow-bar {
      width: 100%;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(201,168,76,0.6) 30%, rgba(160,125,224,0.6) 70%, transparent 100%);
      box-shadow: 0 0 18px 2px rgba(201,168,76,0.18), 0 0 32px 4px rgba(124,92,191,0.12);
    }

    /* Subtle star-dust noise overlay */
    .footer-noise {
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      background-size: 200px 200px;
      pointer-events: none;
      opacity: 0.35;
    }

    /* Ambient glow blobs inside footer */
    .footer-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(70px);
      pointer-events: none;
      opacity: 0.09;
    }
    .footer-blob-1 {
      width: 350px; height: 350px;
      background: #7c5cbf;
      top: -80px; left: -60px;
    }
    .footer-blob-2 {
      width: 250px; height: 250px;
      background: #c9a84c;
      bottom: -60px; right: 10%;
    }

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

    /* Brand column */
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

    /* Nav & Contact columns */
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
    .footer-nav-list li a:hover {
      color: #c9a84c;
      padding-left: 6px;
    }

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
    .footer-contact-list li .contact-icon {
      font-size: 0.9rem;
      margin-top: 1px;
      flex-shrink: 0;
    }

    /* Bottom bar */
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

    .footer-copy {
      color: rgba(155,142,196,0.4);
      font-size: 0.76rem;
      letter-spacing: 0.3px;
    }
    .footer-copy b {
      color: rgba(201,168,76,0.6);
      font-weight: 400;
    }

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
</head>
<body>

<div class="landing-bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<script src="/mysticmind/assets/js/main.js"></script>

<nav>
  <div class="nav-inner">
    <a href="/mysticmind/" class="nav-logo">Mystic<span>Mind</span></a>
    <div class="nav-links">
      <a href="/mysticmind/" class="active">Home</a>
      <a href="/mysticmind/about.php">About</a>
      <a href="/mysticmind/contact.php">Contact</a>
      <a href="/mysticmind/login.php" class="btn-nav">Sign In</a>
    </div>
  </div>
</nav>

<div class="landing-wrap" id="home">

  <div class="landing-symbol">
    <img src="/mysticmind/assets/images/logofinalnatalagamm.png" alt="MysticMind Logo" style="width:100px;height:100px;object-fit:contain;filter:drop-shadow(0 0 20px rgba(201,168,76,0.5))">
  </div>

  <h1 class="landing-logo">Mystic<span>Mind</span></h1>
  <p class="landing-tagline">Intelligent Tarot · Self-Reflection · Clarity</p>

  <div class="landing-divider"></div>

  <p class="landing-desc">
    A tarot reading system that listens, interprets, and guides.
    Discover what the cards reveal about your path, your choices, and your inner world.
  </p>

  <div class="landing-btns">
    <a href="/mysticmind/register.php" class="btn btn-gold">✨ Begin Your Journey</a>
    <a href="/mysticmind/login.php" class="btn btn-ghost">Sign In</a>
  </div>

</div>

<footer class="site-footer">

  <!-- Glow bar at top -->
  <div class="footer-glow-bar"></div>

  <!-- Ambient blobs -->
  <div class="footer-blob footer-blob-1"></div>
  <div class="footer-blob footer-blob-2"></div>

  <!-- Noise texture -->
  <div class="footer-noise"></div>

  <!-- Main grid -->
  <div class="footer-inner-wrap">
    <div class="footer-grid">

      <!-- Brand -->
      <div>
        <a href="/mysticmind/" class="footer-brand-logo">Mystic<span>Mind</span></a>
        <p class="footer-brand-tagline">Intelligent Tarot · Self-Reflection · Clarity</p>
        <div class="footer-brand-divider"></div>
        <p class="footer-brand-desc">An intelligent tarot reading system blending ancient wisdom with modern AI. Discover clarity, reflect deeply, and let the cards guide your path.</p>
      </div>

      <!-- Navigate -->
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

      <!-- Contact -->
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

  <!-- Bottom bar -->
  <div class="footer-bottom-bar">
    <p class="footer-copy">© <?= date('Y') ?> <b>MysticMind</b> · All rights reserved</p>
    <p class="footer-tagline-text">Intelligent Tarot · Self-Reflection · Clarity</p>
  </div>

</footer>

</body>
</html>