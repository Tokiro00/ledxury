<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — KPI tile
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/kpi_tile', [
 *       'eyebrow' => 'Presupuestos hoy',
 *       'value'   => '38',
 *       'sub'     => '$4.82M en valor',
 *       'delta'   => '18.4%',
 *       'deltaTone' => 'up',   // up|dn|mid
 *       'spark'   => [12, 18, 15, 22, 19, 28, 38],   // opcional
 *       'accent'  => 'var(--lx-accent)',              // color del sparkline
 *   ]);
 */
$eyebrow   = $eyebrow   ?? '';
$value     = $value     ?? '';
$sub       = $sub       ?? '';
$delta     = $delta     ?? null;
$deltaTone = $deltaTone ?? 'up';
$spark     = $spark     ?? null;
$accent    = $accent    ?? 'var(--lx-accent)';

$deltaColors = ['up' => 'var(--lx-success)', 'dn' => 'var(--lx-danger)', 'mid' => 'var(--lx-ink3)'];
$deltaSym    = ['up' => '↑', 'dn' => '↓', 'mid' => '→'];
$dc = $deltaColors[$deltaTone] ?? $deltaColors['up'];
$ds = $deltaSym[$deltaTone] ?? '↑';
?>
<div style="
  background: var(--lx-surface); border:1px solid var(--lx-line);
  border-radius: var(--lx-radius-lg);
  padding: 18px 20px; display:flex; flex-direction:column; gap:14px;
">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
    <div>
      <div class="eyebrow"><?= htmlspecialchars($eyebrow) ?></div>
      <div class="tabular" style="
        font-family: var(--lx-font-display); font-weight: var(--lx-display-weight);
        font-size: var(--lx-text-3xl); margin-top: 8px; line-height: 1;
        letter-spacing: var(--lx-display-ls); color: var(--lx-ink);
      "><?= htmlspecialchars((string) $value) ?></div>
      <?php if ($sub): ?>
        <div style="font-size:12px;color:var(--lx-ink3);margin-top:6px;"><?= htmlspecialchars($sub) ?></div>
      <?php endif; ?>
    </div>
    <?php if ($spark && is_array($spark) && count($spark) >= 2): ?>
      <?php $this->load->view('sisvent/v2/_components/sparkline', ['data' => $spark, 'color' => $accent, 'fill' => true]); ?>
    <?php endif; ?>
  </div>
  <?php if ($delta !== null): ?>
    <div style="display:flex;align-items:center;gap:6px;font-size:12px;">
      <span style="color:<?= $dc ?>;display:inline-flex;align-items:center;gap:3px;font-weight:500;">
        <?= $ds ?> <?= htmlspecialchars($delta) ?>
      </span>
      <span style="color:var(--lx-ink3);">vs período anterior</span>
    </div>
  <?php endif; ?>
</div>
