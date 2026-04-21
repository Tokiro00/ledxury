<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Mensajes Bot <?= htmlspecialchars($bot_config->name) ?> - MAM</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="px-6 mx-auto grid">

        <!-- Header -->
        <div class="flex items-center justify-between my-6">
          <div class="flex items-center">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-4 text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="text-2xl font-semibold text-gray-700">Mensajes - <?= htmlspecialchars($bot_config->name) ?></h2>
          </div>
        </div>

        <!-- Send Message Form (Owner Only) -->
        <?php if($is_owner): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <h3 class="mb-4 text-lg font-semibold text-gray-700">Enviar Mensaje</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Telefono *</label>
              <input type="text" id="msgPhone" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="573001234567">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje *</label>
              <input type="text" id="msgContent" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="Escribe tu mensaje...">
            </div>
            <div class="flex items-end">
              <button id="btnSendMsg" class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none">
                Enviar
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 mt-3">URL Multimedia (opcional)</label>
            <input type="text" id="msgMediaUrl" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="https://...">
          </div>
          <div id="msgResult" class="mt-3 hidden"></div>
        </div>
        <?php endif; ?>

        <!-- Messages Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
              <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                  <th class="px-4 py-3">Fecha</th>
                  <th class="px-4 py-3">Direccion</th>
                  <th class="px-4 py-3">Telefono</th>
                  <th class="px-4 py-3">Contenido</th>
                  <th class="px-4 py-3">Estado</th>
                  <th class="px-4 py-3">Enviado por</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <?php if(empty($messages)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay mensajes registrados.</td></tr>
                <?php else: ?>
                <?php foreach($messages as $msg): ?>
                <tr class="text-gray-700 hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm"><?= date('d/m/Y H:i', strtotime($msg->created_at)) ?></td>
                  <td class="px-4 py-3 text-sm">
                    <?php if($msg->direction === 'outgoing'): ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Enviado</span>
                    <?php else: ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Recibido</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-sm font-medium"><?= htmlspecialchars($msg->phone_number) ?></td>
                  <td class="px-4 py-3 text-sm" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($msg->content) ?>
                    <?php if($msg->media_url): ?>
                    <br><a href="<?= htmlspecialchars($msg->media_url) ?>" target="_blank" class="text-blue-500 text-xs hover:underline">Ver adjunto</a>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <?php
                      $s_class = 'bg-gray-100 text-gray-800';
                      if ($msg->status === 'sent') $s_class = 'bg-green-100 text-green-800';
                      elseif ($msg->status === 'delivered') $s_class = 'bg-blue-100 text-blue-800';
                      elseif ($msg->status === 'failed') $s_class = 'bg-red-100 text-red-800';
                      elseif ($msg->status === 'queued') $s_class = 'bg-yellow-100 text-yellow-800';
                    ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $s_class ?>"><?= ucfirst($msg->status) ?></span>
                  </td>
                  <td class="px-4 py-3 text-sm"><?= htmlspecialchars($msg->sent_by ?: '-') ?></td>
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
$(document).on('click', '#btnSendMsg', function() {
  var phone = $('#msgPhone').val().trim();
  var content = $('#msgContent').val().trim();
  var mediaUrl = $('#msgMediaUrl').val().trim();

  if (!phone || !content) {
    $('#msgResult').removeClass('hidden').html('<div class="p-3 text-sm text-red-700 bg-red-100 rounded">Telefono y mensaje son requeridos.</div>');
    return;
  }

  var $btn = $(this);
  $btn.prop('disabled', true).text('Enviando...');

  $.post(base_url + 'sisvent/admin/bots/sendMessage', {
    bot_config_id: <?= $bot_config->id ?>,
    phone: phone,
    content: content,
    media_url: mediaUrl || ''
  }, function(r) {
    $btn.prop('disabled', false).text('Enviar');
    if (r.success) {
      $('#msgResult').removeClass('hidden').html('<div class="p-3 text-sm text-green-700 bg-green-100 rounded">' + r.message + '</div>');
      $('#msgContent').val('');
      $('#msgMediaUrl').val('');
    } else {
      $('#msgResult').removeClass('hidden').html('<div class="p-3 text-sm text-red-700 bg-red-100 rounded">' + (r.error || r.message) + '</div>');
    }
  }, 'json').fail(function() {
    $btn.prop('disabled', false).text('Enviar');
    $('#msgResult').removeClass('hidden').html('<div class="p-3 text-sm text-red-700 bg-red-100 rounded">Error de conexion.</div>');
  });
});
</script>
</body>
</html>
