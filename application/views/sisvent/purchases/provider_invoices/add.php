<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$inv          = $inv ?? null;            // objeto factura en modo edición, null al crear
$isEdit       = ($inv !== null);
$itemsPrefill = $items_prefill ?? [];
$v = function ($field, $default = '') use ($inv) {
    return ($inv && isset($inv->$field) && $inv->$field !== null) ? $inv->$field : $default;
};
?>
<!DOCTYPE html>
<html lang="es">
<title><?= $isEdit ? 'Editar' : 'Cargar' ?> factura proveedor · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pia-page { max-width: 880px; margin: 0 auto; padding: 24px; }
.pia-head { margin-bottom: 24px; }
.pia-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.pia-breadcrumb a { color: inherit; text-decoration: none; }
.pia-h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.pia-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.pia-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 24px; box-shadow: 0 1px 2px rgba(15,15,20,.04); }
.pia-section { margin-bottom: 24px; }
.pia-section-title { font-family: var(--font-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-500); margin-bottom: 12px; padding-bottom: 6px; border-bottom: 1px solid var(--ink-150); }
.pia-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pia-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.pia-field { display: flex; flex-direction: column; gap: 4px; }
.pia-field.full { grid-column: 1 / -1; }
.pia-field label { font-size: 12px; font-weight: 600; color: var(--ink-700); }
.pia-field label .req { color: var(--danger); }
.pia-field input, .pia-field select, .pia-field textarea {
  height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;
  color: var(--ink-900); border: 1px solid var(--ink-200); border-radius: 6px;
  background: white; font-family: var(--font-sans);
}
.pia-field textarea { height: auto; padding: 10px 12px; min-height: 70px; resize: vertical; }
.pia-field input.mono, .pia-field select.mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.pia-field input:focus, .pia-field select:focus, .pia-field textarea:focus {
  outline: none; border-color: var(--stock-red); box-shadow: 0 0 0 3px rgba(237,50,55,.18);
}
.pia-help { font-size: 11px; color: var(--ink-500); }
.pia-totals-card { background: var(--ink-25); border: 1px solid var(--ink-150); border-radius: 8px; padding: 16px 20px; margin-top: 20px; }
.pia-totals-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; }
.pia-totals-row.total { font-size: 18px; font-weight: 700; color: var(--ink-900); border-top: 1px solid var(--ink-200); padding-top: 10px; margin-top: 6px; font-family: var(--font-mono); }
.pia-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; }
.pia-btn { display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 16px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; border: 1px solid transparent; transition: all .12s; font-family: var(--font-sans); text-decoration: none; }
.pia-btn-primary { background: var(--ink-900); color: white !important; }
.pia-btn-primary:hover { background: var(--ink-800); }
.pia-btn-secondary { background: white; color: var(--ink-800); border-color: var(--ink-200); }
.pia-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: 500; background: var(--danger-100); color: var(--danger); border: 1px solid var(--danger); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pia-page">

        <div class="pia-head">
          <div class="pia-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · <a href="<?= base_url() ?>sisvent/purchases/cxp">CxP</a> · <a href="<?= base_url() ?>sisvent/purchases/provider_invoices">Facturas</a> · <?= $isEdit ? 'Editar' : 'Cargar' ?></div>
          <h1 class="pia-h1"><?= $isEdit ? 'Editar' : 'Cargar' ?> factura de proveedor</h1>
          <div class="pia-sub"><?= $isEdit ? 'Solo facturas sin recibir y sin pagos. Se recalcula el asiento de CxP.' : 'Registra una factura pendiente — puede ser deuda histórica o una factura nueva' ?></div>
        </div>

        <?php if ($msg = $this->session->flashdata('error')): ?>
          <div class="pia-flash"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/save">
          <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $inv->id ?>"><?php endif; ?>
          <div class="pia-card">

            <div class="pia-section">
              <div class="pia-section-title">Datos de la factura</div>
              <div class="pia-grid">
                <div class="pia-field">
                  <label>Nº de factura <span class="req">*</span></label>
                  <input type="text" name="inv_code" required placeholder="ej. INV-2026-001" class="mono" value="<?= htmlspecialchars($v('inv_code')) ?>">
                  <span class="pia-help">El número que aparece en el documento del proveedor</span>
                </div>
                <div class="pia-field">
                  <label>Proveedor <span class="req">*</span></label>
                  <select name="provider_id" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($providers as $p): ?>
                      <option value="<?= (int)$p->idProvider ?>" <?= $preset_provider_id === (int)$p->idProvider ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="pia-field">
                  <label>Fecha emisión <span class="req">*</span></label>
                  <input type="date" name="issue_date" required value="<?= htmlspecialchars($v('issue_date', date('Y-m-d'))) ?>" class="mono">
                </div>
                <div class="pia-field">
                  <label>Fecha vencimiento</label>
                  <input type="date" name="due_date" class="mono" value="<?= htmlspecialchars($v('due_date')) ?>">
                  <span class="pia-help">Si la dejas vacía, se considera al contado</span>
                </div>
              </div>
            </div>

            <div class="pia-section">
              <div class="pia-section-title">Moneda y tasa</div>
              <div class="pia-grid-3">
                <div class="pia-field">
                  <label>Moneda <span class="req">*</span></label>
                  <?php $curSel = $v("currency", "COP"); ?>
                  <select name="currency" required id="pia-currency">
                    <?php foreach (["COP"=>"COP · peso","USD"=>"USD · dólar","CNY"=>"CNY · yuan (RMB)","EUR"=>"EUR · euro"] as $cc=>$cl): ?>
                      <option value="<?= $cc ?>" <?= $curSel === $cc ? 'selected' : '' ?>><?= $cl ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="pia-field">
                  <label>Tasa de cambio (pesos por unidad)</label>
                  <input type="number" step="0.0001" min="0" name="exchange_rate" value="<?= htmlspecialchars($v('exchange_rate', '1')) ?>" class="mono" id="pia-rate">
                  <span class="pia-help">1 si la moneda es COP · ej. 4000 si es USD, 550 si es RMB</span>
                </div>
                <div class="pia-field"></div>
              </div>
            </div>

            <?php if (!$isEdit): ?>
            <div class="pia-section">
              <div class="pia-section-title">Estado de la mercancía</div>
              <label style="display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid var(--ink-150); border-radius:8px; background:var(--ink-25); cursor:pointer;">
                <input type="checkbox" name="in_transit" value="1" style="width:18px; height:18px;">
                <span>
                  <strong style="font-size:13px; color:var(--ink-900);">Cargar como EN TRÁNSITO</strong>
                  <span style="display:block; font-size:11px; color:var(--ink-500); margin-top:2px;">La deuda (CxP) se registra ya, pero la mercancía queda como <em>Mercancía en Tránsito</em> y solo entra al inventario cuando le des <strong>Recibir</strong> (con aduana/flete). Para importaciones que aún no llegan.</span>
                </span>
              </label>
              <label class="pia-field" style="margin-top:12px; max-width:260px;">
                <span class="lbl">% Financiación (se capitaliza)</span>
                <input type="number" step="0.001" min="0" name="financing_pct" value="" placeholder="ej. 11 (0 = ninguna)" class="mono">
                <span class="pia-help">Total = mercancía × (1 + %). Se suma al costo al recibir.</span>
              </label>
            </div>
            <?php endif; ?>

            <div class="pia-section">
              <div class="pia-section-title">Artículos <span style="text-transform:none; color:var(--ink-500); font-weight:500;">(opcional — si se agregan, el subtotal se calcula automáticamente)</span></div>

              <!-- Línea de búsqueda + cantidad + precio + Agregar (mismo layout que crear presupuesto) -->
              <div style="display:grid; grid-template-columns: 1fr 100px 130px auto; gap:8px; align-items:end; margin-bottom:14px;">
                <div class="pia-field" id="pii-search-mount" style="margin:0; position:relative;">
                  <label style="font-size:11px; color:var(--ink-500); font-weight:600;">Producto</label>
                  <input type="text" id="pii-search" placeholder="Buscar por código o descripción…" autocomplete="off">
                  <div id="pii-results" style="position:absolute; top:64px; left:0; right:0; background:white; border:1px solid var(--ink-200); border-radius:6px; max-height:280px; overflow-y:auto; z-index:1000; display:none; box-shadow:0 8px 24px rgba(15,15,20,.12);"></div>
                </div>
                <div class="pia-field" style="margin:0;">
                  <label style="font-size:11px; color:var(--ink-500); font-weight:600;">Cantidad</label>
                  <input type="number" id="pii-qty" class="mono" min="0.001" step="0.001" placeholder="1" value="1">
                </div>
                <div class="pia-field" style="margin:0;">
                  <label style="font-size:11px; color:var(--ink-500); font-weight:600;">Costo unit.</label>
                  <input type="number" id="pii-cost" class="mono" min="0" step="0.0001" placeholder="0,00">
                </div>
                <button type="button" id="pii-add-btn" style="height:38px; padding:0 18px; background:var(--ink-900); color:white; border:0; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; white-space:nowrap;">+ Agregar</button>
              </div>

              <!-- Tabla de líneas -->
              <div style="border:1px solid var(--ink-150); border-radius:8px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                  <thead>
                    <tr style="background:var(--ink-25); border-bottom:1px solid var(--ink-150);">
                      <th style="padding:10px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600; width:40px;">#</th>
                      <th style="padding:10px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600; width:120px;">Código</th>
                      <th style="padding:10px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600;">Descripción</th>
                      <th style="padding:10px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600; width:90px;">Cant.</th>
                      <th style="padding:10px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600; width:120px;">Costo unit.</th>
                      <th style="padding:10px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-500); font-weight:600; width:120px;">Subtotal</th>
                      <th style="padding:10px; width:40px;"></th>
                    </tr>
                  </thead>
                  <tbody id="pii-tbody">
                    <tr id="pii-empty"><td colspan="7" style="padding:20px; text-align:center; color:var(--ink-500); font-size:12px;">Sin artículos · busca un producto y pulsa Agregar</td></tr>
                  </tbody>
                  <tfoot>
                    <tr style="background:var(--ink-25); border-top:1px solid var(--ink-150);">
                      <td colspan="5" style="padding:12px; text-align:right; font-weight:700; color:var(--ink-700);">Subtotal artículos</td>
                      <td style="padding:12px; text-align:right; font-weight:700; font-family:var(--font-mono); color:var(--ink-900);" id="pii-foot-subtotal">$0,00</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <input type="hidden" name="items_json" id="pii-items-json" value="">
            </div>

            <div class="pia-section">
              <div class="pia-section-title">Importes</div>
              <div class="pia-grid-3">
                <div class="pia-field">
                  <label>Subtotal</label>
                  <input type="number" step="0.01" min="0" name="subtotal" value="<?= htmlspecialchars($v('subtotal', '0')) ?>" class="mono" id="pia-subtotal">
                </div>
                <div class="pia-field">
                  <label>IVA</label>
                  <input type="number" step="0.01" min="0" name="tax" value="<?= htmlspecialchars($v('tax', '0')) ?>" class="mono" id="pia-tax">
                </div>
                <div class="pia-field">
                  <label>Retenciones</label>
                  <input type="number" step="0.01" min="0" name="withholding" value="<?= htmlspecialchars($v('withholding', '0')) ?>" class="mono" id="pia-withholding">
                  <span class="pia-help">ISLR · IVA retenido · otros</span>
                </div>
                <div class="pia-field full">
                  <label>Total <span class="req">*</span></label>
                  <input type="number" step="0.01" min="0.01" name="total" required value="<?= htmlspecialchars($v('total', '0')) ?>" class="mono" id="pia-total" style="font-size: 18px; font-weight: 700;">
                  <span class="pia-help">Lo que efectivamente debes al proveedor (subtotal + IVA − retenciones)</span>
                </div>
              </div>

              <div class="pia-totals-card" id="pia-summary" style="display:none;">
                <div class="pia-totals-row"><span>Subtotal</span><span class="mono" id="pia-sum-subtotal">0,00</span></div>
                <div class="pia-totals-row"><span>+ IVA</span><span class="mono" id="pia-sum-tax">0,00</span></div>
                <div class="pia-totals-row" style="color: var(--success);"><span>− Retenciones</span><span class="mono" id="pia-sum-with">0,00</span></div>
                <div class="pia-totals-row total"><span>Total · <span id="pia-sum-cur">COP</span></span><span id="pia-sum-total">0,00</span></div>
                <div class="pia-totals-row" style="color: var(--ink-500); font-size: 11px; font-family: var(--font-mono);"><span>Equivalente COP (tasa <span id="pia-sum-rate">1</span>)</span><span id="pia-sum-usd">0,00</span></div>
              </div>
            </div>

            <div class="pia-section">
              <div class="pia-section-title">Notas</div>
              <div class="pia-grid">
                <div class="pia-field full">
                  <label>Observaciones</label>
                  <textarea name="notes" placeholder="ej. Factura inicial PEPE — apertura 2026. Comprende guía No.123"><?= htmlspecialchars($v('notes')) ?></textarea>
                </div>
              </div>
            </div>

            <div class="pia-actions">
              <a href="<?= base_url() ?>sisvent/purchases/provider_invoices" class="pia-btn pia-btn-secondary">Cancelar</a>
              <button type="submit" class="pia-btn pia-btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Cargar factura' ?></button>
            </div>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
// Auto-cálculo de total + preview de equivalente USD (fuera de Vue mount)
(function () {
  function recompute() {
    var sub  = parseFloat(document.getElementById('pia-subtotal').value) || 0;
    var tax  = parseFloat(document.getElementById('pia-tax').value) || 0;
    var wh   = parseFloat(document.getElementById('pia-withholding').value) || 0;
    var totEl = document.getElementById('pia-total');
    var cur  = document.getElementById('pia-currency').value || 'COP';
    var rate = parseFloat(document.getElementById('pia-rate').value) || 1;

    // Auto-actualiza total SI el usuario no lo ha tocado manualmente
    var computed = sub + tax - wh;
    if (!totEl._userTouched) {
      totEl.value = computed.toFixed(2);
    }
    var total = parseFloat(totEl.value) || 0;

    // Summary preview
    var sum = document.getElementById('pia-summary');
    sum.style.display = (sub + tax + wh + total) > 0 ? 'block' : 'none';
    document.getElementById('pia-sum-subtotal').textContent = sub.toFixed(2);
    document.getElementById('pia-sum-tax').textContent      = tax.toFixed(2);
    document.getElementById('pia-sum-with').textContent     = wh.toFixed(2);
    document.getElementById('pia-sum-total').textContent    = total.toFixed(2);
    document.getElementById('pia-sum-cur').textContent      = cur;
    document.getElementById('pia-sum-rate').textContent     = rate.toFixed(4);
    document.getElementById('pia-sum-usd').textContent      = (total * rate).toFixed(2);
  }
  ['pia-subtotal','pia-tax','pia-withholding','pia-currency','pia-rate'].forEach(function(id){
    document.getElementById(id).addEventListener('input', recompute);
    document.getElementById(id).addEventListener('change', recompute);
  });
  document.getElementById('pia-total').addEventListener('input', function () {
    this._userTouched = true; recompute();
  });
  // Default rate switch on currency change
  document.getElementById('pia-currency').addEventListener('change', function () {
    var defaults = { COP: 1, USD: 4000, CNY: 550, EUR: 4400 };
    var d = defaults[this.value];
    if (d !== undefined) document.getElementById('pia-rate').value = d;
    recompute();
  });
  recompute();
})();

// === Artículos de la factura — dropdown manual + tabla ===
(function () {
  var BASE = '<?= base_url() ?>';
  var items = <?= json_encode(array_map(function ($it) {
      return [
          'product_id'  => $it->product_id,
          'description' => $it->description,
          'quantity'    => (float) $it->quantity,
          'unit_cost'   => (float) $it->unit_cost,
      ];
  }, $itemsPrefill), JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>;
  var selectedProduct = null;

  function fmtMoney(n) {
    var v = Number(n) || 0;
    return '$' + v.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }

  function render() {
    var tbody = document.getElementById('pii-tbody');
    var footSub = document.getElementById('pii-foot-subtotal');
    var hiddenJson = document.getElementById('pii-items-json');
    var subtotalIn = document.getElementById('pia-subtotal');

    if (items.length === 0) {
      tbody.innerHTML = '<tr id="pii-empty"><td colspan="7" style="padding:20px; text-align:center; color:var(--ink-500); font-size:12px;">Sin artículos · busca un producto y pulsa Agregar</td></tr>';
      footSub.textContent = '$0,00';
      hiddenJson.value = '';
      subtotalIn.readOnly = false;
      subtotalIn.style.background = '';
      subtotalIn.dispatchEvent(new Event('input', { bubbles: true }));
      return;
    }

    var html = '';
    var sub = 0;
    items.forEach(function (it, i) {
      var line = (Number(it.quantity) || 0) * (Number(it.unit_cost) || 0);
      sub += line;
      html += '<tr style="border-bottom:1px solid var(--ink-100);">'
        + '<td style="padding:10px 12px; color:var(--ink-500); font-family:var(--font-mono); font-size:12px;">' + (i + 1) + '</td>'
        + '<td style="padding:10px 12px; font-family:var(--font-mono); color:var(--ink-700); font-size:12px;">' + escapeHtml(it.product_id) + '</td>'
        + '<td style="padding:10px 12px; color:var(--ink-800);">' + escapeHtml(it.description) + '</td>'
        + '<td style="padding:10px 12px; text-align:right;"><input type="number" step="0.001" min="0" value="' + (it.quantity || 0) + '" data-i="' + i + '" data-f="quantity" style="width:80px; text-align:right; font-family:var(--font-mono); padding:4px 8px; border:1px solid var(--ink-200); border-radius:4px;"></td>'
        + '<td style="padding:10px 12px; text-align:right;"><input type="number" step="0.0001" min="0" value="' + (it.unit_cost || 0) + '" data-i="' + i + '" data-f="unit_cost" style="width:110px; text-align:right; font-family:var(--font-mono); padding:4px 8px; border:1px solid var(--ink-200); border-radius:4px;"></td>'
        + '<td style="padding:10px 12px; text-align:right; font-family:var(--font-mono); font-weight:600; color:var(--ink-900);">' + fmtMoney(line) + '</td>'
        + '<td style="padding:10px; text-align:center;"><button type="button" data-rm="' + i + '" title="Eliminar" style="background:none; border:0; color:var(--danger); cursor:pointer; font-size:20px; line-height:1;">×</button></td>'
        + '</tr>';
    });
    tbody.innerHTML = html;
    footSub.textContent = fmtMoney(sub);
    hiddenJson.value = JSON.stringify(items);
    subtotalIn.value = sub.toFixed(2);
    subtotalIn.readOnly = true;
    subtotalIn.style.background = 'var(--ink-50, #F6F4F0)';
    // Recalcular Total directamente (no podemos depender de listeners de Vue-re-rendered nodes)
    recomputeTotals();
  }

  // Cálculo de totales inline (Subtotal + IVA - Retenciones), sobrevive a re-renders de Vue
  function recomputeTotals() {
    var sub = parseFloat(getEl('pia-subtotal').value) || 0;
    var tax = parseFloat(getEl('pia-tax').value) || 0;
    var wh  = parseFloat(getEl('pia-withholding').value) || 0;
    var totEl = getEl('pia-total');
    if (!totEl._userTouched) totEl.value = (sub + tax - wh).toFixed(2);
    // Summary preview
    var rate  = parseFloat(getEl('pia-rate').value) || 1;
    var cur   = getEl('pia-currency').value || 'COP';
    var total = parseFloat(totEl.value) || 0;
    var sumEl = getEl('pia-summary');
    if (sumEl) {
      sumEl.style.display = (sub + tax + wh + total) > 0 ? 'block' : 'none';
      getEl('pia-sum-subtotal').textContent = sub.toFixed(2);
      getEl('pia-sum-tax').textContent      = tax.toFixed(2);
      getEl('pia-sum-with').textContent     = wh.toFixed(2);
      getEl('pia-sum-total').textContent    = total.toFixed(2);
      getEl('pia-sum-cur').textContent      = cur;
      getEl('pia-sum-rate').textContent     = rate.toFixed(4);
      getEl('pia-sum-usd').textContent      = (total * rate).toFixed(2);
    }
  }

  function addCurrent() {
    if (!selectedProduct) { alert('Primero busca y selecciona un producto.'); return; }
    var qty  = Number(document.getElementById('pii-qty').value)  || 0;
    var cost = Number(document.getElementById('pii-cost').value) || 0;
    if (qty <= 0) { alert('La cantidad debe ser mayor a 0.'); return; }
    var existing = items.find(function (x) { return x.product_id === selectedProduct.id; });
    if (existing) {
      existing.quantity = (Number(existing.quantity) || 0) + qty;
      if (cost > 0) existing.unit_cost = cost;
    } else {
      items.push({
        product_id:  selectedProduct.id,
        description: selectedProduct.desc,
        quantity:    qty,
        unit_cost:   cost
      });
    }
    selectedProduct = null;
    document.getElementById('pii-search').value = '';
    document.getElementById('pii-qty').value = '1';
    document.getElementById('pii-cost').value = '';
    render();
    document.getElementById('pii-search').focus();
  }

  // Inline edit + remove en la tabla
  document.getElementById('pii-tbody').addEventListener('input', function (e) {
    var t = e.target;
    if (!t.dataset || t.dataset.i === undefined) return;
    var i = parseInt(t.dataset.i, 10);
    var f = t.dataset.f;
    if (!items[i]) return;
    items[i][f] = Number(t.value) || 0;
    render();
  });
  document.getElementById('pii-tbody').addEventListener('click', function (e) {
    var t = e.target;
    if (!t.dataset || t.dataset.rm === undefined) return;
    items.splice(parseInt(t.dataset.rm, 10), 1);
    render();
  });

  document.getElementById('pii-add-btn').addEventListener('click', addCurrent);

  // Dropdown manual — listeners en DOCUMENT (delegación) para sobrevivir a re-renders de Vue
  var searchTimer = null;
  function getEl(id) { return document.getElementById(id); }

  function showResults(rows) {
    var box = getEl('pii-results');
    if (!box) return;
    if (!rows || rows.length === 0) {
      box.innerHTML = '<div style="padding:10px 12px; color:var(--ink-500); font-size:12px;">Sin resultados</div>';
      box.style.display = 'block';
      return;
    }
    box.innerHTML = rows.map(function (p) {
      // cost_cop es la fuente real de costos en Ledxury (en pesos).
      var cost = Number(p.cost_cop) > 0 ? Number(p.cost_cop)
               : (Number(p.cost) > 0 ? Number(p.cost) : (Number(p.price) || 0));
      var payload = JSON.stringify({ id: p.idProduct, desc: p.description || '', cost: cost })
        .replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/"/g,'&quot;');
      return '<div class="pii-opt" data-p="' + payload + '" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid var(--ink-100); display:flex; justify-content:space-between; gap:10px; align-items:center;">'
        + '<div style="min-width:0; flex:1;"><div style="font-weight:600; color:var(--ink-800); font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escapeHtml(p.description || '') + '</div>'
        + '<div style="font-family:var(--font-mono); font-size:11px; color:var(--ink-500);">' + escapeHtml(p.idProduct) + '</div></div>'
        + '<div style="font-family:var(--font-mono); font-weight:600; color:var(--ink-700); font-size:13px; white-space:nowrap;">$' + cost.toFixed(2) + '</div>'
        + '</div>';
    }).join('');
    box.style.display = 'block';
  }

  function doSearch(q) {
    fetch(BASE + 'sisvent/purchases/provider_invoices/search_products?q=' + encodeURIComponent(q), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) {
        return r.text().then(function (body) {
          try { return JSON.parse(body); } catch (e) { return { rows: [] }; }
        });
      })
      .then(function (data) {
        showResults((data && data.rows) || []);
      })
      .catch(function () { /* silent */ });
  }

  // DELEGACIÓN GLOBAL — sobreviven a Vue re-rendering
  document.addEventListener('input', function (e) {
    if (!e.target) return;
    var id = e.target.id;
    if (id === 'pii-search') {
      var q = e.target.value.trim();
      clearTimeout(searchTimer);
      var box = getEl('pii-results');
      if (q.length < 2) { if (box) box.style.display = 'none'; selectedProduct = null; return; }
      searchTimer = setTimeout(function () { doSearch(q); }, 200);
      return;
    }
    // Cambios en IVA, retenciones, subtotal manual, tasa o moneda → recalcular Total
    if (id === 'pia-subtotal' || id === 'pia-tax' || id === 'pia-withholding' || id === 'pia-rate' || id === 'pia-currency') {
      recomputeTotals();
      return;
    }
    // Si el usuario edita el Total manualmente, marcarlo como "tocado" para no sobrescribirlo
    if (id === 'pia-total') {
      e.target._userTouched = true;
      recomputeTotals();
      return;
    }
    // Edición inline de cantidad/costo en la tabla
    var t = e.target;
    if (t.dataset && t.dataset.i !== undefined) {
      var i = parseInt(t.dataset.i, 10);
      var f = t.dataset.f;
      if (items[i]) { items[i][f] = Number(t.value) || 0; render(); }
    }
  });
  document.addEventListener('change', function (e) {
    if (!e.target) return;
    if (e.target.id === 'pia-currency') {
      var defaults = { COP: 1, USD: 4000, CNY: 550, EUR: 4400 };
      var d = defaults[e.target.value];
      if (d !== undefined) getEl('pia-rate').value = d;
      recomputeTotals();
    }
  });

  document.addEventListener('click', function (e) {
    var t = e.target;
    // 1) click en una opción del dropdown
    var opt = t.closest && t.closest('.pii-opt');
    if (opt) {
      var data = JSON.parse(opt.dataset.p);
      selectedProduct = { id: data.id, desc: data.desc, cost: data.cost };
      getEl('pii-search').value = data.id + ' · ' + data.desc;
      getEl('pii-cost').value = (Number(data.cost) || 0).toFixed(2);
      getEl('pii-results').style.display = 'none';
      getEl('pii-qty').focus();
      getEl('pii-qty').select();
      return;
    }
    // 2) click en botón Agregar
    if (t && t.id === 'pii-add-btn') { addCurrent(); return; }
    // 3) click en × eliminar de la tabla
    if (t && t.dataset && t.dataset.rm !== undefined) {
      items.splice(parseInt(t.dataset.rm, 10), 1);
      render();
      return;
    }
    // 4) click fuera del dropdown → cerrar
    var box = getEl('pii-results');
    var inp = getEl('pii-search');
    if (box && t !== inp && !box.contains(t)) {
      box.style.display = 'none';
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.keyCode !== 13) return;
    if (e.target && (e.target.id === 'pii-qty' || e.target.id === 'pii-cost')) {
      e.preventDefault();
      addCurrent();
    }
  });

  // Pintar ítems precargados (modo edición)
  if (items.length) render();

})();
</script>
</body>
</html>
