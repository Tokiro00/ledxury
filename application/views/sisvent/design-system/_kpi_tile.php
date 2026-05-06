<?php
/**
 * Design System partial — KpiTile (v1.28.0)
 *
 * Bloque metrica con accent-bar izquierdo y wash gradient en bg.
 * Hover lift via box-shadow (CSS).
 *
 * Diferencia con _kpi_strip.php (legacy, no se borra):
 *   - _kpi_strip.php  layout grid de tiles antiguos (pre-Salesboard).
 *   - _kpi_tile.php   tile individual moderna; combinable en grid
 *                     custom segun la vista.
 *
 * Las oleadas v1.28.1+ migran callsites de _kpi_strip a _kpi_tile.
 *
 * Parametros:
 *   eyebrow      string  texto pequeño UPPERCASE arriba (ej. "VENTAS HOY")
 *   value        string|HTML  numero grande (ej. "$487.2M") — HTML permitido
 *                             para incluir spans con tone diferenciado
 *   delta        string  delta secundario (ej. "▲ $12.4M vs ayer"). Opcional.
 *   delta_tone   string  up|dn|mid|fg4 (default fg4 = neutral)
 *   accent       string  CSS color para el border-left + wash
 *                        (default var(--mam-blue-petroleo))
 *   slot         string  HTML extra debajo del value (sparklines,
 *                        progress bars, mini barras). Opcional.
 *
 * Ejemplo:
 *   $this->load->view('design-system/_kpi_tile', [
 *       'eyebrow'    => 'Ventas hoy',
 *       'value'      => '$487.2M',
 *       'delta'      => '▲ $12.4M vs ayer',
 *       'delta_tone' => 'up',
 *       'accent'     => 'var(--mam-green-program)',
 *   ]);
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$eyebrow    = isset($eyebrow) ? $eyebrow : '';
$value      = isset($value) ? $value : '';
$delta      = isset($delta) ? $delta : '';
$delta_tone = isset($delta_tone) ? $delta_tone : 'fg4';
$accent     = isset($accent) && $accent !== '' ? $accent : 'var(--mam-blue-petroleo)';
$slot       = isset($slot) ? $slot : '';

$allowedDeltaTones = array('up', 'dn', 'mid', 'fg4');
if (!in_array($delta_tone, $allowedDeltaTones, true)) {
    $delta_tone = 'fg4';
}
?>
<div class="kpi-tile" style="--accent: <?= htmlspecialchars($accent, ENT_QUOTES) ?>;">
    <div class="kpi-tile__eyebrow"><?= htmlspecialchars($eyebrow) ?></div>
    <div class="kpi-tile__value"><?= $value /* HTML permitido */ ?></div>
    <?php if ($delta !== ''): ?>
        <div class="kpi-tile__delta kpi-tile__delta--<?= htmlspecialchars($delta_tone, ENT_QUOTES) ?>">
            <?= htmlspecialchars($delta) ?>
        </div>
    <?php endif; ?>
    <?= $slot /* HTML pre-renderizado por el caller */ ?>
</div>
