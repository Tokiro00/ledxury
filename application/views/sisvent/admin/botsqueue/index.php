<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Cola de Bots - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <style>
        .tab-active { border-bottom: 2px solid #2E7D91; color: #2E7D91; font-weight: 600; }
        .status-pill { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-failed { background: #FEE2E2; color: #991B1B; }
        .status-completed { background: #D1FAE5; color: #065F46; }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-processing { background: #DBEAFE; color: #1E40AF; }
        .mono { font-family: 'Courier New', monospace; font-size: 12px; }
    </style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php
    $ud = $this->session->userdata('user_data');
    $role = $ud['role'] ?? null;
    $this->load->view('sisvent/layouts/sidebar', ['thisFile' => 'sisvent/admin/botsqueue/index', 'role' => $role]);
    ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Cola de ventas del Bot</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Reintenta fallidos y administra alias de productos</p>
                    </div>
                    <div class="flex items-center gap-3 mt-2 lg:mt-0">
                        <button id="btnRetryAll" class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-red-600 hover:bg-red-700">Reintentar todos los fallidos</button>
                        <button id="btnShowAliases" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">Alias de productos</button>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Fallidos</p>
                        <p class="text-2xl font-bold text-red-600 mt-1"><?= number_format($stats['failed'], 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Pendientes</p>
                        <p class="text-2xl font-bold text-yellow-600 mt-1"><?= number_format($stats['pending'] + $stats['processing'], 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Completados</p>
                        <p class="text-2xl font-bold text-green-600 mt-1"><?= number_format($stats['completed'], 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Alias registrados</p>
                        <p class="text-2xl font-bold text-gray-700 mt-1"><?= count($aliases) ?></p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white border rounded-t-lg px-4 flex items-center gap-1 text-sm">
                    <?php foreach (['failed' => 'Fallidos', 'completed' => 'Completados', 'all' => 'Todos'] as $k => $label): ?>
                        <a href="?status=<?= $k ?>" class="px-4 py-3 <?= $status === $k ? 'tab-active' : 'text-gray-500 hover:text-gray-700' ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                    <form method="GET" class="ml-auto py-2">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar en payload o error..." class="px-3 py-1.5 text-xs border rounded">
                    </form>
                </div>

                <!-- Lista -->
                <div class="bg-white border-l border-r border-b rounded-b-lg">
                    <?php if (empty($items)): ?>
                        <div class="p-8 text-center text-gray-400 text-sm">No hay items en este estado.</div>
                    <?php else: ?>
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-3 py-2 text-left">ID</th>
                                    <th class="px-3 py-2 text-left">Fecha</th>
                                    <th class="px-3 py-2 text-left">Vendedor</th>
                                    <th class="px-3 py-2 text-left">Estado</th>
                                    <th class="px-3 py-2 text-left">Intentos</th>
                                    <th class="px-3 py-2 text-left">Error</th>
                                    <th class="px-3 py-2 text-left">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it):
                                    $pl = json_decode($it->payload, true);
                                    $nombre = $pl['nombre'] ?? '';
                                    $cel = $pl['celular'] ?? '';
                                ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-3 py-2 mono text-gray-500">#<?= $it->id ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?= date('d/m H:i', strtotime($it->created_at)) ?></td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium text-gray-700"><?= htmlspecialchars($nombre ?: '—') ?></div>
                                        <div class="text-gray-400 mono"><?= htmlspecialchars($cel) ?></div>
                                    </td>
                                    <td class="px-3 py-2"><span class="status-pill status-<?= $it->status ?>"><?= $it->status ?></span></td>
                                    <td class="px-3 py-2 text-gray-500"><?= (int)$it->attempts ?></td>
                                    <td class="px-3 py-2 text-red-600 max-w-xs truncate" title="<?= htmlspecialchars($it->error_message ?? '') ?>"><?= htmlspecialchars($it->error_message ?? '—') ?></td>
                                    <td class="px-3 py-2">
                                        <button class="btn-view text-blue-600 hover:underline mr-2" data-id="<?= $it->id ?>">Ver</button>
                                        <?php if ($it->status === 'failed'): ?>
                                            <button class="btn-retry text-green-600 hover:underline" data-id="<?= $it->id ?>">Reintentar</button>
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

<!-- Modal: Ver payload + mapeo rápido de producto -->
<div id="modalView" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Detalle del item <span id="mvId"></span></h3>
            <button onclick="$('#modalView').addClass('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4">
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Error:</p>
                <p id="mvError" class="text-sm text-red-700 bg-red-50 rounded p-2 mono"></p>
            </div>
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Cliente:</p>
                <p id="mvClient" class="text-sm text-gray-700"></p>
            </div>
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Productos en el payload:</p>
                <div id="mvProducts" class="text-sm"></div>
            </div>
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Payload completo:</p>
                <pre id="mvPayload" class="bg-gray-50 rounded p-2 mono text-xs overflow-x-auto max-h-64"></pre>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t">
                <button id="mvRetry" class="px-4 py-2 text-sm text-white rounded bg-green-600 hover:bg-green-700">Reintentar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: mapear alias -->
<div id="modalAlias" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Mapear alias</h3>
            <button onclick="$('#modalAlias').addClass('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4">
            <p class="text-xs text-gray-500 mb-2">Texto del bot:</p>
            <input id="alRaw" type="text" class="w-full px-3 py-2 border rounded text-sm mb-3">
            <p class="text-xs text-gray-500 mb-2">Codigo real en la BD:</p>
            <input id="alCode" type="text" class="w-full px-3 py-2 border rounded text-sm mono uppercase mb-3" placeholder="Ej: 3LED-12V-A">
            <div class="flex justify-end gap-2">
                <button onclick="$('#modalAlias').addClass('hidden')" class="px-4 py-2 text-sm text-gray-600 rounded border">Cancelar</button>
                <button id="alSave" class="px-4 py-2 text-sm text-white rounded" style="background:#2E7D91;">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: administrar aliases -->
<div id="modalAliases" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Alias de productos</h3>
            <button onclick="$('#modalAliases').addClass('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto">
            <details class="mb-3">
                <summary class="cursor-pointer text-sm font-medium text-gray-700">Importar en lote</summary>
                <div class="mt-2">
                    <p class="text-xs text-gray-500 mb-1">Una línea por alias. Formato: <code class="mono bg-gray-100 px-1">texto del bot||CODIGO</code> (también acepta tab o coma final como separador)</p>
                    <textarea id="bulkCsv" rows="6" class="w-full px-3 py-2 border rounded text-xs mono" placeholder="aspiradora pequeña||TP-012&#10;candado con alarma||DISC-ALARM"></textarea>
                    <button id="bulkSave" class="mt-2 px-4 py-2 text-sm text-white rounded" style="background:#2E7D91;">Importar</button>
                </div>
            </details>
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Texto bot</th>
                        <th class="px-3 py-2 text-left">Código</th>
                        <th class="px-3 py-2 text-left">Hits</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody id="aliasBody">
                    <?php foreach ($aliases as $a): ?>
                    <tr class="border-t" data-id="<?= $a->id ?>">
                        <td class="px-3 py-2"><?= htmlspecialchars($a->alias_raw) ?></td>
                        <td class="px-3 py-2 mono"><?= htmlspecialchars($a->product_code) ?></td>
                        <td class="px-3 py-2 text-gray-500"><?= (int)$a->hits ?></td>
                        <td class="px-3 py-2 text-right">
                            <button class="btn-del-alias text-red-500 hover:underline" data-id="<?= $a->id ?>">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
(function(){
    const BASE = '<?= base_url('sisvent/admin/botsqueue') ?>';

    function showToast(msg, ok){
        const t = $('<div>').css({
            position: 'fixed', bottom: '20px', right: '20px',
            padding: '10px 16px', borderRadius: '6px', zIndex: 9999,
            color: 'white', fontSize: '13px',
            background: ok ? '#10B981' : '#EF4444'
        }).text(msg).appendTo('body');
        setTimeout(() => t.fadeOut(300, () => t.remove()), 2500);
    }

    $(document).on('click', '.btn-view', function(){
        const id = $(this).data('id');
        $.getJSON(BASE + '/view_item?id=' + id, function(res){
            if (!res.ok) { showToast(res.error, false); return; }
            const it = res.item, pl = res.payload || {};
            $('#mvId').text('#' + it.id);
            $('#mvError').text(it.error_message || '(sin error)');
            const dir = [pl.direccion, pl.ciudad, pl.departamento].filter(Boolean).join(', ');
            $('#mvClient').text((pl.nombre || '') + ' · ' + (pl.documento || 'sin doc') + ' · ' + (pl.celular || '') + (dir ? ' · ' + dir : ''));
            const prods = (pl.productos || []).map(p => {
                const code = (p.codigo || '').toUpperCase();
                return '<div class="flex items-center justify-between py-1 border-b">' +
                    '<span class="mono">' + code + ' × ' + (p.cantidad || 1) + ' @ $' + (p.precio || 0) + '</span>' +
                    '<button class="btn-map-alias text-xs text-blue-600 hover:underline" data-raw="' + code + '">Mapear alias</button>' +
                '</div>';
            }).join('') || '<p class="text-gray-400 italic">Sin productos</p>';
            $('#mvProducts').html(prods);
            $('#mvPayload').text(JSON.stringify(pl, null, 2));
            $('#mvRetry').data('id', it.id).toggle(it.status === 'failed');
            $('#modalView').removeClass('hidden');
        });
    });

    $(document).on('click', '.btn-map-alias', function(){
        $('#alRaw').val($(this).data('raw'));
        $('#alCode').val('');
        $('#modalAlias').removeClass('hidden');
    });

    $(document).on('click', '#alSave', function(){
        const raw = $('#alRaw').val().trim();
        const code = $('#alCode').val().trim().toUpperCase();
        if (!raw || !code) { showToast('Faltan datos', false); return; }
        $.post(BASE + '/save_alias', { alias_raw: raw, product_code: code }, function(res){
            if (res.ok) {
                showToast(res.message, true);
                $('#modalAlias').addClass('hidden');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(res.error, false);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-retry, #mvRetry', function(){
        const id = $(this).data('id');
        if (!id) return;
        const $btn = $(this).prop('disabled', true).text('...');
        $.post(BASE + '/retry', { id: id }, function(res){
            if (res.ok) {
                showToast('Reprocesado, budget #' + res.budget_id, true);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.error, false);
                $btn.prop('disabled', false).text('Reintentar');
            }
        }, 'json');
    });

    $(document).on('click', '#btnRetryAll', function(){
        if (!confirm('Reintentar TODOS los items fallidos?')) return;
        const $btn = $(this).prop('disabled', true).text('Procesando...');
        $.post(BASE + '/retry_all', {}, function(res){
            showToast('Recuperados: ' + res.recovered + ' / ' + res.total, true);
            setTimeout(() => location.reload(), 1000);
        }, 'json').fail(() => $btn.prop('disabled', false).text('Reintentar todos los fallidos'));
    });

    $(document).on('click', '#btnShowAliases', function(){
        $('#modalAliases').removeClass('hidden');
    });

    $(document).on('click', '.btn-del-alias', function(){
        const id = $(this).data('id');
        if (!confirm('Eliminar alias?')) return;
        $.post(BASE + '/delete_alias', { id: id }, function(res){
            if (res.ok) $('tr[data-id="' + id + '"]').remove();
        }, 'json');
    });

    $(document).on('click', '#bulkSave', function(){
        const csv = $('#bulkCsv').val();
        if (!csv.trim()) return;
        const $btn = $(this).prop('disabled', true).text('...');
        $.post(BASE + '/bulk_import', { csv: csv }, function(res){
            if (res.ok) {
                showToast('Creados: ' + res.created + ', actualizados: ' + res.updated + ', omitidos: ' + (res.skipped || []).length, true);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.error, false);
                $btn.prop('disabled', false).text('Importar');
            }
        }, 'json');
    });

})();
</script>
</body>
</html>
