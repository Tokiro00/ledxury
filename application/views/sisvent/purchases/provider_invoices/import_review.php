<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return number_format((float)$n, 2, ',', '.'); };
$badge = ['mapeado'=>['Mapeado','#0EA572'],'catálogo'=>['En catálogo','#1B365D'],'nuevo'=>['Nuevo','#E1306C']];
?>
<!DOCTYPE html>
<html lang="es">
<title>Revisar packing list · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.pir-page { max-width: 1200px; margin: 0 auto; padding: 20px; }
.pir-breadcrumb { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing:.06em; }
.pir-h1 { margin: 4px 0 0; font-size: 22px; font-weight: 700; color: var(--ink-900); }
.pir-sub { font-size: 13px; color: var(--ink-500); margin-top: 4px; }
.pir-card { background:#fff; border:1px solid var(--ink-150); border-radius:8px; overflow:hidden; margin-top:16px; }
.pir-strip { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin-top:14px; }
.pir-tile { background:#fff; border:1px solid var(--ink-150); border-top:3px solid #1B365D; border-radius:8px; padding:12px 14px; }
.pir-tile .l { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:var(--ink-400); }
.pir-tile .v { font-size:18px; font-weight:700; color:var(--ink-900); }
table.pir { width:100%; border-collapse:collapse; font-size:12.5px; }
table.pir th { background:#F1F3F5; padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.4px; color:#575964; font-weight:600; }
table.pir td { padding:7px 10px; border-top:1px solid #EEF0F3; vertical-align:top; }
table.pir input { width:100%; height:32px; padding:0 8px; border:1px solid var(--ink-200); border-radius:5px; font-size:12px; }
.pir-r { text-align:right; font-family:var(--font-mono); }
.pir-badge { display:inline-block; padding:1px 7px; border-radius:99px; font-size:10px; font-weight:700; color:#fff; }
.pir-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:18px; }
.pir-btn { height:38px; padding:0 16px; font-size:13px; font-weight:600; border-radius:6px; border:1px solid transparent; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; }
.pir-btn-primary { background:var(--stock-red,#ED3237); color:#fff !important; }
.pir-btn-secondary { background:#fff; color:var(--ink-800); border-color:var(--ink-200); }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="pir-page">
        <div class="pir-breadcrumb">Facturas de proveedor · Cargar · Revisar</div>
        <h1 class="pir-h1">Revisar packing list — <?= htmlspecialchars($provider->name ?? '') ?></h1>
        <div class="pir-sub">Factura <strong><?= htmlspecialchars($header['inv_code']) ?></strong> · <?= count($items) ?> líneas · revisa el <strong>código y nombre en TU catálogo</strong> antes de crear. Los SKU nuevos se crearán; el mapeo queda guardado para la próxima.</div>

        <div class="pir-strip">
          <div class="pir-tile"><div class="l">Cartones</div><div class="v"><?= (int)$totals['ctns'] ?></div></div>
          <div class="pir-tile"><div class="l">Piezas</div><div class="v"><?= (int)$totals['qty'] ?></div></div>
          <div class="pir-tile"><div class="l">Total factura (RMB)</div><div class="v"><?= $fmt($totals['amount']) ?></div></div>
          <div class="pir-tile"><div class="l">Total COP (TRM <?= $fmt($header['trm']) ?>)</div><div class="v">$<?= $fmt($totals['amount']*$header['trm']) ?></div></div>
          <div class="pir-tile"><div class="l">CBM total</div><div class="v"><?= number_format($totals['cbm'],3,',','.') ?></div></div>
        </div>

        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/import_save">
          <input type="hidden" name="provider_id" value="<?= (int)$providerId ?>">
          <input type="hidden" name="trm" value="<?= htmlspecialchars((string)$header['trm']) ?>">

          <!-- Encabezado extraído del archivo (editable) -->
          <div class="pir-card" style="padding:14px 16px; margin-bottom:14px;">
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-500); margin-bottom:2px;">Datos de la factura <span style="text-transform:none; font-weight:500; color:#0EA572;">✓ leídos del archivo</span></div>
            <div style="font-size:11px; color:var(--ink-500); margin-bottom:12px;">Revisa y corrige si hace falta. TRM <?= $fmt($header['trm']) ?> COP por RMB.</div>
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px;">
              <label style="display:flex;flex-direction:column;gap:4px;"><span style="font-size:11px;font-weight:600;color:var(--ink-700);">Nº factura</span>
                <input type="text" name="inv_code" required value="<?= htmlspecialchars($header['inv_code'], ENT_QUOTES) ?>" style="height:36px;padding:0 10px;border:1px solid var(--ink-200);border-radius:6px;font-family:var(--font-mono);"></label>
              <label style="display:flex;flex-direction:column;gap:4px;"><span style="font-size:11px;font-weight:600;color:var(--ink-700);">Fecha emisión</span>
                <input type="date" name="issue_date" required value="<?= htmlspecialchars($header['issue_date']) ?>" style="height:36px;padding:0 10px;border:1px solid var(--ink-200);border-radius:6px;"></label>
              <label style="display:flex;flex-direction:column;gap:4px;"><span style="font-size:11px;font-weight:600;color:var(--ink-700);">Vencimiento (financiación)</span>
                <input type="date" name="due_date" value="<?= htmlspecialchars((string)$header['due_date']) ?>" style="height:36px;padding:0 10px;border:1px solid var(--ink-200);border-radius:6px;"></label>
              <label style="display:flex;flex-direction:column;gap:4px;"><span style="font-size:11px;font-weight:600;color:var(--ink-700);">% Financiación</span>
                <input type="number" step="0.001" min="0" name="financing_pct" value="<?= htmlspecialchars((string)$header['financing_pct']) ?>" style="height:36px;padding:0 10px;border:1px solid var(--ink-200);border-radius:6px;font-family:var(--font-mono);"></label>
              <label style="display:flex;flex-direction:column;gap:4px;grid-column:1/-1;"><span style="font-size:11px;font-weight:600;color:var(--ink-700);">Notas / contenedor / puerto</span>
                <input type="text" name="notes" value="<?= htmlspecialchars((string)$header['notes'], ENT_QUOTES) ?>" style="height:36px;padding:0 10px;border:1px solid var(--ink-200);border-radius:6px;"></label>
            </div>
          </div>

          <div class="pir-card" style="overflow-x:auto;">
            <table class="pir">
              <thead>
                <tr>
                  <th style="width:90px;">SKU Yufun</th>
                  <th style="width:80px;">Ref</th>
                  <th style="width:60px;">Estado</th>
                  <th style="width:120px;">Código ERP</th>
                  <th>Nombre en tu catálogo</th>
                  <th style="width:60px;" class="pir-r">Cant.</th>
                  <th style="width:80px;" class="pir-r">RMB/pza</th>
                  <th style="width:90px;" class="pir-r">Monto RMB</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $idx => $it): $b = $badge[$it['match']] ?? ['?','#888']; ?>
                <tr>
                  <td style="font-family:var(--font-mono);"><?= htmlspecialchars($it['sku']) ?>
                    <input type="hidden" name="sku[]" value="<?= htmlspecialchars($it['sku'], ENT_QUOTES) ?>">
                    <input type="hidden" name="ref[]" value="<?= htmlspecialchars($it['ref'], ENT_QUOTES) ?>">
                    <input type="hidden" name="desc[]" value="<?= htmlspecialchars($it['desc'], ENT_QUOTES) ?>">
                    <input type="hidden" name="qty[]" value="<?= htmlspecialchars((string)$it['qty']) ?>">
                    <input type="hidden" name="rmb_pc[]" value="<?= htmlspecialchars((string)$it['rmb_pc']) ?>">
                    <input type="hidden" name="cbm[]" value="<?= htmlspecialchars((string)$it['cbm']) ?>">
                  </td>
                  <td style="font-family:var(--font-mono);color:#575964;"><?= htmlspecialchars($it['ref']) ?></td>
                  <td><span class="pir-badge" style="background:<?= $b[1] ?>;"><?= $b[0] ?></span></td>
                  <td style="position:relative; min-width:200px;">
                    <input type="text" class="pir-search" data-i="<?= (int)$idx ?>" placeholder="🔍 buscar en tu catálogo…" autocomplete="off" style="margin-bottom:4px;">
                    <div class="pir-res" data-i="<?= (int)$idx ?>" style="display:none; position:absolute; z-index:50; left:0; right:0; background:#fff; border:1px solid var(--ink-200); border-radius:6px; max-height:220px; overflow:auto; box-shadow:0 8px 24px rgba(15,15,20,.14);"></div>
                    <input type="text" name="product_id[]" data-pid="<?= (int)$idx ?>" value="<?= htmlspecialchars($it['product_id'], ENT_QUOTES) ?>" style="font-family:var(--font-mono);" title="Código en tu ERP (o escribe uno nuevo)">
                  </td>
                  <td><input type="text" name="erp_name[]" data-nm="<?= (int)$idx ?>" value="<?= htmlspecialchars($it['erp_name'] ?? $it['desc'], ENT_QUOTES) ?>"></td>
                  <td class="pir-r"><?= (int)$it['qty'] ?></td>
                  <td class="pir-r"><?= $fmt($it['rmb_pc']) ?></td>
                  <td class="pir-r"><?= $fmt($it['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="pir-sub" style="margin-top:10px;">
            <strong>Estado:</strong>
            <span class="pir-badge" style="background:#0EA572;">Mapeado</span> ya vinculado ·
            <span class="pir-badge" style="background:#1B365D;">En catálogo</span> existe con ese código ·
            <span class="pir-badge" style="background:#E1306C;">Nuevo</span> se creará. Edita código/nombre si quieres otro.
          </div>

          <div class="pir-actions">
            <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/import?provider_id=<?= (int)$providerId ?>" class="pir-btn pir-btn-secondary">← Volver</a>
            <button type="submit" class="pir-btn pir-btn-primary">Crear factura en tránsito</button>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
<style>.pir-opt:hover{background:var(--ink-25,#F6F4F0);} .pir-search{width:100%;height:30px;padding:0 8px;border:1px solid var(--ink-200);border-radius:5px;font-size:12px;}</style>
<script>
// Buscador de catálogo por línea (fuera del mount Vue). Vincula el SKU de
// Yufun a TU producto: llena Código ERP + Nombre y guarda el mapeo al confirmar.
(function () {
  var BASE = '<?= base_url() ?>';
  var timer = null;
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t.classList || !t.classList.contains('pir-search')) return;
    var i = t.getAttribute('data-i');
    var box = document.querySelector('.pir-res[data-i="' + i + '"]');
    if (!box) return;
    var q = t.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { box.style.display = 'none'; return; }
    timer = setTimeout(function () {
      fetch(BASE + 'sisvent/purchases/provider_invoices/search_products?q=' + encodeURIComponent(q), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.text(); })
        .then(function (txt) { var d; try { d = JSON.parse(txt); } catch (e) { d = { rows: [] }; }
          var rows = (d && d.rows) || [];
          if (!rows.length) { box.innerHTML = '<div style="padding:8px;color:#999;font-size:12px;">Sin resultados</div>'; box.style.display = 'block'; return; }
          box.innerHTML = rows.map(function (p) {
            return '<div class="pir-opt" data-i="' + i + '" data-id="' + esc(p.idProduct) + '" data-nm="' + esc(p.description || '') + '" style="padding:7px 9px;cursor:pointer;border-bottom:1px solid #eee;font-size:12px;"><b style="font-family:monospace;">' + esc(p.idProduct) + '</b> · ' + esc(p.description || '') + '</div>';
          }).join('');
          box.style.display = 'block';
        }).catch(function(){});
    }, 220);
  });
  document.addEventListener('click', function (e) {
    var opt = e.target.closest && e.target.closest('.pir-opt');
    if (opt) {
      var i = opt.getAttribute('data-i');
      var pid = document.querySelector('input[data-pid="' + i + '"]');
      var nm  = document.querySelector('input[data-nm="' + i + '"]');
      if (pid) pid.value = opt.getAttribute('data-id');
      if (nm)  nm.value  = opt.getAttribute('data-nm');
      var box = document.querySelector('.pir-res[data-i="' + i + '"]'); if (box) box.style.display = 'none';
      var srch = document.querySelector('.pir-search[data-i="' + i + '"]'); if (srch) { srch.value = '✓ ' + opt.getAttribute('data-id'); srch.style.color = '#0EA572'; }
      return;
    }
    if (!e.target.classList || !e.target.classList.contains('pir-search')) {
      document.querySelectorAll('.pir-res').forEach(function (b) { if (!b.contains(e.target)) b.style.display = 'none'; });
    }
  });
})();
</script>
</body>
</html>
