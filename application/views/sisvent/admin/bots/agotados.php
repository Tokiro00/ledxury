<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Productos Agotados - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/bots/agotados', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Productos Agotados</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Gestiona los productos sin existencia para que el bot no los venda</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-sm font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg"><?= $total ?> agotados</span>
                        <a href="<?= base_url() ?>sisvent/admin/bots" class="text-xs text-blue-600 hover:underline">&larr; Bots</a>
                    </div>
                </div>

                <?php if ($this->session->flashdata('agotados_error')): ?>
                <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg"><?= $this->session->flashdata('agotados_error') ?></div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('agotados_success')): ?>
                <div class="p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg"><?= $this->session->flashdata('agotados_success') ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <!-- Upload Panel -->
                    <div class="lg:col-span-1 space-y-4">
                        <!-- Upload Excel -->
                        <div class="bg-white rounded-lg border p-5">
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Subir archivo de bodega</h3>
                            <form action="<?= base_url() ?>sisvent/admin/bots/uploadAgotados" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="file" name="agotados_file" accept=".xlsx,.xls,.csv" required
                                    class="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 mb-3">
                                <label class="flex items-center text-xs text-gray-500 mb-3 cursor-pointer">
                                    <input type="checkbox" name="replace" value="1" class="mr-2">
                                    Reemplazar lista actual (borra anteriores)
                                </label>
                                <button type="submit" class="w-full py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600">
                                    Subir Agotados
                                </button>
                            </form>
                            <p class="text-xs text-gray-300 mt-2">Excel con columna A = Referencia (6LED-12V, 3LED-12V, etc.) y columnas B-F = Colores agotados</p>
                        </div>

                        <!-- Manual Add -->
                        <div class="bg-white rounded-lg border p-5">
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Agregar manualmente</h3>
                            <div class="flex gap-2">
                                <input type="text" id="manualCode" placeholder="Código (ej: 6LED-12V-E)" class="flex-1 px-3 py-2 text-sm border rounded-lg">
                                <button onclick="addManual()" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600">+</button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white rounded-lg border p-5">
                            <button onclick="clearAll()" class="w-full py-2 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
                                Limpiar todos los agotados
                            </button>
                        </div>
                    </div>

                    <!-- Current List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg border overflow-hidden">
                            <div class="px-4 py-3 border-b bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-600">Productos actualmente agotados (<?= $total ?>)</h3>
                            </div>

                            <?php if (empty($grouped)): ?>
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p>No hay productos agotados</p>
                                <p class="text-xs mt-1">Sube un archivo de bodega o agrega manualmente</p>
                            </div>
                            <?php else: ?>
                            <div class="divide-y">
                                <?php foreach ($grouped as $ref => $items): ?>
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-bold text-gray-700"><?= htmlspecialchars($ref) ?></h4>
                                        <span class="text-xs text-gray-400"><?= count($items) ?> colores</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($items as $item): ?>
                                        <div class="inline-flex items-center px-3 py-1.5 bg-red-50 border border-red-200 rounded-lg text-xs" id="blocked_<?= $item->id ?>">
                                            <span class="font-bold text-red-700 mr-1"><?= $item->product_code ?></span>
                                            <?php if ($item->color): ?>
                                            <span class="text-red-500">(<?= htmlspecialchars($item->color) ?>)</span>
                                            <?php endif; ?>
                                            <button onclick="removeOne(<?= $item->id ?>)" class="ml-2 text-red-400 hover:text-red-700" title="Quitar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
var BASE = '<?= base_url() ?>';
var CSRF = { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' };

function removeOne(id) {
    if (!confirm('¿Quitar este producto de agotados?')) return;
    $.post(BASE + 'sisvent/admin/bots/removeAgotado', Object.assign({id: id}, CSRF), function(r) {
        if (r.success) { $('#blocked_' + id).fadeOut(300, function(){ $(this).remove(); }); }
    }, 'json');
}

function clearAll() {
    if (!confirm('¿Limpiar TODOS los productos agotados? El bot podrá vender todos los productos.')) return;
    $.post(BASE + 'sisvent/admin/bots/clearAgotados', CSRF, function(r) {
        if (r.success) location.reload();
    }, 'json');
}

function addManual() {
    var code = $('#manualCode').val().trim();
    if (!code) return;
    $.post(BASE + 'sisvent/admin/bots/addAgotado', Object.assign({code: code}, CSRF), function(r) {
        if (r.success) { location.reload(); }
        else { alert(r.error || 'Error'); }
    }, 'json');
}

$('#manualCode').on('keydown', function(e) { if (e.key === 'Enter') addManual(); });
</script>
</body>
</html>
