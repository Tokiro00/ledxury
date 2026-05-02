<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Productos Agotados - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <style>
        .prod-card { transition: all .15s ease; }
        .prod-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .prod-card.is-blocked { background: #fef2f2; border-color: #fecaca; }
        .prod-card.is-blocked .prod-img { opacity: .55; filter: grayscale(.4); }
        .prod-img-wrap { position: relative; padding-top: 100%; background: #f8fafc; border-radius: 6px; overflow: hidden; }
        .prod-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; padding: 6px; }
        .stamp-agotado { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-12deg); background: rgba(220,38,38,.92); color: #fff; font-weight: 800; font-size: 13px; padding: 4px 14px; border-radius: 4px; letter-spacing: 1px; pointer-events: none; }
        .family-chip { transition: all .15s; }
        .family-chip.active { background: #1f2937; color: #fff; border-color: #1f2937; }
        .family-chip:not(.active):hover { background: #f3f4f6; }
    </style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/bots/agotados', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-2xl mx-auto">

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Catálogo y Agotados</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Marca cada producto como agotado o disponible. El bot consulta esta lista antes de cotizar.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-sm font-bold text-gray-700 bg-gray-100 border border-gray-200 rounded-lg"><?= $catalog_count ?> productos</span>
                        <span class="px-3 py-1 text-sm font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg" id="blockedCounter"><?= $blocked_count ?> agotados</span>
                        <a href="<?= base_url() ?>sisvent/admin/bots" class="text-xs text-blue-600 hover:underline">&larr; Bots</a>
                    </div>
                </div>

                <?php if ($this->session->flashdata('agotados_error')): ?>
                <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg"><?= $this->session->flashdata('agotados_error') ?></div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('agotados_success')): ?>
                <div class="p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg"><?= $this->session->flashdata('agotados_success') ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

                    <!-- Sidebar acciones -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white rounded-lg border p-5">
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Buscar</h3>
                            <input type="text" id="searchInput" placeholder="Código o descripción"
                                class="w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:border-blue-400">
                            <div class="mt-3 flex gap-2 text-xs">
                                <button id="filterAll" onclick="setFilter('all')" class="flex-1 py-1.5 px-2 border rounded font-semibold bg-blue-50 border-blue-200 text-blue-700">Todos</button>
                                <button id="filterAvail" onclick="setFilter('avail')" class="flex-1 py-1.5 px-2 border rounded font-semibold">Disponibles</button>
                                <button id="filterBlock" onclick="setFilter('block')" class="flex-1 py-1.5 px-2 border rounded font-semibold">Agotados</button>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg border p-5">
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Agregar producto</h3>
                            <div class="flex gap-2">
                                <input type="text" id="manualCode" placeholder="Ej: 6LED-12V-E" class="flex-1 px-3 py-2 text-sm border rounded-lg uppercase">
                                <button onclick="addManual()" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600">+</button>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2">Lo marca como agotado. Si no existe en productos, se rechaza.</p>
                        </div>

                        <div class="bg-white rounded-lg border p-5">
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Subir Excel bodega</h3>
                            <form action="<?= base_url() ?>sisvent/admin/bots/uploadAgotados" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="file" name="agotados_file" accept=".xlsx,.xls,.csv" required
                                    class="w-full px-3 py-2 text-xs border rounded-lg bg-gray-50 mb-3">
                                <label class="flex items-center text-xs text-gray-500 mb-3 cursor-pointer">
                                    <input type="checkbox" name="replace" value="1" class="mr-2">
                                    Reemplazar lista actual
                                </label>
                                <button type="submit" class="w-full py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                                    Subir
                                </button>
                            </form>
                        </div>

                        <div class="bg-white rounded-lg border p-5">
                            <button onclick="clearAll()" class="w-full py-2 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
                                Limpiar todos los agotados
                            </button>
                        </div>
                    </div>

                    <!-- Catálogo -->
                    <div class="lg:col-span-3">
                        <!-- Chips familias -->
                        <div class="mb-4 flex flex-wrap gap-2">
                            <button class="family-chip active px-4 py-1.5 text-xs font-semibold border border-gray-300 rounded-full" data-family="all" onclick="setFamily(this, 'all')">Todas</button>
                            <?php foreach ($catalog as $family => $items): ?>
                            <button class="family-chip px-4 py-1.5 text-xs font-semibold border border-gray-300 rounded-full" data-family="<?= htmlspecialchars($family) ?>" onclick="setFamily(this, '<?= htmlspecialchars($family) ?>')">
                                <?= htmlspecialchars($family) ?> <span class="text-gray-400">(<?= count($items) ?>)</span>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <?php if (empty($catalog)): ?>
                        <div class="bg-white rounded-lg border p-12 text-center text-gray-400">
                            <p>No hay productos LED en el catálogo</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($catalog as $family => $items): ?>
                        <div class="family-block mb-6" data-family="<?= htmlspecialchars($family) ?>">
                            <div class="flex items-center justify-between mb-3 px-1">
                                <h3 class="text-base font-bold text-gray-700"><?= htmlspecialchars($family) ?></h3>
                                <span class="text-xs text-gray-400"><?= count($items) ?> variantes</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
                                <?php foreach ($items as $p): ?>
                                <div class="prod-card bg-white border border-gray-200 rounded-lg p-3 <?= $p->is_blocked ? 'is-blocked' : '' ?>"
                                     id="card_<?= htmlspecialchars($p->idProduct) ?>"
                                     data-code="<?= htmlspecialchars(strtolower($p->idProduct)) ?>"
                                     data-desc="<?= htmlspecialchars(strtolower($p->description)) ?>"
                                     data-blocked="<?= $p->is_blocked ? '1' : '0' ?>">
                                    <div class="prod-img-wrap mb-2">
                                        <img class="prod-img" src="<?= base_url() ?>public/images/products/<?= rawurlencode($p->idProduct) ?>.png"
                                             onerror="this.onerror=null; this.src='<?= base_url() ?>public/dist/images/products/no_image.png';"
                                             alt="<?= htmlspecialchars($p->idProduct) ?>">
                                        <span class="stamp-agotado" style="<?= $p->is_blocked ? '' : 'display:none' ?>">AGOTADO</span>
                                    </div>
                                    <div class="text-xs font-bold text-gray-800 truncate" title="<?= htmlspecialchars($p->idProduct) ?>"><?= htmlspecialchars($p->idProduct) ?></div>
                                    <div class="text-[11px] text-gray-500 truncate" title="<?= htmlspecialchars($p->color) ?>"><?= htmlspecialchars($p->color) ?></div>
                                    <button onclick="toggleBlock('<?= htmlspecialchars($p->idProduct, ENT_QUOTES) ?>', this)"
                                            class="toggle-btn mt-2 w-full py-1.5 text-[11px] font-bold rounded
                                                   <?= $p->is_blocked ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-emerald-500 text-white hover:bg-emerald-600' ?>">
                                        <?= $p->is_blocked ? '🔴 Agotado' : '🟢 Disponible' ?>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
var BASE = '<?= base_url() ?>';
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
var currentFilter = 'all';
var currentFamily = 'all';

function csrfData(extra) {
    var d = extra || {};
    d[CSRF_NAME] = CSRF_HASH;
    return d;
}

function refreshCsrf(newHash) {
    if (newHash) CSRF_HASH = newHash;
}

function setFilter(f) {
    currentFilter = f;
    ['filterAll','filterAvail','filterBlock'].forEach(function(id){
        var el = document.getElementById(id);
        el.classList.remove('bg-blue-50','border-blue-200','text-blue-700');
    });
    var map = {all:'filterAll', avail:'filterAvail', block:'filterBlock'};
    var active = document.getElementById(map[f]);
    active.classList.add('bg-blue-50','border-blue-200','text-blue-700');
    applyFilters();
}

function setFamily(btn, fam) {
    currentFamily = fam;
    document.querySelectorAll('.family-chip').forEach(function(c){ c.classList.remove('active'); });
    btn.classList.add('active');
    applyFilters();
}

function applyFilters() {
    var q = (document.getElementById('searchInput').value || '').trim().toLowerCase();

    document.querySelectorAll('.family-block').forEach(function(block){
        var fam = block.getAttribute('data-family');
        var familyVisible = (currentFamily === 'all' || currentFamily === fam);
        var anyCard = false;

        block.querySelectorAll('.prod-card').forEach(function(card){
            var code = card.getAttribute('data-code');
            var desc = card.getAttribute('data-desc');
            var blocked = card.getAttribute('data-blocked') === '1';

            var matchesQ = !q || code.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
            var matchesS = currentFilter === 'all' || (currentFilter === 'block' && blocked) || (currentFilter === 'avail' && !blocked);

            var show = familyVisible && matchesQ && matchesS;
            card.style.display = show ? '' : 'none';
            if (show) anyCard = true;
        });

        block.style.display = anyCard ? '' : 'none';
    });
}

function toggleBlock(code, btn) {
    var card = document.getElementById('card_' + code);
    if (!card) return;
    var wasBlocked = card.getAttribute('data-blocked') === '1';
    btn.disabled = true;
    var origText = btn.textContent;
    btn.textContent = '...';

    var url = BASE + 'sisvent/admin/bots/' + (wasBlocked ? 'removeAgotadoByCode' : 'addAgotado');

    $.post(url, csrfData({code: code}), function(r){
        if (r.success) {
            var nowBlocked = !wasBlocked;
            card.setAttribute('data-blocked', nowBlocked ? '1' : '0');
            card.classList.toggle('is-blocked', nowBlocked);
            var stamp = card.querySelector('.stamp-agotado');
            if (stamp) stamp.style.display = nowBlocked ? '' : 'none';
            btn.className = 'toggle-btn mt-2 w-full py-1.5 text-[11px] font-bold rounded ' +
                (nowBlocked ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-emerald-500 text-white hover:bg-emerald-600');
            btn.textContent = nowBlocked ? '🔴 Agotado' : '🟢 Disponible';
            updateCounter(nowBlocked ? 1 : -1);
            applyFilters();
        } else {
            alert(r.error || 'Error');
            btn.textContent = origText;
        }
        if (r.csrf_hash) refreshCsrf(r.csrf_hash);
    }, 'json').fail(function(){
        alert('Error de conexión');
        btn.textContent = origText;
    }).always(function(){ btn.disabled = false; });
}

function updateCounter(delta) {
    var el = document.getElementById('blockedCounter');
    var n = parseInt(el.textContent.match(/\d+/)[0], 10) + delta;
    el.textContent = n + ' agotados';
}

function addManual() {
    var code = document.getElementById('manualCode').value.trim().toUpperCase();
    if (!code) return;
    $.post(BASE + 'sisvent/admin/bots/addAgotado', csrfData({code: code}), function(r){
        if (r.success) location.reload();
        else alert(r.error || 'Error');
    }, 'json');
}

function clearAll() {
    if (!confirm('¿Limpiar TODOS los agotados? El bot podrá vender todo.')) return;
    $.post(BASE + 'sisvent/admin/bots/clearAgotados', csrfData(), function(r){
        if (r.success) location.reload();
    }, 'json');
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('manualCode').addEventListener('keydown', function(e){ if (e.key === 'Enter') addManual(); });
</script>
</body>
</html>
