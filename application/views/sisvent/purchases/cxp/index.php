<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmtCompact = function ($n) {
    $n = (float) $n;
    if (abs($n) >= 1_000_000_000) return '$' . number_format($n / 1_000_000_000, 1, ',', '.') . 'B';
    if (abs($n) >= 1_000_000)     return '$' . number_format($n / 1_000_000, 1, ',', '.') . 'M';
    if (abs($n) >= 1_000)         return '$' . number_format($n / 1_000, 1, ',', '.') . 'K';
    return '$' . number_format($n, 0, ',', '.');
};
$fmtFull = function ($n) { return '$' . number_format((float)$n, 2, ',', '.'); };

$t = $totals;
$totalUsd      = (float) ($t['total_base']     ?? 0);
$overdue       = (float) ($t['overdue_base']   ?? 0);
$over90        = (float) ($t['over_90d_base']  ?? 0);
$numInvoices   = (int)   ($t['num_invoices']  ?? 0);
$numProviders  = (int)   ($t['num_providers'] ?? 0);

$totalsBucket = ['b1'=>0,'b2'=>0,'b3'=>0,'b4'=>0];
foreach ($aging as $row) {
    $totalsBucket['b1'] += (float)$row->b1;
    $totalsBucket['b2'] += (float)$row->b2;
    $totalsBucket['b3'] += (float)$row->b3;
    $totalsBucket['b4'] += (float)$row->b4;
}
$pct = function($v, $tot) { return $tot > 0 ? ($v / $tot) * 100 : 0; };
?>
<!DOCTYPE html>
<html lang="es">
<title>CxP · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.cxp-page { max-width: 1280px; margin: 0 auto; padding: 24px; }
.cxp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.cxp-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.cxp-breadcrumb a { color: inherit; text-decoration: none; }
.cxp-h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.cxp-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.cxp-actions { display: flex; gap: 8px; align-items: center; }
.cxp-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid transparent; transition: all .12s; font-family: var(--font-sans); text-decoration: none; }
.cxp-btn-primary { background: var(--ink-900); color: white !important; }
.cxp-btn-primary:hover { background: var(--ink-800); }
.cxp-btn-secondary { background: white; color: var(--ink-800); border-color: var(--ink-200); }
.cxp-btn-secondary:hover { background: var(--ink-25); border-color: var(--ink-300); }

.cxp-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
@media (max-width: 900px) { .cxp-kpis { grid-template-columns: repeat(2, 1fr); } }
.cxp-kpi { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 18px 20px; position: relative; overflow: hidden; }
.cxp-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--accent, var(--stock-red)); }
.cxp-kpi-label { font-family: var(--font-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); }
.cxp-kpi-val { font-size: 26px; font-weight: 700; color: var(--ink-900); margin-top: 6px; letter-spacing: -0.025em; font-variant-numeric: tabular-nums; font-family: var(--font-mono); }
.cxp-kpi-meta { font-size: 11px; color: var(--ink-500); margin-top: 4px; }
.cxp-kpi-meta.up { color: var(--success); }
.cxp-kpi-meta.dn { color: var(--danger); }

/* Stacked aging bar */
.cxp-banner { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 20px 24px; margin-bottom: 20px; }
.cxp-banner-title { font-size: 13px; font-weight: 700; color: var(--ink-800); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 12px; }
.cxp-stack { display: flex; height: 24px; border-radius: 4px; overflow: hidden; background: var(--ink-100); margin-bottom: 8px; }
.cxp-stack-seg { transition: width .3s; min-width: 1px; }
.cxp-stack-seg.b1 { background: var(--success); }
.cxp-stack-seg.b2 { background: #F59E0B; }
.cxp-stack-seg.b3 { background: #FB923C; }
.cxp-stack-seg.b4 { background: var(--danger); }
.cxp-stack-legend { display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; font-family: var(--font-mono); color: var(--ink-600); }
.cxp-stack-legend .leg-dot { width: 10px; height: 10px; border-radius: 2px; display: inline-block; vertical-align: middle; margin-right: 6px; }

/* Table */
.cxp-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,15,20,.04); }
.cxp-card-head { padding: 14px 18px; border-bottom: 1px solid var(--ink-150); display: flex; align-items: center; justify-content: space-between; background: var(--ink-25); }
.cxp-card-title { font-size: 14px; font-weight: 700; color: var(--ink-800); }
.cxp-card-tag { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); }
.cxp-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.cxp-tbl thead th { text-align: left; font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); padding: 10px 14px; border-bottom: 1px solid var(--ink-150); background: var(--ink-25); }
.cxp-tbl thead th.right { text-align: right; }
.cxp-tbl tbody td { padding: 14px; border-bottom: 1px solid var(--ink-100); vertical-align: middle; }
.cxp-tbl tbody tr:last-child td { border-bottom: 0; }
.cxp-tbl tbody tr:hover { background: var(--ink-25); }
.cxp-tbl .right { text-align: right; }
.cxp-tbl .mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.cxp-prov-name { font-weight: 600; color: var(--ink-800); }
.cxp-prov-sub { font-size: 11px; color: var(--ink-500); font-family: var(--font-mono); margin-top: 2px; }
.cxp-bucket-bar { display: flex; height: 18px; border-radius: 3px; overflow: hidden; background: var(--ink-100); min-width: 160px; }
.cxp-bucket-bar > div { transition: width .3s; }
.cxp-empty { padding: 60px 24px; text-align: center; color: var(--ink-500); }
.cxp-empty h3 { font-size: 18px; color: var(--ink-700); margin: 0 0 8px; }
.cxp-empty p { font-size: 13px; color: var(--ink-500); margin: 0 0 16px; }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="cxp-page">

        <div class="cxp-head">
          <div>
            <div class="cxp-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · CxP</div>
            <h1 class="cxp-h1">Cuentas por pagar</h1>
            <div class="cxp-sub">Deuda con proveedores · aging por antigüedad · valores en pesos</div>
          </div>
          <div class="cxp-actions" style="flex-wrap:wrap;">
            <a class="cxp-btn cxp-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_invoices">Facturas</a>
            <a class="cxp-btn cxp-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_advances">Pagos y anticipos</a>
            <a class="cxp-btn cxp-btn-secondary" href="<?= base_url() ?>sisvent/purchases/orders">Órdenes</a>
            <a class="cxp-btn cxp-btn-secondary" href="<?= base_url() ?>sisvent/business/providers">Proveedores</a>
            <a class="cxp-btn cxp-btn-primary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/add">+ Cargar factura</a>
          </div>
        </div>

        <!-- KPI strip -->
        <div class="cxp-kpis">
          <div class="cxp-kpi" style="--accent: var(--stock-red);">
            <div class="cxp-kpi-label">CxP total</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($totalUsd) ?></div>
            <div class="cxp-kpi-meta"><?= $numInvoices ?> facturas · <?= $numProviders ?> proveedores</div>
          </div>
          <div class="cxp-kpi" style="--accent: var(--danger);">
            <div class="cxp-kpi-label">Vencida</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($overdue) ?></div>
            <div class="cxp-kpi-meta dn"><?= $totalUsd > 0 ? number_format(($overdue / $totalUsd) * 100, 1, ',', '.') : '0' ?>% del total</div>
          </div>
          <div class="cxp-kpi" style="--accent: #FB923C;">
            <div class="cxp-kpi-label">+90 días</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($over90) ?></div>
            <div class="cxp-kpi-meta">crítica · revisar pagos</div>
          </div>
          <div class="cxp-kpi" style="--accent: var(--info);">
            <div class="cxp-kpi-label">Pagos del mes</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($month_payments) ?></div>
            <div class="cxp-kpi-meta">salida de caja a proveedores</div>
          </div>
          <?php
            $advances = $advances ?? [];
            $transit  = $transit ?? [];
            $totalAdvances = 0.0;
            foreach ($advances as $a) $totalAdvances += (float) $a->available;
            $totalTransit = array_sum($transit);
            $netoCxp = $totalUsd + $totalTransit - $totalAdvances;
          ?>
          <div class="cxp-kpi" style="--accent: var(--success);">
            <div class="cxp-kpi-label">Anticipos disponibles</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($totalAdvances) ?></div>
            <div class="cxp-kpi-meta">pagados, sin aplicar a facturas</div>
          </div>
          <div class="cxp-kpi" style="--accent: var(--ink-700);">
            <div class="cxp-kpi-label">Neto con proveedores</div>
            <div class="cxp-kpi-val"><?= $fmtCompact($netoCxp) ?></div>
            <div class="cxp-kpi-meta">deuda<?= $totalTransit > 0 ? ' + tránsito' : '' ?> − anticipos</div>
          </div>
        </div>

        <!-- Aging stacked bar -->
        <?php if ($totalUsd > 0): ?>
        <div class="cxp-banner">
          <div class="cxp-banner-title">Distribución de la deuda por antigüedad</div>
          <div class="cxp-stack">
            <?php if ($totalsBucket['b1'] > 0): ?><div class="cxp-stack-seg b1" style="width: <?= $pct($totalsBucket['b1'], $totalUsd) ?>%;" title="0–30 días"></div><?php endif; ?>
            <?php if ($totalsBucket['b2'] > 0): ?><div class="cxp-stack-seg b2" style="width: <?= $pct($totalsBucket['b2'], $totalUsd) ?>%;" title="31–60 días"></div><?php endif; ?>
            <?php if ($totalsBucket['b3'] > 0): ?><div class="cxp-stack-seg b3" style="width: <?= $pct($totalsBucket['b3'], $totalUsd) ?>%;" title="61–90 días"></div><?php endif; ?>
            <?php if ($totalsBucket['b4'] > 0): ?><div class="cxp-stack-seg b4" style="width: <?= $pct($totalsBucket['b4'], $totalUsd) ?>%;" title="+90 días"></div><?php endif; ?>
          </div>
          <div class="cxp-stack-legend">
            <span><span class="leg-dot" style="background: var(--success);"></span>0–30d · <?= $fmtCompact($totalsBucket['b1']) ?> (<?= number_format($pct($totalsBucket['b1'], $totalUsd), 0) ?>%)</span>
            <span><span class="leg-dot" style="background: #F59E0B;"></span>31–60d · <?= $fmtCompact($totalsBucket['b2']) ?> (<?= number_format($pct($totalsBucket['b2'], $totalUsd), 0) ?>%)</span>
            <span><span class="leg-dot" style="background: #FB923C;"></span>61–90d · <?= $fmtCompact($totalsBucket['b3']) ?> (<?= number_format($pct($totalsBucket['b3'], $totalUsd), 0) ?>%)</span>
            <span><span class="leg-dot" style="background: var(--danger);"></span>+90d · <?= $fmtCompact($totalsBucket['b4']) ?> (<?= number_format($pct($totalsBucket['b4'], $totalUsd), 0) ?>%)</span>
          </div>
        </div>
        <?php endif; ?>

        <!-- Aging by provider -->
        <div class="cxp-card">
          <div class="cxp-card-head">
            <span class="cxp-card-title">CxP por proveedor</span>
            <span class="cxp-card-tag"><?= count($aging) ?> con saldo</span>
          </div>
          <?php if (empty($aging)): ?>
            <div class="cxp-empty">
              <h3>Sin deudas registradas todavía</h3>
              <p>Carga las facturas pendientes de tus proveedores para empezar a ver el aging.</p>
              <a class="cxp-btn cxp-btn-primary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/add">+ Cargar primera factura</a>
            </div>
          <?php else: ?>
            <table class="cxp-tbl">
              <thead>
                <tr>
                  <th>Proveedor</th>
                  <th class="right">Facturas</th>
                  <th class="right">Saldo</th>
                  <th class="right">Anticipos</th>
                  <th class="right">Neto</th>
                  <th>Distribución</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php $seenProviders = [];
              foreach ($aging as $r):
                  $bTot = (float)$r->balance_base;
                  $pid = (int)$r->provider_id;
                  $seenProviders[$pid] = true;
                  $adv = isset($advances[$pid]) ? (float)$advances[$pid]->available : 0.0;
                  $trn = isset($transit[$pid]) ? (float)$transit[$pid] : 0.0;
                  $neto = $bTot + $trn - $adv;
              ?>
                <tr>
                  <td>
                    <div class="cxp-prov-name"><?= htmlspecialchars($r->provider_name) ?></div>
                    <div class="cxp-prov-sub">moneda <?= htmlspecialchars($r->provider_currency) ?><?= $trn > 0 ? ' · en tránsito ' . $fmtCompact($trn) : '' ?></div>
                  </td>
                  <td class="right mono"><?= (int)$r->num_invoices ?></td>
                  <td class="right mono" style="font-weight:700;color:var(--ink-900);"><?= $fmtFull($r->balance_base) ?></td>
                  <td class="right mono" style="color: var(--success);"><?= $adv > 0 ? '−' . $fmtFull($adv) : '—' ?></td>
                  <td class="right mono" style="font-weight:700; color: <?= $neto < 0 ? 'var(--success)' : 'var(--ink-900)' ?>;"><?= $fmtFull($neto) ?></td>
                  <td>
                    <div class="cxp-bucket-bar">
                      <?php if ($r->b1 > 0): ?><div style="width: <?= $pct($r->b1, $bTot) ?>%; background: var(--success);" title="0-30d <?= $fmtCompact($r->b1) ?>"></div><?php endif; ?>
                      <?php if ($r->b2 > 0): ?><div style="width: <?= $pct($r->b2, $bTot) ?>%; background: #F59E0B;" title="31-60d <?= $fmtCompact($r->b2) ?>"></div><?php endif; ?>
                      <?php if ($r->b3 > 0): ?><div style="width: <?= $pct($r->b3, $bTot) ?>%; background: #FB923C;" title="61-90d <?= $fmtCompact($r->b3) ?>"></div><?php endif; ?>
                      <?php if ($r->b4 > 0): ?><div style="width: <?= $pct($r->b4, $bTot) ?>%; background: var(--danger);" title="+90d <?= $fmtCompact($r->b4) ?>"></div><?php endif; ?>
                    </div>
                  </td>
                  <td class="right" style="white-space:nowrap;">
                    <a class="cxp-btn cxp-btn-secondary" style="height: 28px; padding: 0 10px; font-size: 12px;" href="<?= base_url() ?>sisvent/purchases/provider_invoices/statement/<?= $pid ?>">Estado de cuenta</a>
                    <a class="cxp-btn cxp-btn-secondary" style="height: 28px; padding: 0 10px; font-size: 12px;" href="<?= base_url() ?>sisvent/purchases/provider_invoices?provider_id=<?= $pid ?>">Facturas →</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php // Proveedores con anticipos pero SIN facturas abiertas — antes eran invisibles
              foreach ($advances as $pid => $a):
                  if (isset($seenProviders[(int)$pid])) continue;
                  $trn = isset($transit[(int)$pid]) ? (float)$transit[(int)$pid] : 0.0;
                  $neto = $trn - (float)$a->available;
              ?>
                <tr>
                  <td>
                    <div class="cxp-prov-name"><?= htmlspecialchars($a->provider_name) ?></div>
                    <div class="cxp-prov-sub">sin facturas abiertas<?= $trn > 0 ? ' · en tránsito ' . $fmtCompact($trn) : '' ?></div>
                  </td>
                  <td class="right mono">0</td>
                  <td class="right mono">—</td>
                  <td class="right mono" style="color: var(--success);">−<?= $fmtFull((float)$a->available) ?></td>
                  <td class="right mono" style="font-weight:700; color: var(--success);"><?= $fmtFull($neto) ?></td>
                  <td></td>
                  <td class="right" style="white-space:nowrap;">
                    <a class="cxp-btn cxp-btn-secondary" style="height: 28px; padding: 0 10px; font-size: 12px;" href="<?= base_url() ?>sisvent/purchases/provider_advances">Ver anticipos →</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
