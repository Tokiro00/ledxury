<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Sparkline (mini chart SVG inline)
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/sparkline', [
 *       'data'  => [12, 18, 15, 22, 19, 28, 38],
 *       'color' => 'var(--lx-accent)',  // opcional
 *       'width' => 120, 'height' => 36,
 *       'fill'  => true,                  // opcional, area bajo la curva
 *   ]);
 */
$data   = $data   ?? [];
$color  = $color  ?? 'var(--lx-accent)';
$width  = (int) ($width  ?? 120);
$height = (int) ($height ?? 36);
$fill   = $fill ?? false;

if (count($data) < 2) {
    echo '<svg width="' . $width . '" height="' . $height . '"></svg>';
    return;
}

$max = max($data);
$min = min($data);
$rng = ($max - $min) ?: 1;
$step = $width / (count($data) - 1);

$pts = [];
foreach ($data as $i => $v) {
    $x = $i * $step;
    $y = $height - (($v - $min) / $rng) * ($height - 4) - 2;
    $pts[] = [round($x, 1), round($y, 1)];
}

$d = '';
foreach ($pts as $i => $p) {
    $d .= ($i === 0 ? 'M' : ' L') . $p[0] . ' ' . $p[1];
}
$fillD = $d . " L $width $height L 0 $height Z";
?>
<svg width="<?= $width ?>" height="<?= $height ?>" viewBox="0 0 <?= $width ?> <?= $height ?>" style="display:block;">
  <?php if ($fill): ?>
    <path d="<?= $fillD ?>" fill="<?= htmlspecialchars($color) ?>" opacity="0.08"/>
  <?php endif; ?>
  <path d="<?= $d ?>" fill="none" stroke="<?= htmlspecialchars($color) ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
