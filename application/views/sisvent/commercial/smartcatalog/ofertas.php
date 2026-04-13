<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$role = $this->session->userdata('user_data')['role'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<title>MAM — Gestionar Ofertas</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.offer-row { transition: all 0.15s; }
.offer-row:hover { background: #F0F9FF; }
.override-row { background: #EFF6FF; }
.stat-card { border-radius: 12px; padding: 16px; border: 1px solid #E5E7EB; background: white; text-align: center; }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="container mx-auto px-6 py-6">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Gestionar Catalogo Digital</h2>
                        <p class="text-sm text-gray-500 mt-1">Controla productos en ofertas y remate. Los cambios se reflejan en la PWA de clientes.</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/commercial/smartcatalog" class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90" style="background:#FF6B00;">
                        ← Smart Catalog
                    </a>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="text-xs text-gray-500 font-semibold uppercase">En Ofertas</div>
                        <div class="text-2xl font-black mt-1" style="color:#7C3AED;"><?= count(array_filter($ofertas, function($p) { return ($p->final_tab ?? '') === 'ofertas' && (int)($p->total_stock ?? 0) >= 5; })) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="text-xs text-gray-500 font-semibold uppercase">En Remate</div>
                        <div class="text-2xl font-black text-red-600 mt-1"><?= count(array_filter($ofertas, function($p) { return ($p->final_tab ?? '') === 'remate' || (int)($p->total_stock ?? 0) < 5; })) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="text-xs text-gray-500 font-semibold uppercase">Con Override</div>
                        <div class="text-2xl font-black text-blue-600 mt-1"><?= count(array_filter($ofertas, function($p) { return !empty($p->override_id); })) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="text-xs text-gray-500 font-semibold uppercase">Excluidos</div>
                        <div class="text-2xl font-black text-gray-600 mt-1"><?= count($excluidos) ?></div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg p-4 border border-gray-200 mb-6">
                    <form class="flex flex-wrap items-center gap-3" method="GET">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por codigo o nombre..." class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                        <select name="f" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Todas las familias</option>
                            <?php foreach ($families as $f): ?>
                                <option value="<?= $f->idFamily ?>" <?= $familyId == $f->idFamily ? 'selected' : '' ?>><?= htmlspecialchars($f->name) ?> (<?= $f->total ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Filtrar</button>
                        <?php if ($search || $familyId): ?>
                            <a href="<?= base_url() ?>sisvent/commercial/smartcatalog/ofertas" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabla -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold text-gray-600">Producto</th>
                                <th class="text-center px-3 py-3 font-semibold text-gray-600 w-20">Stock</th>
                                <th class="text-center px-3 py-3 font-semibold text-gray-600 w-20">Dias</th>
                                <th class="text-right px-3 py-3 font-semibold text-gray-600 w-24">P.Lista</th>
                                <th class="text-right px-3 py-3 font-semibold text-gray-600 w-24">Costo</th>
                                <th class="text-center px-3 py-3 font-semibold text-gray-600 w-28">Tab</th>
                                <th class="text-right px-3 py-3 font-semibold text-gray-600 w-28">P.Auto</th>
                                <th class="text-right px-3 py-3 font-semibold text-gray-600 w-28">P.Manual</th>
                                <th class="text-center px-3 py-3 font-semibold text-gray-600 w-20">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ofertas as $p):
                            $hasOverride = !empty($p->override_id);
                            $rowClass = $hasOverride ? 'override-row' : '';
                            $isRemate = (int)$p->total_stock < 5;
                        ?>
                            <tr class="border-b offer-row <?= $rowClass ?>" id="row-<?= htmlspecialchars($p->idProduct) ?>">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <?php
                                          $pic = $p->picture_url ?: 'products/no_image.png';
                                          $imgPath = file_exists(FCPATH . 'uploads/' . $pic) ? base_url() . 'uploads/' . $pic : base_url() . 'public/dist/images/' . $pic;
                                        ?>
                                        <img src="<?= $imgPath ?>" alt="" class="w-10 h-10 rounded object-cover border" onerror="this.style.display='none'">
                                        <div class="min-w-0">
                                            <div class="font-bold text-gray-800 truncate"><?= htmlspecialchars($p->idProduct) ?></div>
                                            <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($p->description) ?></div>
                                            <span class="text-xs text-gray-400"><?= htmlspecialchars($p->familyName ?: '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center px-3 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $isRemate ? 'bg-red-100 text-red-700' : ($p->total_stock <= 20 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') ?>">
                                        <?= number_format($p->total_stock) ?>
                                    </span>
                                </td>
                                <td class="text-center px-3 py-3">
                                    <span class="font-bold <?= (int)$p->dias_sin_venta > 365 ? 'text-red-600' : ((int)$p->dias_sin_venta > 180 ? 'text-orange-500' : 'text-yellow-600') ?>">
                                        <?= (int)$p->dias_sin_venta ?>d
                                    </span>
                                </td>
                                <td class="text-right px-3 py-3 font-medium text-gray-700">$<?= number_format($p->price, 0, ',', '.') ?></td>
                                <td class="text-right px-3 py-3 text-gray-400">$<?= number_format($p->cost_cop, 0, ',', '.') ?></td>
                                <td class="text-center px-3 py-3">
                                    <select onchange="saveOverride('<?= htmlspecialchars($p->idProduct) ?>', {tab: this.value})" class="text-xs border rounded px-2 py-1 w-full <?= $hasOverride ? 'border-blue-400 bg-blue-50' : 'border-gray-300' ?>">
                                        <option value="ofertas" <?= ($p->final_tab ?? '') === 'ofertas' ? 'selected' : '' ?>>Ofertas</option>
                                        <option value="remate" <?= ($p->final_tab ?? '') === 'remate' ? 'selected' : '' ?>>Remate</option>
                                        <option value="hot" <?= ($p->final_tab ?? '') === 'hot' ? 'selected' : '' ?>>Hot</option>
                                        <option value="excluido" <?= ($p->override_tab ?? '') === 'excluido' ? 'selected' : '' ?>>Excluir</option>
                                    </select>
                                </td>
                                <td class="text-right px-3 py-3">
                                    <div class="text-xs text-gray-400 line-through">$<?= number_format($p->price, 0, ',', '.') ?></div>
                                    <div class="font-bold text-green-700">$<?= number_format($p->auto_price, 0, ',', '.') ?></div>
                                    <div class="text-xs text-red-500">-<?= $p->auto_discount ?>%</div>
                                </td>
                                <td class="text-right px-3 py-3">
                                    <input type="number" id="price-<?= htmlspecialchars($p->idProduct) ?>" value="<?= $p->price_override ?: '' ?>" placeholder="Auto"
                                        class="w-full text-right text-sm border rounded px-2 py-1 <?= $p->price_override ? 'border-blue-400 bg-blue-50 font-bold' : 'border-gray-300' ?>"
                                        onchange="saveOverride('<?= htmlspecialchars($p->idProduct) ?>', {price_override: this.value})">
                                </td>
                                <td class="text-center px-3 py-3">
                                    <?php if ($hasOverride): ?>
                                        <button onclick="resetOverride('<?= htmlspecialchars($p->idProduct) ?>')" class="text-xs text-red-500 hover:text-red-700 font-medium">Reset</button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Auto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($ofertas)): ?>
                        <div class="text-center py-12 text-gray-400">No se encontraron productos</div>
                    <?php endif; ?>
                </div>

                <!-- Excluidos -->
                <?php if (!empty($excluidos)): ?>
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-700 mb-3">Excluidos (<?= count($excluidos) ?>)</h3>
                    <div class="bg-white rounded-lg border border-gray-200">
                        <?php foreach ($excluidos as $e): ?>
                        <div class="flex items-center justify-between px-4 py-3 border-b hover:bg-gray-50">
                            <div>
                                <span class="font-bold text-gray-800"><?= htmlspecialchars($e->productId) ?></span>
                                <span class="text-gray-500 ml-2 text-sm"><?= htmlspecialchars($e->description) ?></span>
                            </div>
                            <button onclick="resetOverride('<?= htmlspecialchars($e->productId) ?>')" class="text-xs text-blue-500 hover:text-blue-700 font-medium px-3 py-1 border border-blue-200 rounded">Restaurar</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="text-center text-xs text-gray-400 mt-6">
                    Mostrando <?= count($ofertas) ?> productos. Los cambios se aplican inmediatamente en la PWA de clientes.
                </div>

            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    // Nothing needed on load
});

function saveOverride(productId, data) {
    var postData = { productId: productId };
    if (data.tab !== undefined) postData.tab = data.tab;
    if (data.price_override !== undefined) postData.price_override = data.price_override;

    // Keep existing price if only changing tab
    if (!data.price_override) {
        var priceInput = document.getElementById('price-' + productId);
        if (priceInput && priceInput.value) postData.price_override = priceInput.value;
    }

    $.post('<?= base_url() ?>sisvent/commercial/smartcatalog/saveOverride', postData, function(res) {
        try { res = typeof res === 'string' ? JSON.parse(res) : res; } catch(e) {}
        if (res && res.success) {
            var row = document.getElementById('row-' + productId);
            if (data.tab === 'excluido') {
                if (row) { row.style.opacity = '0.3'; row.style.pointerEvents = 'none'; }
                showMsg('Producto excluido');
            } else {
                if (row) { row.classList.add('override-row'); }
                showMsg('Guardado');
            }
        } else {
            showMsg('Error al guardar', true);
        }
    });
}

function resetOverride(productId) {
    if (!confirm('Volver a precio automatico para ' + productId + '?')) return;
    $.post('<?= base_url() ?>sisvent/commercial/smartcatalog/deleteOverride', { productId: productId }, function(res) {
        try { res = typeof res === 'string' ? JSON.parse(res) : res; } catch(e) {}
        if (res && res.success) location.reload();
    });
}

function showMsg(msg, isError) {
    var el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = 'position:fixed;top:20px;right:20px;padding:10px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;background:' + (isError ? '#ef4444' : '#10b981');
    document.body.appendChild(el);
    setTimeout(function() { el.remove(); }, 2000);
}
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
