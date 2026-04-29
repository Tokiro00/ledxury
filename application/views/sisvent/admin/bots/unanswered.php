<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Sin responder — Bots — MAM</title>
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
              <h2 class="text-2xl font-semibold text-gray-700">Conversaciones sin responder</h2>
              <p class="text-xs text-gray-500 mt-1">Cliente preguntó algo y nadie respondió en más de <?= $minutes ?> minutos.</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 text-sm font-bold rounded-full bg-red-100 text-red-700"><?= count($conversations) ?> sin responder</span>
            <button onclick="location.reload()" class="px-3 py-1.5 text-xs text-blue-600 border border-blue-200 bg-blue-50 rounded hover:bg-blue-100">↻ Refrescar</button>
          </div>
        </div>

        <!-- Filtros -->
        <form method="get" class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Bot</label>
            <select name="bot" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
              <option value="">— Todos —</option>
              <?php foreach ($bots as $b): ?>
                <option value="<?= (int)$b->id ?>" <?= ($selected_bot === (int)$b->id) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Sin responder hace al menos</label>
            <select name="minutes" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
              <?php foreach ([5, 10, 15, 30, 60, 120] as $m): ?>
                <option value="<?= $m ?>" <?= $minutes === $m ? 'selected' : '' ?>><?= $m ?> min</option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <!-- Lista -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <?php if (empty($conversations)): ?>
            <div class="p-10 text-center text-gray-400">
              <svg class="w-16 h-16 mx-auto mb-3 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <p class="font-semibold text-emerald-600">¡Todo al día!</p>
              <p class="text-sm mt-1">No hay conversaciones sin responder con el filtro actual.</p>
            </div>
          <?php else: ?>
            <table class="w-full whitespace-no-wrap text-sm">
              <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                  <th class="px-4 py-3">Cliente</th>
                  <th class="px-4 py-3">Bot</th>
                  <th class="px-4 py-3">Último mensaje</th>
                  <th class="px-4 py-3 text-right">Sin responder</th>
                  <th class="px-4 py-3 text-center">Acción</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <?php foreach ($conversations as $c): ?>
                  <?php
                    $mins = (int)$c->minutos_sin_responder;
                    if ($mins >= 60) {
                      $bgClass = 'bg-red-50'; $txtClass = 'text-red-700'; $label = floor($mins/60) . 'h ' . ($mins % 60) . 'min';
                    } elseif ($mins >= 30) {
                      $bgClass = 'bg-orange-50'; $txtClass = 'text-orange-700'; $label = $mins . ' min';
                    } else {
                      $bgClass = 'bg-amber-50'; $txtClass = 'text-amber-700'; $label = $mins . ' min';
                    }
                  ?>
                  <tr class="<?= $bgClass ?> hover:bg-white">
                    <td class="px-4 py-3">
                      <div class="font-semibold text-gray-800"><?= htmlspecialchars($c->client_name ?: $c->phone) ?></div>
                      <div class="text-xs text-gray-500"><?= htmlspecialchars($c->phone) ?></div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600"><?= htmlspecialchars($c->bot_name ?? '?') ?></td>
                    <td class="px-4 py-3 text-xs text-gray-700 max-w-md">
                      <div class="truncate"><?= htmlspecialchars(mb_substr((string)$c->last_message, 0, 120)) ?></div>
                      <div class="text-[10px] text-gray-400 mt-0.5"><?= date('d/m H:i', strtotime($c->last_message_at)) ?></div>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <span class="px-2 py-1 text-xs font-bold rounded-full <?= $bgClass ?> <?= $txtClass ?> border border-current">
                        <?= $label ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <a href="<?= base_url() ?>sisvent/admin/bots/messages/<?= (int)$c->bot_config_id ?>?conv=<?= (int)$c->id ?>"
                         class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white bg-emerald-600 rounded hover:bg-emerald-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Abrir chat
                      </a>
                      <a href="https://wa.me/57<?= preg_replace('/[^0-9]/', '', $c->phone) ?>"
                         target="_blank" rel="noopener"
                         class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-emerald-700 bg-emerald-100 rounded hover:bg-emerald-200 ml-1">
                        WhatsApp
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <!-- Auto-refresh badge in console for owner curious -->
        <p class="text-[11px] text-gray-400 mb-4">
          La página se refresca manualmente. Para badge en navbar consulta el endpoint <code>/sisvent/admin/bots/unansweredCount</code>.
        </p>

      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/script_footer'); ?>
</body>
</html>
