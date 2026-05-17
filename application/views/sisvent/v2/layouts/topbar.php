<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — topbar
 *
 * Espera (todos opcionales):
 *   $pageTitle    — string (visible como <h1>)
 *   $breadcrumbs  — array de strings
 *   $topbarRight  — HTML pre-renderizado para slot derecho (botones)
 *   $v1Url        — URL para "Volver a versión clásica" (opcional)
 */
$pageTitle   = $pageTitle   ?? 'Página';
$breadcrumbs = $breadcrumbs ?? [];
$topbarRight = $topbarRight ?? '';
$v1Url       = $v1Url       ?? base_url('sisvent');
?>
<header style="
  padding: 18px 28px;
  border-bottom: 1px solid var(--lx-line);
  background: var(--lx-surface);
  display: flex; align-items: center; gap: 16px;
  flex: 0 0 auto;
">
  <div style="flex:1; min-width:0;">
    <?php if (!empty($breadcrumbs)): ?>
      <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--lx-ink3);margin-bottom:6px;">
        <?php foreach ($breadcrumbs as $i => $b): ?>
          <?php if ($i > 0): ?><span style="opacity:.5;">/</span><?php endif; ?>
          <span style="color: <?= $i === count($breadcrumbs)-1 ? 'var(--lx-ink2)' : 'var(--lx-ink3)' ?>;">
            <?= htmlspecialchars($b) ?>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <h1 style="font-size: var(--lx-text-2xl); margin: 0;">
      <?= $pageTitle /* permitir HTML — el caller debe escapar */ ?>
    </h1>
  </div>

  <div style="display:flex;align-items:center;gap:10px;">
    <!-- Buscador (read-only por ahora) -->
    <div style="position:relative;">
      <span style="position:absolute;left:11px;top:9px;color:var(--lx-ink3);">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </span>
      <input type="text" placeholder="Buscar presupuesto, cliente, producto…" disabled title="Disponible próximamente"
             style="
               width: 280px; font-family: inherit; font-size: 13px;
               padding: 8px 12px 8px 36px; border-radius: 8px;
               border: 1px solid var(--lx-line); background: var(--lx-bg);
               outline: none; color: var(--lx-ink); cursor: not-allowed; opacity: 0.7;
             ">
    </div>

    <!-- Botón notificaciones -->
    <button style="
      width:36px;height:36px;border-radius:8px;
      border:1px solid var(--lx-line);background:var(--lx-surface);
      color:var(--lx-ink2);display:grid;place-items:center;cursor:pointer;
      position:relative;font-family:inherit;
    " title="Notificaciones (próximamente)">
      <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
      </svg>
    </button>

    <!-- Switcher a v1 -->
    <a href="<?= htmlspecialchars($v1Url) ?>" id="lx-switch-to-v1"
       title="Volver a la versión clásica del ERP"
       style="
         display:inline-flex;align-items:center;gap:6px;
         font-size:12px;font-weight:500;color:var(--lx-ink2);
         padding:8px 12px;border-radius:8px;
         border:1px solid var(--lx-line);background:var(--lx-surface);
         text-decoration:none;cursor:pointer;
       ">
      ↩ Versión clásica
    </a>

    <?php if (!empty($topbarRight)): ?>
      <?= $topbarRight ?>
    <?php endif; ?>
  </div>
</header>
