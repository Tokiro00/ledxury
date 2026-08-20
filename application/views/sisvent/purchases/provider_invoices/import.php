<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<title>Cargar packing list · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pia-page { max-width: 760px; margin: 0 auto; padding: 24px; }
.pia-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
.pia-h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -.02em; color: var(--ink-900); }
.pia-sub { font-size: 13px; color: var(--ink-500); margin-top: 6px; }
.pia-card { background:#fff; border:1px solid var(--ink-150); border-radius:8px; padding:24px; margin-top:18px; }
.pia-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.pia-field { display:flex; flex-direction:column; gap:4px; }
.pia-field.full { grid-column:1/-1; }
.pia-field label { font-size:12px; font-weight:600; color:var(--ink-700); }
.pia-field input, .pia-field select, .pia-field textarea { height:38px; padding:0 12px; font-size:13px; border:1px solid var(--ink-200); border-radius:6px; background:#fff; color:var(--ink-900); }
.pia-field textarea { height:auto; padding:10px 12px; min-height:60px; }
.pia-help { font-size:11px; color:var(--ink-500); }
.pia-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:20px; }
.pia-btn { height:38px; padding:0 16px; font-size:13px; font-weight:600; border-radius:6px; border:1px solid transparent; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; }
.pia-btn-primary { background:var(--ink-900); color:#fff !important; }
.pia-btn-secondary { background:#fff; color:var(--ink-800); border-color:var(--ink-200); }
.pia-flash { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; background:var(--danger-100); color:var(--danger); border:1px solid var(--danger); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pia-page">
        <div class="pia-breadcrumb"><a href="<?= base_url() ?>sisvent/purchases/provider_invoices" style="color:inherit;text-decoration:none;">Facturas de proveedor</a> · Cargar packing list Yufun</div>
        <h1 class="pia-h1">Cargar packing list Yufun</h1>
        <div class="pia-sub">Sube el xlsx que envía el proveedor: se leen automáticamente los datos (Nº, fecha, contenedor, puertos, % financiación) y los SKU. Se crea la factura <strong>en tránsito</strong> (no pesa en CxP hasta recibir).</div>

        <?php if ($msg = $this->session->flashdata('error')): ?><div class="pia-flash" style="margin-top:16px;"><?= $msg ?></div><?php endif; ?>

        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/import_review" enctype="multipart/form-data">
          <div class="pia-card">
            <div class="pia-grid">
              <div class="pia-field">
                <label>Proveedor <span style="color:var(--danger)">*</span></label>
                <select name="provider_id" required>
                  <option value="">— Seleccionar —</option>
                  <?php foreach ($providers as $p): ?>
                    <option value="<?= (int)$p->idProvider ?>" <?= $preset_provider_id === (int)$p->idProvider ? 'selected' : '' ?>><?= htmlspecialchars($p->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="pia-field">
                <label>TRM (pesos por RMB) <span style="color:var(--danger)">*</span></label>
                <input type="number" step="0.0001" min="0" name="trm" required value="6.74">
                <span class="pia-help">Del estado de cuenta (ej. 6,74). No viene en la factura.</span>
              </div>
              <div class="pia-field full">
                <label>Archivo de factura / packing list (.xlsx) <span style="color:var(--danger)">*</span></label>
                <input type="file" name="packing" accept=".xlsx" required style="padding:7px 12px;height:auto;">
                <span class="pia-help">Sube la <strong>proforma / invoice de Yufun</strong>. El Nº, fecha, contenedor, puertos y el % de financiación se leen automáticamente del archivo (los revisas después).</span>
              </div>
            </div>
            <div class="pia-actions">
              <a href="<?= base_url() ?>sisvent/purchases/provider_invoices" class="pia-btn pia-btn-secondary">Cancelar</a>
              <button type="submit" class="pia-btn pia-btn-primary">Leer y revisar →</button>
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
