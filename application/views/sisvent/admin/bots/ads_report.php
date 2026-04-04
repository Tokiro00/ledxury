<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Campanas Meta Ads - Ledxury</title>
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
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/></svg>
            <h2 class="text-xl font-semibold text-gray-700">Campanas Meta Ads</h2>
          </div>
          <a href="<?= base_url() ?>sisvent/admin/bots" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Dashboard
          </a>
        </div>

        <?php if (!empty($api_error)): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
          <strong>Error API Meta:</strong> <?= htmlspecialchars($api_error) ?>
          <p class="mt-1 text-xs">Si el token expiro, genera uno nuevo en developers.facebook.com/tools/explorer con permiso ads_read</p>
        </div>
        <?php endif; ?>

        <!-- Filtro de fechas -->
        <form method="GET" class="flex items-end space-x-3 mb-6 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
            <input type="date" name="from" value="<?= $from ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
            <input type="date" name="to" value="<?= $to ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none">
            Consultar
          </button>
        </form>

        <!-- KPI Cards -->
        <div class="grid gap-4 mb-6 md:grid-cols-4">
          <!-- Inversion Total -->
          <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
              <div class="p-3 mr-4 text-red-500 bg-red-100 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500">Inversion Total</p>
                <p class="text-lg font-bold text-gray-700">$<?= number_format($totals['spend'], 0, ',', '.') ?></p>
              </div>
            </div>
          </div>
          <!-- Conversaciones -->
          <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
              <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500">Conversaciones</p>
                <p class="text-lg font-bold text-gray-700"><?= number_format($totals['conversations'], 0, ',', '.') ?></p>
              </div>
            </div>
          </div>
          <!-- Impresiones -->
          <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
              <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500">Impresiones</p>
                <p class="text-lg font-bold text-gray-700"><?= number_format($totals['impressions'], 0, ',', '.') ?></p>
              </div>
            </div>
          </div>
          <!-- Costo por Conversacion -->
          <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center">
              <div class="p-3 mr-4 text-purple-500 bg-purple-100 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500">Costo / Conversacion</p>
                <p class="text-lg font-bold text-gray-700">$<?= number_format($totals['cost_per_conv'], 0, ',', '.') ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Grafica de barras CSS -->
        <?php if (!empty($report)):
          $maxSpend = max(array_map(function($r){ return (float)$r['spend']; }, $report)) ?: 1;
          $maxConv = max(array_map(function($r){ return (int)$r['conversations']; }, $report)) ?: 1;
        ?>
        <div class="mb-6 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
          <h3 class="text-sm font-semibold text-gray-600 mb-3">Inversion vs Conversaciones por Campana</h3>
          <div class="flex items-end justify-between" style="gap:12px; min-height:200px;">
            <?php foreach ($report as $r):
              $spendPct = round(((float)$r['spend'] / $maxSpend) * 100);
              $convPct = round(((int)$r['conversations'] / $maxConv) * 100);
              $shortName = mb_strlen($r['name']) > 18 ? mb_substr($r['name'], 0, 15) . '...' : $r['name'];
            ?>
            <div class="flex-1 text-center">
              <div class="flex items-end justify-center" style="gap:4px; height:180px;">
                <div style="width:40%; min-width:16px; height:<?= max($spendPct, 3) ?>%; background:rgba(239,68,68,0.7); border-radius:4px 4px 0 0;" title="Inversion: $<?= number_format($r['spend'], 0, ',', '.') ?>"></div>
                <div style="width:40%; min-width:16px; height:<?= max($convPct, 3) ?>%; background:rgba(34,197,94,0.7); border-radius:4px 4px 0 0;" title="Conversaciones: <?= number_format($r['conversations'], 0, ',', '.') ?>"></div>
              </div>
              <p class="mt-2 text-xs text-gray-500 truncate" title="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($shortName) ?></p>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="flex items-center justify-center mt-3 space-x-4 text-xs text-gray-500">
            <span class="inline-flex items-center"><span class="w-3 h-3 rounded mr-1" style="background:rgba(239,68,68,0.7)"></span> Inversion</span>
            <span class="inline-flex items-center"><span class="w-3 h-3 rounded mr-1" style="background:rgba(34,197,94,0.7)"></span> Conversaciones</span>
          </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de campanas -->
        <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-600">Detalle por Campana</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs font-medium text-gray-500 uppercase bg-gray-50">
                  <th class="px-4 py-3 text-left">Campana</th>
                  <th class="px-3 py-3 text-center">Estado</th>
                  <th class="px-3 py-3 text-right">Inversion</th>
                  <th class="px-3 py-3 text-right">Impresiones</th>
                  <th class="px-3 py-3 text-right">Clics</th>
                  <th class="px-3 py-3 text-right">CTR</th>
                  <th class="px-3 py-3 text-right">CPC</th>
                  <th class="px-3 py-3 text-right">Conversaciones</th>
                  <th class="px-3 py-3 text-right">Costo/Conv</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($report as $r): ?>
                <tr class="hover:bg-gray-50" data-id="<?= $r['id'] ?>">
                  <td class="px-4 py-3 font-medium text-gray-700"><?= htmlspecialchars($r['name']) ?></td>
                  <td class="px-3 py-3 text-center">
                    <?php
                      $statusClass = 'bg-gray-100 text-gray-600';
                      $statusLabel = $r['status'];
                      if ($r['status'] === 'ACTIVE') { $statusClass = 'bg-green-100 text-green-700'; $statusLabel = 'Activa'; }
                      elseif ($r['status'] === 'PAUSED') { $statusClass = 'bg-yellow-100 text-yellow-700'; $statusLabel = 'Pausada'; }
                    ?>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $statusLabel ?></span>
                  </td>
                  <td class="px-3 py-3 text-right font-medium">$<?= number_format($r['spend'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($r['impressions'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($r['clicks'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($r['ctr'], 2) ?>%</td>
                  <td class="px-3 py-3 text-right">$<?= number_format($r['cpc'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right font-semibold text-green-600"><?= number_format($r['conversations'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= $r['cost_per_conv'] ? '$' . number_format($r['cost_per_conv'], 0, ',', '.') : '-' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="bg-gray-50 font-semibold text-gray-700">
                  <td class="px-4 py-3">TOTAL</td>
                  <td></td>
                  <td class="px-3 py-3 text-right">$<?= number_format($totals['spend'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['impressions'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['clicks'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['ctr'], 2) ?>%</td>
                  <td class="px-3 py-3 text-right">-</td>
                  <td class="px-3 py-3 text-right text-green-600"><?= number_format($totals['conversations'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right">$<?= number_format($totals['cost_per_conv'], 0, ',', '.') ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
// AI menu toggle (normalmente en footer.php)
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});

</script>
</body>
</html>
