<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$user = getCurrentUser();
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
  <title>Ask the Oracle — MysticMind</title>
  <link rel="stylesheet" href="/mysticmind/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
      <a href="/mysticmind/chat" class="active">Ask the Oracle</a>
      <a href="/mysticmind/history">History</a>
      <a href="/mysticmind/journal">Journal</a>
      <a href="/mysticmind/logout" class="btn-nav">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="page-title">🔯 Ask the Oracle</h1>
  <p class="page-subtitle">Share what's on your mind — the Oracle will guide you to your cards</p>

  <div class="chat-layout">
    <!-- Chat window -->
    <div class="chat-panel panel">
      <div class="chat-messages" id="chat-messages">
        <div class="chat-msg ai">
          <div class="chat-avatar"><img src="/mysticmind/assets/images/logofinalnatalagamm.png" alt="Oracle" style="width:32px;height:32px;object-fit:contain"></div>
          <div class="chat-bubble">
            Greetings, <?= htmlspecialchars($user['username']) ?>. I am the Oracle of MysticMind.<br><br>
            Tell me what weighs on your heart, what question stirs within you, or what area of your life you seek clarity on. I am here to listen.
          </div>
        </div>
      </div>

      <div class="chat-input-row" id="chat-input-row">
        <textarea id="chat-input" class="form-control chat-textarea"
          placeholder="Type your question or share what's on your mind..."
          rows="2"
          onkeydown="handleChatKey(event)"></textarea>
        <button class="btn btn-gold chat-send-btn" onclick="sendMessage()">Send</button>
      </div>
    </div>

    <!-- Card picker (hidden until AI triggers it) -->
    <div id="chat-card-area"></div>
  </div>
</div>

<script src="/mysticmind/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script src="/mysticmind/assets/js/chat.js"></script>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <a href="/mysticmind/" class="footer-logo">Mystic<span>Mind</span></a>
      <p>An intelligent tarot reading system blending ancient wisdom with modern AI. Discover clarity, reflect deeply, and let the cards guide your path.</p>
    </div>
    <div class="footer-col">
      <h4>Navigate</h4>
      <ul>
        <li><a href="/mysticmind/home">Home</a></li>
        <li><a href="/mysticmind/dashboard">Dashboard</a></li>
        <li><a href="/mysticmind/reading">New Reading</a></li>
        <li><a href="/mysticmind/history">History</a></li>
        <li><a href="/mysticmind/journal">Journal</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <ul>
        <li><span>✉ mysticmind@email.com</span></li>
        <li><span>📍 In-person · by appointment</span></li>
        <li><span>🕐 Mon – Sat</span></li>
      </ul>
    </div>
  </div>
  <?php include __DIR__ . '/includes/disclaimer.php'; ?>
  <div class="footer-bottom">
    <p class="footer-copy">© <?= date('Y') ?> <b>MysticMind</b> · All rights reserved · <span class="footer-dev">Developed by <em>Jessie Jean Santos</em></span></p>
    <p class="footer-tagline">Intelligent Tarot · Self-Reflection · Clarity</p>
  </div>
</footer>
</body>
</html>
