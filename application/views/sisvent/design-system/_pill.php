<?php
/**
 * Design System partial — Pill (v1.28.0)
 *
 * Status badge con vocabulario cerrado segun docs/design-kit/SKILL.md.
 * El partial NO valida que el label este en el vocabulario — eso lo
 * hace scripts/lint_views.php (Fase 4 de v1.28.0).
 *
 * Diferencia con _severity_pill.php (legacy, no se borra):
 *   - _severity_pill.php   buckets por dias vencidos (≤60 / 61-180 / 181-365 / >365)
 *   - _pill.php            status genericos (vendedor, factura, cliente, producto)
 *
 * Vocabulario sugerido (lowercase siempre):
 *   vendedor: activo, en riesgo, critico, inactivo
 *   factura:  pagada, pendiente, parcial, vencida, anulada
 *   cliente:  al dia, con saldo, bloqueado
 *   producto: en stock, bajo stock, agotado
 *
 * Parametros:
 *   tone   string  success|warning|danger|gray|info  (default gray)
 *   label  string  texto a mostrar (lowercase recomendado)
 *   icon   string  HTML pre-renderizado del icono. Opcional.
 *
 * Ejemplo:
 *   $this->load->view('design-system/_pill', [
 *       'tone'  => 'success',
 *       'label' => 'pagada',
 *   ]);
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$tone  = isset($tone) ? $tone : 'gray';
$label = isset($label) ? $label : '';
$icon  = isset($icon) ? $icon : '';

$allowedTones = array('success', 'warning', 'danger', 'gray', 'info');
if (!in_array($tone, $allowedTones, true)) {
    $tone = 'gray';
}
?>
<span class="pill-ds pill-ds--<?= htmlspecialchars($tone, ENT_QUOTES) ?>">
    <?= $icon /* HTML pre-escapado por el caller */ ?>
    <?= htmlspecialchars($label) ?>
</span>
