<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Ventas Bot <?= htmlspecialchars($bot_config->name) ?> - MAM</title>
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
        <div class="flex items-center my-6">
          <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-4 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </a>
          <h2 class="text-2xl font-semibold text-gray-700">Ventas - <?= htmlspecialchars($bot_config->name) ?></h2>
        </div>

        <!-- Stats -->
        <?php if($stats): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="p-4 bg-white rounded-lg shadow">
            <p class="text-sm text-gray-600">Total Registros</p>
            <p class="text-2xl font-bold text-gray-700"><?= $stats->total ?: 0 ?></p>
          </div>
          <div class="p-4 bg-white rounded-lg shadow">
            <p class="text-sm text-gray-600">Exitosas</p>
            <p class="text-2xl font-bold text-green-600"><?= $stats->exitosas ?: 0 ?></p>
          </div>
          <div class="p-4 bg-white rounded-lg shadow">
            <p class="text-sm text-gray-600">Fallidas</p>
            <p class="text-2xl font-bold text-red-600"><?= $stats->fallidas ?: 0 ?></p>
          </div>
        </div>
        <?php endif; ?>

        <!-- Sales Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
              <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Fecha</th>
                  <th class="px-4 py-3">Estado Webhook</th>
                  <th class="px-4 py-3">Estado Cola</th>
                  <th class="px-4 py-3">Presupuesto</th>
                  <th class="px-4 py-3">Vendedor</th>
                  <th class="px-4 py-3">Error</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y">
                <?php if(empty($sales)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay ventas registradas para este bot.</td></tr>
                <?php else: ?>
                <?php foreach($sales as $sale): ?>
                <tr class="text-gray-700 hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm"><?= $sale->id ?></td>
                  <td class="px-4 py-3 text-sm"><?= date('d/m/Y H:i', strtotime($sale->created_at)) ?></td>
                  <td class="px-4 py-3 text-sm">
                    <?php
                      $wh_class = 'bg-gray-100 text-gray-800';
                      if ($sale->status === 'processed') $wh_class = 'bg-green-100 text-green-800';
                      elseif ($sale->status === 'failed') $wh_class = 'bg-red-100 text-red-800';
                      elseif ($sale->status === 'received') $wh_class = 'bg-yellow-100 text-yellow-800';
                      elseif ($sale->status === 'transformed') $wh_class = 'bg-blue-100 text-blue-800';
                    ?>
                    <span class="px-2 py-1 text-xs font-semibold leading-tight rounded-full <?= $wh_class ?>"><?= ucfirst($sale->status) ?></span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <?php if(isset($sale->queue_status)): ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $sale->queue_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                      <?= ucfirst($sale->queue_status ?: '-') ?>
                    </span>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <?php if($sale->budget_id): ?>
                    <a href="<?= base_url() ?>sisvent/commercial/budgets/view/<?= $sale->budget_id ?>" class="text-blue-600 hover:underline font-medium">#<?= $sale->budget_id ?></a>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-sm"><?= htmlspecialchars($sale->vendor_id ?: '-') ?></td>
                  <td class="px-4 py-3 text-sm">
                    <?php if($sale->error_message): ?>
                    <span class="text-red-500 text-xs" title="<?= htmlspecialchars($sale->error_message) ?>"><?= htmlspecialchars(substr($sale->error_message, 0, 60)) ?>...</span>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
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
</body>
</html>
