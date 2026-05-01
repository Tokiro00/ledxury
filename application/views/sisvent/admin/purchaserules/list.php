<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reglas de Compra Automática</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto">
            <div class="px-6 mx-auto grid">

                <div class="flex items-center justify-between mb-4 mt-2 flex-wrap gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Reglas de Compra Automática</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Cada regla genera órdenes de compra borrador a un proveedor según una frecuencia.
                            Cuando llega la hora, el cron crea la PO y la verás en
                            <a href="<?= base_url() ?>sisvent/store/reorder/orders" class="underline hover:text-mam-blue">Compras → Órdenes</a>.
                        </p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/purchaserules/add"
                       class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white bg-mam-blue-petroleo rounded-lg hover:opacity-90">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Nueva regla</span>
                    </a>
                </div>

                <?php if($this->session->flashdata('success')): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-emerald-600 rounded-lg shadow-md">
                        <p><?= htmlspecialchars($this->session->flashdata('success')) ?></p>
                    </div>
                <?php endif; ?>
                <?php if($this->session->flashdata('error')): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?= htmlspecialchars($this->session->flashdata('error')) ?></p>
                    </div>
                <?php endif; ?>

                <div class="w-full overflow-hidden rounded-lg shadow-xs">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full whitespace-no-wrap">
                            <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-3">Regla</th>
                                    <th class="px-3 py-3">Proveedor</th>
                                    <th class="px-3 py-3">Tienda</th>
                                    <th class="px-3 py-3">Frecuencia</th>
                                    <th class="px-3 py-3">Productos</th>
                                    <th class="px-3 py-3">Próx. ejecución</th>
                                    <th class="px-3 py-3 text-center">Estado</th>
                                    <th class="px-3 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y">
                                <?php if(empty($rules)): ?>
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 text-sm">
                                            No hay reglas configuradas.
                                            <a href="<?= base_url() ?>sisvent/admin/purchaserules/add" class="text-mam-blue-petroleo hover:underline">
                                                Crear la primera
                                            </a>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($rules as $r):
                                        $cfg = json_decode((string)$r->frequency_config, true) ?: [];
                                        $hour = isset($cfg['hour']) ? sprintf('%02d:00', $cfg['hour']) : '06:00';
                                        $freq_label = '-';
                                        if ($r->frequency_type === 'weekly') {
                                            $dows = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
                                            $dow = $cfg['day_of_week'] ?? 1;
                                            $freq_label = "Semanal · {$dows[$dow]} · $hour";
                                        } elseif ($r->frequency_type === 'monthly') {
                                            $dom = $cfg['day_of_month'] ?? 1;
                                            $freq_label = "Mensual · día $dom · $hour";
                                        } elseif ($r->frequency_type === 'custom') {
                                            $freq_label = 'Custom · ' . htmlspecialchars($cfg['cron'] ?? '-');
                                        }

                                        $filter_label = '-';
                                        if ($r->product_filter === 'all_sold')      $filter_label = "Vendidos últimos {$r->lookback_days}d";
                                        elseif ($r->product_filter === 'all_provider') $filter_label = 'Todos del proveedor';
                                        elseif ($r->product_filter === 'specific_list') {
                                            $skus = json_decode((string)$r->product_list, true) ?: [];
                                            $filter_label = 'Lista específica · ' . count($skus) . ' SKUs';
                                        }

                                        $next = '-';
                                        if ($r->next_run_at) {
                                            $dt = new DateTime($r->next_run_at, new DateTimeZone('UTC'));
                                            $dt->setTimezone(new DateTimeZone('America/Bogota'));
                                            $next = $dt->format('d M Y · H:i');
                                        }
                                    ?>
                                    <tr class="text-sm text-gray-700 hover:bg-gray-50">
                                        <td class="px-3 py-3 font-semibold">
                                            <?= htmlspecialchars($r->name) ?>
                                            <?php if($r->exclude_blocked): ?>
                                                <span class="ml-1 px-1 py-0.5 text-[10px] font-medium rounded bg-amber-100 text-amber-800">Excluye agotados</span>
                                            <?php endif; ?>
                                            <?php if(!empty($r->since_date)):
                                                $sd = new DateTime($r->since_date, new DateTimeZone('UTC'));
                                                $sd->setTimezone(new DateTimeZone('America/Bogota'));
                                            ?>
                                                <span class="ml-1 px-1 py-0.5 text-[10px] font-medium rounded bg-blue-100 text-blue-800"
                                                      title="Override one-shot: el próximo run sumará desde esta fecha en lugar de aplicar lookback_days. Después se nullea automáticamente.">
                                                    📌 Solo este run desde <?= $sd->format('d M Y H:i') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3"><?= htmlspecialchars($r->provider_name ?? '?') ?></td>
                                        <td class="px-3 py-3"><?= htmlspecialchars($r->store_name ?? '?') ?></td>
                                        <td class="px-3 py-3 text-xs"><?= htmlspecialchars($freq_label) ?></td>
                                        <td class="px-3 py-3 text-xs"><?= htmlspecialchars($filter_label) ?></td>
                                        <td class="px-3 py-3 text-xs whitespace-nowrap"><?= $next ?></td>
                                        <td class="px-3 py-3 text-center">
                                            <?php if($r->active): ?>
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">ACTIVA</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-200 text-gray-600">INACTIVA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button class="btn-run-now px-2 py-1 text-[11px] font-semibold text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
                                                        data-id="<?= (int)$r->id ?>"
                                                        title="Ejecutar ahora (genera la PO inmediatamente sin esperar al horario)">
                                                    ▶ Ejecutar
                                                </button>
                                                <button class="btn-toggle px-2 py-1 text-[11px] font-semibold rounded
                                                    <?= $r->active ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-emerald-500 text-white hover:bg-emerald-600' ?>"
                                                        data-id="<?= (int)$r->id ?>">
                                                    <?= $r->active ? 'Pausar' : 'Activar' ?>
                                                </button>
                                                <a href="<?= base_url() ?>sisvent/admin/purchaserules/edit/<?= (int)$r->id ?>"
                                                   class="text-mam-blue-petroleo hover:opacity-70" title="Editar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                                <button class="btn-delete text-red-500 hover:text-red-700"
                                                        data-id="<?= (int)$r->id ?>"
                                                        data-name="<?= htmlspecialchars($r->name, ENT_QUOTES) ?>"
                                                        title="Eliminar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
function csrfData(){
    return { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' };
}

$(document).on('click', '.btn-toggle', function(){
    var id = $(this).data('id');
    $.post('<?= base_url() ?>sisvent/admin/purchaserules/toggle/' + id, csrfData(), function(){
        location.reload();
    }).fail(function(){ alert('Error al cambiar estado'); });
});

$(document).on('click', '.btn-delete', function(){
    var id = $(this).data('id');
    var name = $(this).data('name');
    if (!confirm('¿Eliminar la regla "' + name + '"?\n\nNo se eliminarán las órdenes ya generadas.')) return;
    $.post('<?= base_url() ?>sisvent/admin/purchaserules/delete/' + id, csrfData(), function(){
        location.reload();
    }).fail(function(){ alert('Error al eliminar'); });
});

$(document).on('click', '.btn-run-now', function(){
    var $btn = $(this);
    var id = $btn.data('id');
    if (!confirm('¿Ejecutar la regla AHORA?\n\nEsto generará una orden borrador inmediatamente.')) return;
    var orig = $btn.html();
    $btn.prop('disabled', true).html('...');

    $.post('<?= base_url() ?>sisvent/admin/purchaserules/runNow/' + id, csrfData(), 'json')
        .done(function(res){
            if (res && res.ok) {
                var d = res.detail || {};
                if (d.purchase_id) {
                    alert('✓ Orden generada: id=' + d.purchase_id + '\n\nVer en Compras → Órdenes.');
                } else {
                    alert('La regla corrió pero no generó orden:\n' + (d.status || '-'));
                }
                location.reload();
            } else {
                alert('No se pudo ejecutar: ' + (res && res.error ? res.error : 'desconocido'));
                $btn.prop('disabled', false).html(orig);
            }
        })
        .fail(function(xhr){
            alert('Error de red (HTTP ' + xhr.status + ')');
            $btn.prop('disabled', false).html(orig);
        });
});
</script>
</body>
</html>
