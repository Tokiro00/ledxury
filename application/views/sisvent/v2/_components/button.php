<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Button
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/button', [
 *       'variant' => 'primary',          // primary|accent|secondary|ghost|soft|danger
 *       'size'    => 'md',               // sm|md|lg
 *       'label'   => 'Nuevo presupuesto',
 *       'href'    => base_url(...),      // opcional. Si está, render <a>
 *       'onclick' => "...",              // opcional. Si está, render <button onclick>
 *       'icon'    => '<svg>...</svg>',   // opcional (HTML)
 *       'iconRight' => '...',
 *       'disabled' => false,             // si true, queda con cursor not-allowed
 *       'title'   => 'tooltip',          // opcional
 *   ]);
 *
 * Fase 1 read-only: cuando un botón es de mutación pero su accion vive en
 * v1, pasá $href con la URL v1 (en vez de disabled). Así el usuario "salta"
 * a v1 cuando hace click — más útil que un botón muerto.
 */
$variant   = $variant   ?? 'primary';
$size      = $size      ?? 'md';
$label     = $label     ?? '';
$href      = $href      ?? null;
$onclick   = $onclick   ?? null;
$icon      = $icon      ?? null;
$iconRight = $iconRight ?? null;
$disabled  = $disabled  ?? false;
$title     = $title     ?? '';

$sizes = [
    'sm' => ['fs' => 12,   'py' => 6,  'px' => 10, 'gap' => 6],
    'md' => ['fs' => 13,   'py' => 9,  'px' => 14, 'gap' => 7],
    'lg' => ['fs' => 14,   'py' => 12, 'px' => 18, 'gap' => 8],
];
$variants = [
    'primary'   => 'background:var(--lx-ink);color:#fff;border:1px solid var(--lx-ink);',
    'accent'    => 'background:var(--lx-accent);color:#fff;border:1px solid var(--lx-accent);',
    'secondary' => 'background:var(--lx-surface);color:var(--lx-ink);border:1px solid var(--lx-line-strong);',
    'ghost'     => 'background:transparent;color:var(--lx-ink2);border:1px solid transparent;',
    'soft'      => 'background:var(--lx-accent-soft);color:var(--lx-accent-ink);border:1px solid transparent;',
    'danger'    => 'background:var(--lx-danger);color:#fff;border:1px solid var(--lx-danger);',
];
$s = $sizes[$size] ?? $sizes['md'];
$variantCss = $variants[$variant] ?? $variants['primary'];

$style = "
  display:inline-flex; align-items:center; gap:{$s['gap']}px;
  font-family:inherit; font-size:{$s['fs']}px; font-weight:500;
  padding:{$s['py']}px {$s['px']}px;
  border-radius:8px; cursor:pointer;
  transition: opacity .12s, background .12s;
  white-space:nowrap; line-height:1;
  letter-spacing:-0.005em; text-decoration:none;
  {$variantCss}
";
if ($disabled) {
    $style .= 'opacity:0.5; cursor:not-allowed;';
}

$attrs = 'style="' . htmlspecialchars($style) . '"';
if ($title) $attrs .= ' title="' . htmlspecialchars($title) . '"';
if ($disabled) $attrs .= ' disabled';

if ($href && !$disabled) {
    echo '<a href="' . htmlspecialchars($href) . '" ' . $attrs . '>';
} else {
    $onclickAttr = ($onclick && !$disabled) ? ' onclick="' . htmlspecialchars($onclick) . '"' : '';
    echo '<button type="button"' . $onclickAttr . ' ' . $attrs . '>';
}

if ($icon)      echo '<span style="display:inline-flex;">' . $icon . '</span>';
echo htmlspecialchars($label);
if ($iconRight) echo '<span style="display:inline-flex;">' . $iconRight . '</span>';

echo ($href && !$disabled) ? '</a>' : '</button>';
