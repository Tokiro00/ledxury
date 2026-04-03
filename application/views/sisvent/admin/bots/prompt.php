<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Asistente IA - <?= htmlspecialchars($bot_config->name, ENT_QUOTES, 'UTF-8') ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
  .prompt-editor {
    font-family: 'Inter', monospace;
    font-size: 13px;
    line-height: 1.6;
    resize: vertical;
    min-height: 500px;
    tab-size: 2;
  }
  .prompt-editor:focus {
    border-color: #E63946;
    box-shadow: 0 0 0 3px rgba(230,57,70,0.15);
    outline: none;
  }
  .char-count { font-variant-numeric: tabular-nums; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="container px-6 mx-auto" style="max-width: 960px;">

        <!-- Header -->
        <div class="flex items-center justify-between mt-6 mb-4">
          <div class="flex items-center">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-3 text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
              <h2 class="text-xl font-semibold text-gray-700">Asistente IA</h2>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($bot_config->name, ENT_QUOTES, 'UTF-8') ?> &middot; answer: <?= htmlspecialchars($bot_config->answer_id) ?></p>
            </div>
          </div>
          <button id="btnSavePrompt" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none transition-all" style="background: linear-gradient(135deg, #E63946, #c1121f);">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Guardar en BuilderBot
          </button>
        </div>

        <!-- Status -->
        <div id="saveResult" class="mb-4 hidden"></div>

        <?php if($instructions === null): ?>
        <div class="p-4 bg-red-50 text-red-700 rounded-lg text-sm mb-4">
          No se pudo obtener el prompt del bot. Verifica que el answer_id sea correcto.
        </div>
        <?php endif; ?>

        <!-- Inject Data Button -->
        <div class="flex items-center space-x-2 mb-4">
          <button id="btnInjectData" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
            Inyectar datos de productos (BD)
          </button>
          <span class="text-xs text-gray-400">Agrega al final del prompt la lista actualizada de productos, precios y stock</span>
        </div>

        <!-- Editor -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
          <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Instrucciones del Asistente (Prompt)</span>
            <span class="text-xs text-gray-400 char-count" id="charCount">0 caracteres</span>
          </div>
          <textarea id="promptEditor" class="w-full px-4 py-3 prompt-editor border-0 text-gray-700" placeholder="Escribe las instrucciones del asistente IA..."><?= htmlspecialchars($instructions ?: '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!-- Info -->
        <div class="p-4 bg-blue-50 rounded-lg text-sm text-blue-700 mb-6">
          <strong>Nota:</strong> Al guardar, el prompt se actualiza directamente en BuilderBot Cloud via API. Los cambios aplican inmediatamente para las nuevas conversaciones del bot.
        </div>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
// Character count
function updateCount() {
  var len = document.getElementById('promptEditor').value.length;
  document.getElementById('charCount').textContent = len.toLocaleString() + ' caracteres';
}
$(document).on('input', '#promptEditor', updateCount);
updateCount();

// Inject product data
$(document).on('click', '#btnInjectData', function() {
  var $btn = $(this);
  $btn.prop('disabled', true).addClass('opacity-75').text('Cargando datos...');

  $.getJSON(base_url + 'sisvent/admin/bots/getProductData', function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').html('<svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg> Inyectar datos de productos (BD)');

    if (r.success) {
      var editor = document.getElementById('promptEditor');
      // Buscar si ya hay una sección de datos inyectados y reemplazarla
      var marker = '\n\n--- DATOS ACTUALIZADOS DESDE MAM (';
      var markerEnd = '--- FIN DATOS MAM ---';
      var currentVal = editor.value;
      var markerIdx = currentVal.indexOf(marker);
      if (markerIdx > 0) {
        var endIdx = currentVal.indexOf(markerEnd);
        if (endIdx > 0) {
          currentVal = currentVal.substring(0, markerIdx) + currentVal.substring(endIdx + markerEnd.length);
        }
      }
      editor.value = currentVal.trimEnd() + '\n\n' + r.data;
      updateCount();
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-blue-50 text-blue-700 rounded-lg text-sm">Datos de ' + r.product_count + ' productos inyectados. Recuerda guardar en BuilderBot.</div>');
    } else {
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">' + r.error + '</div>');
    }
  }).fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75');
  });
});

// Save
$(document).on('click', '#btnSavePrompt', function() {
  var $btn = $(this);
  var instructions = $('#promptEditor').val();

  if (!instructions.trim()) {
    $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">El prompt no puede estar vacio.</div>');
    return;
  }

  $btn.prop('disabled', true).addClass('opacity-75').text('Guardando...');

  $.post(base_url + 'sisvent/admin/bots/savePrompt', {
    bot_config_id: <?= $bot_config->id ?>,
    instructions: instructions
  }, function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').html('<svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar en BuilderBot');

    if (r.success) {
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm">' + r.message + '</div>');
    } else {
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">' + (r.error || r.message) + '</div>');
    }

    setTimeout(function() { $('#saveResult').addClass('hidden'); }, 5000);
  }, 'json').fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75').text('Guardar en BuilderBot');
    $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">Error de conexion.</div>');
  });
});
</script>
</body>
</html>
