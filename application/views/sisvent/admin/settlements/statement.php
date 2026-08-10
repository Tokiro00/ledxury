<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return ($n >= 0 ? '' : '-') . '$' . number_format(abs((float)$n), 0, ',', '.'); };
$typeLabels = array(
    'liquidacion'        => array('label' => 'Liquidación',       'icon' => '💰', 'cls' => 'st-pill-green'),
    'vale'               => array('label' => 'Vale',              'icon' => '📄', 'cls' => 'st-pill-gray'),
    'anticipo'           => array('label' => 'Anticipo',          'icon' => '💸', 'cls' => 'st-pill-amber'),
    'cruce_anticipo'     => array('label' => 'Cruce anticipo',    'icon' => '✂️', 'cls' => 'st-pill-orange'),
    'abono_empleado'     => array('label' => 'Abono',             'icon' => '💵', 'cls' => 'st-pill-blue'),
    'comision_pendiente' => array('label' => 'Comisión ganada',   'icon' => '🎯', 'cls' => 'st-pill-emerald'),
    'comision_bot'       => array('label' => 'Comisión bot',      'icon' => '🤖', 'cls' => 'st-pill-indigo'),
    'comision_bot_estimado' => array('label' => 'Comisión bot (estimado)', 'icon' => '🤖', 'cls' => 'st-pill-indigo'),
    'pago_comision_bot'  => array('label' => 'Pago comisión',     'icon' => '💰', 'cls' => 'st-pill-red'),
);
?>
<!DOCTYPE html>
<html lang="es">
<title>Estado de cuenta — <?= htmlspecialchars($vendor->name) ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
:root{
  --st-ink:#18181b; --st-ink2:#52525b; --st-ink3:#a1a1aa; --st-line:#ececec; --st-line2:#f4f4f5;
  --st-bg:#fafafa; --st-card:#fff; --st-pay:#15803d; --st-pay-bg:#f0fdf4; --st-neg:#b91c1c;
  --st-accent:#2E7D91;
  --st-mono:"SF Mono",ui-monospace,"Cascadia Code",Menlo,Consolas,monospace;
}
.st-main{background:var(--st-bg);min-height:100%}
.st-wrap{max-width:1040px;margin:0 auto;padding:24px 20px}
.st-crumb{font-size:12px;color:var(--st-ink3);margin-bottom:14px}
.st-crumb a{color:var(--st-ink3);text-decoration:none}
.st-crumb a:hover{color:var(--st-accent)}
.st-head{margin-bottom:18px}
.st-head .label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--st-ink3);font-weight:600}
.st-head h1{font-size:24px;font-weight:680;letter-spacing:-.01em;margin-top:3px;color:var(--st-ink)}
.st-head .id{font-size:12px;color:var(--st-ink3);margin-top:2px}

.st-hero{background:var(--st-card);border:1px solid var(--st-line);border-radius:16px;
  padding:20px 22px;display:flex;align-items:center;gap:26px;flex-wrap:wrap;margin-bottom:16px}
.st-hero .pay{min-width:190px}
.st-hero .pay .k{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--st-ink3);font-weight:600}
.st-hero .pay .v{font-family:var(--st-mono);font-size:33px;font-weight:600;letter-spacing:-.02em;margin-top:4px}
.st-hero .pay .v.pos{color:var(--st-pay)} .st-hero .pay .v.neg{color:var(--st-neg)}
.st-hero .pay .sub{font-size:12px;color:var(--st-ink2);margin-top:3px}
.st-hero .vline{width:1px;align-self:stretch;background:var(--st-line)}
.st-hero .mini{display:flex;gap:26px;flex-wrap:wrap}
.st-hero .mini .k{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--st-ink3);font-weight:600}
.st-hero .mini .v{font-family:var(--st-mono);font-size:17px;font-weight:550;margin-top:4px;color:var(--st-ink)}
.st-hero .mini .v.muted{color:var(--st-ink3)}
.st-hero .acts{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.st-btn{border-radius:10px;padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--st-line);background:var(--st-card);color:var(--st-ink2);text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.st-btn:hover{background:var(--st-line2)}
.st-btn.primary{background:var(--st-pay);border-color:var(--st-pay);color:#fff}
.st-btn.primary:hover{background:#166534}
.st-btn.amber{color:#92400e;border-color:#fde68a;background:#fffbeb}

.st-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;font-size:12px;color:var(--st-ink2)}
.st-bar .lbl{font-weight:600;color:var(--st-ink3);text-transform:uppercase;letter-spacing:.06em;font-size:11px}
.st-bar input{border:1px solid var(--st-line);border-radius:8px;padding:7px 9px;font-size:12px;color:var(--st-ink);background:#fff}
.st-bar .go{background:var(--st-accent);color:#fff;border:0;font-weight:600;cursor:pointer;padding:7px 14px;border-radius:8px}
.st-bar .chip{padding:5px 11px;border:1px solid var(--st-line);border-radius:99px;color:var(--st-ink2);text-decoration:none}
.st-bar .chip:hover{background:var(--st-line2)}

.st-panel{background:var(--st-card);border:1px solid var(--st-line);border-radius:16px;overflow:hidden}
.st-scroll{overflow-x:auto}
table.st{width:100%;border-collapse:collapse}
table.st thead th{font-size:10.5px;letter-spacing:.07em;text-transform:uppercase;color:var(--st-ink3);
  font-weight:600;text-align:left;padding:13px 18px;border-bottom:1px solid var(--st-line);
  position:sticky;top:0;background:var(--st-card);z-index:2}
table.st thead th.num{text-align:right}
table.st tbody td{padding:12px 18px;border-bottom:1px solid var(--st-line2);vertical-align:middle}
table.st tbody tr:hover{background:#fcfcfc}
.st-date{font-size:12px;color:var(--st-ink3);white-space:nowrap}
.st-client{font-size:13.5px;font-weight:560;color:var(--st-ink)}
.st-ref{font-family:var(--st-mono);font-size:11px;color:var(--st-ink3);margin-left:6px}
.st-num{font-family:var(--st-mono);text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;font-size:13.5px}
.st-gan{color:var(--st-pay);font-weight:550}
.st-ent{color:var(--st-neg);font-weight:550}
.st-sal{font-weight:600}
.st-sal.pos{color:var(--st-ink)} .st-sal.neg{color:var(--st-neg)}
.st-muted{color:var(--st-ink3)}

/* type pills (colores conservados) */
.st-pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;border-radius:999px;padding:2px 9px}
.st-pill-green{background:#dcfce7;color:#15803d} .st-pill-gray{background:#f1f5f9;color:#475569}
.st-pill-amber{background:#fef3c7;color:#92400e} .st-pill-orange{background:#ffedd5;color:#9a3412}
.st-pill-blue{background:#dbeafe;color:#1e40af} .st-pill-emerald{background:#d1fae5;color:#047857}
.st-pill-indigo{background:#e0e7ff;color:#4338ca} .st-pill-red{background:#fee2e2;color:#b91c1c}

/* breakdown chips (colores conservados) */
.st-chips{margin-top:5px;display:flex;flex-wrap:wrap;gap:5px;font-size:10.5px}
.st-chips span{padding:1px 7px;border-radius:5px;font-weight:600}
.st-c-cobro{background:#f1f5f9;color:#374151}
.st-c-flete{background:#fef3c7;color:#92400e} .st-c-flete0{background:#f3f4f6;color:#6b7280}
.st-c-base{background:#e0f2fe;color:#0369a1}
.st-c-pct{background:#dbeafe;color:#1e40af} .st-c-pct-bad{background:#fee2e2;color:#991b1b}

/* opening + footer */
tr.st-open td{background:#fbfbfb;border-bottom:1px solid var(--st-line)}
tr.st-open .st-client{color:var(--st-ink2)}
table.st tfoot td{padding:14px 18px;border-top:1px solid var(--st-line);font-weight:600}
table.st tfoot .lbl{font-size:11px;letter-spacing:.05em;text-transform:uppercase;color:var(--st-ink3);text-align:right}
tr.st-break td{background:#f8fafc}
tr.st-break .sum{display:inline-flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;font-size:11px}
tr.st-grand td{background:var(--st-pay-bg);border-top:2px solid #bbf7d0}
tr.st-grand .lbl{color:var(--st-pay)}
tr.st-grand .v{font-family:var(--st-mono);text-align:right;font-size:18px;font-weight:680}
tr.st-grand .v.pos{color:var(--st-pay)} tr.st-grand .v.neg{color:var(--st-neg)}

.st-empty{padding:48px 20px;text-align:center;color:var(--st-ink3);font-size:13px}
@media (max-width:640px){ .st-hero .pay .v{font-size:27px} .st-head h1{font-size:20px} }
@media print{ #bars > aside, #bars nav, .st-noprint{display:none!important} .st-main{background:#fff} .st-panel{border:0} }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/settlements/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto st-main">
            <div class="st-wrap">

                <div class="st-crumb st-noprint"><a href="<?= base_url() ?>sisvent/admin/settlements">Liquidaciones</a> &nbsp;/&nbsp; Estado de cuenta</div>

                <div class="st-head">
                    <div class="label">Estado de cuenta · comisión bot</div>
                    <h1><?= htmlspecialchars($vendor->name) ?></h1>
                    <div class="id">ID <?= htmlspecialchars($vendor->idUser) ?> · <?= date('d/m/Y', strtotime($from)) ?> – <?= date('d/m/Y', strtotime($to)) ?></div>
                </div>

                <!-- HERO: valor a pagar -->
                <div class="st-hero">
                    <div class="pay">
                        <div class="k">Valor a pagar</div>
                        <div class="v <?= $current_balance >= 0 ? 'pos' : 'neg' ?>"><?= $fmt($current_balance) ?></div>
                        <div class="sub"><?= $current_balance >= 0 ? 'La empresa le debe al vendedor' : 'El vendedor le debe a la empresa' ?></div>
                    </div>
                    <div class="vline"></div>
                    <div class="mini">
                        <div>
                            <div class="k">Comisión liquidable</div>
                            <div class="v">$<?= number_format($current_commission, 0, ',', '.') ?></div>
                        </div>
                        <div>
                            <div class="k">Anticipos</div>
                            <div class="v <?= $current_advances > 0 ? '' : 'muted' ?>">$<?= number_format($current_advances, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="acts st-noprint">
                        <a href="<?= base_url() ?>sisvent/admin/advances/add?employee_id=<?= urlencode($vendor->idUser) ?>" class="st-btn amber">+ Anticipo</a>
                        <button type="button" onclick="window.print()" class="st-btn">Imprimir</button>
                        <?php if ($current_commission > 0): ?>
                        <button type="button" id="btn-pay-commission" class="st-btn primary">💰 Pagar comisión</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- toolbar de rango -->
                <?php
                    $today = date('Y-m-d');
                    $baseUrl = base_url() . 'sisvent/admin/settlements/statement/' . urlencode($vendor->idUser);
                ?>
                <form method="GET" class="st-bar st-noprint">
                    <span class="lbl">Rango</span>
                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
                    <span class="st-muted">—</span>
                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
                    <button type="submit" class="go">Filtrar</button>
                    <a href="<?= $baseUrl ?>?from=<?= date('Y-m-21', strtotime('-1 month')) ?>&to=<?= date('Y-m-20') ?>" class="chip">Ciclo 21–20</a>
                    <a href="<?= $baseUrl ?>?from=<?= date('Y-m-01') ?>&to=<?= $today ?>" class="chip">Este mes</a>
                    <a href="<?= $baseUrl ?>?from=<?= date('Y-01-01') ?>&to=<?= $today ?>" class="chip">Este año</a>
                </form>

                <!-- tabla de movimientos -->
                <div class="st-panel"><div class="st-scroll">
                    <table class="st">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th class="num">Entregado</th>
                                <th class="num">Ganado</th>
                                <th class="num">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $saldoAnterior = isset($kpis['previous_balance']) ? (float)$kpis['previous_balance'] : 0;
                            if ($saldoAnterior != 0): ?>
                            <tr class="st-open">
                                <td class="st-date"><?= date('d/m/Y', strtotime($from)) ?></td>
                                <td><span class="st-client">Saldo anterior</span>
                                    <div class="st-muted" style="font-size:11.5px;margin-top:2px;">Acumulado al inicio del período</div></td>
                                <td class="st-num st-muted">—</td>
                                <td class="st-num st-muted">—</td>
                                <td class="st-num st-sal <?= $saldoAnterior >= 0 ? 'pos' : 'neg' ?>"><?= $fmt($saldoAnterior) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if (empty($rows) && $saldoAnterior == 0): ?>
                            <tr><td colspan="5" class="st-empty">Sin movimientos de comisión en el período.</td></tr>
                            <?php elseif (!empty($rows)):
                                $totEntregado = 0; $totGanado = 0;
                                $totCobros = 0; $totFlete = 0; $totBase = 0; $totAjustes = 0;
                                foreach ($rows as $r):
                                    $tl = $typeLabels[$r->tipo] ?? array('label' => $r->tipo, 'icon' => '•', 'cls' => 'st-pill-gray');
                                    $totEntregado += (float)$r->debito;
                                    $totGanado    += (float)$r->credito;
                                    $invoiceTotal = isset($r->invoice_total) ? (float)$r->invoice_total : 0;
                                    $fleteVal     = isset($r->flete) ? (float)$r->flete : 0;
                                    $pctVal       = isset($r->percentage) ? (float)$r->percentage : 0;
                                    $saldoRow     = isset($r->saldo) ? (float)$r->saldo : null;
                                    $isCommissionRow = in_array($r->tipo, array('comision_pendiente','comision_bot','comision_bot_estimado'), true);
                                    $ajustesVal   = isset($r->ajustes) ? (float)$r->ajustes : 0;
                                    if (in_array($r->tipo, array('comision_bot','comision_bot_estimado'), true)) {
                                        $totCobros  += $invoiceTotal;
                                        $totFlete   += $fleteVal;
                                        $totAjustes += $ajustesVal;
                                        $totBase    += max(0, $invoiceTotal - $ajustesVal - $fleteVal);
                                    }
                            ?>
                            <tr>
                                <td class="st-date"><?= date('d/m/Y', strtotime($r->fecha)) ?></td>
                                <td style="max-width:560px;">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <span class="st-pill <?= $tl['cls'] ?>"><?= $tl['icon'] ?> <?= $tl['label'] ?></span>
                                        <span class="st-client"><?= htmlspecialchars($r->concepto) ?></span>
                                        <span class="st-ref"><?= htmlspecialchars($r->code) ?></span>
                                    </div>
                                    <?php if ($isCommissionRow && $invoiceTotal > 0):
                                        $baseVal = max(0, $invoiceTotal - $ajustesVal - $fleteVal);
                                        $isUnderpriced = !empty($r->is_underpriced);
                                        $isBotRow = ($r->tipo === 'comision_bot' || $r->tipo === 'comision_bot_estimado');
                                        $baseLabel = $isBotRow ? 'Cobro' : 'Factura';
                                        $showFlete = $isBotRow || $fleteVal > 0;
                                        // Desglose de lo que cobró la transportadora
                                        $fPuro  = isset($r->flete_puro) ? (float)$r->flete_puro : 0;
                                        $fSeg   = isset($r->seguro) ? (float)$r->seguro : 0;
                                        $fContr = isset($r->contraentrega) ? (float)$r->contraentrega : 0;
                                        $fTip = 'Lo que cobró la transportadora por este envío';
                                        if ($fPuro > 0 || $fSeg > 0 || $fContr > 0) {
                                            $fTip = 'Flete $' . number_format($fPuro, 0, ',', '.')
                                                  . ' + Seguro $' . number_format($fSeg, 0, ',', '.')
                                                  . ' + Contraentrega $' . number_format($fContr, 0, ',', '.');
                                        }
                                        $devVal = isset($r->devuelto) ? (float)$r->devuelto : 0;
                                        $dctoVal = isset($r->descuento) ? (float)$r->descuento : 0;
                                    ?>
                                    <div class="st-chips">
                                        <span class="st-c-cobro"><?= $baseLabel ?>: $<?= number_format($invoiceTotal, 0, ',', '.') ?></span>
                                        <?php if ($ajustesVal > 0):
                                            $ajTip = array();
                                            if ($devVal > 0)  $ajTip[] = 'Devolución $' . number_format($devVal, 0, ',', '.');
                                            if ($dctoVal > 0) $ajTip[] = 'Descuento $' . number_format($dctoVal, 0, ',', '.');
                                        ?>
                                        <span class="st-c-flete" title="<?= htmlspecialchars(implode(' + ', $ajTip) ?: 'Devoluciones y descuentos') ?>">− <?= $devVal > 0 && $dctoVal == 0 ? 'Devolución' : ($dctoVal > 0 && $devVal == 0 ? 'Descuento' : 'Devol./Dcto') ?>: $<?= number_format($ajustesVal, 0, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <?php if ($showFlete): ?>
                                        <span class="<?= $fleteVal > 0 ? 'st-c-flete' : 'st-c-flete0' ?>" title="<?= htmlspecialchars($fTip) ?>">− Transportadora: $<?= number_format($fleteVal, 0, ',', '.') ?></span>
                                        <span class="st-c-base">= Base: $<?= number_format($baseVal, 0, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <?php if ($pctVal > 0): ?>
                                        <span class="<?= $isUnderpriced ? 'st-c-pct-bad' : 'st-c-pct' ?>" <?= $isUnderpriced ? 'title="Vendió por debajo del precio mínimo: comisión al 5% (regla underpriced)"' : '' ?>>× <?= number_format($pctVal, 2) ?>%<?php if ($isUnderpriced): ?> ⚠️<?php endif; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="st-num <?= $r->debito > 0 ? 'st-ent' : 'st-muted' ?>"><?= $r->debito > 0 ? '$' . number_format($r->debito, 0, ',', '.') : '—' ?></td>
                                <td class="st-num <?= $r->credito > 0 ? 'st-gan' : 'st-muted' ?>"><?= $r->credito > 0 ? '+' . number_format($r->credito, 0, ',', '.') : '—' ?></td>
                                <td class="st-num st-sal <?= $saldoRow === null ? 'st-muted' : ($saldoRow >= 0 ? 'pos' : 'neg') ?>"><?= $saldoRow === null ? '—' : $fmt($saldoRow) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <?php if ($totCobros > 0): ?>
                            <tr class="st-break">
                                <td colspan="5">
                                    <div class="sum">
                                        <span class="st-c-cobro" style="padding:2px 8px;border-radius:5px;">Cobros: $<?= number_format($totCobros, 0, ',', '.') ?></span>
                                        <?php if ($totAjustes > 0): ?>
                                        <span class="st-c-flete" style="padding:2px 8px;border-radius:5px;">− Devol./Dcto: $<?= number_format($totAjustes, 0, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <span class="st-c-flete" style="padding:2px 8px;border-radius:5px;" title="Flete + seguro + comisión de contraentrega que cobra la transportadora">− Transportadora: $<?= number_format($totFlete, 0, ',', '.') ?></span>
                                        <span class="st-c-base" style="padding:2px 8px;border-radius:5px;">= Base: $<?= number_format($totBase, 0, ',', '.') ?></span>
                                        <span style="padding:2px 8px;border-radius:5px;background:#dcfce7;color:#166534;">Comisión: $<?= number_format($totGanado, 0, ',', '.') ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="2" class="lbl">Totales del período</td>
                                <td class="st-num st-ent"><?= $totEntregado > 0 ? '$' . number_format($totEntregado, 0, ',', '.') : '—' ?></td>
                                <td class="st-num st-gan">$<?= number_format($totGanado, 0, ',', '.') ?></td>
                                <td class="st-num st-muted">—</td>
                            </tr>
                            <tr class="st-grand">
                                <td colspan="4" class="lbl">Saldo neto · valor a pagar</td>
                                <td class="v <?= $current_balance >= 0 ? 'pos' : 'neg' ?>"><?= $fmt($current_balance) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div></div>

            </div>
        </main>
    </div>
</div>

<!-- Modal Pagar comisión (preview dinámico en JS) -->
<div id="pay-comm-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.5);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold text-gray-800">Pagar comisión a <?= htmlspecialchars($vendor->name) ?></h3>
            <button type="button" onclick="document.getElementById('pay-comm-modal').classList.add('hidden');document.getElementById('pay-comm-modal').classList.remove('flex');" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <!-- Saldo y anticipos disponibles -->
        <div class="mb-3 p-3 rounded border border-gray-200 bg-gray-50">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Comisión pendiente total:</span>
                <span class="font-mono font-bold text-green-700">$<?= number_format($current_commission, 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Anticipos disponibles:</span>
                <span class="font-mono font-bold text-yellow-700">$<?= number_format($current_advances, 0, ',', '.') ?></span>
            </div>
        </div>

        <form id="pay-comm-form" onsubmit="return false;" data-comm="<?= (float)$current_commission ?>" data-advances="<?= (float)$current_advances ?>">
            <input type="hidden" name="vendor_id" value="<?= htmlspecialchars($vendor->idUser) ?>">

            <label class="block mb-3">
                <span class="block text-xs font-bold text-gray-600 uppercase mb-1">Monto a liquidar este pago</span>
                <div class="flex items-center gap-2">
                    <input type="number" name="amount" id="pcm-stmt-amount" min="1" step="1" max="<?= (int)$current_commission ?>"
                           value="<?= (int)$current_commission ?>"
                           class="flex-1 px-3 py-2 text-sm border rounded font-mono">
                    <button type="button" id="pcm-stmt-all"
                            class="px-2 py-2 text-xs font-medium text-gray-600 border border-gray-300 rounded hover:bg-gray-100"
                            title="Liquidar todo el saldo">Todo</button>
                </div>
                <span id="pcm-stmt-hint" class="block text-xxs text-gray-400 mt-1"></span>
            </label>

            <!-- Preview dinámico -->
            <div class="mb-3 p-3 rounded border border-gray-200 bg-gray-50">
                <div class="flex justify-between text-sm mb-1" id="pcm-stmt-cross-row">
                    <span class="text-gray-600">− Cruce con anticipos:</span>
                    <span class="font-mono font-bold text-yellow-700" id="pcm-stmt-cross">−$0</span>
                </div>
                <hr class="my-2 border-gray-300">
                <div class="flex justify-between text-base">
                    <span class="font-bold text-gray-700">= A pagar en efectivo:</span>
                    <span class="font-mono font-bold text-mam-blue-petroleo" id="pcm-stmt-cash">$0</span>
                </div>
            </div>

            <label class="block mb-3" id="pcm-stmt-source-wrap">
                <span class="block text-xs font-bold text-gray-600 uppercase mb-1">Caja o banco origen del pago</span>
                <select name="source" id="pcm-stmt-source" class="w-full px-2 py-2 text-sm border rounded">
                    <option value="">-- Selecciona --</option>
                    <?php if (!empty($cashboxes)): ?>
                    <optgroup label="Cajas">
                        <?php foreach ($cashboxes as $cb): ?>
                        <option value="caja:<?= (int)$cb->id ?>">
                            <?= htmlspecialchars($cb->name) ?> ($<?= number_format((float)$cb->currentBalance, 0, ',', '.') ?>)
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($bank_accounts)): ?>
                    <optgroup label="Bancos">
                        <?php foreach ($bank_accounts as $ba): ?>
                        <option value="banco:<?= (int)$ba->id ?>">
                            <?= htmlspecialchars($ba->name) ?> ($<?= number_format((float)$ba->currentBalance, 0, ',', '.') ?>)
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
            </label>
            <div id="pcm-stmt-consig" class="mb-3 hidden p-3 rounded border border-blue-200 bg-blue-50">
                <span class="block text-xs font-bold text-gray-600 uppercase mb-2">Datos de la consignación</span>
                <div class="flex gap-2 mb-2">
                    <label class="flex-1">
                        <span class="block text-xxs text-gray-500 mb-1">Nº comprobante / referencia *</span>
                        <input type="text" name="reference" id="pcm-stmt-doc" maxlength="100"
                               class="w-full px-2 py-2 text-sm border rounded font-mono" placeholder="Ej: 4581234567">
                    </label>
                    <label style="width:38%">
                        <span class="block text-xxs text-gray-500 mb-1">Fecha consignación</span>
                        <input type="date" name="payment_date" id="pcm-stmt-date"
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"
                               class="w-full px-2 py-2 text-sm border rounded">
                    </label>
                </div>
                <label class="block">
                    <span class="block text-xxs text-gray-500 mb-1">Observación (opcional)</span>
                    <input type="text" name="notes" id="pcm-stmt-notes" maxlength="150"
                           class="w-full px-2 py-2 text-sm border rounded" placeholder="Ej: transferencia desde cuenta de Gonzalo">
                </label>
            </div>
            <p id="pcm-stmt-nocash" class="text-xs text-gray-500 mb-3 hidden">No hay efectivo a pagar — todo el saldo se cruza con anticipos. No requiere caja/banco.</p>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('pay-comm-modal').classList.add('hidden');document.getElementById('pay-comm-modal').classList.remove('flex');" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-100">Cancelar</button>
                <button type="button" id="pay-comm-submit" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded">Confirmar pago</button>
            </div>
            <p id="pay-comm-msg" class="text-xs text-red-600 mt-2 hidden"></p>
        </form>
    </div>
</div>

<script>
(function() {
    var btnOpen   = document.getElementById('btn-pay-commission');
    var modal     = document.getElementById('pay-comm-modal');
    var form      = document.getElementById('pay-comm-form');
    var submit    = document.getElementById('pay-comm-submit');
    var msgEl     = document.getElementById('pay-comm-msg');
    var amountEl  = document.getElementById('pcm-stmt-amount');
    var amountAll = document.getElementById('pcm-stmt-all');
    var amtHint   = document.getElementById('pcm-stmt-hint');
    var crossEl   = document.getElementById('pcm-stmt-cross');
    var crossRow  = document.getElementById('pcm-stmt-cross-row');
    var cashEl    = document.getElementById('pcm-stmt-cash');
    var sourceWrap= document.getElementById('pcm-stmt-source-wrap');
    var sourceSel = document.getElementById('pcm-stmt-source');
    var noCashMsg = document.getElementById('pcm-stmt-nocash');
    var consigWrap= document.getElementById('pcm-stmt-consig');
    var docEl     = document.getElementById('pcm-stmt-doc');
    var dateEl    = document.getElementById('pcm-stmt-date');
    var notesEl   = document.getElementById('pcm-stmt-notes');
    if (!btnOpen || !modal || !form) return;

    // Consignación: solo para banco con efectivo a pagar; al ocultarse se
    // limpian los campos para que no contaminen un pago desde caja.
    function toggleConsig() {
        var esBanco = sourceSel.value.indexOf('banco:') === 0;
        var hayCash = !sourceWrap.classList.contains('hidden');
        var visible = esBanco && hayCash;
        consigWrap.classList.toggle('hidden', !visible);
        if (!visible) {
            docEl.value = '';
            notesEl.value = '';
            dateEl.value = dateEl.defaultValue;
        }
    }

    var comm     = parseFloat(form.getAttribute('data-comm')) || 0;
    var advances = parseFloat(form.getAttribute('data-advances')) || 0;
    var fmt = function(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

    function recompute() {
        var amount = parseFloat(amountEl.value) || 0;
        amount = Math.max(0, Math.min(amount, comm));
        var cross = Math.min(amount, advances);
        var cash  = Math.max(0, amount - cross);
        crossEl.textContent = '−' + fmt(cross);
        cashEl.textContent  = fmt(cash);
        crossRow.style.display = (advances > 0) ? '' : 'none';
        amtHint.textContent = 'Saldo total: ' + fmt(comm) + (amount < comm ? ' · Quedará pendiente: ' + fmt(comm - amount) : '');
        if (cash > 0) {
            sourceWrap.classList.remove('hidden');
            noCashMsg.classList.add('hidden');
            sourceSel.required = true;
        } else {
            sourceWrap.classList.add('hidden');
            noCashMsg.classList.remove('hidden');
            sourceSel.required = false;
            // Sin efectivo no hay origen: evitar que viaje un banco obsoleto
            sourceSel.value = '';
        }
        toggleConsig();
    }
    recompute();
    amountEl.addEventListener('input', recompute);
    if (amountAll) amountAll.addEventListener('click', function() { amountEl.value = Math.round(comm); recompute(); });
    sourceSel.addEventListener('change', toggleConsig);

    btnOpen.addEventListener('click', function() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        msgEl.classList.add('hidden');
    });

    // Click handler (no 'submit'): el form tiene onsubmit="return false;" para
    // que Enter o un error de JS nunca caigan a un submit GET nativo.
    submit.addEventListener('click', function(e) {
        e.preventDefault();
        msgEl.classList.add('hidden');
        var amount = parseFloat(amountEl.value) || 0;
        if (amount <= 0) {
            msgEl.textContent = 'Ingresa un monto válido.';
            msgEl.classList.remove('hidden');
            return;
        }
        var sourceVal = sourceSel.value;
        var needsCash = !sourceWrap.classList.contains('hidden');
        if (needsCash && !sourceVal) {
            msgEl.textContent = 'Selecciona caja o banco.';
            msgEl.classList.remove('hidden');
            return;
        }
        var parts = sourceVal ? sourceVal.split(':') : ['caja','0'];
        var esBanco = parts[0] === 'banco';
        if (esBanco && needsCash && !docEl.value.trim()) {
            msgEl.textContent = 'Ingresa el número de comprobante de la consignación.';
            msgEl.classList.remove('hidden');
            return;
        }
        var body = new FormData();
        body.append('vendor_id', form.querySelector('input[name="vendor_id"]').value);
        body.append('source_type', parts[0]);
        body.append('source_id', parts[1]);
        body.append('amount', amount);
        if (esBanco) { // la consignación es un concepto bancario
            body.append('reference', docEl.value.trim());
            body.append('payment_date', dateEl.value);
            body.append('notes', notesEl.value.trim());
        }

        submit.disabled = true;
        submit.textContent = 'Procesando…';

        fetch('<?= base_url() ?>sisvent/admin/settlements/payCommission', {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.success) {
                alert(json.message || 'Liquidación realizada.');
                window.location.reload();
            } else {
                msgEl.textContent = json.message || 'Error procesando el pago.';
                msgEl.classList.remove('hidden');
                submit.disabled = false;
                submit.textContent = 'Confirmar pago';
            }
        })
        .catch(function(err) {
            msgEl.textContent = 'Error de red: ' + err.message;
            msgEl.classList.remove('hidden');
            submit.disabled = false;
            submit.textContent = 'Confirmar pago';
        });
    });
})();
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
