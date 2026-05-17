<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Filter chip (botón redondo clickeable con count)
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/filter_chip', [
 *       'label'  => 'Nuevos',
 *       'count'  => 24,                                       // opcional
 *       'active' => false,
 *       'href'   => '?estado=nuevo',                          // GET filter
 *   ]);
 */
$label  = $label  ?? '';
$count  = $count  ?? null;
$active = $active ?? false;
$href   = $href   ?? '#';
?>
<a href="<?= htmlspecialchars($href) ?>" style="
  display:inline-flex; align-items:center; gap:6px;
  font-family:inherit; font-size:13px; font-weight:500;
  padding:7px 12px; border-radius:999px; cursor:pointer;
  border:1px solid <?= $active ? 'var(--lx-ink)' : 'var(--lx-line)' ?>;
  background: <?= $active ? 'var(--lx-ink)' : 'var(--lx-surface)' ?>;
  color: <?= $active ? '#fff' : 'var(--lx-ink2)' ?>;
  transition: all .12s;
  text-decoration:none;
">
  <?= htmlspecialchars($label) ?>
  <?php if ($count !== null): ?>
    <span style="
      font-size:11px; padding:1px 6px; border-radius:99px;
      background: <?= $active ? 'rgba(255,255,255,0.18)' : 'var(--lx-bg)' ?>;
      color: <?= $active ? '#fff' : 'var(--lx-ink3)' ?>;
    "><?= (int) $count ?></span>
  <?php endif; ?>
</a>
