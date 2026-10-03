(function (root, factory) {
  'use strict';
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.AOCaptcha = factory();
  }
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var SHIELD = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
  var REFRESH = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';
  var CHECK = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
  var XMARK = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

  var SHAPES = {
    'rounded-square': '<rect x="4" y="4" width="44" height="44" rx="11"/>',
    'circle':         '<circle cx="26" cy="26" r="22"/>',
    'hexagon':        '<polygon points="26,3 48,15 48,37 26,49 4,37 4,15"/>',
    'diamond':        '<rect x="11" y="11" width="30" height="30" rx="5" transform="rotate(45 26 26)"/>',
    'triangle':       '<polygon points="26,5 47,45 5,45"/>',
    'pentagon':       '<polygon points="26,4 48,20 39.5,47 12.5,47 4,20"/>',
    'octagon':        '<polygon points="16,4 36,4 48,16 48,36 36,48 16,48 4,36 4,16"/>',
    'star':           '<polygon points="26,2 31.6,18.2 48.8,18.6 35.1,29 40.1,45.4 26,35.6 11.9,45.4 16.9,29 3.2,18.6 20.4,18.2"/>',
    'cross':          '<polygon points="20,4 32,4 32,20 48,20 48,32 32,32 32,48 20,48 20,32 4,32 4,20 20,20"/>',
    'heart':          '<path d="M26 46 C6 30 4 18 13 10 C19 5 25 9 26 14 C27 9 33 5 39 10 C48 18 46 30 26 46 Z"/>',
    'bolt':           '<polygon points="31,3 13,29 24,29 20,49 39,21 28,21"/>',
    'moon':           '<path d="M30 6 A20 20 0 1 0 30 46 A15 15 0 1 1 30 6 Z"/>',
    'arrow':          '<polygon points="4,20 30,20 30,10 48,26 30,42 30,32 4,32"/>',
    'shield':         '<path d="M26 4 L44 10 L44 26 C44 38 36 45 26 49 C16 45 8 38 8 26 L8 10 Z"/>',
    'logo-triangle':  '<path fill-rule="evenodd" d="M4 7 L48 7 L26 46 Z M15 13 L37 13 L26 33 Z"/>'
  };

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html) e.innerHTML = html;
    return e;
  }

  function svgWrap(inner) {
    return '<svg viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">' + inner + '</svg>';
  }

  function dist(x1, y1, x2, y2) {
    return Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2));
  }

  function initCaptchas() {
    var containers = document.querySelectorAll('[data-ao-captcha]');
    for (var i = 0; i < containers.length; i++) {
      if (containers[i].dataset.aoCaptchaInit) continue;
      containers[i].dataset.aoCaptchaInit = '1';
      new AoCaptcha(containers[i]);
    }
  }

  function AoCaptcha(container) {
    this.container = container;
    this.endpoint  = container.dataset.aoCaptchaEndpoint || 'aocaptcha-endpoint.php';
    this.form      = container.closest('form');
    this.verified  = false;
    this.challenge = null;

    this.dragging  = false;
    this.movements = [];
    this.startTime = 0;
    this.lastRecordTime = 0;
    this.offsetX   = 0;
    this.offsetY   = 0;
    this.startDist = 0;

    this._onMove = this.onPointerMove.bind(this);
    this._onUp   = this.onPointerUp.bind(this);

    this.build();
    this.fetchChallenge();
  }

  AoCaptcha.prototype.build = function () {
    var self = this;
    this.container.innerHTML = '';
    this.container.className = 'ao-captcha';

    if (this.form) {
      var old = this.form.querySelector('input[name="_captcha_answer"]');
      if (old) old.remove();
      this.hiddenInput = document.createElement('input');
      this.hiddenInput.type  = 'hidden';
      this.hiddenInput.name  = '_captcha_answer';
      this.hiddenInput.value = '';
      this.form.appendChild(this.hiddenInput);
    }

    var header = el('div', 'ao-captcha__header');
    var brand  = el('div', 'ao-captcha__brand', SHIELD + ' <span>Verification</span>');
    this.refreshBtn = el('button', 'ao-captcha__refresh', REFRESH);
    this.refreshBtn.type = 'button';
    this.refreshBtn.title = 'New challenge';
    this.refreshBtn.setAttribute('aria-label', 'Get a new captcha challenge');
    header.appendChild(brand);
    header.appendChild(this.refreshBtn);

    this.promptEl = el('p', 'ao-captcha__prompt');

    this.arena = el('div', 'ao-captcha__arena');

    this.statusEl = el('div', 'ao-captcha__status');
    this.statusEl.textContent = '';

    this.loadingEl = el('div', 'ao-captcha__loading',
      '<span class="ao-captcha__loading-dot"></span>' +
      '<span class="ao-captcha__loading-dot"></span>' +
      '<span class="ao-captcha__loading-dot"></span>'
    );

    this.container.appendChild(header);
    this.container.appendChild(this.promptEl);
    this.container.appendChild(this.loadingEl);
    this.container.appendChild(this.arena);
    this.container.appendChild(this.statusEl);

    this.arena.style.display = 'none';
    this.promptEl.style.display = 'none';
    this.statusEl.style.display = 'none';

    this.refreshBtn.addEventListener('click', function () {
      self.refreshBtn.classList.add('is-spinning');
      setTimeout(function () { self.refreshBtn.classList.remove('is-spinning'); }, 500);
      self.fetchChallenge();
    });

    if (this.form) {
      this.form.addEventListener('submit', function (e) {
        if (!self.verified) {
          e.preventDefault();
          e.stopPropagation();
          self.setStatus('error', 'Please complete the verification above.');
          self.container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    }
  };

  AoCaptcha.prototype.fetchChallenge = function () {
    var self = this;
    this.verified = false;
    this.container.classList.remove('is-verified', 'is-error');
    if (this.hiddenInput) this.hiddenInput.value = '';

    this.loadingEl.style.display = 'flex';
    this.arena.style.display = 'none';
    this.promptEl.style.display = 'none';
    this.statusEl.style.display = 'none';
    this.statusEl.textContent = '';

    fetch(this.endpoint + '?action=new', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
      },
      credentials: 'same-origin',
      cache: 'no-store',
      body: '{}'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      self.challenge = data;
      self.renderChallenge(data);
    })
    .catch(function () {
      self.loadingEl.innerHTML = '<span style="color:var(--color-danger);font-size:var(--text-xs)">Could not load. Please refresh the page.</span>';
    });
  };

  AoCaptcha.prototype.renderChallenge = function (data) {
    var self = this;
    var shapePath = SHAPES[data.shape] || SHAPES['rounded-square'];

    this.loadingEl.style.display = 'none';
    this.arena.style.display = '';
    this.promptEl.style.display = '';
    this.statusEl.style.display = 'flex';
    this.promptEl.textContent = data.prompt;
    this.arena.innerHTML = '';

    this.target = el('div', 'ao-captcha__target');
    this.target.style.left = data.target.x + '%';
    this.target.style.top  = data.target.y + '%';

    var ghostFill = el('div', '', svgWrap(shapePath));
    this.target.appendChild(ghostFill);

    var ghostOutline = el('div', 'ao-captcha__target-outline', svgWrap(shapePath));
    this.target.appendChild(ghostOutline);

    var pulse = el('div', 'ao-captcha__target-pulse');
    this.target.appendChild(pulse);

    this.arena.appendChild(this.target);

    this.piece = el('div', 'ao-captcha__piece');
    this.piece.style.left = data.start.x + '%';
    this.piece.style.top  = data.start.y + '%';
    this.piece.style.transform = 'translate(-50%,-50%) rotate(' + data.rotation + 'deg)';

    var mainShape = el('div', '', svgWrap(shapePath));
    this.piece.appendChild(mainShape);

    var inner = el('div', 'ao-captcha__piece-inner', svgWrap(shapePath));
    this.piece.appendChild(inner);

    this.arena.appendChild(this.piece);

    var arenaRect = this.arena.getBoundingClientRect();
    var targetPx = { x: data.target.x / 100 * arenaRect.width, y: data.target.y / 100 * arenaRect.height };
    var startPx  = { x: data.start.x / 100 * arenaRect.width,  y: data.start.y / 100 * arenaRect.height };
    this.startDist = dist(startPx.x, startPx.y, targetPx.x, targetPx.y);

    this.piece.addEventListener('mousedown',  function (e) { self.onPointerDown(e); });
    this.piece.addEventListener('touchstart', function (e) { self.onPointerDown(e); }, { passive: false });

    this.setStatus('idle', 'Drag the shape to its outline');
  };

  AoCaptcha.prototype.onPointerDown = function (e) {
    if (this.verified || this.dragging) return;
    e.preventDefault();

    this.dragging = true;
    this.movements = [];
    this.startTime = performance.now();
    this.lastRecordTime = 0;

    this.piece.classList.add('is-dragging');
    this.piece.classList.remove('is-snapping');

    var arenaRect = this.arena.getBoundingClientRect();
    var ptr = this.getPointer(e);

    var pieceRect = this.piece.getBoundingClientRect();
    this.offsetX = ptr.x - (pieceRect.left + pieceRect.width / 2);
    this.offsetY = ptr.y - (pieceRect.top + pieceRect.height / 2);

    this.recordMovement(ptr, arenaRect);

    document.addEventListener('mousemove', this._onMove);
    document.addEventListener('mouseup',   this._onUp);
    document.addEventListener('touchmove', this._onMove, { passive: false });
    document.addEventListener('touchend',  this._onUp);
  };

  AoCaptcha.prototype.onPointerMove = function (e) {
    if (!this.dragging) return;
    e.preventDefault();

    var ptr = this.getPointer(e);
    var arenaRect = this.arena.getBoundingClientRect();

    var x = Math.max(0, Math.min(ptr.x - this.offsetX - arenaRect.left, arenaRect.width));
    var y = Math.max(0, Math.min(ptr.y - this.offsetY - arenaRect.top, arenaRect.height));

    var pctX = (x / arenaRect.width) * 100;
    var pctY = (y / arenaRect.height) * 100;
    this.piece.style.left = pctX + '%';
    this.piece.style.top  = pctY + '%';

    var targetPx = {
      x: this.challenge.target.x / 100 * arenaRect.width,
      y: this.challenge.target.y / 100 * arenaRect.height
    };
    var currentDist = dist(x, y, targetPx.x, targetPx.y);
    var progress = 1 - Math.min(currentDist / Math.max(this.startDist, 1), 1);
    var rot = this.challenge.rotation * (1 - progress);
    var scale = this.dragging ? (1 + 0.06 * (1 - progress * 0.5)) : 1;
    this.piece.style.transform = 'translate(-50%,-50%) rotate(' + rot.toFixed(1) + 'deg) scale(' + scale.toFixed(3) + ')';

    var nearThreshold = this.challenge.tolerance * 2;
    if (currentDist < nearThreshold) {
      this.target.classList.add('is-near');
    } else {
      this.target.classList.remove('is-near');
    }

    this.recordMovement({ x: ptr.x, y: ptr.y }, arenaRect);

    if (currentDist < this.challenge.tolerance) {
      this.snap(pctX, pctY, arenaRect);
    }
  };

  AoCaptcha.prototype.onPointerUp = function (e) {
    if (!this.dragging) return;
    this.dragging = false;
    this.piece.classList.remove('is-dragging');
    this.cleanupListeners();
  };

  AoCaptcha.prototype.recordMovement = function (ptr, arenaRect) {
    var t = performance.now() - this.startTime;
    if (t - this.lastRecordTime < 25 && this.movements.length > 0) return;
    this.lastRecordTime = t;

    this.movements.push({
      x: +((ptr.x - arenaRect.left) / arenaRect.width * 100).toFixed(2),
      y: +((ptr.y - arenaRect.top) / arenaRect.height * 100).toFixed(2),
      t: Math.round(t)
    });
  };

  AoCaptcha.prototype.snap = function (pctX, pctY, arenaRect) {
    var self = this;
    this.dragging = false;
    this.piece.classList.remove('is-dragging');
    this.cleanupListeners();

    var finalT = performance.now() - this.startTime;
    this.movements.push({ x: +pctX.toFixed(2), y: +pctY.toFixed(2), t: Math.round(finalT) });

    this.piece.classList.add('is-snapping');

    requestAnimationFrame(function () {
      self.piece.style.left = self.challenge.target.x + '%';
      self.piece.style.top  = self.challenge.target.y + '%';
      self.piece.style.transform = 'translate(-50%,-50%) rotate(0deg) scale(1)';
    });

    this.spawnSnapRing(false);
    this.spawnSnapRing(true);

    this.target.classList.remove('is-near');

    setTimeout(function () {
      self.verify();
    }, 500);
  };

  AoCaptcha.prototype.spawnSnapRing = function (delayed) {
    var ring = el('div', 'ao-captcha__snap-ring' + (delayed ? ' ao-captcha__snap-ring--delay' : ''));
    ring.style.left = this.challenge.target.x + '%';
    ring.style.top  = this.challenge.target.y + '%';
    this.arena.appendChild(ring);
    setTimeout(function () { ring.remove(); }, 700);
  };

  AoCaptcha.prototype.verify = function () {
    var self = this;
    var duration = performance.now() - this.startTime;

    var overlay = el('div', 'ao-captcha__verifying',
      '<span class="ao-captcha__verifying-spinner"></span> Verifying…');
    this.arena.appendChild(overlay);

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var payload = {
      movements:    this.movements,
      duration:     Math.round(duration),
      snapped:      true,
      snapPosition: { x: this.challenge.target.x, y: this.challenge.target.y }
    };

    fetch(this.endpoint + '?action=verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      credentials: 'same-origin',
      cache: 'no-store',
      body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      overlay.remove();
      if (data.valid) {
        self.onVerified(data.token);
      } else {
        self.onFailed(data.message || 'Verification failed.');
      }
    })
    .catch(function () {
      overlay.remove();
      self.onFailed('Network error. Please try again.');
    });
  };

  AoCaptcha.prototype.onVerified = function (token) {
    this.verified = true;
    if (this.hiddenInput) this.hiddenInput.value = token;

    this.piece.classList.add('is-merged');

    var self = this;
    setTimeout(function () {
      self.container.classList.add('is-verified');
      self.setStatus('ok', 'Verified — you\'re all set.');
    }, 600);
  };

  AoCaptcha.prototype.onFailed = function (msg) {
    this.container.classList.add('is-error');
    this.setStatus('error', msg);

    var self = this;
    setTimeout(function () {
      self.container.classList.remove('is-error');
      self.fetchChallenge();
    }, 1800);
  };

  AoCaptcha.prototype.setStatus = function (type, text) {
    this.statusEl.style.display = 'flex';
    if (type === 'ok') {
      this.statusEl.innerHTML = '<span class="ao-captcha__status-icon ao-captcha__status-icon--ok">' + CHECK + '</span> ' + text;
      this.statusEl.style.color = '';
    } else if (type === 'error') {
      this.statusEl.innerHTML = '<span class="ao-captcha__status-icon ao-captcha__status-icon--err">' + XMARK + '</span> ' + text;
      this.statusEl.style.color = '';
    } else {
      this.statusEl.innerHTML = text;
      this.statusEl.style.color = '';
    }
  };

  AoCaptcha.prototype.getPointer = function (e) {
    if (e.touches && e.touches.length) {
      return { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
    if (e.changedTouches && e.changedTouches.length) {
      return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
    }
    return { x: e.clientX, y: e.clientY };
  };

  AoCaptcha.prototype.cleanupListeners = function () {
    document.removeEventListener('mousemove', this._onMove);
    document.removeEventListener('mouseup',   this._onUp);
    document.removeEventListener('touchmove', this._onMove);
    document.removeEventListener('touchend',  this._onUp);
  };

  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCaptchas);
    } else {
      initCaptchas();
    }
  }

  return { AoCaptcha: AoCaptcha, init: initCaptchas };
}));
