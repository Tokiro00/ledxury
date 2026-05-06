<?php
/**
 * Design System partial — Avatar (v1.28.0)
 *
 * Circulo con iniciales y gradient brand/danger/purple o flat inactive.
 * Tamaño configurable; el font-size se calcula automatico (size * 0.36).
 *
 * Parametros:
 *   initials  string  max 2 chars, lo que el caller decida (suele ser
 *                     primera letra de nombre + primera de apellido)
 *   size      int     pixeles, default 32
 *   tone      string  brand|danger|purple|inactive  (default brand)
 *
 * Ejemplo:
 *   $this->load->view('design-system/_avatar', [
 *       'initials' => 'CJ',
 *       'size'     => 40,
 *       'tone'     => 'brand',
 *   ]);
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$initials = isset($initials) ? (string) $initials : '';
$size     = isset($size) ? (int) $size : 32;
$tone     = isset($tone) ? $tone : 'brand';

$allowedTones = array('brand', 'danger', 'purple', 'inactive');
if (!in_array($tone, $allowedTones, true)) {
    $tone = 'brand';
}

if ($size < 16) { $size = 16; }
if ($size > 128) { $size = 128; }

$initials = mb_substr($initials, 0, 2);
$fontPx   = (int) round($size * 0.36);
?>
<span class="avatar-ds avatar-ds--<?= htmlspecialchars($tone, ENT_QUOTES) ?>"
      style="width:<?= $size ?>px;height:<?= $size ?>px;font-size:<?= $fontPx ?>px;">
    <?= htmlspecialchars($initials) ?>
</span>
