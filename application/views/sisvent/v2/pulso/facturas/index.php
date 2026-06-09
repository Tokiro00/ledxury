<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso × Ledxury — Facturas (listado)
 */
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function ($n) {
    $n = (float) $n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};

$stateMap = array(
    0 => array('label' => 'por cobrar', 'tone' => 'warning'),
    1 => array('label' => 'parcial',    'tone' => 'info'),
    2 => array('label' => 'pagada',     'tone' => 'success'),
    3 => array('label' => 'anulada',    'tone' => 'neutral'),
);
$sparkStr = implode(',', $sparkVolumen);
$baseUrl  = base_url('sisvent/commercial/invoices');
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Facturas · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">

    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <?php
        $topbarActions = '
            <a href="' . base_url('sisvent/commercial/invoices/add') . '" class="pulso-btn pulso-btn--primary pulso-btn--pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14 M12 5v14"/>
                </svg>
                Nueva factura
            </a>
        ';
        $this->load->view('sisvent/v2/pulso/layouts/topbar', array(
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'topbarActions' => $topbarActions,
        ));
        ?>

        <div class="pulso-content">

            <!-- KPI strip -->
            <div class="pulso-kpi-grid" style="grid-template-columns: 1.4fr 1fr 1fr 1fr;">

                <div class="pulso-kpi pulso-kpi--big">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Cobrado este mes</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($volumenMes) ?></div>
                            <div class="pulso-kpi-sub"><?= number_format($cobradasMes, 0, ',', '.') ?> facturas pagadas</div>
                        </div>
                        <?php if (!empty($sparkVolumen) && count($sparkVolumen) > 1): ?>
                        <span data-pulso-sparkline="<?= $sparkStr ?>" data-color="#FF5A36" data-width="160" data-height="48"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Por cobrar</div>
                            <div class="pulso-kpi-value"><?= number_format($counts['pendiente'], 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">facturas pendientes</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Cartera vencida</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($carteraVencida) ?></div>
                            <div class="pulso-kpi-sub"><?= number_format($factsVencidas, 0, ',', '.') ?> facturas &gt; 30d</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Pagadas</div>
                            <div class="pulso-kpi-value"><?= number_format($counts['pagada'], 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">total histórico</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Filter chips -->
            <div class="pulso-filters">
                <?php
                $chips = array(
                    'todos'     => array('Todas',      $counts['todos']),
                    'pendiente' => array('Por cobrar', $counts['pendiente']),
                    'parcial'   => array('Parcial',    $counts['parcial']),
                    'pagada'    => array('Pagadas',    $counts['pagada']),
                    'anulada'   => array('Anuladas',   $counts['anulada']),
                );
                foreach ($chips as $key => $info):
                    list($label, $count) = $info;
                    $active = ($estado === $key);
                ?>
                <a href="<?= $baseUrl . '?estado=' . $key ?>" class="pulso-chip <?= $active ? 'is-active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                    <span class="pulso-chip-count"><?= number_format($count, 0, ',', '.') ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Tabla facturas -->
            <div class="pulso-table-wrap">
                <table class="pulso-table">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Fecha</th>
                            <th class="pulso-text-right">Total</th>
                            <th class="pulso-text-right">Saldo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" style="padding:48px;text-align:center;color:var(--pulso-ink3);">
                                Sin facturas en este estado.
                            </td>
                        </tr>
                        <?php else: foreach ($invoices as $inv):
                            $stateNum = (int)$inv->state;
                            $st = $stateMap[$stateNum] ?? $stateMap[0];
                            $clientName = !empty($inv->client_name) ? $inv->client_name : ($inv->clientName ?? 'Cliente');
                            // Usar el vendedor REAL (del budget si invoice quedó como Administrador)
                            $vendorName = !empty($inv->real_vendor_name) ? $inv->real_vendor_name
                                        : (!empty($inv->vendor_name) ? $inv->vendor_name : ($inv->vendorName ?? '—'));
                            $facturadoPor = $inv->facturado_por ?? null;
                            $words = preg_split('/\s+/', trim($clientName));
                            $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));

                            // Saldo: total - SUM(payments)
                            $pagado = (float)($inv->paid ?? $inv->totalPayment ?? 0);
                            $saldo = max(0, (float)$inv->total - $pagado);
                            $vencida = $stateNum === 0 && !empty($inv->date) && (time() - strtotime($inv->date)) > 30 * 86400;
                            if ($vencida) $st = array('label' => 'vencida', 'tone' => 'danger');
                        ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= base_url('sisvent/commercial/invoices/' . (int)$inv->idInvoice) ?>'">
                            <td>
                                <span class="pulso-mono" style="color:var(--pulso-ink);font-weight:600;">
                                    #<?= str_pad($inv->idInvoice, 6, '0', STR_PAD_LEFT) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span class="pulso-avatar" style="width:28px;height:28px;font-size:11px;">
                                        <?= htmlspecialchars($initials) ?: '?' ?>
                                    </span>
                                    <div style="font-weight:500;color:var(--pulso-ink);">
                                        <?= htmlspecialchars($clientName) ?>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--pulso-ink2);font-size:12px;">
                                <?= htmlspecialchars($vendorName) ?>
                                <?php if (!empty($facturadoPor)): ?>
                                <div style="font-size:10.5px; color:var(--pulso-ink3); margin-top:2px;">
                                    facturada por <?= htmlspecialchars($facturadoPor) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--pulso-ink3);font-size:12px;">
                                <?= !empty($inv->date) ? date('d M Y', strtotime($inv->date)) : '—' ?>
                            </td>
                            <td class="pulso-text-right pulso-num" style="font-weight:500;color:var(--pulso-ink);">
                                <?= $fmt($inv->total ?? 0) ?>
                            </td>
                            <td class="pulso-text-right pulso-num" style="color:<?= $saldo > 0 ? 'var(--pulso-danger)' : 'var(--pulso-ink3)' ?>;font-weight:<?= $saldo > 0 ? '600' : '400' ?>;">
                                <?= $saldo > 0 ? $fmt($saldo) : '$0' ?>
                            </td>
                            <td>
                                <span class="pulso-pill pulso-pill--<?= $st['tone'] ?>">
                                    <span class="pulso-dot"></span>
                                    <?= htmlspecialchars($st['label']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($lastPage > 1): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding:0 4px;">
                <div style="font-size:12px;color:var(--pulso-ink3);">
                    Página <?= $page ?> de <?= $lastPage ?> · <?= number_format($total, 0, ',', '.') ?> en total
                </div>
                <div style="display:flex;gap:6px;">
                    <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl ?>?estado=<?= $estado ?>&p=<?= $page - 1 ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $lastPage): ?>
                    <a href="<?= $baseUrl ?>?estado=<?= $estado ?>&p=<?= $page + 1 ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">Siguiente →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

</div>

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>
</body>
</html>
