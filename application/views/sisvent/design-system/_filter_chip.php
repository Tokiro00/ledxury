<?php
/**
 * Design System partial — FilterChip (v1.28.0)
 *
 * Chip usado en toolbars stickys de filtros. Dos modos:
 *   - default: chip activo con label + boton X opcional para quitar.
 *   - dashed:  chip "+ filtro" para abrir picker. Renderea como <a>
 *              si se pasa $href, sino como <span>.
 *
 * Parametros:
 *   label      string  texto del chip
 *   removable  bool    muestra X (default true para chip default,
 *                      ignorado para dashed)
 *   dashed     bool    chip dashed para "+ filtro" (default false)
 *   href       string  si dashed=true, URL para el <a>. Opcional.
 *   onclick_remove  string  JS para el X. Ejemplo: 'removeFilter("vendedor")'.
 *                           Solo aplica si removable=true.
 *
 * Ejemplos:
 *   $this->load->view('design-system/_filter_chip', [
 *       'label' => 'Vendedor: Carlos',
 *       'onclick_remove' => 'removeFilter(\\'vendedor\\')'
 *   ]);
 *
 *   $this->load->view('design-system/_filter_chip', [
 *       'label'  => '+ filtro',
 *       'dashed' => true,
 *       'href'   => '#filter-picker',
 *   ]);
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$label          = isset($label) ? $label : '';
$removable      = isset($removable) ? (bool) $removable : true;
$dashed         = isset($dashed) ? (bool) $dashed : false;
$href           = isset($href) ? $href : null;
$onclick_remove = isset($onclick_remove) ? $onclick_remove : '';

if ($dashed) {
    $tag   = $href !== null ? 'a' : 'span';
    $hrefAttr = $href !== null ? ' href="' . htmlspecialchars($href, ENT_QUOTES) . '"' : '';
    echo '<' . $tag . ' class="chip-ds chip-ds--dashed"' . $hrefAttr . '>';
    echo htmlspecialchars($label);
    echo '</' . $tag . '>';
    return;
}
?>
<span class="chip-ds">
    <span><?= htmlspecialchars($label) ?></span>
    <?php if ($removable): ?>
        <button type="button"
                class="chip-ds__remove"
                aria-label="Quitar filtro"
                <?php if ($onclick_remove !== ''): ?>onclick="<?= htmlspecialchars($onclick_remove, ENT_QUOTES) ?>"<?php endif; ?>>×</button>
    <?php endif; ?>
</span>
