<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Pill (badge pequeño con tono)
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/pill', [
 *       'tone'  => 'success', // success|warning|danger|info|neutral|ink
 *       'dot'   => true,
 *       'label' => 'entregado',
 *   ]);
 */
$tone  = $tone  ?? 'neutral';
$dot   = $dot   ?? false;
$label = $label ?? '';

$tones = [
    'success'  => ['bg' => '#E8F5EC', 'fg' => '#138A4A', 'dot' => '#138A4A'],
    'warning'  => ['bg' => '#FBF3E0', 'fg' => '#A67110', 'dot' => '#C98A14'],
    'danger'   => ['bg' => '#FDEBE9', 'fg' => '#A23026', 'dot' => '#D03A2E'],
    'info'     => ['bg' => 'var(--lx-accent-soft)', 'fg' => 'var(--lx-accent-ink)', 'dot' => 'var(--lx-accent)'],
    'neutral'  => ['bg' => '#F4F4F5', 'fg' => '#4A4F66', 'dot' => '#8A8F9F'],
    'ink'      => ['bg' => 'var(--lx-ink)', 'fg' => '#fff', 'dot' => '#fff'],
];
$t = $tones[$tone] ?? $tones['neutral'];
?>
<span style="
  display:inline-flex; align-items:center; gap:6px;
  padding:3px 9px; border-radius:999px;
  font-size:11.5px; font-weight:500; letter-spacing:0;
  background: <?= $t['bg'] ?>;
  color: <?= $t['fg'] ?>;
  line-height:1.3;
">
  <?php if ($dot): ?>
    <span style="width:6px;height:6px;border-radius:99px;background:<?= $t['dot'] ?>;"></span>
  <?php endif; ?>
  <?= htmlspecialchars($label) ?>
</span>
