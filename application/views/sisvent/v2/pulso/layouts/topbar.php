<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso topbar — port directo del Topbar de components.jsx
 *
 * Espera:
 *   $pageTitle      string  (titulo principal, mostrado en DM Serif)
 *   $breadcrumbs    array   (ej. ['Comercial', 'Presupuestos'])
 *   $topbarActions  string  (HTML opcional con botones a la derecha)
 */
$pageTitle     = $pageTitle ?? 'Ledxury';
$breadcrumbs   = $breadcrumbs ?? array();
$topbarActions = $topbarActions ?? '';
?>
<header class="pulso-topbar">
    <div class="pulso-topbar-titles">
        <?php if (!empty($breadcrumbs)): ?>
        <div class="pulso-breadcrumbs">
            <?php foreach ($breadcrumbs as $i => $b):
                $isLast = ($i === count($breadcrumbs) - 1);
            ?>
                <?php if ($i > 0): ?><span class="pulso-sep">/</span><?php endif; ?>
                <span class="<?= $isLast ? 'pulso-current' : '' ?>"><?= htmlspecialchars($b) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <div class="pulso-topbar-actions">
        <div class="pulso-search-input">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" placeholder="Buscar presupuesto, cliente, producto…">
        </div>

        <button type="button" class="pulso-icon-btn" style="border:1px solid var(--pulso-line);width:36px;height:36px;position:relative;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9 M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            <span style="position:absolute;top:7px;right:7px;width:7px;height:7px;border-radius:99px;background:var(--pulso-butter);border:2px solid var(--pulso-surface);"></span>
        </button>

        <?= $topbarActions ?>

        <!-- Volver a v1 (preserva switcher) -->
        <a href="<?= base_url() ?>sisvent/dashboard" class="pulso-btn pulso-btn--ghost pulso-btn--sm" title="Volver a la versión clásica">
            ↩ Clásica
        </a>
    </div>
</header>
