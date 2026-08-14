<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso × Ledxury — Presupuesto · detalle
 */
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function ($n) {
    $n = (float)$n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};

// Estados de PRESUPUESTOS
$stateMap = array(
    0 => array('label' => 'nuevo',       'tone' => 'warning'),
    1 => array('label' => 'preparando',  'tone' => 'info'),
    2 => array('label' => 'con guía',    'tone' => 'neutral'),
    3 => array('label' => 'en tránsito', 'tone' => 'info'),
    4 => array('label' => 'entregado',   'tone' => 'success'),
    5 => array('label' => 'incidencia',  'tone' => 'danger'),
);
$stateNum = (int)$budget->state;
$state    = $stateMap[$stateNum] ?? $stateMap[0];

// Subtotales
$subtotal = 0;
foreach ($details as $d) $subtotal += (float)($d->total ?? 0);
$ivaTotal = 0;
if (!empty($budget->hasIva) && !empty($budget->iva)) {
    $ivaTotal = $subtotal * ((float)$budget->iva / 100);
}
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Presupuesto #<?= str_pad($budget->idBudget, 6, '0', STR_PAD_LEFT) ?> · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">
    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <?php
        $topbarActions = '
            <button onclick="window.print()" class="pulso-btn pulso-btn--secondary pulso-btn--sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9V2h12v7 M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2 M6 14h12v8H6z"/>
                </svg>
                Imprimir
            </button>
            <a href="' . base_url('sisvent/commercial/budgets/edit/' . (int)$budget->idBudget) . '" class="pulso-btn pulso-btn--primary pulso-btn--pill pulso-btn--sm">
                Editar en clásica
            </a>
        ';
        $this->load->view('sisvent/v2/pulso/layouts/topbar', array(
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'topbarActions' => $topbarActions,
        ));
        ?>

        <div class="pulso-content" style="max-width: 920px; margin: 0 auto;">

            <!-- Header de presupuesto -->
            <div class="pulso-card" style="margin-bottom: 16px; position:relative; overflow:hidden;">
                <?php if ($stateNum === 4): ?>
                <div style="position:absolute; top:-24px; right:-24px; padding:24px 38px 8px 38px; background:var(--pulso-mint); color:#fff; transform:rotate(35deg); font-weight:800; font-size:11px; letter-spacing:0.1em; text-transform:uppercase;">
                    Entregado
                </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; flex-wrap:wrap;">
                    <div>
                        <div class="pulso-eyebrow">Presupuesto</div>
                        <div style="font-family:var(--pulso-font-display); font-size:36px; line-height:1; letter-spacing:-0.02em; margin-top:6px; color:var(--pulso-ink);">
                            #<?= str_pad($budget->idBudget, 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div style="margin-top:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span class="pulso-pill pulso-pill--<?= $state['tone'] ?>">
                                <span class="pulso-dot"></span>
                                <?= htmlspecialchars($state['label']) ?>
                            </span>
                            <?php if (!empty($budget->archived)): ?>
                            <span class="pulso-pill pulso-pill--neutral">archivado</span>
                            <?php endif; ?>
                            <?php if (!empty($invoice)): ?>
                            <a href="<?= base_url('sisvent/commercial/invoices/' . (int)$invoice->idInvoice) ?>" class="pulso-pill pulso-pill--success" style="text-decoration:none;">
                                ✓ facturado #<?= str_pad($invoice->idInvoice, 6, '0', STR_PAD_LEFT) ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($budget->list_price)): ?>
                            <span class="pulso-pill pulso-pill--info">precio lista</span>
                            <?php endif; ?>
                            <?php if (!empty($budget->e_commerce)): ?>
                            <span class="pulso-pill pulso-pill--info">e-commerce</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <div class="pulso-eyebrow">Total</div>
                        <div style="font-family:var(--pulso-font-display); font-size:36px; line-height:1; letter-spacing:-0.02em; margin-top:6px; color:var(--pulso-accent);">
                            <?= $fmt($budget->total) ?>
                        </div>
                        <?php if (!empty($budget->date)): ?>
                        <div style="margin-top:8px; font-size:12px; color:var(--pulso-ink3);">
                            Creado <?= date('d M Y · H:i', strtotime($budget->date)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Datos cliente + vendedor + bodega -->
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:24px; padding-top:20px; border-top:1px solid var(--pulso-line);">
                    <div>
                        <div class="pulso-eyebrow" style="margin-bottom:6px;">Cliente</div>
                        <?php if ($client): ?>
                        <div style="font-weight:700; font-size:14px; color:var(--pulso-ink);"><?= htmlspecialchars($client->name) ?></div>
                        <?php if (!empty($client->idNum)): ?>
                            <div style="font-size:12px; color:var(--pulso-ink2); margin-top:2px; font-family:var(--pulso-font-mono);"><?= htmlspecialchars($client->idNum) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($client->city)): ?>
                            <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;"><?= htmlspecialchars($client->city) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($client->cellphone)): ?>
                            <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;">📱 <?= htmlspecialchars($client->cellphone) ?></div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div style="color:var(--pulso-ink3);">—</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="pulso-eyebrow" style="margin-bottom:6px;">Vendedor</div>
                        <?php if ($vendor): ?>
                        <div style="font-weight:600; font-size:14px; color:var(--pulso-ink);"><?= htmlspecialchars($vendor->name) ?></div>
                        <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;"><?= htmlspecialchars($budget->vendorId) ?></div>
                        <?php else: ?>
                        <div style="color:var(--pulso-ink3);">—</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="pulso-eyebrow" style="margin-bottom:6px;">Bodega</div>
                        <?php if ($store): ?>
                        <div style="font-weight:600; font-size:14px; color:var(--pulso-ink);"><?= htmlspecialchars($store->name) ?></div>
                        <?php else: ?>
                        <div style="color:var(--pulso-ink3);">—</div>
                        <?php endif; ?>
                        <?php if (!empty($budget->delivery_type)): ?>
                        <div style="font-size:12px; color:var(--pulso-ink3); margin-top:6px;">
                            Entrega: <strong style="color:var(--pulso-ink2);"><?= htmlspecialchars($budget->delivery_type) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="pulso-card" style="margin-bottom: 16px; padding: 0;">
                <div style="padding: 18px 22px 12px;">
                    <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                        Productos
                    </div>
                </div>
                <table class="pulso-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Descripción</th>
                            <th class="pulso-text-right">Cant.</th>
                            <th class="pulso-text-right">Precio</th>
                            <th class="pulso-text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($details)): ?>
                        <tr><td colspan="5" style="padding:32px; text-align:center; color:var(--pulso-ink3);">Sin items.</td></tr>
                        <?php else: foreach ($details as $d):
                            $prod = $productMap[$d->productId] ?? null;
                            $desc = $prod ? $prod->description : '(producto eliminado)';
                            $cant = (int)($d->quantity ?? 0);
                            $unit = (float)($d->unit ?? 0);
                            $total = (float)($d->total ?? ($cant * $unit));
                        ?>
                        <tr>
                            <td class="pulso-mono" style="font-weight:600; color:var(--pulso-ink);"><?= htmlspecialchars($d->productId) ?></td>
                            <td style="color:var(--pulso-ink);"><?= htmlspecialchars($desc) ?></td>
                            <td class="pulso-text-right pulso-num"><?= $cant ?></td>
                            <td class="pulso-text-right pulso-num"><?= $fmt($unit) ?></td>
                            <td class="pulso-text-right pulso-num" style="font-weight:600; color:var(--pulso-ink);"><?= $fmt($total) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <!-- Totales -->
                <div style="padding: 16px 22px 18px; border-top: 1px solid var(--pulso-line); display:flex; justify-content:flex-end;">
                    <div style="min-width: 280px;">
                        <div style="display:flex; justify-content:space-between; padding:5px 0; font-size:13px;">
                            <span style="color:var(--pulso-ink3);">Subtotal</span>
                            <span class="pulso-num" style="color:var(--pulso-ink2);"><?= $fmt($subtotal) ?></span>
                        </div>
                        <?php if ($ivaTotal > 0): ?>
                        <div style="display:flex; justify-content:space-between; padding:5px 0; font-size:13px;">
                            <span style="color:var(--pulso-ink3);">IVA (<?= $budget->iva ?>%)</span>
                            <span class="pulso-num" style="color:var(--pulso-ink2);">+<?= $fmt($ivaTotal) ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; padding:12px 0 4px; margin-top:6px; border-top: 2px solid var(--pulso-line-strong);">
                            <span style="font-weight:700; font-size:14px;">Total</span>
                            <span class="pulso-num" style="font-family:var(--pulso-font-display); font-size:22px; line-height:1; color:var(--pulso-accent); letter-spacing:-0.01em;">
                                <?= $fmt($budget->total) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado de envío + Comentarios (2 col) -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;" class="pulso-dash-grid">

                <!-- Guías shipping -->
                <div class="pulso-card">
                    <div style="display:flex; align-items:center; margin-bottom: 12px;">
                        <div style="font-family: var(--pulso-font-display); font-size: 18px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                            Envío
                        </div>
                        <div style="margin-left:auto; font-size:12px; color:var(--pulso-ink3); font-weight:600;">
                            <?= count($guias) ?> guía<?= count($guias) === 1 ? '' : 's' ?>
                        </div>
                    </div>

                    <?php if (empty($guias)): ?>
                        <div style="padding: 20px; text-align:center; color:var(--pulso-ink3); font-size:13px;">
                            Sin guías generadas.
                        </div>
                    <?php else: foreach ($guias as $i => $g):
                        $gStatus = strtolower($g->status ?: '');
                        $gTone = in_array($gStatus, ['entregado','delivered']) ? 'success'
                              : (in_array($gStatus, ['anulado','cancelado']) ? 'danger'
                              : (in_array($gStatus, ['transito','en_transito','enviado','despachado']) ? 'info' : 'warning'));
                    ?>
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 0; <?= $i < count($guias) - 1 ? 'border-bottom:1px solid var(--pulso-line);' : '' ?>">
                        <div style="width:32px; height:32px; border-radius:10px; background:var(--pulso-ink); color:var(--pulso-bg); display:grid; place-items:center; flex:0 0 auto;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                                <path d="M15 18H9 M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                                <circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
                            </svg>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; color:var(--pulso-ink);" class="pulso-mono">
                                <?= htmlspecialchars($g->numeroPreenvio ?: '#' . $g->id) ?>
                            </div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;">
                                <?= htmlspecialchars($g->carrierName ?: 'Interrapidísimo') ?>
                                <?php if (!empty($g->valorTotal)): ?>
                                · Flete <?= $fmt($g->valorTotal) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="pulso-pill pulso-pill--<?= $gTone ?>" style="flex:0 0 auto;">
                            <?= htmlspecialchars($g->status ?: '—') ?>
                        </span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Conversión a factura -->
                <div class="pulso-card">
                    <div style="font-family: var(--pulso-font-display); font-size: 18px; letter-spacing: -0.01em; color: var(--pulso-ink); margin-bottom: 12px;">
                        Conversión a factura
                    </div>

                    <?php if (empty($invoice)): ?>
                    <div style="display:flex; flex-direction:column; align-items:center; padding:20px; text-align:center; gap:10px;">
                        <div style="width:48px; height:48px; border-radius:14px; background:var(--pulso-bg); color:var(--pulso-ink3); display:grid; place-items:center;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8"/>
                            </svg>
                        </div>
                        <div style="color:var(--pulso-ink3); font-size:13px;">Aún sin factura asociada</div>
                    </div>
                    <?php else: ?>
                    <a href="<?= base_url('sisvent/commercial/invoices/' . (int)$invoice->idInvoice) ?>" style="display:block; padding:14px; background:var(--pulso-bg); border-radius:12px; text-decoration:none; color:inherit;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:38px; height:38px; border-radius:12px; background:var(--pulso-mint); color:#fff; display:grid; place-items:center;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:var(--pulso-ink);" class="pulso-mono">
                                    Factura #<?= str_pad($invoice->idInvoice, 6, '0', STR_PAD_LEFT) ?>
                                </div>
                                <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;">
                                    <?= $fmt($invoice->total) ?>
                                    <?php if (!empty($invoice->date)): ?>
                                    · <?= date('d M Y', strtotime($invoice->date)) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="pulso-pill pulso-pill--<?= (int)$invoice->state === 2 ? 'success' : 'warning' ?>">
                                <?= (int)$invoice->state === 2 ? 'pagada' : ((int)$invoice->state === 1 ? 'parcial' : ((int)$invoice->state === 3 ? 'anulada' : 'por cobrar')) ?>
                            </span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Comentarios -->
            <?php if (!empty($budget->comments)): ?>
            <div class="pulso-card" style="margin-bottom:16px; background: var(--pulso-bg);">
                <div class="pulso-eyebrow" style="margin-bottom:6px;">Notas</div>
                <div style="font-size:13px; color:var(--pulso-ink2); white-space:pre-wrap;">
                    <?= htmlspecialchars($budget->comments) ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<style>
@media (max-width: 900px) {
    .pulso-dash-grid { grid-template-columns: 1fr !important; }
}
@media print {
    .pulso-sidebar, .pulso-topbar { display: none !important; }
    .pulso-main { background: white !important; }
    .pulso-card { box-shadow: none !important; border-color: #ddd !important; }
}
</style>

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>
</body>
</html>
