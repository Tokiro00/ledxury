<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmtFull = function ($n) { return '$' . number_format((float)$n, 2, ',', '.'); };
$dateEs = function ($d) {
    if (!$d) return '—';
    $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};

$impLabels = ['aduana'=>'Aduana','flete'=>'Flete','descargue'=>'Descargue','nacionalizacion'=>'Nacionalización','otro'=>'Otro'];
$importPayables = $import_payables ?? [];
$importPayTotal = $import_pay_total ?? 0;

$totalBalance = 0; $cntOpen = 0; $totalTransit = 0; $cntTransit = 0;
foreach ($invoices as $inv) {
    if (in_array($inv->status, ["open","paid_partial"])) {
        $totalBalance += (float)$inv->balance;
        $cntOpen++;
    } elseif ($inv->status === "en_transito") {
        $totalTransit += (float)$inv->balance;
        $cntTransit++;
    }
}
$deudaTotal = $totalBalance + $totalTransit;
?>
<!DOCTYPE html>
<html lang="es">
<title>Facturas proveedor · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pi-page { max-width: 1280px; margin: 0 auto; padding: 24px; }
.pi-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.pi-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.pi-breadcrumb a { color: inherit; text-decoration: none; }
.pi-h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.pi-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.pi-actions { display: flex; gap: 8px; align-items: center; }
.pi-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid transparent; transition: all .12s; font-family: var(--font-sans); text-decoration: none; }
.pi-btn-primary { background: var(--ink-900); color: white !important; }
.pi-btn-primary:hover { background: var(--ink-800); }
.pi-btn-secondary { background: white; color: var(--ink-800); border-color: var(--ink-200); }

.pi-filters { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
.pi-filter { display: flex; flex-direction: column; gap: 4px; }
.pi-filter label { font-size: 11px; font-weight: 600; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.04em; }
.pi-filter select, .pi-filter input { height: 34px; padding: 0 10px; font-size: 13px; border: 1px solid var(--ink-200); border-radius: 6px; background: white; color: var(--ink-800); min-width: 200px; }

.pi-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; overflow: hidden; }
.pi-card-head { padding: 14px 18px; border-bottom: 1px solid var(--ink-150); display: flex; align-items: center; justify-content: space-between; background: var(--ink-25); }
.pi-card-title { font-size: 14px; font-weight: 700; color: var(--ink-800); }
.pi-card-tag { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); }
.pi-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.pi-tbl thead th { text-align: left; font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); padding: 10px 14px; border-bottom: 1px solid var(--ink-150); background: var(--ink-25); }
.pi-tbl thead th.right { text-align: right; }
.pi-tbl tbody td { padding: 12px 14px; border-bottom: 1px solid var(--ink-100); }
.pi-tbl tbody tr:last-child td { border-bottom: 0; }
.pi-tbl tbody tr:hover { background: var(--ink-25); }
.pi-tbl .right { text-align: right; }
.pi-tbl .mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.pi-pill { display: inline-flex; align-items: center; gap: 6px; height: 20px; padding: 0 8px; font-size: 10px; font-weight: 700; border-radius: 9999px; letter-spacing: 0.04em; text-transform: uppercase; }
.pi-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pi-pill.open          { background: var(--info-100); color: var(--info); }
.pi-pill.paid_partial  { background: var(--warning-100); color: #B17F0B; }
.pi-pill.paid          { background: var(--success-100); color: var(--success); }
.pi-pill.cancelled     { background: var(--ink-100); color: var(--ink-600); }
.pi-pill.overdue       { background: var(--danger-100); color: var(--danger); }
.pi-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
.pi-flash.success { background: var(--success-100); color: var(--success); border: 1px solid var(--success); }
.pi-flash.error   { background: var(--danger-100);  color: var(--danger);  border: 1px solid var(--danger); }
.pi-empty { padding: 60px 24px; text-align: center; color: var(--ink-500); }
.pi-empty h3 { font-size: 18px; color: var(--ink-700); margin: 0 0 8px; }
.pi-empty p { font-size: 13px; color: var(--ink-500); margin: 0 0 16px; }
/* Modal abono gasto de importación */
.pic-modal-bg { position: fixed; inset: 0; background: rgba(15,15,20,.5); z-index: 100; display: none; align-items: center; justify-content: center; padding: 24px; backdrop-filter: blur(4px); }
.pic-modal-bg.open { display: flex; }
.pic-modal { background: #fff; border-radius: 12px; width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,.3); overflow: hidden; }
.pic-modal-head { padding: 16px 20px; border-bottom: 1px solid var(--ink-150); display: flex; justify-content: space-between; align-items: center; font-weight: 700; color: var(--ink-900); }
.pic-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.pic-modal-foot { padding: 14px 20px; border-top: 1px solid var(--ink-150); display: flex; justify-content: flex-end; gap: 8px; }
.pic-field { display: flex; flex-direction: column; gap: 4px; }
.pic-field label { font-size: 12px; font-weight: 600; color: var(--ink-700); }
.pic-field input, .pic-field select { height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid var(--ink-200); border-radius: 6px; background: #fff; color: var(--ink-900); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pi-page">

        <div class="pi-head">
          <div>
            <div class="pi-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Ledxury</a> · Compras · <a href="<?= base_url() ?>sisvent/purchases/cxp">CxP</a> · Facturas proveedor</div>
            <h1 class="pi-h1">
              Facturas de proveedor
              <?php if ($selected_provider): ?>
                · <span style="color: var(--stock-red);"><?= htmlspecialchars($selected_provider->name) ?></span>
              <?php endif; ?>
            </h1>
            <div class="pi-sub"><?= count($invoices) ?> facturas · deuda total <b><?= $fmtFull($deudaTotal) ?></b><?= $cntTransit ? " · de la cual " . $fmtFull($totalTransit) . " en " . $cntTransit . " factura(s) por recibir" : "" ?></div>
          </div>
          <div class="pi-actions">
            <a class="pi-btn pi-btn-secondary" href="<?= base_url() ?>sisvent/purchases/cxp">← Volver al panel CxP</a>
            <?php if ($selected_provider): ?>
              <a class="pi-btn pi-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/statement/<?= (int)$selected_provider->idProvider ?>">📄 Estado de cuenta</a>
            <?php endif; ?>
            <a class="pi-btn pi-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/import<?= $selected_provider ? '?provider_id=' . $selected_provider->idProvider : '' ?>">⬆ Importar packing list</a>
            <a class="pi-btn pi-btn-primary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/add<?= $selected_provider ? '?provider_id=' . $selected_provider->idProvider : '' ?>">+ Cargar factura</a>
          </div>
        </div>

        <?php if ($msg = $this->session->flashdata('success')): ?>
          <div class="pi-flash success"><?= $msg ?></div>
        <?php endif; ?>
        <?php if ($msg = $this->session->flashdata('error')): ?>
          <div class="pi-flash error"><?= $msg ?></div>
        <?php endif; ?>

        <!-- Filtros -->
        <form class="pi-filters" method="GET" id="pi-filters">
          <div class="pi-filter">
            <label>Proveedor</label>
            <select name="provider_id" onchange="document.getElementById('pi-filters').submit()">
              <option value="">— Todos —</option>
              <?php foreach ($providers as $p): ?>
                <option value="<?= (int)$p->idProvider ?>" <?= (int)($filters['provider_id'] ?? 0) === (int)$p->idProvider ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p->name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pi-filter">
            <label>Estado</label>
            <select name="status" onchange="document.getElementById('pi-filters').submit()">
              <option value="">— Todos —</option>
              <option value="open"          <?= ($filters['status'] ?? '') === 'open' ? 'selected' : '' ?>>Abierta</option>
              <option value="paid_partial"  <?= ($filters['status'] ?? '') === 'paid_partial' ? 'selected' : '' ?>>Pago parcial</option>
              <option value="paid"          <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Pagada</option>
              <option value="cancelled"     <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Anulada</option>
            </select>
          </div>
        </form>

        <!-- Gastos de importación por pagar (consolidado) -->
        <?php if (!empty($importPayables)): ?>
        <div class="pi-card" style="margin-bottom:16px; border-color:#FCD34D;">
          <div class="pi-card-head" style="background:#FFFBEB; border-bottom-color:#FCD34D;">
            <span class="pi-card-title" style="color:#92400E;">💸 Gastos de importación por pagar</span>
            <span class="pi-card-tag" style="color:#92400E;"><?= count($importPayables) ?> pendiente(s) · saldo $<?= number_format($importPayTotal, 2, ',', '.') ?></span>
          </div>
          <table class="pi-tbl">
            <thead>
              <tr>
                <th>Proveedor</th>
                <th>Factura</th>
                <th>Concepto</th>
                <th class="right">Saldo (COP)</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($importPayables as $ip): ?>
              <tr>
                <td><?= htmlspecialchars($ip->provider_name ?: '—') ?></td>
                <td class="mono"><a href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$ip->invoice_id ?>" style="color:var(--stock-red);text-decoration:none;font-weight:600;"><?= htmlspecialchars($ip->inv_code) ?></a><?= !empty($ip->received_at) ? ' <span style="font-size:10px;color:var(--ink-400);">(recibida)</span>' : '' ?></td>
                <td>
                  <b><?= htmlspecialchars($impLabels[$ip->concept] ?? ucfirst($ip->concept)) ?></b>
                  <?php if (!empty($ip->description)): ?><div style="font-size:11px;color:var(--ink-500);"><?= htmlspecialchars($ip->description) ?></div><?php endif; ?>
                  <?php if ((float)$ip->paid_amount > 0.005): ?><div style="font-size:11px;color:var(--ink-500);font-family:var(--font-mono);">Abonado <?= number_format((float)$ip->paid_amount, 2, ',', '.') ?> de <?= number_format((float)$ip->amount_base, 2, ',', '.') ?></div><?php endif; ?>
                </td>
                <td class="right mono" style="font-weight:700; color:#B45309;">$<?= number_format((float)$ip->outstanding, 0, ',', '.') ?></td>
                <td class="right">
                  <button type="button" class="pi-pay-cost pi-btn pi-btn-secondary" style="height:28px; padding:0 10px; font-size:12px; background:#e0edff; color:#1e40af; border-color:#93c5fd;"
                          data-id="<?= (int)$ip->id ?>"
                          data-label="<?= htmlspecialchars(($impLabels[$ip->concept] ?? ucfirst($ip->concept)) . ' · ' . $ip->inv_code, ENT_QUOTES) ?>"
                          data-out="<?= number_format((float)$ip->outstanding, 2, '.', '') ?>">💸 Pagar</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="pi-card">
          <div class="pi-card-head">
            <span class="pi-card-title">Facturas registradas</span>
            <span class="pi-card-tag"><?= count($invoices) ?> resultados</span>
          </div>
          <?php if (empty($invoices)): ?>
            <div class="pi-empty">
              <h3>Sin facturas todavía</h3>
              <p>Carga una factura de proveedor para empezar el aging de CxP.</p>
              <a class="pi-btn pi-btn-primary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/add">+ Cargar primera factura</a>
            </div>
          <?php else: ?>
            <table class="pi-tbl">
              <thead>
                <tr>
                  <th>Nº factura</th>
                  <th>Proveedor</th>
                  <th>Emisión</th>
                  <th>Vence</th>
                  <th>Estado</th>
                  <th class="right">Total</th>
                  <th class="right">Pagado</th>
                  <th class="right">Saldo</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($invoices as $inv):
                  $overdue = $inv->status !== 'paid' && $inv->status !== 'cancelled' && (int)$inv->days_overdue > 0;
                  $statusLabel = ['en_transito'=>'En tránsito','open'=>'Abierta','paid_partial'=>'Parcial','paid'=>'Pagada','cancelled'=>'Anulada'][$inv->status] ?? $inv->status;
              ?>
                <tr>
                  <td class="mono" style="font-weight:600;color:var(--ink-800);"><?= htmlspecialchars($inv->inv_code) ?></td>
                  <td><?= htmlspecialchars($inv->provider_name) ?></td>
                  <td class="mono"><?= $dateEs($inv->issue_date) ?></td>
                  <td class="mono"><?= $dateEs($inv->due_date) ?></td>
                  <td>
                    <span class="pi-pill <?= htmlspecialchars($inv->status) ?>">
                      <span class="pi-pill-dot"></span>
                      <?= htmlspecialchars($statusLabel) ?>
                    </span>
                    <?php if ($overdue): ?>
                      <span class="pi-pill overdue" style="margin-left:4px;">
                        <span class="pi-pill-dot"></span>
                        vencida <?= (int)$inv->days_overdue ?>d
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="right mono"><?= htmlspecialchars($inv->currency) ?> <?= number_format((float)$inv->total, 2, ',', '.') ?></td>
                  <td class="right mono" style="color: var(--success);"><?= $inv->paid > 0 ? number_format((float)$inv->paid, 2, ',', '.') : '—' ?></td>
                  <td class="right mono" style="font-weight:700;color: <?= $inv->balance > 0 ? 'var(--ink-900)' : 'var(--ink-400)' ?>;"><?= number_format((float)$inv->balance, 2, ',', '.') ?></td>
                  <td class="right" style="white-space:nowrap;">
                    <a class="pi-btn pi-btn-secondary" style="height:28px; padding:0 10px; font-size:12px;" href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$inv->id ?>">Ver →</a>
                    <?php if ((int)($inv->cash_payments ?? 0) === 0 && empty($inv->received_at)): ?>
                      <a class="pi-btn pi-btn-secondary" style="height:28px; padding:0 10px; font-size:12px; margin-left:4px;" href="<?= base_url() ?>sisvent/purchases/provider_invoices/edit/<?= (int)$inv->id ?>">Editar</a>
                    <?php endif; ?>
                    <?php if ((float)$inv->paid < 0.01 && empty($inv->received_at)): ?>
                      <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/delete/<?= (int)$inv->id ?>" style="display:inline-block; margin-left:4px;" onsubmit="return confirm('¿Eliminar la factura <?= htmlspecialchars($inv->inv_code, ENT_QUOTES) ?>? Esta acción no se puede deshacer.');">
                        <button type="submit" title="Eliminar factura" style="height:28px; padding:0 8px; font-size:12px; background:white; color:var(--danger); border:1px solid #FCA5A5; border-radius:6px; cursor:pointer;">🗑</button>
                      </form>
                    <?php endif; ?>
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

<!-- Modal abonar gasto de importación (compartido; se rellena por JS) -->
<?php if (!empty($importPayables)): ?>
<div class="pic-modal-bg" id="pic-paycost-modal">
  <div class="pic-modal">
    <form method="POST" action="" id="pic-paycost-form">
      <div class="pic-modal-head">
        <div>Abonar gasto · <span id="pic-paycost-label"></span></div>
        <button type="button" id="pic-paycost-close" style="background:none;border:0;font-size:20px;line-height:1;cursor:pointer;color:var(--ink-400);">×</button>
      </div>
      <div class="pic-modal-body">
        <div style="background:var(--info-100);border:1px solid var(--info);border-radius:6px;padding:10px 14px;font-size:12px;color:var(--info);">Saldo por pagar · <b>$<span id="pic-paycost-out">0,00</span></b>. Puedes abonar total o por partes.</div>
        <div class="pic-field"><label>Fecha del abono</label><input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="pic-field"><label>Monto del abono (COP)</label><input type="number" step="0.01" min="0.01" name="amount_base" id="pic-paycost-amount" required placeholder="0.00"></div>
        <div class="pic-field"><label>Pagar desde caja / banco</label>
          <select name="cash_account_id" required>
            <option value="">— Seleccionar —</option>
            <?php foreach (($cash_accounts ?? []) as $ca): ?>
              <option value="<?= (int)$ca->id ?>"><?= htmlspecialchars($ca->name) ?> · <?= htmlspecialchars($ca->currency) ?> <?= number_format((float)$ca->current_balance, 2, ',', '.') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="pic-field"><label>Referencia (opcional)</label><input type="text" name="reference" placeholder="Nº transferencia / comprobante"></div>
      </div>
      <div class="pic-modal-foot">
        <button type="button" class="pi-btn pi-btn-secondary" id="pic-paycost-cancel">Cancelar</button>
        <button type="submit" class="pi-btn" style="background:var(--stock-red);color:#fff;">Registrar abono</button>
      </div>
    </form>
  </div>
</div>
<script>
// Abrir/rellenar modal de abono vía delegación (sobrevive re-render de Vue)
(function () {
  var PAY_URL = '<?= base_url() ?>sisvent/purchases/provider_invoices/pay_import_cost/';
  function m() { return document.getElementById('pic-paycost-modal'); }
  document.addEventListener('click', function (e) {
    if (!e.target.closest) return;
    var btn = e.target.closest('.pi-pay-cost');
    if (btn) {
      var mm = m(); if (!mm) return;
      var out = btn.getAttribute('data-out') || '0';
      var f = document.getElementById('pic-paycost-form'); if (f) f.setAttribute('action', PAY_URL + btn.getAttribute('data-id'));
      var l = document.getElementById('pic-paycost-label'); if (l) l.textContent = btn.getAttribute('data-label') || '';
      var o = document.getElementById('pic-paycost-out'); if (o) o.textContent = parseFloat(out).toLocaleString('es', {minimumFractionDigits:2, maximumFractionDigits:2});
      var a = document.getElementById('pic-paycost-amount'); if (a) { a.setAttribute('max', out); a.value = out; }
      mm.classList.add('open'); return;
    }
    if (e.target.closest('#pic-paycost-close') || e.target.closest('#pic-paycost-cancel')) { var m2 = m(); if (m2) m2.classList.remove('open'); return; }
    var m3 = m(); if (m3 && e.target === m3) m3.classList.remove('open');
  });
})();
</script>
<?php endif; ?>
</body>
</html>
