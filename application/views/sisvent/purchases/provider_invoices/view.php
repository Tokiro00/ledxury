<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmt = function ($n) { return number_format((float)$n, 2, ',', '.'); };
// Compat: variables de tesorería de Ledxury (cajas/bancos, no cash_accounts).
$cash_accounts = array();
$bancos = $bancos ?? array();
$cajas  = $cajas ?? array();
// Etiqueta de moneda: CNY se muestra como RMB (yuanes).
$curName = function ($cur) { $c = strtoupper((string) $cur); return $c === 'CNY' ? 'RMB' : ($c === 'COP' ? '$' : $c); };
// Monto con moneda explícita (ej. "RMB 75.663,00" / "USD 3.200,00").
$money = function ($n, $cur) use ($fmt, $curName) { return $curName($cur) . ' ' . $fmt($n); };
$dateEs = function ($d) {
    if (!$d) return '—';
    $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};
$statusLabel = ['en_transito'=>'En tránsito','open'=>'Abierta','paid_partial'=>'Pago parcial','paid'=>'Pagada','cancelled'=>'Anulada'][$invoice->status] ?? $invoice->status;
$overdue = $invoice->status !== 'paid' && $invoice->status !== 'cancelled' && $invoice->due_date && strtotime($invoice->due_date) < strtotime('today');
$daysOver = $overdue ? (int)((time() - strtotime($invoice->due_date)) / 86400) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<title>Factura <?= htmlspecialchars($invoice->inv_code) ?> · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.piv-page { max-width: 1100px; margin: 0 auto; padding: 24px; }
.piv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.piv-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.piv-breadcrumb a { color: inherit; text-decoration: none; }
.piv-h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.piv-h1 .code { font-family: var(--font-mono); }
.piv-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; font-family: var(--font-mono); }
.piv-actions { display: flex; gap: 8px; align-items: center; }
.piv-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid transparent; transition: all .12s; font-family: var(--font-sans); text-decoration: none; }
.piv-btn-primary { background: var(--ink-900); color: white !important; }
.piv-btn-primary:hover { background: var(--ink-800); }
.piv-btn-red { background: var(--stock-red); color: white !important; box-shadow: 0 4px 12px -2px rgba(237,50,55,.25); }
.piv-btn-red:hover { background: var(--stock-red-700); }
.piv-btn-secondary { background: white; color: var(--ink-800); border-color: var(--ink-200); }
.piv-pill { display: inline-flex; align-items: center; gap: 6px; height: 24px; padding: 0 10px; font-size: 11px; font-weight: 700; border-radius: 9999px; letter-spacing: 0.04em; text-transform: uppercase; }
.piv-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.piv-pill.open          { background: var(--info-100); color: var(--info); }
.piv-pill.paid_partial  { background: var(--warning-100); color: #B17F0B; }
.piv-pill.paid          { background: var(--success-100); color: var(--success); }
.piv-pill.cancelled     { background: var(--ink-100); color: var(--ink-600); }
.piv-pill.overdue       { background: var(--danger-100); color: var(--danger); }

.piv-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
@media (max-width: 900px) { .piv-grid { grid-template-columns: 1fr; } }

.piv-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; overflow: hidden; }
.piv-card-head { padding: 14px 18px; border-bottom: 1px solid var(--ink-150); background: var(--ink-25); }
.piv-card-title { font-size: 13px; font-weight: 700; color: var(--ink-800); text-transform: uppercase; letter-spacing: 0.04em; }
.piv-card-body { padding: 18px; }

.piv-stat-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; color: var(--ink-700); border-bottom: 1px dashed var(--ink-100); }
.piv-stat-row:last-child { border-bottom: 0; }
.piv-stat-row .lbl { color: var(--ink-500); }
.piv-stat-row .val { font-family: var(--font-mono); font-weight: 600; color: var(--ink-900); }

.piv-totals { background: var(--ink-25); border: 1px solid var(--ink-150); border-radius: 8px; padding: 16px 20px; }
.piv-totals-row { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; font-family: var(--font-mono); }
.piv-totals-row.big { font-size: 20px; font-weight: 700; color: var(--ink-900); border-top: 1px solid var(--ink-200); padding-top: 10px; margin-top: 6px; }
.piv-totals-row.balance { font-size: 24px; font-weight: 800; color: var(--stock-red); border-top: 2px solid var(--stock-red); padding-top: 12px; margin-top: 10px; }

.piv-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.piv-tbl thead th { text-align: left; padding: 8px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); border-bottom: 1px solid var(--ink-150); }
.piv-tbl thead th.right { text-align: right; }
.piv-tbl tbody td { padding: 10px 12px; border-bottom: 1px solid var(--ink-100); font-size: 13px; }
.piv-tbl tbody tr:last-child td { border-bottom: 0; }
.piv-tbl .mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.piv-tbl .right { text-align: right; }
.piv-empty { padding: 32px; text-align: center; color: var(--ink-500); font-size: 13px; }

/* Modal pago */
.piv-modal-bg { position: fixed; inset: 0; background: rgba(15,15,20,.5); z-index: 100; display: none; align-items: center; justify-content: center; padding: 24px; backdrop-filter: blur(4px); }
.piv-modal-bg.open { display: flex; }
.piv-modal { background: white; border-radius: 12px; max-width: 540px; width: 100%; box-shadow: 0 24px 64px -16px rgba(15,15,20,.22); overflow: hidden; }
.piv-modal-head { padding: 16px 20px; border-bottom: 1px solid var(--ink-150); display: flex; justify-content: space-between; align-items: center; }
.piv-modal-title { font-size: 16px; font-weight: 700; color: var(--ink-900); }
.piv-modal-close { background: none; border: 0; font-size: 22px; color: var(--ink-500); cursor: pointer; padding: 0 6px; }
.piv-modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
.piv-modal-foot { padding: 14px 20px; border-top: 1px solid var(--ink-150); display: flex; justify-content: flex-end; gap: 8px; background: var(--ink-25); }
.piv-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
.piv-field label { font-size: 12px; font-weight: 600; color: var(--ink-700); }
.piv-field input, .piv-field select, .piv-field textarea {
  height: 36px; padding: 0 12px; font-size: 13px;
  color: var(--ink-900); border: 1px solid var(--ink-200); border-radius: 6px;
  background: white; font-family: var(--font-sans);
}
.piv-field textarea { height: auto; padding: 10px 12px; min-height: 60px; resize: vertical; }
.piv-field input.mono, .piv-field select.mono { font-family: var(--font-mono); }
.piv-field input:focus, .piv-field select:focus, .piv-field textarea:focus {
  outline: none; border-color: var(--stock-red); box-shadow: 0 0 0 3px rgba(237,50,55,.18);
}
.piv-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="piv-page">

        <div class="piv-head">
          <div>
            <div class="piv-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · <a href="<?= base_url() ?>sisvent/purchases/cxp">CxP</a> · <a href="<?= base_url() ?>sisvent/purchases/provider_invoices">Facturas</a> · <?= htmlspecialchars($invoice->inv_code) ?></div>
            <h1 class="piv-h1">
              Factura <span class="code"><?= htmlspecialchars($invoice->inv_code) ?></span>
              <span class="piv-pill <?= htmlspecialchars($invoice->status) ?>"><span class="piv-pill-dot"></span><?= htmlspecialchars($statusLabel) ?></span>
              <?php if ($overdue): ?>
                <span class="piv-pill overdue"><span class="piv-pill-dot"></span>vencida <?= $daysOver ?>d</span>
              <?php endif; ?>
            </h1>
            <div class="piv-sub"><?= htmlspecialchars($invoice->provider_name) ?> · emitida <?= $dateEs($invoice->issue_date) ?></div>
          </div>
          <div class="piv-actions">
            <a class="piv-btn piv-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_invoices">← Volver</a>
            <?php
              // Gastos de importación registrados (buckets para mostrar y repartir).
              $impCosts = $import_costs ?? [];
              $impValue = 0.0; $impCbm = 0.0;
              foreach ($impCosts as $c) { if ($c->alloc_basis === 'cbm') $impCbm += (float)$c->amount_base; else $impValue += (float)$c->amount_base; }
              $impTotal = $impValue + $impCbm;
              $impLabels = ['aduana'=>'Aduana','flete'=>'Flete','descargue'=>'Descargue','nacionalizacion'=>'Nacionalización','otro'=>'Otro'];
              // Solo se registran gastos mientras la mercancía sigue en tránsito (antes de recibir).
              $canAddCost = ($invoice->status === 'en_transito') && empty($invoice->received_at);
            ?>
            <?php if ($canAddCost): ?>
              <button type="button" class="piv-btn piv-btn-red" id="piv-open-cost">+ Registrar gasto de importación</button>
            <?php endif; ?>
            <?php if ($invoice->balance > 0.01 && $invoice->status !== 'en_transito'): ?>
              <button type="button" class="piv-btn piv-btn-red" id="piv-open-pay">💰 Registrar pago</button>
            <?php endif; ?>
            <?php if ($invoice->balance > 0.01): ?>
              <a class="piv-btn" style="background:#e0edff; color:#1e40af; border:1px solid #93c5fd;" href="<?= base_url() ?>sisvent/purchases/provider_advances/add?provider_id=<?= (int)$invoice->provider_id ?>" title="Registrar un anticipo a <?= htmlspecialchars($invoice->provider_name) ?> (luego aplícalo con «Aplicar anticipos»)">💸 Registrar anticipo</a>
            <?php endif; ?>
            <?php if ($invoice->balance > 0.01 && !empty($advance_balance) && $advance_balance > 0.01): ?>
              <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/apply_advances/<?= (int)$invoice->id ?>" style="display:inline-block;" onsubmit="return confirm('¿Aplicar los anticipos disponibles del proveedor ($<?= number_format($advance_balance,2,',','.') ?>) a esta factura?');">
                <button type="submit" class="piv-btn" style="background:#dcfce7; color:#166534; border:1px solid #86efac;" title="Aplicar anticipos del proveedor">↩ Aplicar anticipos ($<?= number_format($advance_balance,2,',','.') ?>)</button>
              </form>
            <?php endif; ?>
            <?php if ((int)($invoice->cash_payments ?? 0) === 0 && empty($invoice->received_at)): ?>
              <a class="piv-btn piv-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_invoices/edit/<?= (int)$invoice->id ?>">✎ Editar</a>
            <?php endif; ?>
            <?php if ((float)$invoice->paid < 0.01 && empty($invoice->received_at)): ?>
              <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/delete/<?= (int)$invoice->id ?>" style="display:inline-block;" onsubmit="return confirm('¿Eliminar esta factura? Esta acción no se puede deshacer.');">
                <button type="submit" class="piv-btn" style="background:white; color:#991B1B; border:1px solid #FCA5A5;" title="Eliminar factura">🗑 Eliminar</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="piv-grid">
          <!-- Detalle factura -->
          <div>
            <div class="piv-card">
              <div class="piv-card-head"><span class="piv-card-title">Información</span></div>
              <div class="piv-card-body">
                <div class="piv-stat-row"><span class="lbl">Proveedor</span><span class="val"><?= htmlspecialchars($invoice->provider_name) ?></span></div>
                <div class="piv-stat-row"><span class="lbl">Número factura</span><span class="val"><?= htmlspecialchars($invoice->inv_code) ?></span></div>
                <div class="piv-stat-row"><span class="lbl">Emisión</span><span class="val"><?= $dateEs($invoice->issue_date) ?></span></div>
                <div class="piv-stat-row"><span class="lbl">Vencimiento</span><span class="val"><?= $dateEs($invoice->due_date) ?></span></div>
                <div class="piv-stat-row"><span class="lbl">Moneda</span><span class="val"><?= $curName($invoice->currency) ?><?= strtoupper((string)$invoice->currency) === 'CNY' ? ' (CNY)' : '' ?> · tasa <?= number_format((float)$invoice->exchange_rate, 2, ",", ".") ?> COP/<?= $curName($invoice->currency) ?></span></div>
                <div class="piv-stat-row"><span class="lbl">Antigüedad</span><span class="val"><?= (int)$invoice->age_days ?> días</span></div>
                <?php if ($invoice->notes): ?>
                  <div class="piv-stat-row" style="flex-direction:column; align-items:flex-start; gap:4px;">
                    <span class="lbl">Notas</span>
                    <span style="color: var(--ink-700); font-size: 13px;"><?= nl2br(htmlspecialchars($invoice->notes)) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Artículos -->
            <?php if (!empty($items)): ?>
            <?php
              $isReceived = !empty($invoice->received_at);
              $rcvStoreName = '—';
              if ($isReceived && !empty($invoice->received_store_id) && !empty($stores)) {
                foreach ($stores as $s) {
                  if ((int)$s->idStore === (int)$invoice->received_store_id) { $rcvStoreName = $s->name; break; }
                }
              }
            ?>
            <div class="piv-card" style="margin-top: 16px;">
              <div class="piv-card-head" style="display:flex; justify-content:space-between; align-items:center;">
                <span class="piv-card-title">Artículos (<?= count($items) ?>)</span>
                <?php if ($isReceived): ?>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#065F46; background:#D1FAE5; border:1px solid #6EE7B7; border-radius:999px;">
                      <span style="width:6px; height:6px; border-radius:50%; background:#10B981;"></span>
                      Recibida · <?= htmlspecialchars($rcvStoreName) ?> · <?= date('d/m/y H:i', strtotime($invoice->received_at)) ?>
                    </span>
                    <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/unreceive/<?= (int)$invoice->id ?>" style="display:inline;" onsubmit="return confirm('¿Revertir la recepción? Sale el stock que entró, se restaura el costo/precio anterior de cada producto y la factura vuelve a EN TRÁNSITO para corregir. Los gastos de importación NO se tocan.');">
                      <button type="submit" title="Revertir recepción (corregir)" style="height:28px; padding:0 12px; background:#fff; color:#991B1B; border:1px solid #FCA5A5; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer;">↩ Revertir recepción</button>
                    </form>
                  </div>
                <?php else: ?>
                  <button type="button" id="piv-open-receive" style="height:32px; padding:0 14px; background:var(--stock-red); color:white; border:0; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer;">📥 Recibir factura</button>
                <?php endif; ?>
              </div>

              <?php if (!$isReceived): ?>
              <!-- Panel de recepción con costeo de importación -->
              <div id="piv-receive-panel" style="display:none; padding:16px; background:var(--ink-25); border-bottom:1px solid var(--ink-150);">
                <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/receive/<?= (int)$invoice->id ?>" onsubmit="return confirm('¿Recibir esta factura? Los ítems entran al inventario y el costo de cada producto se actualiza con el costeo de importación. Si te equivocas puedes revertirla después.');">
                  <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px;">
                    <label style="display:flex; flex-direction:column; gap:4px;">
                      <span style="font-size:11px; font-weight:600; color:var(--ink-700);">Bodega destino</span>
                      <select name="store_id" required style="height:38px; padding:0 10px; font-size:13px; border:1px solid var(--ink-200); border-radius:6px; background:white;">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($stores as $s): ?>
                          <option value="<?= (int)$s->idStore ?>"><?= htmlspecialchars($s->name) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                    <label style="display:flex; flex-direction:column; gap:4px;">
                      <span style="font-size:11px; font-weight:600; color:var(--ink-700);">Factor de venta (× costo) — para el sugerido</span>
                      <input type="number" step="0.1" min="0" id="piv-factor" value="<?= number_format((float)($price_factor ?? 2.5), 2, '.', '') ?>" style="height:38px; padding:0 10px; font-size:13px; border:1px solid var(--ink-200); border-radius:6px; font-family:var(--font-mono);">
                    </label>
                  </div>
                  <div style="margin-top:12px; padding:10px 12px; background:white; border:1px solid var(--ink-150); border-radius:6px; font-size:12px;">
                    <div style="display:flex; justify-content:space-between; color:var(--ink-600);"><span>Gastos de importación (por valor · aduana/otros)</span><span class="mono">$<?= number_format($impValue, 0, ",", ".") ?></span></div>
                    <div style="display:flex; justify-content:space-between; color:var(--ink-600); margin-top:4px;"><span>Gastos de importación (por CBM · flete/descargue)</span><span class="mono">$<?= number_format($impCbm, 0, ",", ".") ?></span></div>
                    <div style="display:flex; justify-content:space-between; font-weight:700; color:var(--ink-800); margin-top:6px; padding-top:6px; border-top:1px solid var(--ink-100);"><span>Total a prorratear al costo</span><span class="mono">$<?= number_format($impTotal, 0, ",", ".") ?></span></div>
                  </div>

                  <!-- Costo y precio por artículo -->
                  <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px; font-weight:700; color:var(--ink-700); text-transform:uppercase; letter-spacing:.04em;">Costo y precio de venta por artículo</span>
                    <button type="button" id="piv-apply-suggested" style="height:28px; padding:0 12px; background:#e0edff; color:#1e40af; border:1px solid #93c5fd; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer;">Usar sugerido en todos</button>
                  </div>
                  <div style="overflow-x:auto; margin-top:6px;">
                  <table style="width:100%; border-collapse:collapse; font-size:12px; background:white; border:1px solid var(--ink-150); border-radius:6px;">
                    <thead>
                      <tr style="background:var(--ink-25); border-bottom:1px solid var(--ink-150);">
                        <th style="padding:7px 10px; text-align:left; font-size:10px; text-transform:uppercase; color:var(--ink-500);">Artículo</th>
                        <th style="padding:7px 10px; text-align:right; font-size:10px; text-transform:uppercase; color:var(--ink-500);">Nuevo costo (COP)</th>
                        <th style="padding:7px 10px; text-align:right; font-size:10px; text-transform:uppercase; color:var(--ink-500);">Precio actual</th>
                        <th style="padding:7px 10px; text-align:right; font-size:10px; text-transform:uppercase; color:var(--ink-500);">Sugerido (× factor)</th>
                        <th style="padding:7px 10px; text-align:right; font-size:10px; text-transform:uppercase; color:var(--ink-500);">Precio de venta</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $it):
                        $nc = (float) ($it->new_cost_base ?? 0);
                        $cp = (float) ($it->current_price ?? 0);
                        $cc = (float) ($it->current_cost ?? 0);
                        $sug = round($nc * (float)($price_factor ?? 2.5), 2);
                        $inputVal = $cp > 0 ? $cp : $sug;   // default: precio del sistema; si no hay, el sugerido
                      ?>
                      <tr style="border-bottom:1px solid var(--ink-100);">
                        <td style="padding:8px 10px;">
                          <span class="mono" style="color:var(--ink-600);"><?= htmlspecialchars($it->product_id) ?></span>
                          <div style="color:var(--ink-800); font-size:11px;"><?= htmlspecialchars(mb_strimwidth((string)($it->description ?: ($it->product_description ?? '')), 0, 46, '…')) ?></div>
                        </td>
                        <td style="padding:8px 10px; text-align:right; font-family:var(--font-mono); font-weight:600; color:var(--ink-900);">
                          <?= number_format($nc, 4, ',', '.') ?>
                          <?php if ($cc > 0): ?><div style="font-size:10px; color:var(--ink-400); font-weight:400;">antes <?= number_format($cc, 4, ',', '.') ?></div><?php endif; ?>
                        </td>
                        <td style="padding:8px 10px; text-align:right; font-family:var(--font-mono); color:var(--ink-500);"><?= $cp > 0 ? number_format($cp, 2, ',', '.') : '—' ?></td>
                        <td style="padding:8px 10px; text-align:right; font-family:var(--font-mono); color:#1e40af;">
                          <span class="piv-sug-val"><?= number_format($sug, 2, ',', '.') ?></span>
                          <button type="button" class="piv-use-sug" title="Usar sugerido" style="margin-left:4px; background:transparent; border:0; color:#1e40af; cursor:pointer; font-weight:700;">↑</button>
                        </td>
                        <td style="padding:8px 10px; text-align:right;">
                          <input type="number" step="0.01" min="0" name="price[<?= (int)$it->id ?>]" class="piv-price" data-newcost="<?= number_format($nc, 4, '.', '') ?>" value="<?= number_format($inputVal, 2, '.', '') ?>" style="width:110px; height:32px; padding:0 8px; font-size:12px; border:1px solid var(--ink-200); border-radius:6px; font-family:var(--font-mono); text-align:right;">
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  </div>
                  <div style="font-size:11px; color:var(--ink-500); margin-top:8px; font-family:var(--font-mono);">El <b>costo</b> se ajusta siempre (mercancía + gastos). El <b>precio de venta</b> viene con el que ya tienes en el sistema; cámbialo por el sugerido (costo × factor) con «↑» o «Usar sugerido en todos», o edítalo a mano. Vacío = no tocar el precio.</div>
                  <div style="display:flex; justify-content:flex-end; margin-top:12px;">
                    <button type="submit" style="height:38px; padding:0 18px; background:var(--stock-red); color:white; border:0; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">📥 Confirmar recepción</button>
                  </div>
                </form>
              </div>
              <?php endif; ?>
              <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                  <tr style="background:var(--ink-25); border-bottom:1px solid var(--ink-150);">
                    <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Código</th>
                    <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Descripción</th>
                    <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Cant.</th>
                    <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Costo unit. (<?= $curName($invoice->currency) ?>)</th>
                    <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Total (<?= $curName($invoice->currency) ?>)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $it): ?>
                  <tr style="border-bottom:1px solid var(--ink-100);">
                    <td style="padding:10px 12px; font-family:var(--font-mono); color:var(--ink-600); font-size:12px;"><?= htmlspecialchars($it->product_id) ?></td>
                    <td style="padding:10px 12px; color:var(--ink-800);"><?= htmlspecialchars($it->description ?: ($it->product_description ?? '')) ?></td>
                    <td style="padding:10px 12px; text-align:right; font-family:var(--font-mono);"><?= rtrim(rtrim(number_format((float)$it->quantity, 3, ',', '.'), '0'), ',') ?: '0' ?></td>
                    <td style="padding:10px 12px; text-align:right; font-family:var(--font-mono);"><?= number_format((float)$it->unit_cost, 4, ',', '.') ?></td>
                    <td style="padding:10px 12px; text-align:right; font-family:var(--font-mono); font-weight:600;"><?= $fmt($it->total) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>

            <!-- Gastos de importación -->
            <?php if (!empty($impCosts) || $canAddCost): ?>
            <div class="piv-card" style="margin-top: 16px;">
              <div class="piv-card-head" style="display:flex; justify-content:space-between; align-items:center;">
                <span class="piv-card-title">Gastos de importación<?= !empty($impCosts) ? ' · $' . number_format($impTotal, 0, ',', '.') : '' ?></span>
                <?php if ($canAddCost): ?>
                  <button type="button" id="piv-open-cost-2" style="height:30px; padding:0 12px; background:var(--stock-red); color:white; border:0; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer;">+ Registrar gasto</button>
                <?php endif; ?>
              </div>
              <?php if (empty($impCosts)): ?>
                <div class="piv-empty">Sin gastos de importación todavía. Cárgalos (aduana, flete, descargue…) y se capitalizan al costo al recibir.</div>
              <?php else: ?>
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                  <thead>
                    <tr style="background:var(--ink-25); border-bottom:1px solid var(--ink-150);">
                      <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Concepto</th>
                      <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Reparto</th>
                      <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Estado / abonos</th>
                      <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Monto COP</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($impCosts as $c): ?>
                    <?php
                      $isContado = !empty($c->paid_account_id);
                      $out = (float)($c->outstanding ?? ((float)$c->amount_base - (float)$c->paid_amount));
                      $paid = (float)$c->paid_amount;
                      if ($isContado)          { $chipTxt='Pagado de contado'; $chipBg='#D1FAE5'; $chipBd='#6EE7B7'; $chipFg='#065F46'; }
                      elseif ($out <= 0.005)   { $chipTxt='Pagado'; $chipBg='#D1FAE5'; $chipBd='#6EE7B7'; $chipFg='#065F46'; }
                      elseif ($paid > 0.005)   { $chipTxt='Parcial'; $chipBg='#FEF3C7'; $chipBd='#FCD34D'; $chipFg='#92400E'; }
                      else                     { $chipTxt='Por pagar'; $chipBg='#FEE2E2'; $chipBd='#FCA5A5'; $chipFg='#991B1B'; }
                    ?>
                    <tr style="border-bottom:1px solid var(--ink-100); vertical-align:top;">
                      <td style="padding:10px 12px; color:var(--ink-800);">
                        <b><?= htmlspecialchars($impLabels[$c->concept] ?? ucfirst($c->concept)) ?></b>
                        <?php if (!empty($c->description)): ?><div style="font-size:11px; color:var(--ink-500);"><?= htmlspecialchars($c->description) ?></div><?php endif; ?>
                      </td>
                      <td style="padding:10px 12px; color:var(--ink-600); font-size:12px;"><?= $c->alloc_basis === 'cbm' ? 'Por CBM' : 'Por valor' ?></td>
                      <td style="padding:10px 12px; font-size:12px;">
                        <span style="display:inline-block; padding:2px 8px; font-size:10px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:<?=$chipFg?>; background:<?=$chipBg?>; border:1px solid <?=$chipBd?>; border-radius:999px;"><?= $chipTxt ?></span>
                        <?php if ($isContado): ?>
                          <div style="color:var(--ink-500); margin-top:4px;"><?= htmlspecialchars($c->account_name ?: 'caja') ?></div>
                        <?php elseif ($paid > 0.005 || $out > 0.005): ?>
                          <div style="color:var(--ink-600); margin-top:4px; font-family:var(--font-mono);">Pagado <?= number_format($paid, 2, ',', '.') ?> · Saldo <?= number_format($out, 2, ',', '.') ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c->payments)): ?>
                          <div style="margin-top:6px; border-top:1px dashed var(--ink-150); padding-top:6px;">
                            <?php foreach ($c->payments as $p): ?>
                              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; color:var(--ink-500); font-size:11px; margin-top:2px;">
                                <span class="mono">↳ <?= $dateEs($p->pay_date) ?> · <?= htmlspecialchars($p->account_name ?: '—') ?> · $<?= number_format((float)$p->amount_base, 0, ",", ".") ?></span>
                                <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/delete_import_cost_payment/<?= (int)$p->id ?>" onsubmit="return confirm('¿Eliminar este abono? Se reversa el asiento y se ajusta la caja.');" style="display:inline;">
                                  <button type="submit" title="Eliminar abono" style="background:transparent; border:0; color:var(--ink-400); cursor:pointer; padding:0 4px; font-size:13px;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--ink-400)'">×</button>
                                </form>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td style="padding:10px 12px; text-align:right; font-family:var(--font-mono); font-weight:600;">$<?= number_format((float)$c->amount_base, 0, ",", ".") ?></td>
                      <td style="padding:10px 12px; text-align:right; white-space:nowrap;">
                        <?php if (!$isContado && $out > 0.005): ?>
                          <button type="button" class="piv-pay-cost" data-id="<?= (int)$c->id ?>" data-label="<?= htmlspecialchars($impLabels[$c->concept] ?? ucfirst($c->concept), ENT_QUOTES) ?>" data-out="<?= number_format($out, 2, '.', '') ?>" style="height:28px; padding:0 10px; background:#e0edff; color:#1e40af; border:1px solid #93c5fd; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer;">💸 Pagar</button>
                        <?php endif; ?>
                        <?php if ($canAddCost && empty($c->payments)): ?>
                          <button type="button" class="piv-edit-cost" title="Editar gasto"
                                  data-id="<?= (int)$c->id ?>"
                                  data-concept="<?= htmlspecialchars($c->concept, ENT_QUOTES) ?>"
                                  data-basis="<?= htmlspecialchars($c->alloc_basis, ENT_QUOTES) ?>"
                                  data-amount="<?= number_format((float)$c->amount_base, 2, '.', '') ?>"
                                  data-account="<?= (int)($c->paid_account_id ?? 0) ?>"
                                  data-desc="<?= htmlspecialchars($c->description ?? '', ENT_QUOTES) ?>"
                                  style="height:28px; padding:0 10px; background:#fff; color:var(--ink-700); border:1px solid var(--ink-200); border-radius:6px; font-weight:600; font-size:11px; cursor:pointer;">✎ Editar</button>
                          <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/delete_import_cost/<?= (int)$c->id ?>" onsubmit="return confirm('¿Eliminar este gasto de importación? Se reversa su asiento contable.');" style="display:inline;">
                            <button type="submit" title="Eliminar gasto" style="background:transparent; border:0; color:var(--ink-400); cursor:pointer; padding:4px 6px; font-size:14px;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--ink-400)'">×</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Pagos -->
            <div class="piv-card" style="margin-top: 16px;">
              <div class="piv-card-head"><span class="piv-card-title">Pagos registrados (<?= count($payments) ?>)</span></div>
              <?php if (empty($payments)): ?>
                <div class="piv-empty">Sin pagos registrados todavía</div>
              <?php else: ?>
                <?php $canDelete = in_array((int)$role, [1, 2, 4], true); ?>
                <table class="piv-tbl">
                  <thead>
                    <tr>
                      <th>Comprobante</th>
                      <th>Fecha</th>
                      <th>Cuenta origen</th>
                      <th>Referencia</th>
                      <th class="right">Monto pago</th>
                      <th class="right">Aplicado</th>
                      <th class="right">Dif. cambio</th>
                      <?php if ($canDelete): ?><th></th><?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($payments as $pay): ?>
                    <tr>
                      <td class="mono" style="font-weight: 600; color: var(--ink-800);"><?= htmlspecialchars($pay->pay_code) ?></td>
                      <td class="mono"><?= $dateEs($pay->pay_date) ?></td>
                      <td>
                        <?php if (!empty($pay->cash_account_name)): ?>
                          <span style="color: var(--ink-800); font-weight: 600;"><?= htmlspecialchars($pay->cash_account_name) ?></span>
                          <div style="font-size: 10px; color: var(--ink-500); font-family: var(--font-mono);"><?= $pay->source_type === 'banco' ? 'Banco' : 'Caja' ?></div>
                        <?php else: ?>
                          <span style="color: var(--ink-400); font-size: 12px;"><?= htmlspecialchars($pay->payment_method ?: '—') ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="mono" style="font-size: 11px; color: var(--ink-500);"><?= htmlspecialchars($pay->reference ?: '—') ?></td>
                      <td class="right mono"><?= $money($pay->amount, $pay->currency) ?></td>
                      <td class="right mono" style="font-weight: 700; color: var(--success);"><?= $money($pay->amount_invoice_currency, $invoice->currency) ?></td>
                      <td class="right mono <?= $pay->fx_diff < 0 ? 'neg' : '' ?>" style="color: <?= $pay->fx_diff < 0 ? 'var(--danger)' : ($pay->fx_diff > 0 ? 'var(--success)' : 'var(--ink-400)') ?>;"><?= $fmt($pay->fx_diff) ?></td>
                      <?php if ($canDelete): ?>
                      <td class="right">
                        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_payments/delete/<?= (int)$pay->id ?>" onsubmit="return confirm('¿Eliminar el pago <?= htmlspecialchars($pay->pay_code) ?>? El saldo de la caja se ajustará automáticamente.');" style="display: inline;">
                          <button type="submit" title="Eliminar pago" style="background: transparent; border: 0; color: var(--ink-400); cursor: pointer; padding: 4px 6px; font-size: 14px;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--ink-400)'">×</button>
                        </form>
                      </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>

          <!-- Totales -->
          <div>
            <div class="piv-totals">
              <div style="font-size:11px; color:var(--ink-500); text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px;">Valores en <b><?= $curName($invoice->currency) ?></b> (moneda de la factura)</div>
              <div class="piv-totals-row"><span>Subtotal mercancía</span><span><?= $money($invoice->subtotal, $invoice->currency) ?></span></div>
              <?php if ((float)($invoice->financing_pct ?? 0) > 0): ?>
                <div class="piv-totals-row"><span>+ Financiación <?= rtrim(rtrim(number_format((float)$invoice->financing_pct,2),'0'),'.') ?>%</span><span><?= $money((float)$invoice->total - (float)$invoice->subtotal - (float)$invoice->tax + (float)$invoice->withholding, $invoice->currency) ?></span></div>
              <?php endif; ?>
              <?php if ((float)$invoice->tax != 0): ?><div class="piv-totals-row"><span>+ IVA</span><span><?= $money($invoice->tax, $invoice->currency) ?></span></div><?php endif; ?>
              <?php if ($invoice->withholding > 0): ?>
                <div class="piv-totals-row" style="color: var(--success);"><span>− Retenciones</span><span><?= $money($invoice->withholding, $invoice->currency) ?></span></div>
              <?php endif; ?>
              <div class="piv-totals-row big"><span>Total</span><span><?= $money($invoice->total, $invoice->currency) ?></span></div>
              <div class="piv-totals-row" style="color: var(--success);"><span>Pagado</span><span><?= $money($invoice->paid, $invoice->currency) ?></span></div>
              <div class="piv-totals-row balance"><span>Saldo</span><span><?= $money($invoice->balance, $invoice->currency) ?></span></div>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<!-- Modal registrar gasto de importación -->
<?php if ($canAddCost): ?>
<div class="piv-modal-bg" id="piv-cost-modal">
  <div class="piv-modal">
    <form method="POST" id="piv-cost-form" data-add-action="<?= base_url() ?>sisvent/purchases/provider_invoices/import_cost/<?= (int)$invoice->id ?>" data-edit-base="<?= base_url() ?>sisvent/purchases/provider_invoices/edit_import_cost/" action="<?= base_url() ?>sisvent/purchases/provider_invoices/import_cost/<?= (int)$invoice->id ?>">
      <div class="piv-modal-head">
        <div class="piv-modal-title" id="piv-cost-title">Registrar gasto de importación · <?= htmlspecialchars($invoice->inv_code) ?></div>
        <button type="button" class="piv-modal-close" id="piv-modal-close">×</button>
      </div>
      <div class="piv-modal-body">
        <div style="background: var(--info-100); border: 1px solid var(--info); border-radius: 6px; padding: 10px 14px; font-size: 12px; color: var(--info); margin-bottom: 16px;">
          El gasto se capitaliza a la mercancía en tránsito y se reparte al costo de cada artículo al recibir. No abona la deuda con <?= htmlspecialchars($invoice->provider_name) ?>.
        </div>

        <div class="piv-field-grid">
          <div class="piv-field">
            <label>Concepto</label>
            <select name="concept" id="piv-cost-concept">
              <option value="aduana"          data-basis="value">Aduana</option>
              <option value="flete"           data-basis="cbm">Flete</option>
              <option value="descargue"       data-basis="cbm">Descargue</option>
              <option value="nacionalizacion" data-basis="value">Nacionalización</option>
              <option value="otro"            data-basis="value">Otro</option>
            </select>
          </div>
          <div class="piv-field">
            <label>Base de reparto</label>
            <select name="alloc_basis" id="piv-cost-basis">
              <option value="value">Por valor (aduana, nacionalización)</option>
              <option value="cbm">Por CBM / volumen (flete, descargue)</option>
            </select>
          </div>
        </div>

        <div class="piv-field">
          <label>Monto (COP)</label>
          <input type="number" step="0.01" min="0.01" name="amount_base" id="piv-cost-amount" required class="mono" placeholder="0.00">
        </div>

        <div class="piv-field">
          <label>¿Cómo se paga?</label>
          <select name="paid_account_id" id="piv-cost-account">
            <option value="">Queda por pagar (Costos de importación por pagar)</option>
            <?php foreach ($cash_accounts as $ca): ?>
              <option value="<?= (int)$ca->id ?>">Pagado de <?= htmlspecialchars($ca->name) ?> · <?= htmlspecialchars($ca->currency) ?> <?= number_format((float)$ca->current_balance, 2, ',', '.') ?></option>
            <?php endforeach; ?>
          </select>
          <span style="font-size: 11px; color: var(--ink-500); font-family: var(--font-mono);">De contado: eliges caja/banco y se descuenta el saldo. Por pagar: queda como cuenta por pagar y la abonas después (total o por partes) con el botón «Pagar».</span>
        </div>

        <div class="piv-field">
          <label>Descripción (opcional)</label>
          <input type="text" name="description" id="piv-cost-desc" placeholder="Ej. DAU 1234 / agente aduanal / factura flete" class="mono">
        </div>
      </div>
      <div class="piv-modal-foot">
        <button type="button" class="piv-btn piv-btn-secondary" id="piv-modal-cancel">Cancelar</button>
        <button type="submit" class="piv-btn piv-btn-red" id="piv-cost-submit">Registrar gasto</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Modal abonar gasto de importación (compartido; se rellena por JS) -->
<?php if (!empty($impCosts)): ?>
<div class="piv-modal-bg" id="piv-paycost-modal">
  <div class="piv-modal">
    <form method="POST" action="" id="piv-paycost-form">
      <div class="piv-modal-head">
        <div class="piv-modal-title">Abonar gasto de importación · <span id="piv-paycost-label"></span></div>
        <button type="button" class="piv-modal-close" id="piv-paycost-close">×</button>
      </div>
      <div class="piv-modal-body">
        <div style="background: var(--info-100); border: 1px solid var(--info); border-radius: 6px; padding: 10px 14px; font-size: 12px; color: var(--info); margin-bottom: 16px;">
          Saldo por pagar · <b>$ <span id="piv-paycost-out">0,00</span></b>. Puedes abonar total o por partes.
        </div>
        <div class="piv-field-grid">
          <div class="piv-field">
            <label>Fecha del abono</label>
            <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required class="mono">
          </div>
          <div class="piv-field">
            <label>Monto del abono (COP)</label>
            <input type="number" step="0.01" min="0.01" name="amount_base" required class="mono" id="piv-paycost-amount" placeholder="0.00">
          </div>
        </div>
        <div class="piv-field">
          <label>Pagar desde caja / banco</label>
          <select name="cash_account_id" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($cash_accounts as $ca): ?>
              <option value="<?= (int)$ca->id ?>"><?= htmlspecialchars($ca->name) ?> · <?= htmlspecialchars($ca->currency) ?> <?= number_format((float)$ca->current_balance, 2, ',', '.') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="piv-field">
          <label>Referencia (opcional)</label>
          <input type="text" name="reference" placeholder="Nº transferencia / comprobante" class="mono">
        </div>
      </div>
      <div class="piv-modal-foot">
        <button type="button" class="piv-btn piv-btn-secondary" id="piv-paycost-cancel">Cancelar</button>
        <button type="submit" class="piv-btn piv-btn-red">Registrar abono</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Modal: registrar pago a proveedor (agregado en el port a Ledxury) -->
<div class="piv-modal-bg" id="piv-pay-modal">
  <div class="piv-modal">
    <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_payments/save">
      <input type="hidden" name="invoice_id" value="<?= (int)$invoice->id ?>">
      <input type="hidden" name="currency" value="COP">
      <input type="hidden" name="exchange_rate" value="1">
      <div class="piv-modal-head">
        <div class="piv-modal-title">Registrar pago · <?= htmlspecialchars($invoice->inv_code) ?></div>
        <button type="button" class="piv-modal-close" id="piv-pay-close">×</button>
      </div>
      <div class="piv-modal-body">
        <div style="background:var(--info-100); border:1px solid var(--info); border-radius:6px; padding:10px 14px; font-size:12px; color:var(--info); margin-bottom:16px;">
          Saldo de la factura · <b>$<?= number_format((float)$invoice->balance, 0, ',', '.') ?></b>
        </div>
        <div class="piv-field-grid">
          <div class="piv-field">
            <label>Fecha del pago</label>
            <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required class="mono">
          </div>
          <div class="piv-field">
            <label>Monto (COP)</label>
            <input type="number" step="0.01" min="0.01" max="<?= (float)$invoice->balance ?>" name="amount" required class="mono" value="<?= (float)$invoice->balance ?>">
          </div>
        </div>
        <div class="piv-field">
          <label>Pagar desde caja / banco</label>
          <select name="fuente" required>
            <option value="">— Seleccionar —</option>
            <?php if (!empty($bancos)): ?><optgroup label="Bancos">
              <?php foreach ($bancos as $b): ?><option value="banco:<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?>
            </optgroup><?php endif; ?>
            <?php if (!empty($cajas)): ?><optgroup label="Cajas">
              <?php foreach ($cajas as $cb): ?><option value="caja:<?= (int)$cb->id ?>"><?= htmlspecialchars($cb->name) ?></option><?php endforeach; ?>
            </optgroup><?php endif; ?>
          </select>
        </div>
        <div class="piv-field">
          <label>Referencia (comprobante del banco)</label>
          <input type="text" name="reference" class="mono" placeholder="Nº de transferencia / consignación">
        </div>
        <div class="piv-field">
          <label>Notas (opcional)</label>
          <input type="text" name="notes">
        </div>
      </div>
      <div class="piv-modal-foot">
        <button type="button" class="piv-btn piv-btn-secondary" id="piv-pay-cancel">Cancelar</button>
        <button type="submit" class="piv-btn piv-btn-red">Registrar pago</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('click', function (e) {
  var pm = document.getElementById('piv-pay-modal');
  if (!pm) return;
  if (e.target.closest('#piv-open-pay')) { pm.classList.add('open'); return; }
  if (e.target.closest('#piv-pay-close') || e.target.closest('#piv-pay-cancel') || e.target === pm) { pm.classList.remove('open'); }
});
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
// Toggles vía delegación en document — sobreviven al re-render de Vue de #bars
// (los botones viven dentro del mount; un addEventListener directo se pierde).
(function () {
  var PAYCOST_URL = '<?= base_url() ?>sisvent/purchases/provider_invoices/pay_import_cost/';
  function costModal() { return document.getElementById('piv-cost-modal'); }
  function payModal()  { return document.getElementById('piv-paycost-modal'); }
  function pivFactor() { var el = document.getElementById('piv-factor'); var v = el ? parseFloat(String(el.value).replace(',', '.')) : 2.5; return (isNaN(v) || v <= 0) ? 2.5 : v; }
  document.addEventListener('click', function (e) {
    if (!e.target.closest) return;
    // Abrir panel de recepción (costeo de importación)
    if (e.target.closest('#piv-open-receive')) {
      var panel = document.getElementById('piv-receive-panel');
      if (panel) {
        panel.style.display = (panel.style.display === 'none' || !panel.style.display) ? 'block' : 'none';
        if (panel.style.display === 'block') panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      return;
    }
    // Abrir modal de gasto EN MODO ALTA (resetea el formulario)
    if (e.target.closest('#piv-open-cost') || e.target.closest('#piv-open-cost-2')) {
      var bg = costModal(); if (!bg) return;
      var f = document.getElementById('piv-cost-form');
      if (f) {
        f.setAttribute('action', f.getAttribute('data-add-action'));
        var t = document.getElementById('piv-cost-title');   if (t) t.textContent = 'Registrar gasto de importación';
        var s = document.getElementById('piv-cost-submit');  if (s) s.textContent = 'Registrar gasto';
        var cc = document.getElementById('piv-cost-concept'); if (cc) cc.selectedIndex = 0;
        var cb = document.getElementById('piv-cost-basis');   if (cb) cb.value = 'value';
        var am = document.getElementById('piv-cost-amount');  if (am) am.value = '';
        var ac = document.getElementById('piv-cost-account'); if (ac) ac.value = '';
        var de = document.getElementById('piv-cost-desc');    if (de) de.value = '';
      }
      bg.classList.add('open'); return;
    }
    // Editar gasto: precarga el modal y apunta a edit_import_cost
    var editBtn = e.target.closest('.piv-edit-cost');
    if (editBtn) {
      var ebg = costModal(); if (!ebg) return;
      var ef = document.getElementById('piv-cost-form');
      if (ef) {
        ef.setAttribute('action', ef.getAttribute('data-edit-base') + editBtn.getAttribute('data-id'));
        var et = document.getElementById('piv-cost-title');   if (et) et.textContent = 'Editar gasto de importación';
        var es = document.getElementById('piv-cost-submit');  if (es) es.textContent = 'Guardar cambios';
        var ecc = document.getElementById('piv-cost-concept'); if (ecc) ecc.value = editBtn.getAttribute('data-concept') || 'otro';
        var ecb = document.getElementById('piv-cost-basis');   if (ecb) ecb.value = editBtn.getAttribute('data-basis') || 'value';
        var eam = document.getElementById('piv-cost-amount');  if (eam) eam.value = editBtn.getAttribute('data-amount') || '';
        var eacc = document.getElementById('piv-cost-account'); var av = editBtn.getAttribute('data-account'); if (eacc) eacc.value = (av && av !== '0') ? av : '';
        var ede = document.getElementById('piv-cost-desc');    if (ede) ede.value = editBtn.getAttribute('data-desc') || '';
      }
      ebg.classList.add('open'); return;
    }
    // Cerrar modal de gasto
    if (e.target.closest('#piv-modal-close') || e.target.closest('#piv-modal-cancel')) { var bg2 = costModal(); if (bg2) bg2.classList.remove('open'); return; }

    // Abrir modal de abono (rellena action, saldo y monto sugerido)
    var payBtn = e.target.closest('.piv-pay-cost');
    if (payBtn) {
      var pm = payModal(); if (!pm) return;
      var id  = payBtn.getAttribute('data-id');
      var out = payBtn.getAttribute('data-out') || '0';
      var lbl = payBtn.getAttribute('data-label') || '';
      var form = document.getElementById('piv-paycost-form');
      if (form) form.setAttribute('action', PAYCOST_URL + id);
      var lblEl = document.getElementById('piv-paycost-label'); if (lblEl) lblEl.textContent = lbl;
      var outEl = document.getElementById('piv-paycost-out');   if (outEl) outEl.textContent = parseFloat(out).toLocaleString('es', {minimumFractionDigits:2, maximumFractionDigits:2});
      var amtEl = document.getElementById('piv-paycost-amount'); if (amtEl) { amtEl.setAttribute('max', out); amtEl.value = out; }
      pm.classList.add('open');
      return;
    }
    // Cerrar modal de abono
    if (e.target.closest('#piv-paycost-close') || e.target.closest('#piv-paycost-cancel')) { var pm2 = payModal(); if (pm2) pm2.classList.remove('open'); return; }

    // Precio de venta = costo × factor (una fila o todas)
    var useSug = e.target.closest('.piv-use-sug');
    if (useSug) {
      var row = useSug.closest('tr'); var inp = row && row.querySelector('.piv-price');
      if (inp) inp.value = (parseFloat(inp.getAttribute('data-newcost') || '0') * pivFactor()).toFixed(2);
      return;
    }
    if (e.target.closest('#piv-apply-suggested')) {
      var f = pivFactor();
      document.querySelectorAll('.piv-price').forEach(function (inp) {
        inp.value = (parseFloat(inp.getAttribute('data-newcost') || '0') * f).toFixed(2);
      });
      return;
    }

    // Click en el fondo de cualquier modal
    var cbg = costModal(); if (cbg && e.target === cbg) { cbg.classList.remove('open'); return; }
    var pbg = payModal();  if (pbg && e.target === pbg) { pbg.classList.remove('open'); return; }
  });

  // Al cambiar el concepto, autoselecciona la base de reparto sugerida.
  document.addEventListener('change', function (e) {
    if (!e.target || e.target.id !== 'piv-cost-concept') return;
    var opt = e.target.options[e.target.selectedIndex];
    var basis = opt && opt.getAttribute('data-basis');
    var sel = document.getElementById('piv-cost-basis');
    if (basis && sel) sel.value = basis;
  });

  // Al cambiar el factor, recalcula el precio sugerido mostrado por artículo.
  document.addEventListener('input', function (e) {
    if (!e.target || e.target.id !== 'piv-factor') return;
    var f = pivFactor();
    document.querySelectorAll('.piv-price').forEach(function (inp) {
      var row = inp.closest('tr'); var sv = row && row.querySelector('.piv-sug-val');
      if (sv) sv.textContent = (parseFloat(inp.getAttribute('data-newcost') || '0') * f).toLocaleString('es', {minimumFractionDigits:2, maximumFractionDigits:2});
    });
  });
})();
</script>
</body>
</html>
