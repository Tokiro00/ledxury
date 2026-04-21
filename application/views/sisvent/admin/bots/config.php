<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title><?= $bot_config ? 'Editar' : 'Nuevo' ?> Bot - MAM</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="px-6 mx-auto grid" style="max-width: 800px;">

        <div class="flex items-center my-6">
          <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-4 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </a>
          <h2 class="text-2xl font-semibold text-gray-700"><?= $bot_config ? 'Editar Bot' : 'Nuevo Bot' ?></h2>
        </div>

        <form action="<?= base_url() ?>sisvent/admin/bots/saveConfig" method="POST" class="bg-white rounded-lg shadow-md p-6 mb-6">
          <?php if($bot_config): ?>
          <input type="hidden" name="id" value="<?= $bot_config->id ?>">
          <?php endif; ?>

          <!-- Datos del Bot -->
          <h3 class="mb-4 text-lg font-semibold text-gray-700 border-b pb-2">Datos del Bot</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Bot *</label>
              <input type="text" name="name" value="<?= $bot_config ? htmlspecialchars($bot_config->name) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 form-input" required placeholder="Ej: GerMAM Medellin">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bot ID (UUID) *</label>
              <input type="text" name="bot_id" value="<?= $bot_config ? htmlspecialchars($bot_config->bot_id) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 form-input" required placeholder="ej: 1cafcdaf-ee82-4896-...">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">API Key *</label>
              <input type="text" name="api_key" value="<?= $bot_config ? htmlspecialchars($bot_config->api_key) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 form-input" required placeholder="bb-xxxxxxxx-xxxx-...">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Answer ID (Asistente IA)</label>
              <input type="text" name="answer_id" value="<?= $bot_config ? htmlspecialchars($bot_config->answer_id) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="UUID del flujo con plugin assistant">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
              <input type="text" name="base_url" value="<?= $bot_config ? htmlspecialchars($bot_config->base_url) : 'https://app.builderbot.cloud' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input">
            </div>
          </div>

          <!-- Defaults -->
          <h3 class="mb-4 text-lg font-semibold text-gray-700 border-b pb-2">Asignacion por Defecto</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor por defecto</label>
              <select name="default_vendor_id" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input">
                <option value="">-- Seleccionar --</option>
                <?php foreach($vendors as $v): ?>
                <option value="<?= $v->idUser ?>" <?= ($bot_config && $bot_config->default_vendor_id == $v->idUser) ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?> (<?= $v->idUser ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tienda por defecto</label>
              <select name="default_store_id" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input">
                <?php foreach($stores as $s): ?>
                <option value="<?= $s->idStore ?>" <?= ($bot_config && $bot_config->default_store_id == $s->idStore) ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Google Sheets -->
          <h3 class="mb-4 text-lg font-semibold text-gray-700 border-b pb-2">Google Sheets</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sheet ID</label>
              <input type="text" name="sheet_id" value="<?= $bot_config ? htmlspecialchars($bot_config->sheet_id) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="ID del Google Sheet">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sheet GID</label>
              <input type="text" name="sheet_gid" value="<?= $bot_config ? htmlspecialchars($bot_config->sheet_gid) : '0' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="0">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Apps Script URL</label>
              <input type="text" name="script_url" value="<?= $bot_config ? htmlspecialchars($bot_config->script_url) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="https://script.google.com/macros/s/...">
            </div>
          </div>

          <!-- Webhook -->
          <h3 class="mb-4 text-lg font-semibold text-gray-700 border-b pb-2">Webhook</h3>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">URL del Webhook (configurar en BuilderBot)</label>
            <div class="flex items-center">
              <input type="text" value="<?= $webhook_url ?>" class="block w-full text-sm bg-gray-100 border-gray-300 rounded-md shadow-sm form-input" readonly id="webhookUrl">
              <button type="button" onclick="copyWebhookUrl()" class="ml-2 px-3 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300" title="Copiar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
              </button>
            </div>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret</label>
            <div class="flex items-center">
              <input type="text" name="webhook_secret" id="webhookSecret" value="<?= $bot_config ? htmlspecialchars($bot_config->webhook_secret) : '' ?>" class="block w-full text-sm border-gray-300 rounded-md shadow-sm form-input" placeholder="Secret para validar webhooks">
              <button type="button" onclick="generateSecret()" class="ml-2 px-3 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300 whitespace-no-wrap">Generar</button>
            </div>
          </div>

          <!-- Submit -->
          <div class="flex items-center justify-end space-x-3">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none">
              <?= $bot_config ? 'Actualizar' : 'Crear Bot' ?>
            </button>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
function copyWebhookUrl() {
  var el = document.getElementById('webhookUrl');
  el.select();
  document.execCommand('copy');
}
function generateSecret() {
  var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var result = 'wh_';
  for (var i = 0; i < 32; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
  document.getElementById('webhookSecret').value = result;
}
</script>
</body>
</html>
