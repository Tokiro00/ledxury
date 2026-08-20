<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmt = function ($n) { return number_format((float)$n, 2, ',', '.'); };
$fmtCompact = function ($n) {
    $n = (float) $n;
    if (abs($n) >= 1_000_000)     return '$' . number_format($n / 1_000_000, 1, ',', '.') . 'M';
    if (abs($n) >= 1_000)         return '$' . number_format($n / 1_000, 1, ',', '.') . 'K';
    return '$' . number_format($n, 0, ',', '.');
};
$dateEs = function ($d) {
    if (!$d) return '—';
    $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};
?>
<!DOCTYPE html>
<html lang="es">
<title>Pagos a proveedores · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pp-page { max-width: 1280px; margin: 0 auto; padding: 24px; }
.pp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.pp-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.pp-breadcrumb a { color: inherit; text-decoration: none; }
.pp-h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.pp-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.pp-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid var(--ink-200); background: white; color: var(--ink-800); text-decoration: none; transition: all .12s; }
.pp-btn:hover { background: var(--ink-25); }

.pp-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
.pp-kpi { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 16px 18px; position: relative; }
.pp-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--accent, var(--stock-red)); }
.pp-kpi-label { font-family: var(--font-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); }
.pp-kpi-val { font-size: 24px; font-weight: 700; color: var(--ink-900); margin-top: 4px; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; font-family: var(--font-mono); }
.pp-kpi-meta { font-size: 11px; color: var(--ink-500); margin-top: 2px; }

.pp-filters { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
.pp-filter { display: flex; flex-direction: column; gap: 4px; }
.pp-filter label { font-size: 11px; font-weight: 600; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.04em; }
.pp-filter select, .pp-filter input { height: 34px; padding: 0 10px; font-size: 13px; border: 1px solid var(--ink-200); border-radius: 6px; background: white; color: var(--ink-800); min-width: 160px; }

.pp-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; overflow: hidden; }
.pp-card-head { padding: 14px 18px; border-bottom: 1px solid var(--ink-150); display: flex; align-items: center; justify-content: space-between; background: var(--ink-25); }
.pp-card-title { font-size: 14px; font-weight: 700; color: var(--ink-800); }
.pp-card-tag { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); }
.pp-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.pp-tbl thead th { text-align: left; font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); padding: 10px 14px; border-bottom: 1px solid var(--ink-150); background: var(--ink-25); }
.pp-tbl thead th.right { text-align: right; }
.pp-tbl tbody td { padding: 12px 14px; border-bottom: 1px solid var(--ink-100); }
.pp-tbl tbody tr:last-child td { border-bottom: 0; }
.pp-tbl tbody tr:hover { background: var(--ink-25); }
.pp-tbl .right { text-align: right; }
.pp-tbl .mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.pp-empty { padding: 60px 24px; text-align: center; color: var(--ink-500); }
.pp-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: 500; background: var(--success-100); color: var(--success); border: 1px solid var(--success); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pp-page">

        <div class="pp-head">
          <div>
            <div class="pp-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · <a href="<?= base_url() ?>sisvent/purchases/cxp">CxP</a> · Pagos</div>
            <h1 class="pp-h1">Pagos a proveedores</h1>
            <div class="pp-sub">Historial de salidas de caja registradas — usar el botón "Registrar pago" en una factura abierta</div>
          </div>
          <a class="pp-btn" href="<?= base_url() ?>sisvent/purchases/cxp">← Volver al panel CxP</a>
        </div>

        <?php if ($msg = $this->session->flashdata('success')): ?>
          <div class="pp-flash"><?= $msg ?></div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="pp-kpis">
          <div class="pp-kpi" style="--accent: var(--stock-red);">
            <div class="pp-kpi-label">Pagos en el rango</div>
            <div class="pp-kpi-val"><?= (int)$totals['count'] ?></div>
            <div class="pp-kpi-meta">Del <?= $dateEs($filters['date_from']) ?> al <?= $dateEs($filters['date_to']) ?></div>
          </div>
          <div class="pp-kpi" style="--accent: var(--info);">
            <div class="pp-kpi-label">Monto pagado (COP)</div>
            <div class="pp-kpi-val"><?= $fmtCompact($totals['amount_base']) ?></div>
            <div class="pp-kpi-meta">equivalente a la moneda de cada factura</div>
          </div>
          <div class="pp-kpi" style="--accent: <?= $totals['fx_diff'] < 0 ? 'var(--danger)' : 'var(--success)' ?>;">
            <div class="pp-kpi-label">Diferencia en cambio</div>
            <div class="pp-kpi-val" style="color: <?= $totals['fx_diff'] < 0 ? 'var(--danger)' : 'var(--success)' ?>;">
              <?= $totals['fx_diff'] < 0 ? '−' : '+' ?>$<?= number_format(abs($totals['fx_diff']), 2, ',', '.') ?>
            </div>
            <div class="pp-kpi-meta">gasto / ganancia financiera por tasa</div>
          </div>
        </div>

        <!-- Filtros -->
        <form class="pp-filters" method="GET" id="pp-filters">
          <div class="pp-filter">
            <label>Proveedor</label>
            <select name="provider_id" onchange="document.getElementById('pp-filters').submit()">
              <option value="">— Todos —</option>
              <?php foreach ($providers as $p): ?>
                <option value="<?= (int)$p->idProvider ?>" <?= (int)($filters['provider_id'] ?? 0) === (int)$p->idProvider ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p->name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pp-filter">
            <label>Desde</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" onchange="document.getElementById('pp-filters').submit()">
          </div>
          <div class="pp-filter">
            <label>Hasta</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" onchange="document.getElementById('pp-filters').submit()">
          </div>
        </form>

        <div class="pp-card">
          <div class="pp-card-head">
            <span class="pp-card-title">Pagos registrados</span>
            <span class="pp-card-tag"><?= count($payments) ?> resultados</span>
          </div>
          <?php if (empty($payments)): ?>
            <div class="pp-empty">Sin pagos en el rango seleccionado.</div>
          <?php else: ?>
            <table class="pp-tbl">
              <thead>
                <tr>
                  <th>Comprobante</th>
                  <th>Fecha</th>
                  <th>Proveedor</th>
                  <th>Factura</th>
                  <th>Método</th>
                  <th class="right">Monto pago</th>
                  <th class="right">Aplicado</th>
                  <th class="right">Dif. cambio</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td class="mono" style="font-weight: 600; color: var(--ink-800);"><?= htmlspecialchars($p->pay_code) ?></td>
                  <td class="mono"><?= $dateEs($p->pay_date) ?></td>
                  <td><?= htmlspecialchars($p->provider_name) ?></td>
                  <td class="mono" style="font-size: 11px; color: var(--ink-500);"><?= htmlspecialchars($p->inv_code) ?></td>
                  <td><?= htmlspecialchars($p->payment_method ?: '—') ?></td>
                  <td class="right mono"><?= htmlspecialchars($p->currency) ?> <?= $fmt($p->amount) ?></td>
                  <td class="right mono" style="font-weight: 700; color: var(--success);"><?= htmlspecialchars($p->invoice_currency) ?> <?= $fmt($p->amount_invoice_currency) ?></td>
                  <td class="right mono" style="color: <?= $p->fx_diff < 0 ? 'var(--danger)' : ($p->fx_diff > 0 ? 'var(--success)' : 'var(--ink-400)') ?>;"><?= $fmt($p->fx_diff) ?></td>
                  <td class="right"><a class="pp-btn" style="height:28px; padding:0 10px; font-size:12px;" href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$p->invoice_id ?>">Ver factura →</a></td>
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
