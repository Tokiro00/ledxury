<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Bots WhatsApp - MAM</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="container px-6 mx-auto grid">

        <!-- Header -->
        <div class="flex items-center justify-between mt-6 mb-4">
          <div class="flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.638l4.603-1.209A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.24 0-4.326-.68-6.058-1.845l-.29-.194-3.066.806.82-2.994-.212-.316A9.953 9.953 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
            <h2 class="text-xl font-semibold text-gray-700">Bots de WhatsApp</h2>
          </div>
          <?php if($is_owner): ?>
          <div class="flex items-center space-x-2">
            <button id="btnSyncSheet" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 focus:outline-none transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
              Sincronizar Sheet
            </button>
            <a href="<?= base_url() ?>sisvent/admin/bots/report/0" class="inline-flex items-center px-3 py-2 text-sm font-medium text-orange-700 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 focus:outline-none transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              Reporte General
            </a>
            <button id="btnRecoverStock" class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 focus:outline-none transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
              Recuperar Agotados
            </button>
            <button id="btnNotifyGuides" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 focus:outline-none transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              Notificar Guias
            </button>
            <a href="<?= base_url() ?>sisvent/admin/bots/config" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none transition-colors">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              Agregar Bot
            </a>
          </div>
          <?php endif; ?>
        </div>

        <!-- Flash messages -->
        <?php if($this->session->flashdata('success')): ?>
        <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?= $this->session->flashdata('success') ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('bots_error')): ?>
        <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?= $this->session->flashdata('bots_error') ?></div>
        <?php endif; ?>

        <?php if(empty($bots)): ?>
        <div class="p-8 text-center text-gray-500 bg-white rounded-lg shadow">
          <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
          <p class="text-lg">No hay bots configurados</p>
          <?php if($is_owner): ?>
          <a href="<?= base_url() ?>sisvent/admin/bots/config" class="inline-block mt-3 text-sm text-green-600 hover:underline">Configurar primer bot</a>
          <?php endif; ?>
        </div>
        <?php else: ?>

        <?php foreach($bots as $bot): $cfg = $bot['config']; ?>
        <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">

          <!-- Bot Header - compact -->
          <div class="flex items-center justify-between px-5 py-3" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
              <h3 class="text-base font-semibold text-white"><?= htmlspecialchars($cfg->name, ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <div class="flex items-center space-x-2">
              <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $cfg->is_active ? 'bg-white bg-opacity-25 text-white' : 'bg-red-200 text-red-800' ?>">
                <?= $cfg->is_active ? 'En linea' : 'Inactivo' ?>
              </span>
              <?php if($is_owner): ?>
              <a href="<?= base_url() ?>sisvent/admin/bots/config/<?= $cfg->id ?>" class="p-1 text-white hover:bg-white hover:bg-opacity-25 rounded" title="Configurar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Stats Row -->
          <div class="grid grid-cols-3 divide-x border-b">
            <div class="px-5 py-4 text-center">
              <p class="text-2xl font-bold text-gray-800"><?= $bot['ventas_hoy'] ?></p>
              <p class="text-xs text-gray-500 mt-1">Ventas hoy</p>
            </div>
            <div class="px-5 py-4 text-center">
              <p class="text-2xl font-bold text-gray-800"><?= $bot['ventas_semana'] ?></p>
              <p class="text-xs text-gray-500 mt-1">Esta semana</p>
            </div>
            <div class="px-5 py-4 text-center">
              <p class="text-2xl font-bold text-gray-800"><?= $bot['mensajes'] ?></p>
              <p class="text-xs text-gray-500 mt-1">Mensajes enviados</p>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="flex items-center space-x-2 px-5 py-3 bg-gray-50 border-b">
            <a href="<?= base_url() ?>sisvent/admin/bots/sales/<?= $cfg->id ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
              Todas las ventas
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/messages/<?= $cfg->id ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
              Mensajes
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/report/<?= $cfg->id ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-orange-700 bg-orange-50 border border-orange-200 rounded hover:bg-orange-100">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              Reporte
            </a>
            <?php if($is_owner && !empty($cfg->answer_id)): ?>
            <a href="<?= base_url() ?>sisvent/admin/bots/prompt/<?= $cfg->id ?>" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded hover:bg-purple-100">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .796.316 1.559.878 2.121L19 14.5"></path></svg>
              Asistente IA
            </a>
            <?php endif; ?>
          </div>

          <!-- Recent Sales Table -->
          <?php if(!empty($bot['recientes'])): ?>
          <div class="px-5 py-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ventas recientes</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="text-xs font-medium text-gray-400 uppercase tracking-wider border-b">
                    <th class="pb-2 pr-4 text-left">Fecha</th>
                    <th class="pb-2 pr-4 text-left">Cliente</th>
                    <th class="pb-2 pr-4 text-left">Productos</th>
                    <th class="pb-2 pr-4 text-right">Total</th>
                    <th class="pb-2 pr-4 text-center">Estado</th>
                    <th class="pb-2 text-left">Presupuesto</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach($bot['recientes'] as $sale):
                    // Extraer productos del payload
                    $productos_display = '-';
                    if (!empty($sale->sale_payload)) {
                      $payload = json_decode($sale->sale_payload, true);
                      if (isset($payload['productos']) && is_array($payload['productos'])) {
                        $items = array();
                        foreach ($payload['productos'] as $p) {
                          $items[] = ($p['codigo'] ?? '') . ' x' . ($p['cantidad'] ?? 1);
                        }
                        $productos_display = implode(', ', $items);
                      }
                    }
                    // Si no hay payload, intentar del raw_payload del webhook
                    if ($productos_display === '-' && !empty($sale->raw_payload)) {
                      $raw = json_decode($sale->raw_payload, true);
                      if (isset($raw['productos'])) {
                        $productos_display = $raw['productos'] . ' ' . ($raw['color'] ?? '') . ' x' . ($raw['cantidad'] ?? '');
                      }
                    }
                  ?>
                  <tr class="text-sm text-gray-700 hover:bg-gray-50">
                    <td class="py-2.5 pr-4 text-gray-500 text-xs"><?= date('d/m H:i', strtotime($sale->created_at)) ?></td>
                    <td class="py-2.5 pr-4 font-medium"><?= htmlspecialchars($sale->client_name ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2.5 pr-4 text-xs text-gray-500" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($productos_display) ?></td>
                    <td class="py-2.5 pr-4 text-right font-medium">
                      <?php if($sale->budget_total): ?>
                      $<?= number_format($sale->budget_total, 0, ',', '.') ?>
                      <?php else: ?>
                      <span class="text-gray-300">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-2.5 pr-4 text-center">
                      <?php
                        $badge = 'bg-gray-100 text-gray-600';
                        $label = $sale->status;
                        if ($sale->status === 'processed') { $badge = 'bg-green-100 text-green-700'; $label = 'OK'; }
                        elseif ($sale->status === 'failed') { $badge = 'bg-red-100 text-red-700'; $label = 'Error'; }
                        elseif ($sale->status === 'received') { $badge = 'bg-yellow-100 text-yellow-700'; $label = 'Pendiente'; }
                      ?>
                      <span class="px-1.5 py-0.5 text-xs font-medium rounded <?= $badge ?>"><?= $label ?></span>
                    </td>
                    <td class="py-2.5">
                      <?php if($sale->budget_id): ?>
                      <a href="<?= base_url() ?>sisvent/commercial/budgets/view/<?= $sale->budget_id ?>" class="text-blue-600 hover:underline text-xs font-medium">#<?= $sale->budget_id ?></a>
                      <?php else: ?>
                      <span class="text-gray-300">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="px-5 py-6 text-center text-sm text-gray-400">
            Sin ventas registradas. Usa "Sincronizar Sheet" para importar.
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<!-- Sync Result Modal -->
<div id="syncResultModal" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,0.4)">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-5">
      <h3 class="text-base font-semibold text-gray-700 mb-3" id="syncTitle">Sincronizando...</h3>
      <div id="syncBody" class="text-sm text-gray-600"></div>
      <button onclick="$('#syncResultModal').addClass('hidden')" class="mt-4 px-4 py-2 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 w-full font-medium">Cerrar</button>
    </div>
  </div>
</div>

<script>
$(document).on('click', '#btnSyncSheet', function() {
  var $btn = $(this);
  var botConfigId = 1; // GerMAM Medellin (Sheet principal)
  if (!botConfigId) { alert('No hay bots configurados'); return; }

  $btn.prop('disabled', true).addClass('opacity-75').find('svg').addClass('animate-spin');

  $.post(base_url + 'sisvent/admin/bots/syncSheet', { bot_config_id: botConfigId }, function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').find('svg').removeClass('animate-spin');

    var html = '';
    if (r.success) {
      html += '<div class="flex items-center p-3 mb-2 bg-green-50 rounded-lg"><span class="text-2xl font-bold text-green-600 mr-3">' + r.synced + '</span><span class="text-sm text-green-700">ventas sincronizadas</span></div>';
      if (r.skipped) html += '<p class="text-xs text-gray-500 mb-1">' + r.skipped + ' filas omitidas (ya sincronizadas)</p>';
      if (r.errors && r.errors.length > 0) {
        html += '<div class="mt-2 p-2 bg-red-50 rounded text-xs text-red-600"><strong>' + r.errors.length + ' errores:</strong><ul class="mt-1 list-disc list-inside">';
        r.errors.slice(0, 5).forEach(function(e) { html += '<li>' + e + '</li>'; });
        if (r.errors.length > 5) html += '<li>... y ' + (r.errors.length - 5) + ' mas</li>';
        html += '</ul></div>';
      }
      $('#syncTitle').text('Sincronizacion completada');
    } else {
      html = '<div class="p-3 bg-red-50 rounded text-red-700 text-sm">' + (r.error || 'Error desconocido') + '</div>';
      $('#syncTitle').text('Error');
    }

    $('#syncBody').html(html);
    $('#syncResultModal').removeClass('hidden');
    if (r.synced > 0) setTimeout(function() { location.reload(); }, 2000);
  }, 'json').fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75');
    alert('Error de conexion');
  });
});

// Recover Out of Stock
$(document).on('click', '#btnRecoverStock', function() {
  var $btn = $(this);
  // Usar todos los bots (0 = procesar todos)
  var botConfigId = 0;
  if (!confirm('Enviar WhatsApp a clientes con productos agotados ofreciendo alternativas?')) return;

  $btn.prop('disabled', true).addClass('opacity-75').text('Procesando...');

  $.post(base_url + 'sisvent/admin/bots/recoverOutOfStock', { bot_config_id: botConfigId }, function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').html('<svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg> Recuperar Agotados');

    var html = '';
    if (r.success) {
      html += '<div class="flex items-center p-3 mb-2 bg-yellow-50 rounded-lg"><span class="text-2xl font-bold text-yellow-600 mr-3">' + r.sent + '</span><span class="text-sm text-yellow-700">clientes notificados sobre productos agotados</span></div>';
      if (r.skipped) html += '<p class="text-xs text-gray-500">' + r.skipped + ' filas omitidas (ya notificadas o sin datos)</p>';
      if (r.errors && r.errors.length > 0) {
        html += '<div class="mt-2 p-2 bg-red-50 rounded text-xs text-red-600"><ul class="list-disc list-inside">';
        r.errors.forEach(function(e) { html += '<li>' + e + '</li>'; });
        html += '</ul></div>';
      }
      $('#syncTitle').text('Recuperacion de Ventas Agotadas');
    } else {
      html = '<div class="p-3 bg-red-50 rounded text-red-700 text-sm">' + (r.error || 'Error') + '</div>';
      $('#syncTitle').text('Error');
    }
    $('#syncBody').html(html);
    $('#syncResultModal').removeClass('hidden');
  }, 'json').fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75');
    alert('Error de conexion');
  });
});

// Notify Guides
$(document).on('click', '#btnNotifyGuides', function() {
  var $btn = $(this);
  if (!confirm('Enviar WhatsApp con numero de guia a todos los clientes pendientes?')) return;

  $btn.prop('disabled', true).addClass('opacity-75').text('Enviando...');

  $.post(base_url + 'sisvent/admin/bots/notifyGuides', {}, function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').html('<svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg> Notificar Guias');

    var html = '';
    if (r.success) {
      html += '<div class="flex items-center p-3 mb-2 bg-green-50 rounded-lg"><span class="text-2xl font-bold text-green-600 mr-3">' + r.sent + '</span><span class="text-sm text-green-700">clientes notificados</span></div>';
      if (r.sent === 0) html += '<p class="text-xs text-gray-500">No hay guias pendientes de notificacion.</p>';
      if (r.errors && r.errors.length > 0) {
        html += '<div class="mt-2 p-2 bg-red-50 rounded text-xs text-red-600"><ul class="list-disc list-inside">';
        r.errors.forEach(function(e) { html += '<li>' + e + '</li>'; });
        html += '</ul></div>';
      }
      $('#syncTitle').text('Notificacion de Guias');
    } else {
      html = '<div class="p-3 bg-red-50 rounded text-red-700 text-sm">' + (r.error || 'Error') + '</div>';
      $('#syncTitle').text('Error');
    }
    $('#syncBody').html(html);
    $('#syncResultModal').removeClass('hidden');
  }, 'json').fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75');
    alert('Error de conexion');
  });
});
</script>
</body>
</html>
