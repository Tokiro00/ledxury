<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Reporte Efectividad Bots - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.funnel-bar { height: 8px; border-radius: 4px; transition: width 0.6s ease; }
.kpi-card { border-left: 4px solid; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="container px-6 mx-auto">

        <!-- Header -->
        <div class="flex items-center justify-between mt-6 mb-2">
          <div class="flex items-center">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="mr-3 text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-700">Reporte de Efectividad</h2>
          </div>
        </div>

        <!-- Filtros -->
        <!-- Filtro por mes (quick) -->
        <div class="flex flex-wrap gap-2 mb-3">
          <?php
            $meses = array('Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic');
            $current_year = date('Y');
            for ($m = 1; $m <= 12; $m++):
              $m_from = sprintf('%s-%02d-01', $current_year, $m);
              $m_to = date('Y-m-t', strtotime($m_from));
              $is_active = ($from === $m_from && $to === $m_to);
              if (strtotime($m_from) > time()) continue;
          ?>
          <a href="<?= base_url() ?>sisvent/admin/bots/report/<?= $selected_bot ?>?from=<?= $m_from ?>&to=<?= $m_to ?>"
             class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors <?= $is_active ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 bg-white hover:bg-gray-100' ?>"
             <?= $is_active ? 'style="background: linear-gradient(135deg, #E63946, #c1121f);"' : '' ?>>
            <?= $meses[$m-1] ?>
          </a>
          <?php endfor; ?>
          <?php
            $is_all = ($from === date('Y-01-01') && $to === date('Y-12-31'));
          ?>
          <a href="<?= base_url() ?>sisvent/admin/bots/report/<?= $selected_bot ?>?from=<?= date('Y-01-01') ?>&to=<?= date('Y-12-31') ?>"
             class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors <?= $is_all ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 bg-white hover:bg-gray-100' ?>"
             <?= $is_all ? 'style="background: linear-gradient(135deg, #E63946, #c1121f);"' : '' ?>>
            <?= $current_year ?>
          </a>
        </div>

        <!-- Filtro avanzado -->
        <form class="flex flex-wrap items-end gap-3 mb-6 p-4 bg-white rounded-lg shadow-sm border border-gray-200" method="GET">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
            <input type="date" name="from" value="<?= $from ?>" class="text-sm border-gray-300 rounded-md shadow-sm form-input">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
            <input type="date" name="to" value="<?= $to ?>" class="text-sm border-gray-300 rounded-md shadow-sm form-input">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Bot</label>
            <select name="bot" onchange="this.form.action='<?= base_url() ?>sisvent/admin/bots/report/'+this.value; this.form.submit();" class="text-sm border-gray-300 rounded-md shadow-sm form-input">
              <option value="0" <?= $selected_bot == '0' ? 'selected' : '' ?>>Todos los bots</option>
              <?php foreach($all_configs as $c): ?>
              <option value="<?= $c->id ?>" <?= $selected_bot == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none" style="background: linear-gradient(135deg, #E63946, #c1121f);">
            Filtrar
          </button>
        </form>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div class="kpi-card bg-white rounded-lg shadow-sm p-5" style="border-color: #3B82F6;">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Facturas</p>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?= $totals['ventas_bot'] ?></p>
            <p class="text-sm text-gray-500 mt-1">$<?= number_format($totals['total_ventas'], 0, ',', '.') ?></p>
          </div>
          <div class="kpi-card bg-white rounded-lg shadow-sm p-5" style="border-color: #10B981;">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pagadas</p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?= $totals['facturas'] ?></p>
            <p class="text-sm text-gray-500 mt-1">$<?= number_format($totals['total_recaudado'], 0, ',', '.') ?></p>
            <p class="text-xs mt-1 <?= $totals['efectividad'] >= 50 ? 'text-green-600' : 'text-red-600' ?>"><?= $totals['efectividad'] ?>% recaudo</p>
          </div>
          <div class="kpi-card bg-white rounded-lg shadow-sm p-5" style="border-color: #F59E0B;">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Envios</p>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?= $totals['envios'] ?></p>
            <p class="text-sm text-gray-500 mt-1">Flete: $<?= number_format($totals['costo_flete'], 0, ',', '.') ?></p>
          </div>
          <div class="kpi-card bg-white rounded-lg shadow-sm p-5" style="border-color: #EF4444;">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Costo Envios</p>
            <p class="text-3xl font-bold text-red-500 mt-1">$<?= number_format($totals['costo_flete'], 0, ',', '.') ?></p>
            <?php $pct_flete = $totals['total_ventas'] > 0 ? round(($totals['costo_flete'] / $totals['total_ventas']) * 100, 1) : 0; ?>
            <p class="text-xs text-gray-500 mt-1"><?= $pct_flete ?>% del facturado</p>
          </div>
          <div class="kpi-card bg-white rounded-lg shadow-sm p-5" style="border-color: <?= $totals['margen_neto'] >= 0 ? '#10B981' : '#EF4444' ?>;">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Margen Neto</p>
            <p class="text-3xl font-bold <?= $totals['margen_neto'] >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1">$<?= number_format($totals['margen_neto'], 0, ',', '.') ?></p>
            <p class="text-sm text-gray-500 mt-1">Recaudado - Fletes</p>
          </div>
        </div>

        <!-- Funnel Visual -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
          <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Embudo de Conversion</h3>
          <?php
            $w_ventas = 100;
            $w_pagadas = $totals['ventas_bot'] > 0 ? round(($totals['facturas'] / $totals['ventas_bot']) * 100) : 0;
            $w_envios = $totals['ventas_bot'] > 0 ? round(($totals['envios'] / $totals['ventas_bot']) * 100) : 0;
          ?>
          <div class="space-y-3">
            <div>
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Facturas</span><span class="font-bold"><?= $totals['ventas_bot'] ?> — $<?= number_format($totals['total_ventas'], 0, ',', '.') ?></span>
              </div>
              <div class="w-full bg-gray-100 rounded-full"><div class="funnel-bar bg-blue-500" style="width:<?= $w_ventas ?>%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Pagadas</span><span class="font-bold"><?= $totals['facturas'] ?> (<?= $totals['efectividad'] ?>% recaudo)</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full"><div class="funnel-bar bg-green-500" style="width:<?= $w_pagadas ?>%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Enviadas</span><span class="font-bold"><?= $totals['envios'] ?> — Flete: $<?= number_format($totals['costo_flete'], 0, ',', '.') ?></span>
              </div>
              <div class="w-full bg-gray-100 rounded-full"><div class="funnel-bar bg-yellow-500" style="width:<?= $w_envios ?>%"></div></div>
            </div>
          </div>
        </div>

        <!-- Tabla por Bot -->
        <?php if(count($bot_reports) > 1): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Detalle por Bot</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="text-xs font-medium text-gray-400 uppercase tracking-wider border-b">
                  <th class="px-5 py-3 text-left">Bot</th>
                  <th class="px-3 py-3 text-right">Facturas</th>
                  <th class="px-3 py-3 text-right">$ Facturado</th>
                  <th class="px-3 py-3 text-right">Pagadas</th>
                  <th class="px-3 py-3 text-right">$ Recaudado</th>
                  <th class="px-3 py-3 text-center">% Recaudo</th>
                  <th class="px-3 py-3 text-right">Envios</th>
                  <th class="px-3 py-3 text-right">$ Flete</th>
                  <th class="px-3 py-3 text-right">Margen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach($bot_reports as $br): $d = $br['data']; $c = $br['config']; ?>
                <tr class="text-sm text-gray-700 hover:bg-gray-50">
                  <td class="px-5 py-3 font-medium"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="px-3 py-3 text-right"><?= $d['ventas_bot'] ?></td>
                  <td class="px-3 py-3 text-right">$<?= number_format($d['total_ventas'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right font-medium text-green-600"><?= $d['facturas'] ?></td>
                  <td class="px-3 py-3 text-right font-medium text-green-600">$<?= number_format($d['total_recaudado'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-center">
                    <span class="px-1.5 py-0.5 text-xs font-medium rounded <?= $d['efectividad'] >= 50 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $d['efectividad'] ?>%</span>
                  </td>
                  <td class="px-3 py-3 text-right"><?= $d['envios'] ?></td>
                  <td class="px-3 py-3 text-right text-red-500">$<?= number_format($d['costo_flete'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right font-bold <?= $d['margen_neto'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">$<?= number_format($d['margen_neto'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Single Bot Detail -->
        <?php if(count($bot_reports) == 1): $d = $bot_reports[0]['data']; $c = $bot_reports[0]['config']; ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
          <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Detalle — <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-xs text-gray-400">Facturas</p>
              <p class="text-lg font-bold text-gray-700"><?= $d['ventas_bot'] ?></p>
              <p class="text-xs text-gray-500">$<?= number_format($d['total_ventas'], 0, ',', '.') ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-xs text-gray-400">Pagadas</p>
              <p class="text-lg font-bold text-green-600"><?= $d['facturas'] ?></p>
              <p class="text-xs text-gray-500">$<?= number_format($d['total_recaudado'], 0, ',', '.') ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-xs text-gray-400">Envios</p>
              <p class="text-lg font-bold text-gray-700"><?= $d['envios'] ?></p>
              <p class="text-xs text-gray-500">Guias creadas</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-xs text-gray-400">Costo Fletes</p>
              <p class="text-lg font-bold text-red-500">$<?= number_format($d['costo_flete'], 0, ',', '.') ?></p>
              <p class="text-xs text-gray-500">Margen: $<?= number_format($d['margen_neto'], 0, ',', '.') ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Tabla Comparativa Mensual -->
        <?php if(!empty($monthly)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Comparativa Mensual (ultimos 6 meses)</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="text-xs font-medium text-gray-400 uppercase tracking-wider border-b">
                  <th class="px-5 py-3 text-left">Mes</th>
                  <th class="px-3 py-3 text-right">Facturas</th>
                  <th class="px-3 py-3 text-right">$ Facturado</th>
                  <th class="px-3 py-3 text-right">Pagadas</th>
                  <th class="px-3 py-3 text-right">$ Recaudado</th>
                  <th class="px-3 py-3 text-center">% Recaudo</th>
                  <th class="px-3 py-3 text-right">Envios</th>
                  <th class="px-3 py-3 text-right">$ Flete</th>
                  <th class="px-3 py-3 text-right">Margen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach($monthly as $m): $d = $m['data']; ?>
                <tr class="text-sm text-gray-700 hover:bg-gray-50">
                  <td class="px-5 py-3 font-medium"><?= $m['label'] ?></td>
                  <td class="px-3 py-3 text-right"><?= $d['ventas_bot'] ?></td>
                  <td class="px-3 py-3 text-right">$<?= number_format($d['total_ventas'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right font-medium text-green-600"><?= $d['facturas'] ?></td>
                  <td class="px-3 py-3 text-right font-medium text-green-600">$<?= number_format($d['total_recaudado'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-center">
                    <span class="px-1.5 py-0.5 text-xs font-medium rounded <?= $d['efectividad'] >= 50 ? 'bg-green-100 text-green-700' : ($d['ventas_bot'] > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') ?>"><?= $d['efectividad'] ?>%</span>
                  </td>
                  <td class="px-3 py-3 text-right"><?= $d['envios'] ?></td>
                  <td class="px-3 py-3 text-right text-red-500">$<?= number_format($d['costo_flete'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right font-bold <?= $d['margen_neto'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">$<?= number_format($d['margen_neto'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Advertencia -->
        <div class="p-3 mb-6 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-700">
          <strong>Nota:</strong> Los costos de flete anteriores a abril 2026 no estan disponibles. Los envios se gestionaban fuera del sistema. A partir del 7 de abril de 2026 todos los envios se registran via Interrapidisimo y los fletes se calculan automaticamente.
        </div>

        <!-- Periodo -->
        <p class="text-xs text-gray-400 text-center mb-6">Periodo seleccionado: <?= date('d/m/Y', strtotime($from)) ?> — <?= date('d/m/Y', strtotime($to)) ?></p>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
