<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$userName = $this->session->userdata('user_data')['name'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>WhatsApp Bot - MAM</title>
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
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: #25D366;">
                            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.593-.757-6.405-2.043l-.447-.334-2.634.883.883-2.634-.334-.447A9.954 9.954 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">WhatsApp Bot</h2>
                            <p class="text-sm text-gray-500">Genera mensajes personalizados con IA y envialos por WhatsApp a tus clientes</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <!-- Left panel: Client search + type -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                                <h3 class="font-semibold text-gray-700 mb-4">Configuracion del Mensaje</h3>

                                <!-- Mode toggle -->
                                <div class="flex rounded-lg border border-gray-200 mb-4">
                                    <button id="mode-single" class="flex-1 px-3 py-2 text-xs font-semibold rounded-l-lg bg-gray-800 text-white">Individual</button>
                                    <button id="mode-bulk" class="flex-1 px-3 py-2 text-xs font-semibold rounded-r-lg bg-white text-gray-500 hover:bg-gray-50">Masivo</button>
                                </div>

                                <!-- Client search -->
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Buscar Cliente</label>
                                    <input type="text" id="client-search" placeholder="Nombre o telefono..." class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-gray-400" autocomplete="off">
                                    <div id="client-results" class="hidden mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-48 overflow-y-auto">
                                    </div>
                                </div>

                                <!-- Selected clients (bulk mode) -->
                                <div id="selected-clients" class="hidden mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Clientes Seleccionados</label>
                                    <div id="selected-list" class="space-y-1 max-h-32 overflow-y-auto">
                                    </div>
                                </div>

                                <!-- Selected client (single mode) -->
                                <div id="single-client" class="hidden mb-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p id="client-name-display" class="text-sm font-semibold text-gray-800"></p>
                                            <p id="client-phone-display" class="text-xs text-gray-400"></p>
                                        </div>
                                        <button id="clear-client" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Message type -->
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tipo de Mensaje</label>
                                    <select id="message-type" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-gray-400">
                                        <option value="COBRO">Cobro - Recordatorio de pago</option>
                                        <option value="SEGUIMIENTO">Seguimiento - Post cotizacion</option>
                                        <option value="AGRADECIMIENTO">Agradecimiento - Pago recibido</option>
                                        <option value="PROMOCION">Promocion - Oferta comercial</option>
                                    </select>
                                </div>

                                <button id="btn-generate" class="w-full px-4 py-2.5 text-white font-semibold rounded-lg text-sm transition-all" style="background: linear-gradient(135deg, #2E7D91, #1B365D);">
                                    <span id="gen-text">Generar Mensaje</span>
                                    <span id="gen-loading" class="hidden">
                                        <svg class="animate-spin inline w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        Generando...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Right panel: Message preview -->
                        <div class="lg:col-span-2">
                            <!-- Single message preview -->
                            <div id="message-preview" class="hidden">
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-700">Vista Previa del Mensaje</h3>
                                        <span id="preview-client-name" class="text-xs text-gray-400"></span>
                                    </div>
                                    <div class="p-6">
                                        <!-- WhatsApp bubble style -->
                                        <div class="max-w-md mx-auto bg-green-50 rounded-2xl p-4 border border-green-100 relative">
                                            <div class="absolute -top-2 left-6 w-4 h-4 bg-green-50 border-l border-t border-green-100 transform rotate-45"></div>
                                            <textarea id="message-text" rows="6" class="w-full bg-transparent text-sm text-gray-700 resize-none focus:outline-none leading-relaxed" placeholder="El mensaje aparecera aqui..."></textarea>
                                        </div>
                                        <div class="flex justify-center gap-3 mt-6">
                                            <a id="whatsapp-send" href="#" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 text-white font-semibold rounded-lg text-sm hover:opacity-90 transition-all" style="background:#25D366;">
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.593-.757-6.405-2.043l-.447-.334-2.634.883.883-2.634-.334-.447A9.954 9.954 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                                                Enviar por WhatsApp
                                            </a>
                                            <button id="btn-copy" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg text-sm hover:bg-gray-200 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                Copiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bulk messages preview -->
                            <div id="bulk-preview" class="hidden">
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-gray-100">
                                        <h3 class="font-semibold text-gray-700">Mensajes Generados (Masivo)</h3>
                                    </div>
                                    <div id="bulk-messages-list" class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div id="wa-empty-state" class="text-center py-16">
                                <svg class="mx-auto w-16 h-16 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                <p class="text-gray-400 mt-4">Busca un cliente, selecciona el tipo de mensaje y genera</p>
                                <p class="text-gray-300 text-sm mt-1">Los mensajes se generan con IA y se pueden editar antes de enviar</p>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

<script>
var currentMode = 'single';
var selectedClientId = null;
var selectedClientPhone = '';
var bulkClientIds = [];
var searchTimeout = null;

// Mode toggle
$(document).on('click', '#mode-single', function() {
    currentMode = 'single';
    $(this).addClass('bg-gray-800 text-white').removeClass('bg-white text-gray-500');
    $('#mode-bulk').addClass('bg-white text-gray-500').removeClass('bg-gray-800 text-white');
    $('#selected-clients').addClass('hidden');
    bulkClientIds = [];
    $('#selected-list').html('');
});

$(document).on('click', '#mode-bulk', function() {
    currentMode = 'bulk';
    $(this).addClass('bg-gray-800 text-white').removeClass('bg-white text-gray-500');
    $('#mode-single').addClass('bg-white text-gray-500').removeClass('bg-gray-800 text-white');
    $('#selected-clients').removeClass('hidden');
    selectedClientId = null;
    $('#single-client').addClass('hidden');
});

// Client search
$(document).on('input', '#client-search', function() {
    var term = $(this).val().trim();
    clearTimeout(searchTimeout);
    if (term.length < 2) {
        $('#client-results').addClass('hidden');
        return;
    }
    searchTimeout = setTimeout(function() {
        $.post('<?= base_url() ?>sisvent/admin/agents/searchClients', { term: term }, function(data) {
            if (data.length > 0) {
                var html = '';
                data.forEach(function(c) {
                    var phone = c.cellphone || c.phone || 'Sin telefono';
                    html += '<div class="client-item px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-50" data-id="' + c.idClient + '" data-name="' + escapeHtml(c.name) + '" data-phone="' + escapeHtml(phone) + '">';
                    html += '<p class="text-sm font-medium text-gray-700">' + escapeHtml(c.name) + '</p>';
                    html += '<p class="text-xs text-gray-400">' + escapeHtml(c.city || '') + ' - ' + escapeHtml(phone) + '</p>';
                    html += '</div>';
                });
                $('#client-results').html(html).removeClass('hidden');
            } else {
                $('#client-results').html('<div class="px-4 py-3 text-sm text-gray-400">No se encontraron clientes</div>').removeClass('hidden');
            }
        }, 'json');
    }, 300);
});

// Select client
$(document).on('click', '.client-item', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var phone = $(this).data('phone');

    if (currentMode === 'single') {
        selectedClientId = id;
        selectedClientPhone = phone;
        $('#client-name-display').text(name);
        $('#client-phone-display').text(phone);
        $('#single-client').removeClass('hidden');
    } else {
        if (bulkClientIds.indexOf(id) === -1) {
            bulkClientIds.push(id);
            $('#selected-list').append(
                '<div class="flex items-center justify-between bg-gray-50 rounded px-3 py-1.5" data-cid="' + id + '">' +
                '<span class="text-xs text-gray-700">' + escapeHtml(name) + '</span>' +
                '<button class="remove-bulk-client text-gray-400 hover:text-red-500" data-cid="' + id + '">' +
                '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' +
                '</button></div>'
            );
        }
    }

    $('#client-results').addClass('hidden');
    $('#client-search').val('');
});

// Clear single client
$(document).on('click', '#clear-client', function() {
    selectedClientId = null;
    selectedClientPhone = '';
    $('#single-client').addClass('hidden');
});

// Remove bulk client
$(document).on('click', '.remove-bulk-client', function() {
    var cid = $(this).data('cid');
    bulkClientIds = bulkClientIds.filter(function(id) { return id != cid; });
    $('[data-cid="' + cid + '"]').remove();
});

// Generate message
$(document).on('click', '#btn-generate', function() {
    var type = $('#message-type').val();
    var btn = $(this);

    if (currentMode === 'single') {
        if (!selectedClientId) {
            alert('Selecciona un cliente primero');
            return;
        }

        btn.prop('disabled', true);
        $('#gen-text').addClass('hidden');
        $('#gen-loading').removeClass('hidden');
        $('#wa-empty-state').addClass('hidden');

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/agents/generateClientMessage',
            method: 'POST',
            data: { clientId: selectedClientId, type: type },
            dataType: 'json',
            timeout: 30000,
            success: function(data) {
                if (data.success) {
                    $('#message-text').val(data.message);
                    $('#preview-client-name').text(data.client_name);
                    updateWhatsappLink();
                    $('#message-preview').removeClass('hidden');
                    $('#bulk-preview').addClass('hidden');
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            },
            error: function() { alert('Error de conexion'); },
            complete: function() {
                btn.prop('disabled', false);
                $('#gen-text').removeClass('hidden');
                $('#gen-loading').addClass('hidden');
            }
        });
    } else {
        if (bulkClientIds.length === 0) {
            alert('Selecciona al menos un cliente');
            return;
        }

        btn.prop('disabled', true);
        $('#gen-text').addClass('hidden');
        $('#gen-loading').removeClass('hidden');
        $('#wa-empty-state').addClass('hidden');

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/agents/bulkMessages',
            method: 'POST',
            data: { clientIds: bulkClientIds, type: type },
            dataType: 'json',
            timeout: 120000,
            success: function(data) {
                if (data.success) {
                    var html = '';
                    data.messages.forEach(function(m) {
                        html += '<div class="px-6 py-4">';
                        html += '<div class="flex items-center justify-between mb-2">';
                        html += '<p class="font-semibold text-sm text-gray-800">' + escapeHtml(m.client_name) + '</p>';
                        if (m.phone) {
                            html += '<a href="' + m.whatsapp_link + '" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white rounded-lg" style="background:#25D366;">WhatsApp</a>';
                        }
                        html += '</div>';
                        html += '<p class="text-sm text-gray-600 bg-green-50 rounded-lg p-3">' + escapeHtml(m.message) + '</p>';
                        html += '</div>';
                    });
                    $('#bulk-messages-list').html(html);
                    $('#bulk-preview').removeClass('hidden');
                    $('#message-preview').addClass('hidden');
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            },
            error: function() { alert('Error de conexion'); },
            complete: function() {
                btn.prop('disabled', false);
                $('#gen-text').removeClass('hidden');
                $('#gen-loading').addClass('hidden');
            }
        });
    }
});

// Update WhatsApp link when message is edited
$(document).on('input', '#message-text', function() {
    updateWhatsappLink();
});

function updateWhatsappLink() {
    var phone = selectedClientPhone.replace(/[^0-9]/g, '');
    var msg = $('#message-text').val();
    $('#whatsapp-send').attr('href', 'https://wa.me/57' + phone + '?text=' + encodeURIComponent(msg));
}

// Copy message
$(document).on('click', '#btn-copy', function() {
    var msg = $('#message-text').val();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(msg);
        $(this).html('<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Copiado');
        var btn = $(this);
        setTimeout(function() {
            btn.html('<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Copiar');
        }, 2000);
    }
});

// Hide search results on click outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#client-search, #client-results').length) {
        $('#client-results').addClass('hidden');
    }
});

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
</body>
</html>
