<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso × Ledxury — Factura · detalle
 * Layout estilo factura imprimible con cabecera, items, totales y pagos.
 */
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function ($n) {
    $n = (float)$n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};
$stateNum = (int)$invoice->state;
$stMap = array(
    0 => array('label' => 'por cobrar', 'tone' => 'warning'),
    1 => array('label' => 'parcial',    'tone' => 'info'),
    2 => array('label' => 'pagada',     'tone' => 'success'),
    3 => array('label' => 'anulada',    'tone' => 'neutral'),
);
$state = $stMap[$stateNum] ?? $stMap[0];
$vencida = $stateNum === 0 && !empty($invoice->date) && (time() - strtotime($invoice->date)) > 30 * 86400;
if ($vencida) $state = array('label' => 'vencida', 'tone' => 'danger');

// Subtotales
$subtotal = 0;
$ivaTotal = 0;
foreach ($details as $d) $subtotal += (float)$d->total;
$descTotal = (float)($invoice->discount ?? 0);
if (!empty($invoice->hasIva) && !empty($invoice->iva)) {
    $ivaPct = (float)$invoice->iva;
    $ivaTotal = $subtotal * ($ivaPct / 100);
}
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Factura #<?= str_pad($invoice->idInvoice, 6, '0', STR_PAD_LEFT) ?> · Ledxury</title>
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
            <a href="' . base_url('sisvent/commercial/invoices/edit/' . (int)$invoice->idInvoice) . '" class="pulso-btn pulso-btn--primary pulso-btn--pill pulso-btn--sm">
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

            <!-- Header de factura -->
            <div class="pulso-card" style="margin-bottom: 16px; position:relative; overflow:hidden;">
                <!-- Banda coral si pagada -->
                <?php if ($stateNum === 2): ?>
                <div style="position:absolute; top:-24px; right:-24px; padding:24px 38px 8px 38px; background:var(--pulso-mint); color:#fff; transform:rotate(35deg); font-weight:800; font-size:11px; letter-spacing:0.1em; text-transform:uppercase;">
                    Pagada
                </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; flex-wrap:wrap;">
                    <div>
                        <div class="pulso-eyebrow">Factura electrónica</div>
                        <div style="font-family:var(--pulso-font-display); font-size:36px; line-height:1; letter-spacing:-0.02em; margin-top:6px; color:var(--pulso-ink);">
                            #<?= str_pad($invoice->idInvoice, 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div style="margin-top:10px; display:flex; align-items:center; gap:8px;">
                            <span class="pulso-pill pulso-pill--<?= $state['tone'] ?>">
                                <span class="pulso-dot"></span>
                                <?= htmlspecialchars($state['label']) ?>
                            </span>
                            <?php if (!empty($invoice->budgetId)): ?>
                            <span class="pulso-pill pulso-pill--neutral">
                                desde presup. #<?= str_pad($invoice->budgetId, 6, '0', STR_PAD_LEFT) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <div class="pulso-eyebrow">Total facturado</div>
                        <div style="font-family:var(--pulso-font-display); font-size:36px; line-height:1; letter-spacing:-0.02em; margin-top:6px; color:var(--pulso-accent);">
                            <?= $fmt($invoice->total) ?>
                        </div>
                        <?php if ($saldo > 0): ?>
                        <div style="margin-top:8px; font-size:12px; color:var(--pulso-ink3);">
                            Pagado <?= $fmtShort($totalPagado) ?> · <span style="color:var(--pulso-danger); font-weight:700;">Saldo <?= $fmt($saldo) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Datos cliente + vendedor + fecha -->
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
                        <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;"><?= htmlspecialchars($vendor->idUser ?? $invoice->vendorId) ?></div>
                        <?php if (!empty($facturadoPor)): ?>
                        <div style="font-size:11px; color:var(--pulso-ink3); margin-top:6px; padding-top:6px; border-top:1px dashed var(--pulso-line);">
                            <span style="font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">Facturada por</span><br>
                            <?= htmlspecialchars($facturadoPor) ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div style="color:var(--pulso-ink3);">—</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="pulso-eyebrow" style="margin-bottom:6px;">Fecha</div>
                        <div style="font-weight:600; font-size:14px; color:var(--pulso-ink);">
                            <?= !empty($invoice->date) ? date('d M Y', strtotime($invoice->date)) : '—' ?>
                        </div>
                        <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;">
                            <?= !empty($invoice->date) ? date('H:i', strtotime($invoice->date)) : '' ?>
                        </div>
                        <?php if (!empty($invoice->payment)): ?>
                        <div style="font-size:12px; color:var(--pulso-ink3); margin-top:6px;">
                            Cond. pago: <strong style="color:var(--pulso-ink2);"><?= htmlspecialchars($invoice->payment) ?></strong>
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
                        ?>
                        <tr>
                            <td class="pulso-mono" style="font-weight:600; color:var(--pulso-ink);"><?= htmlspecialchars($d->productId) ?></td>
                            <td style="color:var(--pulso-ink);"><?= htmlspecialchars($desc) ?></td>
                            <td class="pulso-text-right pulso-num"><?= (int)$d->quantity ?></td>
                            <td class="pulso-text-right pulso-num"><?= $fmt($d->unit) ?></td>
                            <td class="pulso-text-right pulso-num" style="font-weight:600; color:var(--pulso-ink);"><?= $fmt($d->total) ?></td>
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
                        <?php if ($descTotal > 0): ?>
                        <div style="display:flex; justify-content:space-between; padding:5px 0; font-size:13px;">
                            <span style="color:var(--pulso-ink3);">Descuento<?= !empty($invoice->discount_perc) ? ' (' . $invoice->discount_perc . '%)' : '' ?></span>
                            <span class="pulso-num" style="color:var(--pulso-warning);">−<?= $fmt($descTotal) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($ivaTotal > 0): ?>
                        <div style="display:flex; justify-content:space-between; padding:5px 0; font-size:13px;">
                            <span style="color:var(--pulso-ink3);">IVA (<?= $invoice->iva ?>%)</span>
                            <span class="pulso-num" style="color:var(--pulso-ink2);">+<?= $fmt($ivaTotal) ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; padding:12px 0 4px; margin-top:6px; border-top: 2px solid var(--pulso-line-strong);">
                            <span style="font-weight:700; font-size:14px;">Total</span>
                            <span class="pulso-num" style="font-family:var(--pulso-font-display); font-size:22px; line-height:1; color:var(--pulso-accent); letter-spacing:-0.01em;">
                                <?= $fmt($invoice->total) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagos + Guías (2 col) -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;" class="pulso-dash-grid">

                <!-- Pagos -->
                <div class="pulso-card">
                    <div style="display:flex; align-items:center; margin-bottom: 12px;">
                        <div style="font-family: var(--pulso-font-display); font-size: 18px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                            Pagos recibidos
                        </div>
                        <div style="margin-left:auto; font-size:12px; color:var(--pulso-ink3); font-weight:600;">
                            <?= count($payments) ?> registro<?= count($payments) === 1 ? '' : 's' ?>
                        </div>
                    </div>

                    <?php if (empty($payments)): ?>
                        <div style="padding: 20px; text-align:center; color:var(--pulso-ink3); font-size:13px;">
                            Sin pagos registrados.
                        </div>
                    <?php else:
                        foreach ($payments as $i => $p):
                    ?>
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 0; <?= $i < count($payments) - 1 ? 'border-bottom:1px solid var(--pulso-line);' : '' ?>">
                        <div style="width:32px; height:32px; border-radius:10px; background:var(--pulso-mint); color:#fff; display:grid; place-items:center; flex:0 0 auto;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
                            </svg>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; color:var(--pulso-ink);">
                                <?= $fmt($p->payment) ?>
                                <?php if (!empty($p->methodName)): ?>
                                <span style="font-size:11px; color:var(--pulso-ink3); font-weight:500;">· <?= htmlspecialchars($p->methodName) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;">
                                <?= !empty($p->date) ? date('d M Y', strtotime($p->date)) : '—' ?>
                                <?php if (!empty($p->originType)): ?>
                                · <?= htmlspecialchars($p->originType) ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($p->comments)): ?>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px; font-style:italic;">"<?= htmlspecialchars($p->comments) ?>"</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="padding-top:12px; margin-top:8px; border-top:2px solid var(--pulso-line); display:flex; justify-content:space-between; align-items:baseline;">
                        <span style="font-size:13px; font-weight:700;">Pagado</span>
                        <span class="pulso-num" style="font-family:var(--pulso-font-display); font-size:20px; color:var(--pulso-mint);"><?= $fmt($totalPagado) ?></span>
                    </div>
                    <?php if ($saldo > 0): ?>
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-top:6px;">
                        <span style="font-size:13px; font-weight:700; color:var(--pulso-danger);">Saldo pendiente</span>
                        <span class="pulso-num" style="font-family:var(--pulso-font-display); font-size:20px; color:var(--pulso-danger);"><?= $fmt($saldo) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

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

            </div>

            <!-- Comentarios -->
            <?php if (!empty($invoice->comments)): ?>
            <div class="pulso-card" style="margin-bottom:16px; background: var(--pulso-bg);">
                <div class="pulso-eyebrow" style="margin-bottom:6px;">Notas</div>
                <div style="font-size:13px; color:var(--pulso-ink2); white-space:pre-wrap;">
                    <?= htmlspecialchars($invoice->comments) ?>
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
