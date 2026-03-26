<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$userName = $this->session->userdata('user_data')['name'];

// Stores for filter
$stores = $this->db->where('deleted', 0)->get('stores')->result();
?>
<!DOCTYPE html>
<html lang="es">
    <title>Resumen Ejecutivo Diario - MAM</title>
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
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Resumen Ejecutivo Diario</h2>
                            <p class="text-sm text-gray-500">Genera un analisis inteligente del dia con metricas clave, alertas y recomendaciones</p>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                        <div class="flex flex-wrap items-end gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Fecha</label>
                                <input type="date" id="summary-date" value="<?= date('Y-m-d') ?>" class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bodega</label>
                                <select id="summary-store" class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-gray-400">
                                    <option value="">Todas las bodegas</option>
                                    <?php foreach ($stores as $store): ?>
                                    <option value="<?= $store->idStore ?>"><?= $store->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button id="btn-generate" class="px-6 py-2.5 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all text-sm" style="background: linear-gradient(135deg, #2E7D91, #1B365D);">
                                <span id="btn-text">Generar Resumen del Dia</span>
                                <span id="btn-loading" class="hidden">
                                    <svg class="animate-spin inline w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Generando...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Summary Output -->
                    <div id="summary-output" class="hidden">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background: linear-gradient(135deg, #f0f9ff, #f5f3ff);">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <h3 class="font-semibold text-gray-700">Resumen generado por IA</h3>
                                </div>
                                <span id="summary-date-label" class="text-xs text-gray-400"></span>
                            </div>
                            <div id="summary-content" class="px-8 py-6 prose max-w-none">
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div id="empty-state" class="text-center py-16">
                        <svg class="mx-auto w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-gray-400 mt-4">Selecciona una fecha y haz clic en "Generar Resumen del Dia"</p>
                        <p class="text-gray-300 text-sm mt-1">El agente recopilara datos de ventas, cobros, inventario y finanzas para generar un informe ejecutivo</p>
                    </div>

                </div>
            </main>
        </div>
    </div>

<script>
$(document).on('click', '#btn-generate', function() {
    var btn = $(this);
    btn.prop('disabled', true);
    $('#btn-text').addClass('hidden');
    $('#btn-loading').removeClass('hidden');
    $('#empty-state').addClass('hidden');
    $('#summary-output').addClass('hidden');

    var date = $('#summary-date').val();
    var storeId = $('#summary-store').val();

    $.ajax({
        url: '<?= base_url() ?>sisvent/admin/agents/generateSummary',
        method: 'POST',
        data: { date: date, storeId: storeId },
        dataType: 'json',
        timeout: 60000,
        success: function(data) {
            if (data.success) {
                $('#summary-date-label').text(date);
                $('#summary-content').html(formatResponse(data.response));
                $('#summary-output').removeClass('hidden');
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
                $('#empty-state').removeClass('hidden');
            }
        },
        error: function() {
            alert('Error de conexion al servidor');
            $('#empty-state').removeClass('hidden');
        },
        complete: function() {
            btn.prop('disabled', false);
            $('#btn-text').removeClass('hidden');
            $('#btn-loading').addClass('hidden');
        }
    });
});

function formatResponse(text) {
    text = escapeHtml(text);
    text = text.replace(/^### (.*?)$/gm, '<h4 class="font-semibold text-gray-800 mt-4 mb-2 text-sm">$1</h4>');
    text = text.replace(/^## (.*?)$/gm, '<h3 class="font-bold text-gray-800 mt-5 mb-2 text-base border-b border-gray-200 pb-1">$1</h3>');
    text = text.replace(/^# (.*?)$/gm, '<h2 class="font-bold text-gray-900 mt-6 mb-3 text-lg">$1</h2>');
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="text-gray-800">$1</strong>');
    text = text.replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">$1</code>');
    text = text.replace(/^- (.*?)$/gm, '<li class="ml-4 text-sm text-gray-600">$1</li>');
    text = text.replace(/(<li[^>]*>.*?<\/li>)/gs, '<ul class="space-y-1 my-2 list-disc">$1</ul>');
    text = text.replace(/<\/ul>\s*<ul[^>]*>/g, '');
    text = text.replace(/\n\n/g, '</p><p class="mt-3 text-sm text-gray-700">');
    text = text.replace(/\n/g, '<br>');
    return '<div class="text-sm text-gray-700 leading-relaxed">' + text + '</div>';
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
</body>
</html>
