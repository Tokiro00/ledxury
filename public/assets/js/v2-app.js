/* ============================================================
 * Ledxury v2 — App JS
 *
 * Lógica jQuery delegada para la interfaz v2. Convención del repo:
 *   $(document).on('event', '#selector', fn)
 *
 * Globals expuestos por meta_header.php:
 *   window.LX_V2 = { baseUrl, csrfName, csrfHash, user }
 * ============================================================ */
(function() {
  'use strict';

  if (typeof window.LX_V2 === 'undefined') {
    console.warn('LX_V2 boot config no disponible');
    return;
  }

  // ===== Switcher de paleta =====
  // Permite cambiar la paleta sin recargar: setPalette('obsidian')
  window.setPalette = function(name) {
    if (!['petroleo', 'obsidian', 'ember'].includes(name)) return;
    document.documentElement.dataset.palette = name;
    try { localStorage.setItem('lx_palette', name); } catch (e) {}
  };
  // Restaurar paleta guardada al cargar
  try {
    var saved = localStorage.getItem('lx_palette');
    if (saved) window.setPalette(saved);
  } catch (e) {}

  // ===== Switcher v2 → v1 =====
  // El botón "Versión clásica" en topbar.php tiene id=lx-switch-to-v1 y un
  // href estático. Si en el futuro queremos cálculo dinámico (URL actual ↔
  // equivalente v1), lo hacemos acá interceptando el click.

  // ===== Debug helper para consola =====
  window.LX = window.LX || {};
  window.LX.help = function() {
    console.log('%cLedxury v2 — comandos disponibles', 'font-weight:bold;color:#4487A0;');
    console.log('  setPalette("petroleo" | "obsidian" | "ember")');
    console.log('  window.LX_V2  → boot config');
  };

})();
