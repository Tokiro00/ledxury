<?php
/**
 * Design System partial — PageHeader (v1.29.2)
 *
 * Header canonico de toda list/dashboard view. Reproduce el patron del
 * mockup del kit y de la vista clientStatement (referencia visual):
 *
 *   EYEBROW MICRO CAPS                           [actions slot]
 *   Titulo grande navy
 *   Subtitulo opcional en fg-3
 *   ─────────────────────────────────────────────────────────── (hair line)
 *
 * IMPORTANTE: Estilos inline directos en cada elemento. No usa <style>
 * embebido ni clases custom — eso es vulnerable a PurgeCSS y a
 * sobreescritura por h1 global de colors_and_type.css. Los estilos
 * inline ganan por especificidad maxima sin tocar el bundle.
 *
 * Parametros:
 *   eyebrow   string  micro caps uppercase (ej. "COMERCIAL"). Opcional.
 *   title     string  titulo grande navy. Requerido.
 *   subtitle  string  texto secundario debajo. Opcional.
 *   actions   string  HTML pre-renderizado a la derecha (botones DS,
 *                     dropdowns, etc). Opcional.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$eyebrow  = isset($eyebrow) ? trim((string) $eyebrow) : '';
$title    = isset($title) ? (string) $title : '';
$subtitle = isset($subtitle) ? (string) $subtitle : '';
$actions  = isset($actions) ? (string) $actions : '';
?>
<header style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:4px 0 14px;margin:4px 0 18px;border-bottom:1px solid var(--border-default,#DDDFE8);">
    <div style="min-width:0;">
        <?php if ($eyebrow !== ''): ?>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--mam-blue-petroleo,#4487A0);margin-bottom:2px;line-height:1.2;">
                <?= htmlspecialchars(strtoupper($eyebrow)) ?>
            </div>
        <?php endif; ?>
        <h1 style="font-size:24px;font-weight:800;line-height:1.15;letter-spacing:-0.01em;color:var(--mam-blue-dark,#2B3164);margin:0;">
            <?= htmlspecialchars($title) ?>
        </h1>
        <?php if ($subtitle !== ''): ?>
            <p style="font-size:13px;color:var(--fg-3,#AEAAA6);margin:4px 0 0;line-height:1.4;">
                <?= $subtitle /* HTML permitido */ ?>
            </p>
        <?php endif; ?>
    </div>
    <?php if ($actions !== ''): ?>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?= $actions ?>
        </div>
    <?php endif; ?>
</header>
