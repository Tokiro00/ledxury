<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Empty state
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/empty_state', [
 *       'icon'  => '<svg>...</svg>',
 *       'title' => 'Sin presupuestos',
 *       'text'  => 'Aún no hay presupuestos en este estado.',
 *       'ctaLabel' => 'Crear el primero',
 *       'ctaHref'  => '...',
 *   ]);
 */
$icon  = $icon  ?? '';
$title = $title ?? 'Sin datos';
$text  = $text  ?? '';
$ctaLabel = $ctaLabel ?? null;
$ctaHref  = $ctaHref  ?? null;
?>
<div style="
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding: 48px 24px; text-align:center;
  background: var(--lx-surface); border:1px dashed var(--lx-line);
  border-radius: var(--lx-radius-lg);
">
  <?php if ($icon): ?>
    <div style="
      width:48px;height:48px;border-radius:99px;
      background: var(--lx-bg); color: var(--lx-ink3);
      display:grid;place-items:center; margin-bottom:14px;
    "><?= $icon ?></div>
  <?php endif; ?>
  <h3 style="font-size:15px;color:var(--lx-ink);margin:0 0 6px;"><?= htmlspecialchars($title) ?></h3>
  <?php if ($text): ?>
    <p style="font-size:13px;color:var(--lx-ink3);max-width:320px;margin:0;">
      <?= htmlspecialchars($text) ?>
    </p>
  <?php endif; ?>
  <?php if ($ctaLabel && $ctaHref): ?>
    <a href="<?= htmlspecialchars($ctaHref) ?>" style="
      display:inline-flex;align-items:center;gap:6px;
      margin-top:16px;padding:8px 14px;
      background:var(--lx-ink);color:#fff;
      font-size:13px;font-weight:500;
      border-radius:8px;text-decoration:none;
    "><?= htmlspecialchars($ctaLabel) ?></a>
  <?php endif; ?>
</div>
