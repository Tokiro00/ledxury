<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$currentMonth = (int) date('n');

function fmtM($v) {
    if ($v >= 1000000) return number_format($v/1000000, 0) . 'M';
    if ($v >= 1000) return number_format($v/1000, 0) . 'K';
    return number_format($v, 0);
}
?>
<!DOCTYPE html>
<html lang="es">
    <title>Metas de Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-black text-gray-800">Metas de Vendedores</h2>
                            <p class="text-xs text-gray-400 uppercase tracking-widest">Configura las metas mensuales de cada vendedor</p>
                        </div>
                        <div class="flex items-center gap-2 mt-2 lg:mt-0">
                            <a href="?year=<?= $year - 1 ?>" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-100">&larr; <?= $year - 1 ?></a>
                            <span class="px-4 py-1.5 text-sm font-bold rounded-lg text-white" style="background:#1B365D;"><?= $year ?></span>
                            <a href="?year=<?= $year + 1 ?>" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-100"><?= $year + 1 ?> &rarr;</a>
                            <a href="<?= base_url() ?>sisvent/admin/salesboard" class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-100">Panel Vendedores</a>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <form method="GET" class="flex flex-wrap items-end gap-3 mb-3">
                            <input type="hidden" name="year" value="<?= $year ?>">
                            <div>
                                <label class="text-xs text-gray-500">Bodega</label>
                                <select name="store" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all" <?= $storeFilter == 'all' ? 'selected' : '' ?>>Todas (MDE)</option>
                                    <?php foreach($tiendas as $t): ?>
                                    <option value="<?= $t->idStore ?>" <?= $storeFilter == $t->idStore ? 'selected' : '' ?>><?= $t->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">Filtrar</button>
                        </form>
                        <form id="form-bulk" class="flex flex-wrap items-end gap-3 pt-3 border-t">
                            <div>
                                <label class="text-xs text-gray-500">Meta mensual para todos (en millones)</label>
                                <div class="flex items-center gap-1">
                                    <input type="number" id="bulk-value" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5 w-24" placeholder="Ej: 30" value="30">
                                    <span class="text-sm text-gray-500 font-bold">M</span>
                                </div>
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg bg-orange-500 hover:bg-orange-600">Aplicar del mes actual en adelante</button>
                            <span class="text-xs text-gray-400">Aplica desde <?= $months[$currentMonth - 1] ?> hasta Dic — no toca meses pasados</span>
                        </form>
                    </div>

                    <!-- Tabla de metas -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-2 py-2.5 font-semibold text-left sticky left-0 z-10" style="background:#1B365D; min-width:150px;">Vendedor</th>
                                        <?php foreach($months as $i => $m): $mn = $i + 1; ?>
                                        <th class="px-1 py-2.5 font-semibold text-center <?= $mn == $currentMonth ? 'bg-yellow-600' : '' ?>" style="min-width:90px;">
                                            <?= $m ?>
                                        </th>
                                        <?php endforeach; ?>
                                        <th class="px-2 py-2.5 font-semibold text-center" style="min-width:80px;">Total Año</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $idx = 0; foreach($vendedores as $v): $idx++;
                                        $g = isset($goals[$v->idUser]) ? $goals[$v->idUser] : null;
                                        $totalAnual = 0;
                                    ?>
                                    <tr class="border-t <?= $idx % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50" data-vendor="<?= $v->idUser ?>">
                                        <td class="px-2 py-1.5 font-medium text-gray-800 sticky left-0 z-10 <?= $idx % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>"><?= $v->name ?></td>
                                        <?php for($m = 1; $m <= 12; $m++):
                                            $meta = $g ? (int)$g->{'m'.$m} : 0;
                                            $real = isset($ventasReales[$v->idUser][$m]) ? $ventasReales[$v->idUser][$m] : 0;
                                            $pct = $meta > 0 ? round(($real / $meta) * 100) : 0;
                                            $totalAnual += $meta;
                                            $bgColor = '';
                                            if ($m < $currentMonth && $year == date('Y')) {
                                                $bgColor = $pct >= 100 ? 'background:#d1fae5;' : ($pct >= 60 ? 'background:#fef3c7;' : 'background:#fee2e2;');
                                            }
                                        ?>
                                        <td class="px-1 py-1 text-center <?= $m == $currentMonth && $year == date('Y') ? 'border-2 border-yellow-400' : '' ?>" style="<?= $bgColor ?>">
                                            <div class="flex items-center justify-center">
                                                <input type="number" class="meta-input text-center text-xs border border-gray-200 rounded px-1 py-0.5"
                                                       data-vendor="<?= $v->idUser ?>" data-month="<?= $m ?>"
                                                       value="<?= $meta > 0 ? round($meta / 1000000) : 0 ?>" style="width:45px; <?= $bgColor ?>">
                                                <span class="text-xxs text-gray-400 ml-0.5">M</span>
                                            </div>
                                            <?php if($m <= $currentMonth && $year == date('Y') && $real > 0): ?>
                                            <div class="text-xxs mt-0.5 <?= $pct >= 100 ? 'text-green-600' : ($pct >= 60 ? 'text-yellow-600' : 'text-red-600') ?> font-bold"><?= fmtM($real) ?> (<?= $pct ?>%)</div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endfor; ?>
                                        <td class="px-2 py-1 text-center">
                                            <div class="font-bold text-gray-600 text-xs">$<?= fmtM($totalAnual) ?></div>
                                            <button class="btn-apply-rest text-xxs text-blue-600 underline" data-vendor="<?= $v->idUser ?>">Resto año</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <button id="btn-save-all" class="px-6 py-2 text-sm font-bold text-white rounded-lg" style="background:#1B365D;">Guardar Cambios</button>
                    </div>

                    <div class="mt-2 text-xs text-gray-400">
                        Meses pasados muestran ventas reales con semaforo: <span class="text-green-600 font-bold">verde</span> = cumplio, <span class="text-yellow-600 font-bold">amarillo</span> = 60%+, <span class="text-red-600 font-bold">rojo</span> = menos del 60%
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    // Aplicar valor del mes actual al resto del año para un vendedor
    $(document).on('click', '.btn-apply-rest', function(){
        var vid = $(this).data('vendor');
        var currentMonth = <?= $currentMonth ?>;
        var currentVal = $('input.meta-input[data-vendor="'+vid+'"][data-month="'+currentMonth+'"]').val();
        if (!currentVal || currentVal == '0') { alert('Pon primero la meta del mes actual'); return; }
        for (var m = currentMonth; m <= 12; m++) {
            $('input.meta-input[data-vendor="'+vid+'"][data-month="'+m+'"]').val(currentVal);
        }
    });

    // Guardar todas las metas (convertir millones a valor real)
    $(document).on('click', '#btn-save-all', function(){
        var btn = $(this);
        btn.prop('disabled', true).text('Guardando...');
        var vendors = {};
        $('.meta-input').each(function(){
            var vid = $(this).data('vendor');
            var m = $(this).data('month');
            if (!vendors[vid]) vendors[vid] = {};
            vendors[vid]['m'+m] = (parseInt($(this).val()) || 0) * 1000000;
        });

        var promises = [];
        $.each(vendors, function(vid, metas){
            var d = $.extend({ userId: vid, year: <?= $year ?> }, metas);
            d['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
            promises.push($.post('<?= base_url() ?>sisvent/admin/salesboard/saveMeta', d));
        });

        $.when.apply($, promises).done(function(){
            btn.prop('disabled', false).text('Guardar Cambios');
            alert('Metas guardadas correctamente');
            location.reload();
        }).fail(function(){
            btn.prop('disabled', false).text('Guardar Cambios');
            alert('Error al guardar');
        });
    });

    // Bulk apply
    $(document).on('submit', '#form-bulk', function(e){
        e.preventDefault();
        var valM = parseInt($('#bulk-value').val()) || 0;
        var val = valM * 1000000;
        if (!confirm('Aplicar $' + valM + 'M a TODOS los vendedores desde <?= $months[$currentMonth - 1] ?> hasta Dic <?= $year ?>?')) return;
        var d = { year: <?= $year ?>, value: val, fromMonth: <?= $currentMonth ?> };
        d['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
        $.post('<?= base_url() ?>sisvent/admin/salesboard/bulkMeta', d, function(r){
            if (r.success) { alert(r.count + ' vendedores actualizados'); location.reload(); }
        }, 'json');
    });
    </script>
</body>
</html>
