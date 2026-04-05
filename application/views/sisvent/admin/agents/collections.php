<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$userName = $this->session->userdata('user_data')['name'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Agente de Cobros - MAM</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="container px-6 mx-auto py-6">

                    <!-- Header -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #2E7D91, #1B365D);">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Agente de Cobros Automatico</h2>
                            <p class="text-sm text-gray-500">Analiza la cartera vencida y genera mensajes de cobro personalizados con IA</p>
                        </div>
                        <div class="ml-auto flex items-center gap-3">
                            <select id="store-filter" class="text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-blue-500">
                                <option value="-1">Todas las bodegas</option>
                                <?php
                                $stores = $this->db->where('deleted', 0)->get('stores')->result();
                                foreach($stores as $s): ?>
                                <option value="<?= $s->idStore ?>"><?= $s->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="btn-analyze" class="px-6 py-3 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all" style="background: linear-gradient(135deg, #2E7D91, #1B365D);">
                                <span id="btn-text">Analizar Cartera</span>
                                <span id="btn-loading" class="hidden">
                                    <svg class="animate-spin inline w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Analizando...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards (hidden until analysis) -->
                    <div id="stats-cards" class="hidden grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Total Analizados</p>
                            <p id="stat-total" class="text-2xl font-bold text-gray-800 mt-1">0</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 p-5">
                            <p class="text-xs text-red-500 uppercase font-bold">Critica</p>
                            <p id="stat-critica" class="text-2xl font-bold text-red-600 mt-1">0</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border-l-4 border-orange-500 p-5">
                            <p class="text-xs text-orange-500 uppercase font-bold">Alta</p>
                            <p id="stat-alta" class="text-2xl font-bold text-orange-600 mt-1">0</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-5">
                            <p class="text-xs text-yellow-500 uppercase font-bold">Media</p>
                            <p id="stat-media" class="text-2xl font-bold text-yellow-600 mt-1">0</p>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div id="results-container" class="hidden">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-700">Resultados del Analisis</h3>
                                <div class="flex gap-2">
                                    <select id="filter-urgency" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                                        <option value="">Todas las urgencias</option>
                                        <option value="CRITICA">Critica</option>
                                        <option value="ALTA">Alta</option>
                                        <option value="MEDIA">Media</option>
                                        <option value="BAJA">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                            <th class="px-6 py-3">Cliente</th>
                                            <th class="px-6 py-3">Deuda</th>
                                            <th class="px-6 py-3">Facturas</th>
                                            <th class="px-6 py-3">Dias</th>
                                            <th class="px-6 py-3">Urgencia</th>
                                            <th class="px-6 py-3">Mensaje Generado</th>
                                            <th class="px-6 py-3 text-center">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody id="results-tbody" class="divide-y divide-gray-100">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div id="empty-state" class="text-center py-16">
                        <svg class="mx-auto w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <p class="text-gray-400 mt-4">Haz clic en "Analizar Cartera" para iniciar el agente de cobros</p>
                        <p class="text-gray-300 text-sm mt-1">El agente analizara las facturas vencidas y generara mensajes personalizados</p>
                    </div>

                </div>
            </main>
        </div>
    </div>

<script>
var allClients = [];

$(document).on('click', '#btn-analyze', function() {
    var btn = $(this);
    btn.prop('disabled', true);
    $('#btn-text').addClass('hidden');
    $('#btn-loading').removeClass('hidden');
    $('#empty-state').addClass('hidden');

    $.ajax({
        url: '<?= base_url() ?>sisvent/admin/agents/runCollections',
        method: 'POST',
        data: { storeId: $('#store-filter').val() },
        dataType: 'json',
        timeout: 120000,
        success: function(data) {
            if (data.success) {
                allClients = data.clients;

                // Stats
                $('#stat-total').text(data.stats.total);
                $('#stat-critica').text(data.stats.critica);
                $('#stat-alta').text(data.stats.alta);
                $('#stat-media').text(data.stats.media);
                $('#stats-cards').removeClass('hidden').addClass('grid');

                renderClients(allClients);
                $('#results-container').removeClass('hidden');

                if (data.clients.length === 0) {
                    $('#results-tbody').html('<tr><td colspan="7" class="text-center py-8 text-gray-400">No se encontraron facturas vencidas (>30 dias)</td></tr>');
                }
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
            }
        },
        error: function() {
            alert('Error de conexion al servidor');
        },
        complete: function() {
            btn.prop('disabled', false);
            $('#btn-text').removeClass('hidden');
            $('#btn-loading').addClass('hidden');
        }
    });
});

$(document).on('change', '#filter-urgency', function() {
    var filter = $(this).val();
    if (filter) {
        renderClients(allClients.filter(function(c) { return c.urgency === filter; }));
    } else {
        renderClients(allClients);
    }
});

function renderClients(clients) {
    var html = '';
    clients.forEach(function(c) {
        var badgeClass = {
            'CRITICA': 'bg-red-100 text-red-700',
            'ALTA': 'bg-orange-100 text-orange-700',
            'MEDIA': 'bg-yellow-100 text-yellow-700',
            'BAJA': 'bg-blue-100 text-blue-700'
        }[c.urgency] || 'bg-gray-100 text-gray-700';

        html += '<tr class="hover:bg-gray-50">';
        html += '<td class="px-6 py-4"><div class="font-semibold text-sm text-gray-800">' + escapeHtml(c.name) + '</div><div class="text-xs text-gray-400">' + escapeHtml(c.city || '') + '</div></td>';
        html += '<td class="px-6 py-4 font-bold text-sm text-gray-800">' + c.total_debt_fmt + '</td>';
        html += '<td class="px-6 py-4 text-sm text-gray-600">' + c.invoice_count + '</td>';
        html += '<td class="px-6 py-4 text-sm text-gray-600">' + c.oldest_days + ' dias</td>';
        html += '<td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-bold rounded-full ' + badgeClass + '">' + c.urgency + '</span></td>';
        html += '<td class="px-6 py-4"><p class="text-xs text-gray-500 max-w-xs truncate" title="' + escapeHtml(c.message) + '">' + escapeHtml(c.message) + '</p></td>';
        html += '<td class="px-6 py-4 text-center">';
        if (c.phone) {
            html += '<a href="' + c.whatsapp_link + '" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg hover:opacity-90" style="background:#25D366;">';
            html += '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.593-.757-6.405-2.043l-.447-.334-2.634.883.883-2.634-.334-.447A9.954 9.954 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>';
            html += 'WhatsApp</a>';
        } else {
            html += '<span class="text-xs text-gray-400">Sin telefono</span>';
        }
        html += '</td></tr>';
    });
    $('#results-tbody').html(html);
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
<?php $this->load->view("sisvent/layouts/footer"); ?>
</body>
</html>
