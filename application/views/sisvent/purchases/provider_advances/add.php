<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<title>Nuevo anticipo a proveedor · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pa-page { max-width: 720px; margin: 0 auto; padding: 24px; }
.pa-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.pa-breadcrumb a { color: inherit; text-decoration: none; }
.pa-h1 { margin: 0 0 24px; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-900); }
.pa-card { background: white; border: 1px solid var(--ink-150); border-radius: 8px; padding: 24px; }
.pa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pa-field { display: flex; flex-direction: column; gap: 4px; }
.pa-field.full { grid-column: 1 / -1; }
.pa-field label { font-size: 12px; font-weight: 600; color: var(--ink-700); }
.pa-field input, .pa-field select, .pa-field textarea { height: 40px; padding: 0 12px; font-size: 13px; color: var(--ink-900); border: 1px solid var(--ink-200); border-radius: 6px; background: white; width: 100%; }
.pa-field textarea { height: auto; padding: 10px 12px; min-height: 60px; resize: vertical; }
.pa-field input:focus, .pa-field select:focus, .pa-field textarea:focus { outline: none; border-color: var(--stock-red); box-shadow: 0 0 0 3px rgba(237,50,55,.18); }
.pa-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; }
.pa-btn { display: inline-flex; align-items: center; height: 40px; padding: 0 18px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid transparent; text-decoration: none; }
.pa-btn-primary { background: var(--ink-900); color: white !important; }
.pa-btn-secondary { background: white; color: var(--ink-800); border: 1px solid var(--ink-200); }
.pa-hint { font-size: 11px; color: var(--ink-500); margin-top: 16px; font-family: var(--font-mono); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pa-page">
        <div class="pa-breadcrumb"><a href="<?= base_url() ?>sisvent/dashboard">Stock</a> · Compras · <a href="<?= base_url() ?>sisvent/purchases/provider_advances">Anticipos</a> · Nuevo</div>
        <h1 class="pa-h1">Nuevo anticipo a proveedor</h1>

        <?php if ($this->session->flashdata('error')): ?>
        <div style="padding:12px 16px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:16px; font-size:13px; font-weight:600;"><?= htmlspecialchars($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_advances/save">
          <div class="pa-card">
            <div class="pa-grid">
              <div class="pa-field full">
                <label>Proveedor</label>
                <select name="provider_id" required>
                  <option value="" disabled <?= empty($preset_provider) ? 'selected' : '' ?>>Seleccionar…</option>
                  <?php foreach ($providers as $p): ?>
                    <option value="<?= (int)$p->idProvider ?>" <?= ($preset_provider == $p->idProvider) ? 'selected' : '' ?>><?= htmlspecialchars($p->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="pa-field">
                <label>Fecha</label>
                <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="pa-field">
                <label>Forma de pago</label>
                <select name="payment_method">
                  <option value="Transferencia">Transferencia</option>
                  <option value="Efectivo">Efectivo</option>
                  <option value="Cheque">Cheque</option>
                  <option value="Otra">Otra</option>
                </select>
              </div>
              <div class="pa-field full">
                <label>Caja / banco de donde sale</label>
                <select name="fuente">
                  <option value="">— Sin movimiento de tesorería —</option>
                  <?php if (!empty($bancos)): ?><optgroup label="Bancos">
                    <?php foreach ($bancos as $b): ?><option value="banco:<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?>
                  </optgroup><?php endif; ?>
                  <?php if (!empty($cajas)): ?><optgroup label="Cajas">
                    <?php foreach ($cajas as $cb): ?><option value="caja:<?= (int)$cb->id ?>"><?= htmlspecialchars($cb->name) ?></option><?php endforeach; ?>
                  </optgroup><?php endif; ?>
                </select>
              </div>
              <div class="pa-field">
                <label>Monto</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00">
              </div>
              <div class="pa-field">
                <label>Tasa (pesos por unidad; 1 si es COP)</label>
                <input type="number" step="0.000001" name="exchange_rate" value="1" placeholder="1">
              </div>
              <div class="pa-field full">
                <label>Referencia</label>
                <input type="text" name="reference" placeholder="Nº transferencia / SWIFT / nota">
              </div>
              <div class="pa-field full">
                <label>Notas</label>
                <textarea name="notes" placeholder="Ej: 30% depósito pedido PI-2024-08 · saldo antes de embarque"></textarea>
              </div>
            </div>
            <div class="pa-hint">El anticipo se registra como activo (1330). Sale de la caja elegida y queda como saldo a favor del proveedor. Al recibir la mercancía y generarse la factura, se aplica automáticamente.</div>
            <div class="pa-actions">
              <a class="pa-btn pa-btn-secondary" href="<?= base_url() ?>sisvent/purchases/provider_advances">Cancelar</a>
              <button type="submit" class="pa-btn pa-btn-primary">Registrar anticipo</button>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
