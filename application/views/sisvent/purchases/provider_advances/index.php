<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return '$' . number_format((float)$n, 2, ',', '.'); };
$curName = function ($cur) { $c = strtoupper((string) $cur); return $c === 'CNY' ? 'RMB' : $c; };
$money = function ($n, $cur) use ($curName) { return $curName($cur) . ' ' . number_format((float)$n, 2, ',', '.'); };
$dateEs = function ($d) {
    if (!$d) return '—';
    $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};
$statusLabel = ['open' => ['Abierto', '#166534', '#dcfce7'], 'applied' => ['Aplicado', '#1e40af', '#dbeafe'], 'refunded' => ['Anulado', '#991b1b', '#fee2e2']];
$totalSaldo = 0; foreach ($balances as $b) { $totalSaldo += (float)$b->saldo_base; }
?>
<!DOCTYPE html>
<html lang="es">
<title>Anticipos a proveedores · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pa-page { max-width: 1280px; margin: 0 auto; padding: 24px; }
.pa-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.pa-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.pa-breadcrumb a { color: inherit; text-decoration: none; }
.pa-h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.pa-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.pa-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid transparent; text-decoration: none; }
.pa-btn-primary { background: var(--stock-red); color: white !important; }
.pa-btn-secondary { background: white; color: var(--ink-800); border: 1px solid var(--ink-200); }
.pa-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; margin-bottom: 16px; overflow: hidden; }
.pa-card-head { padding: 12px 16px; border-bottom: 1px solid var(--ink-150); font-family: var(--font-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); display: flex; justify-content: space-between; }
.pa-tbl { width: 100%; border-collapse: collapse; }
.pa-tbl th { text-align: left; padding: 10px 16px; font-family: var(--font-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); background: var(--ink-25); border-bottom: 1px solid var(--ink-150); }
.pa-tbl td { padding: 10px 16px; font-size: 13px; color: var(--ink-800); border-bottom: 1px solid var(--ink-100); }
.pa-tbl .right { text-align: right; }
.pa-tbl .mono { font-family: var(--font-mono); }
.pa-pill { display: inline-block; padding: 2px 8px; border-radius: 9px; font-size: 10px; font-weight: 700; }
.pa-saldo-hero { background: var(--ink-900); color: white; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; }
.pa-saldo-hero .lbl { font-family: var(--font-mono); font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: rgba(255,255,255,.6); }
.pa-saldo-hero .val { font-size: 30px; font-weight: 800; margin-top: 4px; color: #34D399; font-family: var(--font-mono); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pa-page">

        <div class="pa-head">
          <div>
            <div class="pa-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · Pagos y anticipos</div>
            <h1 class="pa-h1">Pagos y anticipos a proveedor</h1>
            <div class="pa-sub">Plata que sale hacia el proveedor: anticipos (saldo a favor, se aplican a las facturas) y pagos aplicados a facturas. Todo en un solo lugar.</div>
          </div>
          <a class="pa-btn pa-btn-primary" href="<?= base_url() ?>sisvent/purchases/provider_advances/add">+ Registrar pago / anticipo</a>
        </div>

        <?php if ($this->session->flashdata('success')): ?>
        <div style="padding:12px 16px; background:#dcfce7; color:#166534; border-radius:8px; margin-bottom:16px; font-size:13px; font-weight:600;"><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
        <div style="padding:12px 16px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:16px; font-size:13px; font-weight:600;"><?= htmlspecialchars($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="pa-saldo-hero">
          <div class="lbl">Saldo total de anticipos sin aplicar (COP)</div>
          <div class="val"><?= $fmt($totalSaldo) ?></div>
        </div>

        <!-- Saldos por proveedor -->
        <?php if (!empty($balances)): ?>
        <div class="pa-card">
          <div class="pa-card-head"><span>Saldo por proveedor</span><span><?= count($balances) ?> proveedores</span></div>
          <table class="pa-tbl">
            <thead><tr><th>Proveedor</th><th class="right"># anticipos</th><th class="right">Saldo disponible (COP)</th></tr></thead>
            <tbody>
              <?php foreach ($balances as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b->provider_name ?: ('Proveedor '.$b->provider_id)) ?></td>
                <td class="right mono"><?= (int)$b->num_anticipos ?></td>
                <td class="right mono" style="font-weight:700; color:#166534;"><?= $fmt($b->saldo_base) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <!-- Listado de anticipos -->
        <div class="pa-card">
          <div class="pa-card-head"><span>Anticipos registrados</span><span><?= count($advances) ?></span></div>
          <?php if (empty($advances)): ?>
          <div style="padding:30px; text-align:center; color:var(--ink-500); font-size:13px;">Sin anticipos registrados todavía.</div>
          <?php else: ?>
          <table class="pa-tbl">
            <thead>
              <tr>
                <th>Código</th><th>Fecha</th><th>Proveedor</th><th>Caja</th>
                <th class="right">Monto</th><th class="right">COP</th><th class="right">Aplicado</th><th class="right">Saldo</th>
                <th>Estado</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($advances as $a):
                  $st = $statusLabel[$a->status] ?? [$a->status, '#555', '#eee'];
              ?>
              <tr>
                <td class="mono"><?= htmlspecialchars($a->adv_code) ?></td>
                <td class="mono"><?= $dateEs($a->pay_date) ?></td>
                <td><?= htmlspecialchars($a->provider_name ?: '—') ?></td>
                <td style="font-size:11px; color:var(--ink-500);"><?= htmlspecialchars($a->cash_account_name ?: '—') ?></td>
                <td class="right mono"><?= htmlspecialchars($a->currency) ?> <?= number_format((float)$a->amount,2,',','.') ?></td>
                <td class="right mono"><?= $fmt($a->amount_base) ?></td>
                <td class="right mono" style="color:var(--ink-500);"><?= $fmt($a->applied_amount) ?></td>
                <td class="right mono" style="font-weight:700; color:<?= (float)$a->saldo_base > 0.01 ? '#166534' : 'var(--ink-400)' ?>;"><?= $fmt($a->saldo_base) ?></td>
                <td><span class="pa-pill" style="background:<?= $st[2] ?>; color:<?= $st[1] ?>;"><?= htmlspecialchars($st[0]) ?></span></td>
                <td class="right">
                  <?php if ($a->status === 'open' && (float)$a->applied_amount < 0.01): ?>
                  <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_advances/delete/<?= (int)$a->id ?>" style="display:inline;" onsubmit="return confirm('¿Anular el anticipo <?= htmlspecialchars($a->adv_code) ?>? Se revierte el movimiento de caja.');">
                    <button type="submit" style="background:none; border:0; color:var(--danger); cursor:pointer; font-size:11px; font-weight:600;">Anular</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

        <!-- Pagos y aplicaciones a facturas -->
        <div class="pa-card">
          <div class="pa-card-head"><span>Pagos y aplicaciones a facturas</span><span><?= count($payments ?? []) ?></span></div>
          <?php if (empty($payments)): ?>
          <div style="padding:30px; text-align:center; color:var(--ink-500); font-size:13px;">Sin pagos aplicados a facturas todavía. Los anticipos se aplican desde cada factura con «Aplicar anticipos».</div>
          <?php else: ?>
          <table class="pa-tbl">
            <thead>
              <tr>
                <th>Comprobante</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th>Caja / método</th>
                <th class="right">Monto pago</th><th class="right">Aplicado</th><th class="right">Dif. cambio</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
              <tr>
                <td class="mono" style="font-weight:600;"><?= htmlspecialchars($p->pay_code) ?></td>
                <td class="mono"><?= $dateEs($p->pay_date) ?></td>
                <td><?= htmlspecialchars($p->provider_name ?: '—') ?></td>
                <td class="mono"><a href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$p->invoice_id ?>" style="color:var(--stock-red);text-decoration:none;font-weight:600;"><?= htmlspecialchars($p->inv_code) ?></a></td>
                <td style="font-size:11px; color:var(--ink-500);"><?= htmlspecialchars($p->cash_account_name ?? ($p->payment_method ?: '—')) ?></td>
                <td class="right mono"><?= $money($p->amount, $p->currency) ?></td>
                <td class="right mono" style="font-weight:700; color:#166534;"><?= $money($p->amount_invoice_currency, $p->invoice_currency) ?></td>
                <td class="right mono" style="color:<?= (float)$p->fx_diff < 0 ? 'var(--danger)' : ((float)$p->fx_diff > 0 ? '#166534' : 'var(--ink-400)') ?>;"><?= number_format((float)$p->fx_diff, 2, ',', '.') ?></td>
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
