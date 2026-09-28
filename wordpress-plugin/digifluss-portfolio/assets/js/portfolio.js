/*!
 * Digifluss — Portfolio carosello
 * Inizializza ogni blocco .dfp generato dallo shortcode [digifluss_portfolio].
 * Progressive enhancement: senza JS le card restano una pila leggibile.
 */
(function () {
  'use strict';

  var current = null; // istanza attualmente "in uso" (per la tastiera)

  function init(section) {
    var carousel = section.querySelector('.dfp-carousel');
    if (!carousel) return;
    var cards = Array.prototype.slice.call(carousel.querySelectorAll('.dfp-card'));
    var n = cards.length;
    if (!n) return;

    var panel   = section.querySelector('.dfp-panel');
    var pTitle  = section.querySelector('.dfp-title');
    var pMeta   = section.querySelector('.dfp-metaline');
    var pStack  = section.querySelector('.dfp-stack');
    var pDesc   = section.querySelector('.dfp-desc');
    var pLink   = section.querySelector('.dfp-link');
    var indexEl = section.querySelector('.dfp-index');
    var fill    = section.querySelector('.dfp-fill');
    var dots    = Array.prototype.slice.call(section.querySelectorAll('.dfp-dot'));
    var prevBtn = section.querySelector('.dfp-prev');
    var nextBtn = section.querySelector('.dfp-next');

    var active = 0, step = 0, dragOffset = 0;
    var dragging = false, lockH = false, justDragged = false, startX = 0, startY = 0;
    var vx = 0, lastX = 0, lastT = 0; // velocità per il "flick"
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine   = window.matchMedia('(hover:hover) and (pointer:fine)').matches;

    carousel.classList.add('dfp-js');

    function pad(i) { return (i < 10 ? '0' : '') + i; }
    function wrap(i) { return ((i % n) + n) % n; }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    function measure() { step = cards[0].offsetWidth * 0.66; }

    function position() {
      for (var i = 0; i < n; i++) {
        var card = cards[i];
        var rel = i - active;
        if (rel > n / 2) rel -= n;
        if (rel < -n / 2) rel += n;
        var abs = Math.abs(rel);
        var x = rel * step + dragOffset;
        var scale = abs === 0 ? 1 : Math.max(0.72, 1 - abs * 0.13);
        var opacity = abs === 0 ? 1 : abs === 1 ? 0.55 : abs === 2 ? 0.16 : 0;
        card.style.transform = 'translate(-50%,-50%) translateX(' + x + 'px) scale(' + scale + ')';
        card.style.opacity = opacity;
        card.style.zIndex = String(100 - abs);
        card.style.pointerEvents = abs > 2 ? 'none' : 'auto';
        if (abs === 0) {
          card.classList.add('is-active');
        } else {
          card.classList.remove('is-active');
          var cov = card.querySelector('.dfp-cover');
          if (cov) { cov.style.setProperty('--rx', '0deg'); cov.style.setProperty('--ry', '0deg'); }
        }
      }
    }

    function setPanel() {
      var c = cards[active];
      if (indexEl) indexEl.innerHTML = '<b>' + pad(active + 1) + '</b> / ' + pad(n);
      if (fill) fill.style.width = ((active + 1) / n * 100) + '%';
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === active); });

      if (panel) { panel.classList.remove('show'); void panel.offsetWidth; }
      if (pTitle) pTitle.textContent = c.getAttribute('data-title') || '';
      if (pMeta) {
        var meta = [c.getAttribute('data-cat'), c.getAttribute('data-year'), c.getAttribute('data-role')]
          .filter(Boolean).join('   \u00b7   ');
        pMeta.textContent = meta;
      }
      if (pStack) {
        var arr = [];
        try { arr = JSON.parse(c.getAttribute('data-stack') || '[]'); } catch (e) { arr = []; }
        pStack.innerHTML = arr.map(function (s) { return '<span class="dfp-chip">' + esc(s) + '</span>'; }).join('');
      }
      if (pDesc) pDesc.textContent = c.getAttribute('data-desc') || '';
      if (pLink) pLink.setAttribute('href', c.getAttribute('data-link') || '#');
      if (panel) panel.classList.add('show');

      cards.forEach(function (cd, i) { cd.setAttribute('aria-current', i === active ? 'true' : 'false'); });
    }

    function goTo(i) { active = wrap(i); position(); setPanel(); }
    var api = { next: function () { goTo(active + 1); }, prev: function () { goTo(active - 1); } };

    if (prevBtn) prevBtn.addEventListener('click', api.prev);
    if (nextBtn) nextBtn.addEventListener('click', api.next);
    dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });
    cards.forEach(function (card, i) {
      // Card attiva: apre il caso di studio. Card laterale: la porta in primo piano.
      function activate() {
        if (i === active) {
          var href = card.getAttribute('data-link');
          if (href) { window.location.href = href; }
        } else {
          goTo(i);
        }
      }
      card.addEventListener('click', function () { if (!justDragged) activate(); });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
      });
    });

    // drag / swipe
    carousel.addEventListener('pointerdown', function (e) {
      dragging = true; lockH = false; startX = e.clientX; startY = e.clientY;
      vx = 0; lastX = e.clientX; lastT = (e.timeStamp || Date.now());
    });
    carousel.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      var dx = e.clientX - startX, dy = e.clientY - startY;
      if (!lockH) {
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 6) {
          lockH = true;
          carousel.classList.add('dragging');
          try { carousel.setPointerCapture(e.pointerId); } catch (_) {}
        }
        else if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 8) { dragging = false; return; }
        else return;
      }
      e.preventDefault();
      var now = (e.timeStamp || Date.now()), dt = now - lastT;
      if (dt > 0) { vx = (e.clientX - lastX) / dt; } // px/ms (>0 = verso destra)
      lastX = e.clientX; lastT = now;
      dragOffset = dx; // tracking 1:1 col dito
      position();
    });
    function endDrag() {
      if (!dragging) return;
      dragging = false; carousel.classList.remove('dragging');
      var moved = dragOffset; dragOffset = 0;
      var threshold = step * 0.18;
      var flick = Math.abs(vx) > 0.45; // flick veloce → cambia comunque
      if (lockH) {
        if (moved <= -threshold || (flick && vx < 0)) active = wrap(active + 1);
        else if (moved >= threshold || (flick && vx > 0)) active = wrap(active - 1);
      }
      justDragged = lockH; lockH = false;
      position(); setPanel();
      setTimeout(function () { justDragged = false; }, 60);
    }
    carousel.addEventListener('pointerup', endDrag);
    carousel.addEventListener('pointercancel', endDrag);

    // trackpad / wheel orizzontale (agisce solo sull'intento orizzontale: lo scroll verticale resta libero)
    var wheelLock = 0;
    carousel.addEventListener('wheel', function (e) {
      if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
      e.preventDefault();
      var now = Date.now();
      if (now < wheelLock) return;
      if (e.deltaX > 10) { api.next(); wheelLock = now + 320; }
      else if (e.deltaX < -10) { api.prev(); wheelLock = now + 320; }
    }, { passive: false });

    // tilt sottile sulla card attiva (solo puntatore fine)
    if (fine && !reduce) {
      carousel.addEventListener('pointermove', function (e) {
        if (dragging) return;
        var card = cards[active]; if (!card) return;
        var cov = card.querySelector('.dfp-cover'); if (!cov) return;
        var r = card.getBoundingClientRect();
        var dx = (e.clientX - (r.left + r.width / 2)) / (r.width / 2);
        var dy = (e.clientY - (r.top + r.height / 2)) / (r.height / 2);
        cov.style.setProperty('--ry', (Math.max(-1, Math.min(1, dx)) * 5).toFixed(2) + 'deg');
        cov.style.setProperty('--rx', (Math.max(-1, Math.min(1, dy)) * -5).toFixed(2) + 'deg');
      });
      carousel.addEventListener('pointerleave', function () {
        var card = cards[active]; if (!card) return;
        var cov = card.querySelector('.dfp-cover');
        if (cov) { cov.style.setProperty('--rx', '0deg'); cov.style.setProperty('--ry', '0deg'); }
      });
    }

    // l'istanza con cui si sta interagendo riceve i comandi da tastiera
    carousel.addEventListener('pointerenter', function () { current = api; });
    carousel.addEventListener('focusin', function () { current = api; });
    if (!current) current = api;

    // resize
    var rt;
    window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(function () { measure(); position(); }, 120); });
    window.addEventListener('orientationchange', function () { setTimeout(function () { measure(); position(); }, 220); });

    // layout iniziale (senza transizione), poi entrata quando entra nel viewport
    measure(); position(); setPanel();

    function ready() { section.classList.add('dfp-ready'); requestAnimationFrame(position); }
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { ready(); io.disconnect(); } });
      }, { threshold: 0.12 });
      io.observe(section);
    } else {
      ready();
    }
  }

  document.addEventListener('keydown', function (e) {
    if (!current) return;
    if (e.key === 'ArrowRight') current.next();
    else if (e.key === 'ArrowLeft') current.prev();
  });

  function boot() {
    Array.prototype.slice.call(document.querySelectorAll('.dfp')).forEach(init);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();

/* =====================================================================
   Lightbox del caso di studio: le immagini .dfp-zoom (galleria, blocchi,
   figure di sezione) si aprono a schermo intero. Senza JS il link porta
   comunque al file originale.
   ===================================================================== */
(function () {
  'use strict';

  var overlay = null;
  var lastFocus = null;

  function close() {
    if (!overlay) { return; }
    overlay.classList.remove('is-open');
    var el = overlay;
    overlay = null;
    window.setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 250);
    document.removeEventListener('keydown', onKey);
    if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
  }

  function onKey(e) {
    if (e.key === 'Escape') { close(); }
  }

  function open(href, alt) {
    lastFocus = document.activeElement;
    overlay = document.createElement('div');
    overlay.className = 'dfp-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    var img = document.createElement('img');
    img.src = href;
    img.alt = alt || '';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'dfp-lightbox-close';
    btn.innerHTML = '&times;';
    btn.setAttribute('aria-label', 'Chiudi');
    overlay.appendChild(img);
    overlay.appendChild(btn);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay || e.target === btn) { close(); }
    });
    document.body.appendChild(overlay);
    document.addEventListener('keydown', onKey);
    window.requestAnimationFrame(function () {
      overlay && overlay.classList.add('is-open');
      btn.focus();
    });
  }

  document.addEventListener('click', function (e) {
    var link = e.target && e.target.closest ? e.target.closest('a.dfp-zoom') : null;
    if (!link) { return; }
    e.preventDefault();
    var inner = link.querySelector('img');
    open(link.getAttribute('href'), inner ? inner.getAttribute('alt') : '');
  });
})();
