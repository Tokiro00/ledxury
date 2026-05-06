/**
 * Reports v2 — autocomplete picker (event delegation, sobrevive Vue re-render).
 *
 * Markup esperado:
 *   <div class="rep-picker" data-picker-type="client">
 *     <input type="hidden" name="client_id" value="">
 *     <div class="rep-picker__row">
 *       <svg class="rep-picker__icon">...</svg>
 *       <input type="text" class="rep-picker__input">
 *       <span class="rep-picker__spinner" hidden></span>
 *       <button class="rep-picker__clear" hidden>×</button>
 *     </div>
 *     <div class="rep-picker__dropdown" hidden></div>
 *   </div>
 *
 * Cambio v1.30.14: TODOS los event listeners viven a nivel `document` (event
 * delegation). Si Vue reemplaza el DOM o el picker se inserta dinámicamente,
 * los handlers siguen funcionando porque el listener está en el documento,
 * no en el elemento.
 */
(function () {
  'use strict';

  // base_url se setea desde PHP en meta_header.php — funciona local (con /lumen/)
  // y prod (sin prefijo). El fallback location.origin + '/lumen/' anterior
  // rompia en prod porque devolvia '/lumen/' aunque la URL real era root.
  var BASE = (window.MAM_BASE_URL || window.base_url || (location.origin + '/')) + 'sisvent/admin/reports/v2';
  var DEBOUNCE_MS = 250;

  // Estado global por picker — usamos WeakMap para que el GC limpie cuando el DOM se recree
  var stateMap = new WeakMap();

  function getState(picker) {
    var s = stateMap.get(picker);
    if (!s) {
      s = {
        debounceTimer: null,
        activeIdx: -1,
        currentResults: [],
        inflight: null,
        labelResolved: false,
      };
      stateMap.set(picker, s);
    }
    return s;
  }

  function getParts(picker) {
    return {
      hidden: picker.querySelector('input[type="hidden"]'),
      input: picker.querySelector('.rep-picker__input'),
      row: picker.querySelector('.rep-picker__row'),
      clearBtn: picker.querySelector('.rep-picker__clear'),
      spinner: picker.querySelector('.rep-picker__spinner'),
      dropdown: picker.querySelector('.rep-picker__dropdown'),
      type: picker.dataset.pickerType,
    };
  }

  function search(picker, q) {
    var p = getParts(picker);
    var s = getState(picker);
    if (!p.input || !p.dropdown) { console.warn('[picker] search aborted: missing parts'); return; }
    if (q.length < 2) { hideDropdown(picker); return; }
    showSpinner(picker);

    if (s.inflight && s.inflight.abort) s.inflight.abort();
    var ctrl = new AbortController();
    s.inflight = ctrl;

    var url = BASE + '/_picker?type=' + encodeURIComponent(p.type) + '&q=' + encodeURIComponent(q);

    fetch(url, { credentials: 'same-origin', signal: ctrl.signal })
      .then(function (r) { return r.text(); })
      .then(function (text) {
        hideSpinner(picker);
        var data;
        try { data = JSON.parse(text); }
        catch (e) {
          console.error('[picker] JSON parse failed', e);
          p.dropdown.innerHTML = '<div class="rep-picker__empty">Respuesta inválida del servidor.</div>';
          showDropdown(picker);
          return;
        }
        s.currentResults = data.results || [];
        renderDropdown(picker, s.currentResults, q);
      })
      .catch(function (err) {
        hideSpinner(picker);
        if (err.name === 'AbortError') return;
        console.error('[picker] fetch error', err);
        p.dropdown.innerHTML = '<div class="rep-picker__empty">Error: ' + escapeHtml(err.message || String(err)) + '</div>';
        showDropdown(picker);
      });
  }

  function renderDropdown(picker, results, q) {
    var p = getParts(picker);
    var s = getState(picker);
    s.activeIdx = -1;
    if (!results.length) {
      p.dropdown.innerHTML =
        '<div class="rep-picker__empty">' +
        '<div style="font-size:14px;color:var(--mam-blue-dark,#2B3164);font-weight:600;margin-bottom:4px;">Sin resultados para "' + escapeHtml(q) + '"</div>' +
        '<div style="font-size:11px;">Intenta con otra parte del nombre, NIT o teléfono.</div>' +
        '</div>';
      showDropdown(picker);
      return;
    }
    var html = '';
    results.forEach(function (r, idx) {
      var isPhonetic = r.label.indexOf('⊙') !== -1;
      var cleanLabel = r.label.replace(/\s*⊙\s*$/, '');
      html += '<div class="rep-picker__item" data-idx="' + idx + '">' +
        '<div class="rep-picker__label">' + escapeHtml(cleanLabel) + '</div>' +
        '<div class="rep-picker__meta">' + escapeHtml(r.meta || '') +
          (isPhonetic ? '<span class="rep-picker__phonetic">⊙ similar fonéticamente</span>' : '') +
        '</div>' +
        '</div>';
    });
    html += '<div class="rep-picker__hint">↑↓ navegar · Enter seleccionar · Esc cerrar · ' + results.length + ' resultado' + (results.length === 1 ? '' : 's') + '</div>';
    p.dropdown.innerHTML = html;
    showDropdown(picker);
  }

  function setActive(picker, idx) {
    var p = getParts(picker);
    var s = getState(picker);
    var items = p.dropdown.querySelectorAll('.rep-picker__item');
    items.forEach(function (el) { el.classList.remove('rep-picker__item--active'); });
    if (idx >= 0 && idx < items.length) {
      items[idx].classList.add('rep-picker__item--active');
      s.activeIdx = idx;
      items[idx].scrollIntoView({ block: 'nearest' });
    }
  }

  function select(picker, r) {
    if (!r) return;
    var p = getParts(picker);
    p.hidden.value = r.id;
    p.input.value = r.label.replace(/\s*⊙\s*$/, '');
    picker.dataset.selected = '1';
    updateClearBtn(picker);
    hideDropdown(picker);
    p.hidden.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function clearSelection(picker) {
    var p = getParts(picker);
    p.hidden.value = '';
    p.input.value = '';
    picker.dataset.selected = '';
    updateClearBtn(picker);
    hideDropdown(picker);
    p.input.focus();
  }

  function updateClearBtn(picker) {
    var p = getParts(picker);
    if (p.clearBtn) p.clearBtn.hidden = !p.input.value;
  }
  function showSpinner(picker) { var p = getParts(picker); if (p.spinner) p.spinner.hidden = false; }
  function hideSpinner(picker) { var p = getParts(picker); if (p.spinner) p.spinner.hidden = true; }
  function showDropdown(picker) { var p = getParts(picker); if (p.dropdown) p.dropdown.hidden = false; }
  function hideDropdown(picker) {
    var p = getParts(picker);
    if (p.dropdown) p.dropdown.hidden = true;
    var s = getState(picker); s.activeIdx = -1;
  }

  function pickerOf(target) {
    return target && target.closest ? target.closest('.rep-picker') : null;
  }

  function resolveLabelIfNeeded(picker) {
    var p = getParts(picker);
    var s = getState(picker);
    if (s.labelResolved) return;
    s.labelResolved = true;
    if (!p.hidden || !p.hidden.value) return;
    fetch(BASE + '/_label?type=' + encodeURIComponent(p.type) + '&id=' + encodeURIComponent(p.hidden.value), {
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.label) {
          p.input.value = data.label;
          picker.dataset.selected = '1';
          updateClearBtn(picker);
        }
      })
      .catch(function () {});
  }

  // ─── Event delegation a nivel document ────────────────────────────────

  document.addEventListener('input', function (e) {
    var picker = pickerOf(e.target);
    if (!picker || !e.target.classList.contains('rep-picker__input')) return;
    var p = getParts(picker);
    var s = getState(picker);
    clearTimeout(s.debounceTimer);
    var q = e.target.value.trim();
    if (picker.dataset.selected === '1') {
      p.hidden.value = '';
      picker.dataset.selected = '';
    }
    if (q.length === 0) { p.hidden.value = ''; hideDropdown(picker); }
    updateClearBtn(picker);
    s.debounceTimer = setTimeout(function () { search(picker, q); }, DEBOUNCE_MS);
  });

  document.addEventListener('keydown', function (e) {
    var picker = pickerOf(e.target);
    if (!picker || !e.target.classList.contains('rep-picker__input')) return;
    var p = getParts(picker);
    var s = getState(picker);
    if (p.dropdown.hidden) {
      if (e.key === 'ArrowDown' && e.target.value.trim().length >= 2) search(picker, e.target.value.trim());
      return;
    }
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(picker, Math.min(s.activeIdx + 1, s.currentResults.length - 1)); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(picker, Math.max(s.activeIdx - 1, 0)); }
    else if (e.key === 'Enter') {
      if (s.activeIdx >= 0 && s.currentResults[s.activeIdx]) {
        e.preventDefault();
        select(picker, s.currentResults[s.activeIdx]);
      }
    }
    else if (e.key === 'Escape') { hideDropdown(picker); }
  });

  document.addEventListener('focusin', function (e) {
    var picker = pickerOf(e.target);
    if (!picker) return;
    if (e.target.classList.contains('rep-picker__input')) {
      if (picker.querySelector('.rep-picker__row')) picker.querySelector('.rep-picker__row').classList.add('rep-picker__row--focused');
      resolveLabelIfNeeded(picker);
      var q = e.target.value.trim();
      if (q.length >= 2 && picker.dataset.selected !== '1') search(picker, q);
    }
  });

  document.addEventListener('focusout', function (e) {
    var picker = pickerOf(e.target);
    if (!picker) return;
    if (e.target.classList.contains('rep-picker__input')) {
      if (picker.querySelector('.rep-picker__row')) picker.querySelector('.rep-picker__row').classList.remove('rep-picker__row--focused');
      // Hide con delay para que click en item registre primero
      setTimeout(function () { hideDropdown(picker); }, 150);
    }
  });

  // Click en item del dropdown → select
  document.addEventListener('mousedown', function (e) {
    var picker = pickerOf(e.target);
    if (!picker) return;
    var item = e.target.closest('.rep-picker__item');
    if (item) {
      e.preventDefault();
      var s = getState(picker);
      var idx = parseInt(item.dataset.idx, 10);
      select(picker, s.currentResults[idx]);
    }
  });

  // Hover en item → setActive
  document.addEventListener('mouseover', function (e) {
    var picker = pickerOf(e.target);
    if (!picker) return;
    var item = e.target.closest('.rep-picker__item');
    if (item) {
      var idx = parseInt(item.dataset.idx, 10);
      setActive(picker, idx);
    }
  });

  // Click en botón clear
  document.addEventListener('click', function (e) {
    var picker = pickerOf(e.target);
    if (!picker) return;
    if (e.target.closest('.rep-picker__clear')) {
      e.preventDefault();
      clearSelection(picker);
    }
  });

  // Init: resolver labels de todos los pickers preseleccionados
  function resolveAllLabels() {
    var pickers = document.querySelectorAll('.rep-picker');
    pickers.forEach(function (p) { resolveLabelIfNeeded(p); });
  }

  // Form submit guard: bloquear si picker tiene texto pero hidden vacío
  function attachFormGuard() {
    var form = document.getElementById('rep-filters-form');
    if (!form || form.dataset.pickerGuarded) return;
    form.dataset.pickerGuarded = '1';
    form.addEventListener('submit', function (e) {
      var bad = null;
      form.querySelectorAll('.rep-picker').forEach(function (p) {
        var hidden = p.querySelector('input[type="hidden"]');
        var input = p.querySelector('.rep-picker__input');
        var hasText = (input && input.value || '').trim().length > 0;
        var hasId = (hidden && hidden.value || '').trim().length > 0;
        if (hasText && !hasId) bad = p;
      });
      if (bad) { e.preventDefault(); flashError(bad); }
    });
  }

  function flashError(picker) {
    var p = getParts(picker);
    if (p.row) p.row.classList.add('rep-picker__row--error');
    var msg = picker.querySelector('.rep-picker__error');
    if (!msg) {
      msg = document.createElement('div');
      msg.className = 'rep-picker__error';
      msg.style.cssText = 'font-size:11px;font-weight:600;color:var(--mam-red,#ef0d0d);margin-top:4px;';
      msg.textContent = 'Seleccioná un resultado del listado (no es texto libre).';
      picker.appendChild(msg);
    }
    if (p.input) p.input.focus();
    setTimeout(function () {
      if (p.row) p.row.classList.remove('rep-picker__row--error');
      if (msg && msg.parentNode) msg.parentNode.removeChild(msg);
    }, 3500);
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function bootAll() {
    resolveAllLabels();
    attachFormGuard();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAll);
  } else {
    bootAll();
  }
  // Retries por si Vue mount tarda y reemplaza el DOM
  setTimeout(bootAll, 200);
  setTimeout(bootAll, 800);
})();
