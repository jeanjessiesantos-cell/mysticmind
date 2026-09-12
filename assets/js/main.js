// ===== MysticMind Main JS =====

// Stars background
(function createStars() {
  const container = document.createElement('div');
  container.className = 'stars';
  document.body.prepend(container);
  for (let i = 0; i < 80; i++) {
    const s = document.createElement('div');
    s.className = 'star';
    const size = Math.random() * 2.5 + 0.5;
    s.style.cssText = `
      width:${size}px; height:${size}px;
      left:${Math.random()*100}%;
      top:${Math.random()*100}%;
      --dur:${(Math.random()*4+2).toFixed(1)}s;
      --op:${(Math.random()*0.5+0.2).toFixed(2)};
      animation-delay:${(Math.random()*5).toFixed(1)}s;
    `;
    container.appendChild(s);
  }
})();

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

  let html = `<div class="panel" style="margin-top:2rem">`;
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
