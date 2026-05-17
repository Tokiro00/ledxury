<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — switcher injectado en v1 sidemenu.
 *
 * Se incluye con 1 línea defensiva desde sidemenu.php:
 *
 *   <?php
 *     $sw = APPPATH . 'views/sisvent/v2/_v1_switcher.php';
 *     if (file_exists($sw)) include $sw;
 *   ?>
 *
 * Si querés revertir, borrá esas 2 líneas de sidemenu.php y este archivo.
 * El partial JS calcula la URL equivalente en v2 según donde esté el user.
 */
?>
<li class="relative px-6 py-3" style="border-top: 1px solid rgba(255,255,255,0.08); margin-top: 12px; padding-top: 12px;">
  <a id="lx-switch-to-v2" href="<?= base_url('sisvent/v2/presupuestos') ?>"
     class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-white"
     style="color: #F7941D; gap: 8px;"
     title="Probar la nueva interfaz v2 (solo lectura)">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 2L13.5 8H22L16 12L18 19L12 15L6 19L8 12L2 8H10.5L12 2Z"/>
    </svg>
    <span>Probar nueva versión</span>
  </a>
</li>
<script>
// Switcher v1 → v2: calcula URL equivalente según donde estés en v1.
(function() {
  var link = document.getElementById('lx-switch-to-v2');
  if (!link) return;
  var path = window.location.pathname;
  var base = '<?= base_url() ?>';

  // Mapeos URL v1 → URL v2. Si la pantalla actual NO tiene equivalente v2,
  // fallback al listado de presupuestos.
  var rules = [
    // /sisvent/commercial/budgets/edit/123 → /sisvent/v2/presupuestos/123
    { re: /\/sisvent\/commercial\/budgets\/(?:edit|view)\/(\d+)/, build: function(m) { return base + 'sisvent/v2/presupuestos/' + m[1]; } },
    // /sisvent/commercial/budgets → /sisvent/v2/presupuestos
    { re: /\/sisvent\/commercial\/budgets/, build: function() { return base + 'sisvent/v2/presupuestos'; } },
  ];
  for (var i = 0; i < rules.length; i++) {
    var m = path.match(rules[i].re);
    if (m) {
      link.href = rules[i].build(m);
      return;
    }
  }
  // Sin match → fallback default ya seteado en href
})();
</script>
