<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Prompts de los Bots - Ledxury AI</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
  .prompt-editor {
    font-family: 'Inter', monospace;
    font-size: 13px;
    line-height: 1.6;
    resize: vertical;
    min-height: 480px;
    tab-size: 2;
  }
  .prompt-editor:focus {
    border-color: #2E7D91;
    box-shadow: 0 0 0 3px rgba(46,125,145,0.15);
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
            <a href="<?= base_url() ?>sisvent/admin/aiassistant" class="mr-3 text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
              <h2 class="text-xl font-semibold text-gray-700">Prompts de los Bots</h2>
              <p class="text-xs text-gray-400">Edita y guarda las instrucciones del asistente IA de cada bot &middot; se actualiza en BuilderBot al instante</p>
            </div>
          </div>
          <button id="btnSavePrompt" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none transition-all" style="background: linear-gradient(135deg, #2E7D91, #1B365D);">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Guardar en BuilderBot
          </button>
        </div>

        <?php if (empty($bots)): ?>
        <div class="p-4 bg-yellow-50 text-yellow-800 rounded-lg text-sm mb-4">
          No hay bots con asistente IA editable (answer_id configurado).
        </div>
        <?php else: ?>

        <!-- Selector de bot -->
        <div class="flex items-center gap-3 mb-4">
          <label class="text-sm font-medium text-gray-600">Bot:</label>
          <select id="botSelect" class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-400">
            <?php foreach ($bots as $i => $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $i === 0 ? 'selected' : '' ?>><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          <span id="loadWarn" class="text-xs text-red-500 hidden">No se pudo leer el prompt de este bot.</span>
        </div>

        <!-- Status -->
        <div id="saveResult" class="mb-4 hidden"></div>

        <!-- Editor -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
          <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Instrucciones del Asistente (Prompt)</span>
            <span class="text-xs text-gray-400 char-count" id="charCount">0 caracteres</span>
          </div>
          <textarea id="promptEditor" class="w-full px-4 py-3 prompt-editor border-0 text-gray-700" placeholder="Escribe las instrucciones del asistente IA..."></textarea>
        </div>

        <!-- Info -->
        <div class="p-4 bg-blue-50 rounded-lg text-sm text-blue-700 mb-6">
          <strong>Nota:</strong> Al guardar, el prompt se actualiza directamente en BuilderBot Cloud vía API. Los cambios aplican de inmediato a las nuevas conversaciones del bot.
        </div>

        <?php endif; ?>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
// Prompts precargados por bot (server-side)
var BOT_PROMPTS = <?= json_encode(array_column($bots, 'instructions', 'id'), JSON_UNESCAPED_UNICODE) ?>;
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';

function currentBotId() { return $('#botSelect').val(); }

function loadPrompt() {
  var id = currentBotId();
  var val = (BOT_PROMPTS && BOT_PROMPTS[id] != null) ? BOT_PROMPTS[id] : '';
  $('#loadWarn').toggleClass('hidden', !(val === '' || val === null));
  document.getElementById('promptEditor').value = val || '';
  updateCount();
}

function updateCount() {
  var len = document.getElementById('promptEditor').value.length;
  document.getElementById('charCount').textContent = len.toLocaleString() + ' caracteres';
}

$(document).on('input', '#promptEditor', updateCount);
$(document).on('change', '#botSelect', loadPrompt);

// Guardar
$(document).on('click', '#btnSavePrompt', function() {
  var $btn = $(this);
  var instructions = $('#promptEditor').val();
  var id = currentBotId();

  if (!instructions.trim()) {
    $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">El prompt no puede estar vacío.</div>');
    return;
  }

  $btn.prop('disabled', true).addClass('opacity-75').text('Guardando...');

  var data = { bot_config_id: id, instructions: instructions };
  data[CSRF_NAME] = CSRF_HASH;

  $.post(base_url + 'sisvent/admin/aiassistant/saveBotPrompt', data, function(r) {
    $btn.prop('disabled', false).removeClass('opacity-75').html('<svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar en BuilderBot');

    if (r.success) {
      BOT_PROMPTS[id] = instructions; // refrescar cache local
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm">' + r.message + '</div>');
    } else {
      $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">' + (r.error || r.message) + '</div>');
    }
    setTimeout(function() { $('#saveResult').addClass('hidden'); }, 5000);
  }, 'json').fail(function() {
    $btn.prop('disabled', false).removeClass('opacity-75').text('Guardar en BuilderBot');
    $('#saveResult').removeClass('hidden').html('<div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">Error de conexión.</div>');
  });
});

// Init
loadPrompt();
</script>
</body>
</html>
