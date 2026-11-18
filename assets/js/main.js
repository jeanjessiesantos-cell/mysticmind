// ===== MysticMind Main JS =====

// ===== CSRF protection =====
// Every page has <meta name="csrf-token">. This adds the token as an
// X-CSRF-Token header to every same-site POST made with fetch(),
// so existing and future AJAX calls are protected automatically.
(function attachCsrfToFetch() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta || !window.fetch) return;
  const token     = meta.content;
  const origFetch = window.fetch.bind(window);

  window.fetch = function (input, init = {}) {
    const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
    const url    = input instanceof Request ? input.url : String(input);
    const sameOrigin = new URL(url, location.href).origin === location.origin;

    if (sameOrigin && method !== 'GET' && method !== 'HEAD') {
      const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
      headers.set('X-CSRF-Token', token);
      init = Object.assign({}, init, { headers });
    }
    return origFetch(input, init);
  };
})();

// ===== Majestic background: stars, gold sparkles, candle glows, rising embers =====
(function createAmbience() {
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const rand = (a, b) => Math.random() * (b - a) + a;

  // Twinkling stars
  const stars = document.createElement('div');
  stars.className = 'stars';
  stars.setAttribute('aria-hidden', 'true');
  for (let i = 0; i < 90; i++) {
    const s = document.createElement('div');
    s.className = 'star';
    const size = rand(0.5, 2.6);
    s.style.cssText = `width:${size}px;height:${size}px;left:${rand(0,100)}%;top:${rand(0,100)}%;` +
      `--dur:${rand(2,6).toFixed(1)}s;--op:${rand(0.25,0.85).toFixed(2)};animation-delay:${rand(0,5).toFixed(1)}s;`;
    stars.appendChild(s);
  }

  // Four-point gold sparkles (bigger glints)
  for (let i = 0; i < 26; i++) {
    const g = document.createElement('div');
    g.className = 'glint';
    const size = rand(6, 16);
    g.style.cssText = `width:${size}px;height:${size}px;left:${rand(0,100)}%;top:${rand(0,100)}%;` +
      `--dur:${rand(3,7).toFixed(1)}s;animation-delay:${rand(0,6).toFixed(1)}s;`;
    stars.appendChild(g);
  }
  document.body.prepend(stars);

  // Soft candle-light glows (bokeh) at the edges of the screen
  const glow = document.createElement('div');
  glow.className = 'candle-glows';
  glow.setAttribute('aria-hidden', 'true');
  const spots = [[4,88,260],[94,82,300],[12,30,160],[88,22,180],[50,104,340],[70,94,140],[26,96,150]];
  spots.forEach(([x, y, size], i) => {
    const b = document.createElement('div');
    b.className = 'bokeh' + (i % 3 === 0 ? ' bokeh-violet' : '');
    b.style.cssText = `left:${x}%;top:${y}%;width:${size}px;height:${size}px;animation-delay:${(i*0.7).toFixed(1)}s;`;
    glow.appendChild(b);
  });
  document.body.prepend(glow);

  if (reduceMotion) return;

  // Golden embers / dust slowly rising like candle smoke
  const embers = document.createElement('div');
  embers.className = 'embers';
  embers.setAttribute('aria-hidden', 'true');
  for (let i = 0; i < 22; i++) {
    const e = document.createElement('span');
    const size = rand(1.5, 4);
    e.style.cssText = `left:${rand(0,100)}%;width:${size}px;height:${size}px;` +
      `--drift:${rand(-60,60).toFixed(0)}px;--dur:${rand(12,26).toFixed(1)}s;animation-delay:-${rand(0,26).toFixed(1)}s;`;
    embers.appendChild(e);
  }
  document.body.prepend(embers);
})();

// Keep emojis in their real colours inside gold-gradient headings
(function protectHeadingEmojis() {
  const sel = '.page-title, .welcome-text h2, .home-greeting, .about-title, .contact-title, .landing-logo, .auth-logo, .picker-title';
  const re  = /(\p{Extended_Pictographic}(?:️|‍\p{Extended_Pictographic})*)/gu;
  document.querySelectorAll(sel).forEach(el => {
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      if (!re.test(node.nodeValue)) return;
      re.lastIndex = 0;
      const span = document.createElement('span');
      span.innerHTML = node.nodeValue.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(re, '<span class="heading-emoji">$1</span>');
      node.replaceWith(...span.childNodes);
    });
  });
})();

// ===== Disclaimer pop-up =====
// Shows every time someone opens the site fresh (typing the URL, a bookmark, a new tab,
// coming from another website) and on every page RELOAD — even if they clicked
// "I Understand" before. It does NOT show when moving between MysticMind pages
// using the site's own links, buttons and forms.
function shouldShowDisclaimer() {
  const nav  = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
  const type = nav ? nav.type : (performance.navigation && performance.navigation.type === 1 ? 'reload' : 'navigate');
  if (type === 'reload') return true;
  if (type === 'back_forward') return false;
  let internal = false;
  try { internal = !!document.referrer && new URL(document.referrer).origin === location.origin; } catch (e) {}
  return !internal;
}

function initDisclaimerPopup() {
  if (!shouldShowDisclaimer()) return;
  const source = document.querySelector('.footer-disclaimer');
  if (!source || document.getElementById('disclaimer-modal')) return;

  const overlay = document.createElement('div');
  overlay.id = 'disclaimer-modal';
  overlay.className = 'disclaimer-overlay';
  overlay.innerHTML = `
    <div class="disclaimer-dialog" role="dialog" aria-modal="true" aria-labelledby="disclaimer-heading">
      <img src="/mysticmind/assets/images/logofinalnatalagamm.png" alt="" class="disclaimer-logo">
      <h2 id="disclaimer-heading" class="disclaimer-heading">Before You Begin</h2>
      <div class="disclaimer-body"></div>
      <button type="button" class="btn btn-gold disclaimer-ok">✦ I Understand</button>
    </div>`;
  // Re-use the footer text so the wording only lives in includes/disclaimer.php
  source.querySelectorAll('p:not(.footer-disclaimer-title)').forEach(p => {
    overlay.querySelector('.disclaimer-body').appendChild(p.cloneNode(true));
  });
  document.body.appendChild(overlay);
  document.body.classList.add('modal-open');
  requestAnimationFrame(() => overlay.classList.add('show'));

  const ok = overlay.querySelector('.disclaimer-ok');
  ok.focus();
  ok.addEventListener('click', () => {
    overlay.classList.remove('show');
    document.body.classList.remove('modal-open');
    setTimeout(() => overlay.remove(), 400);
  });
  // keep keyboard focus inside the dialog
  overlay.addEventListener('keydown', e => { if (e.key === 'Tab') { e.preventDefault(); ok.focus(); } });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDisclaimerPopup);
else initDisclaimerPopup();

// Sparkle burst on gold buttons
document.addEventListener('click', (ev) => {
  const btn = ev.target.closest('.btn-gold, .btn-primary');
  if (!btn || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
  for (let i = 0; i < 10; i++) {
    const s = document.createElement('span');
    s.className = 'sparkle-burst';
    const angle = (Math.PI * 2 * i) / 10;
    const dist  = 30 + Math.random() * 30;
    s.style.left = ev.clientX + 'px';
    s.style.top  = ev.clientY + 'px';
    s.style.setProperty('--tx', Math.cos(angle) * dist + 'px');
    s.style.setProperty('--ty', Math.sin(angle) * dist + 'px');
    document.body.appendChild(s);
    setTimeout(() => s.remove(), 700);
  }
});

// ===== Dashboard: Card of the Day (flip to reveal, remembered for today) =====
function initCardOfTheDay() {
  const card = document.getElementById('cotd-card');
  if (!card) return;
  const panel = card.closest('.cotd-panel');
  const key   = card.dataset.key;
  let seen = false;
  try { seen = localStorage.getItem(key) === '1'; } catch (e) {}
  if (seen) { card.classList.add('flipped'); panel?.classList.add('revealed'); }

  const reveal = () => {
    if (card.classList.contains('flipped')) return;
    card.classList.add('flipped');
    panel?.classList.add('revealed');
    try { localStorage.setItem(key, '1'); } catch (e) {}
  };
  card.addEventListener('click', reveal);
  card.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); reveal(); } });
}

// ===== Dashboard: Mood trend chart (Chart.js) =====
function initMoodChart(data) {
  const canvas = document.getElementById('moodChart');
  if (!canvas) return;
  if (typeof Chart === 'undefined') {
    canvas.parentElement.innerHTML = '<p class="empty-state">Connect to the internet to load the mood chart.</p>';
    return;
  }
  const ctx  = canvas.getContext('2d');
  const fill = ctx.createLinearGradient(0, 0, 0, 260);
  fill.addColorStop(0, 'rgba(232,197,106,0.35)');
  fill.addColorStop(1, 'rgba(155,109,214,0.02)');
  const moodName = v => v === null ? 'No entries' : v >= 0.34 ? 'Positive' : v <= -0.34 ? 'Needs support' : 'Neutral';

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [{
        data: data.values,
        borderColor: '#e8c56a',
        backgroundColor: fill,
        fill: true,
        tension: 0.35,
        spanGaps: true,
        borderWidth: 2.5,
        pointRadius: data.values.map(v => v === null ? 0 : 4),
        pointHoverRadius: 7,
        pointBackgroundColor: data.values.map(v => v >= 0.34 ? '#7ee0d6' : v <= -0.34 ? '#f0a35e' : '#c4a6f2'),
        pointBorderColor: '#1a0f2e',
        pointBorderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      layout: { padding: { left: 4, right: 8 } },
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(20,10,38,0.95)', borderColor: 'rgba(232,197,106,0.5)', borderWidth: 1,
          titleColor: '#f3d98b', bodyColor: '#efe6ff', padding: 10,
          callbacks: { label: c => ' ' + moodName(c.parsed.y) }
        }
      },
      scales: {
        y: {
          min: -1.2, max: 1.2,
          afterBuildTicks: axis => { axis.ticks = [-1, 0, 1].map(v => ({ value: v })); },
          ticks: { color: '#b6a3d9', padding: 6, font: { family: 'Lato', size: 11 },
                   callback: v => v === 1 ? 'Positive' : v === 0 ? 'Neutral' : v === -1 ? 'Low' : '' },
          grid: { color: 'rgba(232,197,106,0.08)' }, border: { display: false }
        },
        x: {
          ticks: { color: '#8f7fb5', maxTicksLimit: 7, maxRotation: 0, font: { size: 10 } },
          grid: { display: false }, border: { color: 'rgba(232,197,106,0.15)' }
        }
      }
    }
  });
}

// ===== Spread selector =====
function initSpreadSelector() {
  const options = document.querySelectorAll('.spread-option');
  const input   = document.getElementById('spread_type');
  if (!options.length || !input) return;

  options.forEach(opt => {
    opt.addEventListener('click', () => {
      options.forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      input.value = opt.dataset.spread;
      updateDecisionFields(opt.dataset.spread);
    });
  });

  if (options[0]) {
    options[0].classList.add('selected');
    input.value = options[0].dataset.spread;
  }
}

function updateDecisionFields(spread) {
  const decisionFields = document.getElementById('decision-fields');
  if (decisionFields) {
    decisionFields.style.display = spread === 'decision' ? 'block' : 'none';
  }
}

// ===== Reading form submit — shows card picker =====
function initReadingForm() {
  const form = document.getElementById('reading-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const spreadType = document.getElementById('spread_type').value;
    const cardCount  = spreadType === 'single' ? 1 : 3;
    const resultArea = document.getElementById('reading-result');

    // Store form data for later AJAX call
    resultArea.dataset.formPayload = JSON.stringify({
      spread_type:   spreadType,
      user_question: document.getElementById('user_question').value,
      option_a:      document.getElementById('option_a')?.value || '',
      option_b:      document.getElementById('option_b')?.value || '',
    });
    resultArea.dataset.cardCount = cardCount;

    renderCardPicker(cardCount, spreadType, resultArea);
    resultArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

// ===== Card Picker =====
function renderCardPicker(cardCount, spreadType, resultArea) {
  const posLabels = {
    single:     ['Your Card'],
    three_card: ['Past', 'Present', 'Future'],
    decision:   ['Option A', 'Option B', 'Outcome'],
  };
  const labels   = posLabels[spreadType];
  const deckSize = 78;

  let slots = '';
  for (let i = 0; i < cardCount; i++) {
    slots += `<div class="picker-slot" id="slot-${i}">
      <div class="slot-label">${labels[i]}</div>
      <div class="slot-empty">?</div>
    </div>`;
  }

  // Fisher-Yates shuffle of indices so card order is random every time
  const indices = Array.from({ length: deckSize }, (_, i) => i);
  for (let i = indices.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [indices[i], indices[j]] = [indices[j], indices[i]];
  }

  let deckCards = '';
  indices.forEach(idx => {
    deckCards += `<div class="facedown-card" data-index="${idx}" onclick="selectCard(this)">
      <div class="facedown-back"><div class="card-back-pattern"></div></div>
    </div>`;
  });

  resultArea.innerHTML = `
    <div class="panel picker-panel" id="card-picker-panel">
      <h3 class="picker-title">🌙 Choose Your ${cardCount === 1 ? 'Card' : cardCount + ' Cards'}</h3>
      <p class="picker-subtitle">Clear your mind, focus on your question, and select ${cardCount === 1 ? 'a card' : cardCount + ' cards'} from the deck below</p>
      <div class="deck-carousel">
        <button class="deck-arrow deck-arrow-left" onclick="scrollDeck(-1)" aria-label="Scroll left">&#8249;</button>
        <div class="facedown-deck" id="facedown-deck">${deckCards}</div>
        <button class="deck-arrow deck-arrow-right" onclick="scrollDeck(1)" aria-label="Scroll right">&#8250;</button>
      </div>
      <div class="picker-slots" id="picker-slots">${slots}</div>
      <div id="picker-confirm" style="text-align:center;margin-top:1.5rem;display:none">
        <p class="picker-confirm-hint">Your cards are chosen. Ready to see what they reveal?</p>
        <button class="btn btn-gold" onclick="confirmCardSelection(event)">✨ Reveal My Reading</button>
      </div>
    </div>`;

  // Infinite wheel: when scroll reaches near end, loop back silently
  const deck = document.getElementById('facedown-deck');
  deck.addEventListener('scroll', () => {
    const maxScroll = deck.scrollWidth - deck.clientWidth;
    if (deck.scrollLeft >= maxScroll - 10) {
      deck.scrollLeft = 1; // loop to start
    } else if (deck.scrollLeft <= 0) {
      deck.scrollLeft = maxScroll - 1; // loop to end
    }
  });
}

function scrollDeck(direction) {
  const deck      = document.getElementById('facedown-deck');
  const cardWidth = 80 + 12; // card width + gap
  const step      = cardWidth * 3; // scroll 3 cards at a time
  deck.scrollBy({ left: direction * step, behavior: 'smooth' });
}

function selectCard(el) {
  const resultArea = document.getElementById('reading-result');
  const maxCount   = parseInt(resultArea.dataset.cardCount);
  const deck       = document.getElementById('facedown-deck');
  const chosen     = [...deck.querySelectorAll('.facedown-card.chosen')];

  if (el.classList.contains('chosen')) {
    // Deselect: remove from its slot
    const slotIdx = parseInt(el.dataset.slotIndex);
    el.classList.remove('chosen');
    delete el.dataset.slotIndex;

    // Re-index remaining chosen cards
    const remaining = [...deck.querySelectorAll('.facedown-card.chosen')];
    remaining.forEach((card, i) => { card.dataset.slotIndex = i; });

    // Refresh all slot displays
    for (let i = 0; i < maxCount; i++) {
      const slot = document.getElementById(`slot-${i}`);
      if (!slot) continue;
      const filled = remaining[i];
      slot.querySelector('.slot-empty').style.display = filled ? 'none' : 'flex';
      const existing = slot.querySelector('.slot-chosen');
      if (existing) existing.remove();
      if (filled) {
        const badge = document.createElement('div');
        badge.className = 'slot-chosen';
        badge.textContent = '✦';
        slot.appendChild(badge);
      }
    }

    document.getElementById('picker-confirm').style.display =
      remaining.length >= maxCount ? 'block' : 'none';
    return;
  }

  if (chosen.length >= maxCount) return;

  const slotIdx = chosen.length;
  el.classList.add('chosen');
  el.dataset.slotIndex = slotIdx;

  const slot = document.getElementById(`slot-${slotIdx}`);
  if (slot) {
    slot.querySelector('.slot-empty').style.display = 'none';
    const badge = document.createElement('div');
    badge.className = 'slot-chosen';
    badge.textContent = '✦';
    slot.appendChild(badge);
  }

  if (chosen.length + 1 >= maxCount) {
    document.getElementById('picker-confirm').style.display = 'block';
  }
}

async function confirmCardSelection(e) {
  const btn        = e.target;
  const resultArea = document.getElementById('reading-result');
  const payload    = JSON.parse(resultArea.dataset.formPayload || '{}');

  btn.disabled  = true;
  btn.innerHTML = '<span class="spinner"></span> Reading the cards...';

  const fd = new FormData();
  Object.entries(payload).forEach(([k, v]) => fd.append(k, v));

  try {
    const res  = await fetch('/mysticmind/ajax/do_reading.php', { method: 'POST', body: fd });
    const json = await res.json();

    if (json.error) {
      resultArea.innerHTML = `<div class="alert alert-error">${escHtml(json.error)}</div>`;
    } else {
      renderReading(json, resultArea);
    }
  } catch {
    btn.disabled  = false;
    btn.innerHTML = '✨ Reveal My Reading';
    resultArea.querySelector('.alert-error')?.remove();
    resultArea.querySelector('.picker-panel')?.insertAdjacentHTML(
      'beforeend', `<div class="alert alert-error" style="margin-top:1rem">Something went wrong. Please try again.</div>`
    );
  }
}

// ===== Card image tag =====
function getCardImageTag(card) {
  const filename = card.name.toLowerCase().replace(/['\s]+/g, '-').replace(/[^a-z0-9-]/g, '') + '.jpg';
  const src      = `/mysticmind/assets/images/tarot/${filename}`;
  const symbol   = escHtml(card.symbol);
  const rotateStyle = card.reversed ? 'transform:rotate(180deg)' : '';

  return `<div class="card-image-wrap">
    <img class="card-image" src="${src}" alt="${escHtml(card.name)}"
      style="${rotateStyle}"
      onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="card-placeholder" style="display:none">
      <div class="card-placeholder-symbol">${symbol}</div>
      <div class="card-placeholder-label">${escHtml(card.name)}</div>
    </div>
  </div>`;
}

// ===== Render revealed reading =====
function renderReading(data, container) {
  const { cards, spread_type, interpretation, sentiment, reading_id } = data;

  let html = renderSupportBox(data.support);
  html += `<div class="panel" style="margin-top:2rem">`;
  html += `<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <h3 style="font-family:'Cinzel',serif;color:var(--accent);font-size:1.2rem">Your Reading</h3>
    <span class="sentiment-badge sentiment-${sentiment.value}">${sentiment.label}</span>
  </div>`;

  html += `<div class="cards-display">`;
  cards.forEach((card, i) => {
    const pos = getPositionLabel(spread_type, i);
    html += `<div class="tarot-card" style="animation-delay:${i * 0.25}s">
      ${pos ? `<div class="card-position">${pos}</div>` : ''}
      ${getCardImageTag(card)}
      <div class="card-name">${escHtml(card.name)}</div>
      <div class="card-suit">${escHtml(card.suit)}</div>
      <span class="card-orientation ${card.reversed ? 'reversed' : 'upright'}">${card.reversed ? 'Reversed' : 'Upright'}</span>
      <div style="margin-top:0.75rem;font-size:0.75rem;color:var(--text-muted);line-height:1.5">${escHtml(card.reversed ? card.reversed_meaning : card.upright_meaning)}</div>
    </div>`;
  });
  html += `</div>`;

  html += renderInterpretations(spread_type, interpretation);

  if (interpretation.summary) {
    html += `<div class="summary-box">
      <h4>✨ Key Message</h4>
      <p>${escHtml(interpretation.summary)}</p>
    </div>`;
  }

  if (interpretation.reflection_prompts?.length) {
    html += `<div class="reflection-prompts"><h4>🌙 Reflection Prompts</h4>`;
    interpretation.reflection_prompts.forEach(p => {
      html += `<div class="prompt-item">${escHtml(p)}</div>`;
    });
    html += `</div>`;
  }

  if (reading_id) {
    html += `<hr class="divider">
    <div>
      <h4 style="color:var(--teal);font-family:'Cinzel',serif;margin-bottom:0.75rem">📝 Journal Your Reflection</h4>
      <textarea class="form-control" id="journal-text" placeholder="Write your thoughts, feelings, or insights from this reading..." rows="4"></textarea>
      <button class="btn btn-outline" style="margin-top:0.75rem" onclick="saveJournal(${reading_id})">Save Journal Entry</button>
      <span id="journal-status" style="margin-left:1rem;font-size:0.85rem;color:var(--teal)"></span>
    </div>`;
  }

  html += `</div>`;
  container.innerHTML = html;
  container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderInterpretations(spreadType, interp) {
  let html = '';
  if (spreadType === 'single' && interp.reading) {
    html += `<div class="interpretation-section"><h4>Interpretation</h4><p>${escHtml(interp.reading)}</p></div>`;
  } else if (spreadType === 'three_card') {
    ['past','present','future'].forEach(pos => {
      if (interp[pos]) {
        html += `<div class="interpretation-section"><h4>${pos.charAt(0).toUpperCase()+pos.slice(1)}</h4><p>${escHtml(interp[pos])}</p></div>`;
      }
    });
  } else if (spreadType === 'decision') {
    ['option_a','option_b','outcome'].forEach(key => {
      if (interp[key]) {
        const label = key === 'option_a' ? 'Option A' : key === 'option_b' ? 'Option B' : 'Overall Outcome';
        html += `<div class="interpretation-section"><h4>${label}</h4><p>${escHtml(interp[key])}</p></div>`;
      }
    });
    if (interp.pros_cons) {
      html += `<div class="pros-cons-grid"><div class="pros-box"><h5>✅ Potential Pros</h5><ul>`;
      (interp.pros_cons.pros || []).forEach(p => { html += `<li>${escHtml(p)}</li>`; });
      html += `</ul></div><div class="cons-box"><h5>⚠️ Potential Cons</h5><ul>`;
      (interp.pros_cons.cons || []).forEach(c => { html += `<li>${escHtml(c)}</li>`; });
      html += `</ul></div></div>`;
    }
  }
  return html;
}

function getPositionLabel(spreadType, index) {
  if (spreadType === 'three_card') return ['Past','Present','Future'][index] || '';
  if (spreadType === 'decision')   return ['Option A','Option B','Outcome'][index] || '';
  return '';
}

// ===== Journal save =====
async function saveJournal(readingId) {
  const text   = document.getElementById('journal-text')?.value?.trim();
  const status = document.getElementById('journal-status');
  if (!text) { if (status) status.textContent = 'Please write something first.'; return; }

  if (status) status.textContent = 'Saving...';
  try {
    const fd = new FormData();
    fd.append('reading_id', readingId);
    fd.append('entry_text', text);
    const res  = await fetch('/mysticmind/ajax/save_journal.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (status) status.textContent = json.success ? '✓ Saved!' : (json.error || 'Error saving.');
    if (json.support && status) status.insertAdjacentHTML('afterend', renderSupportBox(json.support));
  } catch {
    if (status) status.textContent = 'Error saving.';
  }
}

// ===== Live username check =====
function initUsernameCheck() {
  const input  = document.getElementById('username');
  const status = document.getElementById('username-status');
  if (!input || !status) return;

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const val = input.value.trim();
    if (val.length < 3) { status.textContent = ''; return; }
    status.textContent = 'Checking...';
    timer = setTimeout(async () => {
      try {
        const fd = new FormData();
        fd.append('username', val);
        const res  = await fetch('/mysticmind/ajax/check_username.php', { method: 'POST', body: fd });
        const json = await res.json();
        status.textContent = json.available ? '✓ Available' : '✗ Taken';
        status.style.color  = json.available ? 'var(--teal)' : '#ff6b7a';
      } catch { status.textContent = ''; }
    }, 400);
  });
}

// ===== History search (AJAX) =====
function initHistorySearch() {
  const input = document.getElementById('history-search');
  const tbody = document.getElementById('history-tbody');
  if (!input || !tbody) return;

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const q = input.value.trim();
      try {
        const fd = new FormData();
        fd.append('q', q);
        const res  = await fetch('/mysticmind/ajax/search_history.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.html !== undefined) tbody.innerHTML = json.html;
      } catch {}
    }, 350);
  });
}

// ===== Delete confirmation =====
function confirmDelete(id, type) {
  if (!confirm('Are you sure you want to delete this ' + type + '? This cannot be undone.')) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('type', type);
  fetch('/mysticmind/ajax/delete_record.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => { if (j.success) location.reload(); else alert(j.error || 'Delete failed.'); });
}

// ===== Modal =====
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

// ===== Utility =====
// ===== Support message (shown when crisis language is detected) =====
function renderSupportBox(support) {
  if (!support) return '';
  const lines = (support.hotlines || []).map(h => `<li><strong>${escHtml(h)}</strong></li>`).join('');
  return `<div class="support-box" style="background:rgba(160,125,224,0.12);border:1px solid rgba(160,125,224,0.5);border-radius:12px;padding:1.25rem 1.5rem;margin:1.5rem 0">
    <h4 style="font-family:'Cinzel',serif;color:var(--accent);margin-bottom:0.5rem">${escHtml(support.title)}</h4>
    <p style="margin-bottom:0.75rem">${escHtml(support.message)}</p>
    <ul style="margin:0;padding-left:1.2rem">${lines}</ul>
  </div>`;
}

function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  initSpreadSelector();
  initReadingForm();
  initUsernameCheck();
  initHistorySearch();
});
