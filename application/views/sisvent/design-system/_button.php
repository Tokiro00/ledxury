<?php
/**
 * Design System partial — Button (v1.28.0)
 *
 * Renderea un boton del DS con la firma canonica del kit.
 * Las clases legacy `.btn` (whitelist/buttons.scss) siguen disponibles
 * en paralelo para vistas no migradas — esto es la version moderna.
 *
 * Parametros (array $data via load->view o variables sueltas en include):
 *   variant   string  primary|secondary|ghost|danger|success  (default primary)
 *   label     string  texto visible
 *   icon      string  HTML del icono (SVG inline). Opcional.
 *   type      string  button|submit|reset  (default button)
 *   attrs     array   atributos HTML extra. Ejemplo: ['id' => 'foo', 'data-x' => 'y']
 *   classes   string  clases extra para el button (espaciadas). Opcional.
 *
 * Ejemplo:
 *   $this->load->view('design-system/_button', [
 *       'variant' => 'primary',
 *       'label'   => 'Guardar',
 *       'icon'    => '<svg class="w-4 h-4" ...></svg>',
 *       'type'    => 'submit',
 *       'attrs'   => ['id' => 'btn-save', 'data-action' => 'save'],
 *   ]);
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$variant = isset($variant) ? $variant : 'primary';
$label   = isset($label) ? $label : '';
$icon    = isset($icon) ? $icon : '';
$type    = isset($type) ? $type : 'button';
$attrs   = isset($attrs) && is_array($attrs) ? $attrs : array();
$classes = isset($classes) ? $classes : '';

$allowedVariants = array('primary', 'secondary', 'ghost', 'danger', 'success');
if (!in_array($variant, $allowedVariants, true)) {
    $variant = 'primary';
}

$attrStr = '';
foreach ($attrs as $k => $v) {
    $attrStr .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES) . '="' . htmlspecialchars((string) $v, ENT_QUOTES) . '"';
}
?>
<button type="<?= htmlspecialchars($type, ENT_QUOTES) ?>"
        class="btn-ds btn-ds--<?= htmlspecialchars($variant, ENT_QUOTES) ?><?= $classes !== '' ? ' ' . htmlspecialchars($classes, ENT_QUOTES) : '' ?>"
        <?= $attrStr ?>>
    <?= $icon /* HTML pre-escapado por el caller */ ?>
    <?php if ($label !== ''): ?><span><?= htmlspecialchars($label) ?></span><?php endif; ?>
</button>
