<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Errores de Bots — MAM</title>
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
        <div class="flex items-center justify-between my-6 flex-wrap gap-3">
          <div class="flex items-center">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-4 text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
              <h2 class="text-2xl font-semibold text-gray-700">Errores de Bots</h2>
              <p class="text-xs text-gray-500 mt-1">Ventas y webhooks que fallaron — reintenta desde aquí.</p>
            </div>
          </div>
        </div>

        <!-- Stats últimos 7 días -->
        <?php if (!empty($stats)): ?>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
            <?php
              $stat_colors = array(
                'completed'          => array('emerald', '✓ Completados'),
                'failed'             => array('red',     '⚠ Fallidos'),
                'permanently_failed' => array('slate',   '✕ Perm. fallidos'),
                'processing'         => array('blue',    '⟳ Procesando'),
                'pending'            => array('amber',   '⏱ Pendientes'),
              );
              foreach ($stats as $s):
                $info = $stat_colors[$s->status] ?? array('gray', $s->status);
            ?>
              <div class="bg-white rounded-lg shadow-sm border border-<?= $info[0] ?>-200 p-3 text-center">
                <div class="text-2xl font-extrabold text-<?= $info[0] ?>-700"><?= (int)$s->cnt ?></div>
                <div class="text-[11px] text-<?= $info[0] ?>-600 font-semibold mt-1"><?= htmlspecialchars($info[1]) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Filtros -->
        <form method="get" class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Bot</label>
            <select name="bot" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
              <option value="">— Todos —</option>
              <?php foreach ($bots as $b): ?>
                <option value="<?= (int)$b->id ?>" <?= $bot_filter === (int)$b->id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($b->name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Estado</label>
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
              <option value="all"    <?= $status_filter === 'all'    ? 'selected' : '' ?>>Failed + Permanent</option>
              <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Solo failed (auto-retry activo)</option>
            </select>
          </div>
        </form>

        <!-- Queue errors -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Ventas en cola con error <span class="text-xs font-normal text-gray-500">(<?= count($queue_errors) ?>)</span></h3>
          </div>
          <?php if (empty($queue_errors)): ?>
            <div class="p-8 text-center text-gray-400 text-sm">
              <svg class="w-12 h-12 mx-auto mb-2 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <p class="text-emerald-600 font-semibold">Sin errores con el filtro actual.</p>
            </div>
          <?php else: ?>
            <table class="w-full whitespace-no-wrap text-sm">
              <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                  <th class="px-3 py-2">ID</th>
                  <th class="px-3 py-2">Fecha</th>
                  <th class="px-3 py-2">Bot</th>
                  <th class="px-3 py-2">Cliente</th>
                  <th class="px-3 py-2">Error</th>
                  <th class="px-3 py-2 text-center">Estado</th>
                  <th class="px-3 py-2 text-center">Acción</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <?php foreach ($queue_errors as $q): ?>
                  <?php
                    $payload = json_decode((string)$q->payload, true);
                    $client_label = '?';
                    if (is_array($payload)) {
                      $client_label = ($payload['nombre'] ?? '') . ' / ' . ($payload['celular'] ?? $payload['phone'] ?? '');
                      $client_label = trim(trim($client_label, ' /'));
                      if ($client_label === '') $client_label = '?';
                    }
                    $is_perm = $q->status === 'permanently_failed';
                  ?>
                  <tr class="<?= $is_perm ? 'bg-slate-50' : 'bg-red-50' ?> hover:bg-white">
                    <td class="px-3 py-2 text-xs text-gray-500">#<?= (int)$q->id ?></td>
                    <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap"><?= date('d/m H:i', strtotime($q->created_at)) ?></td>
                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars($q->bot_name ?? 'vendor=' . $q->vendor_id) ?></td>
                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars(mb_substr($client_label, 0, 40)) ?></td>
                    <td class="px-3 py-2 text-xs text-red-700 max-w-md">
                      <div class="truncate" title="<?= htmlspecialchars((string)$q->error_message) ?>">
                        <?= htmlspecialchars(mb_substr((string)$q->error_message, 0, 100)) ?>
                      </div>
                      <details class="text-[10px] text-gray-500 mt-1">
                        <summary class="cursor-pointer hover:text-gray-800">Ver payload</summary>
                        <pre class="mt-1 p-2 bg-gray-100 rounded overflow-x-auto max-h-40"><?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                      </details>
                    </td>
                    <td class="px-3 py-2 text-center">
                      <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $is_perm ? 'bg-slate-200 text-slate-700' : 'bg-red-200 text-red-800' ?>">
                        <?= htmlspecialchars($q->status) ?> · <?= (int)$q->attempts ?> intent.
                      </span>
                    </td>
                    <td class="px-3 py-2 text-center">
                      <button class="btn-retry-error px-2 py-1 text-[11px] font-semibold text-white bg-blue-600 rounded hover:bg-blue-700"
                              data-id="<?= (int)$q->id ?>">
                        Reintentar
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <!-- Webhook errors (informativo) -->
        <?php if (!empty($webhook_errors)): ?>
          <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
              <h3 class="font-semibold text-gray-700">Webhooks con error <span class="text-xs font-normal text-gray-500">(últimos 50)</span></h3>
            </div>
            <table class="w-full whitespace-no-wrap text-sm">
              <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                  <th class="px-3 py-2">Fecha</th>
                  <th class="px-3 py-2">Bot</th>
                  <th class="px-3 py-2">Tipo</th>
                  <th class="px-3 py-2">Error</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <?php foreach ($webhook_errors as $w): ?>
                  <tr class="text-xs">
                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap"><?= date('d/m H:i', strtotime($w->created_at)) ?></td>
                    <td class="px-3 py-2"><?= htmlspecialchars($w->bot_name ?? '?') ?></td>
                    <td class="px-3 py-2"><?= htmlspecialchars($w->event_type) ?></td>
                    <td class="px-3 py-2 text-red-700 truncate max-w-md" title="<?= htmlspecialchars((string)$w->error_message) ?>">
                      <?= htmlspecialchars(mb_substr((string)$w->error_message, 0, 120)) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>

<script>
$(document).on('click', '.btn-retry-error', function(){
  var $btn = $(this);
  var id = $btn.data('id');
  if (!confirm('¿Reintentar la venta #' + id + '?\n\nSe ejecutará process_webhook_sale con el payload guardado. Si tiene productos válidos y datos completos, debería convertirse en presupuesto.')) return;
  var orig = $btn.html();
  $btn.prop('disabled', true).html('...');

  $.ajax({
    url: base_url + 'sisvent/admin/bots/retryError',
    type: 'POST',
    dataType: 'json',
    data: { queue_id: id, '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
    success: function(res){
      if (res && res.ok) {
        alert('✓ Reintento exitoso. Budget #' + (res.budget_id || '?'));
        $btn.closest('tr').css({'opacity':'0.5','text-decoration':'line-through'});
        $btn.html('OK ✓').removeClass('bg-blue-600 hover:bg-blue-700').addClass('bg-emerald-600');
      } else {
        alert('Reintento falló: ' + (res && res.error ? res.error : 'desconocido'));
        $btn.prop('disabled', false).html(orig);
      }
    },
    error: function(xhr){
      alert('Error de conexión (HTTP ' + xhr.status + ')');
      $btn.prop('disabled', false).html(orig);
    }
  });
});
</script>

<?php $this->load->view('sisvent/layouts/script_footer'); ?>
</body>
</html>
