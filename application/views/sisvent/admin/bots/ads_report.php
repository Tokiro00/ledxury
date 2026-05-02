<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Campañas Meta Ads - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  .kpi-hero { background: linear-gradient(135deg, #fff 0%, #f8fafc 100%); }
  .kpi-hero .kpi-value { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
  .kpi-hero .kpi-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; font-weight: 600; }
  .delta-up { color: #16a34a; }
  .delta-down { color: #dc2626; }
  .delta-neutral { color: #94a3b8; }
  .chip-period { padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; border: 1px solid #e2e8f0; background: #fff; color: #475569; }
  .chip-period:hover { background: #f1f5f9; }
  .chip-period.active { background: #1e40af; color: #fff; border-color: #1e40af; }
  .funnel-bar { height: 32px; border-radius: 6px; display: flex; align-items: center; padding: 0 12px; color: #fff; font-weight: 600; font-size: 12px; transition: all .3s; }
  .funnel-step { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
  .funnel-step-label { width: 130px; font-size: 12px; color: #475569; font-weight: 600; }
  .funnel-step-value { width: 110px; text-align: right; font-size: 13px; font-weight: 700; color: #1e293b; }
  .funnel-conn { font-size: 10px; color: #64748b; padding: 2px 0 4px 142px; }
  .perf-card { padding: 14px; border-radius: 10px; }
  .perf-card.top { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #6ee7b7; }
  .perf-card.worst { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 1px solid #fca5a5; }
  table.tbl-pro { font-size: 13px; }
  table.tbl-pro thead th { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; background: #f8fafc; padding: 10px 12px; cursor: pointer; user-select: none; border-bottom: 2px solid #e2e8f0; }
  table.tbl-pro thead th:hover { background: #eef2ff; color: #1e40af; }
  table.tbl-pro thead th.sorted::after { content: ' ▾'; color: #1e40af; }
  table.tbl-pro thead th.sorted-asc::after { content: ' ▴'; color: #1e40af; }
  table.tbl-pro tbody td { padding: 12px; vertical-align: middle; }
  table.tbl-pro tbody tr { transition: background .1s; }
  table.tbl-pro tbody tr:hover { background: #f8fafc; }
  .roi-bar { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; margin-top: 4px; }
  .roi-bar > span { display: block; height: 100%; }
  .pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .pill-active { background: #dcfce7; color: #15803d; }
  .pill-paused { background: #fef9c3; color: #854d0e; }
  .pill-other { background: #f1f5f9; color: #475569; }
  .city-medellin { background: #dbeafe; color: #1d4ed8; }
  .city-bogota { background: #ede9fe; color: #6d28d9; }
  .city-barranquilla { background: #ffedd5; color: #c2410c; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="px-6 py-5 mx-auto max-w-screen-2xl">

        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
          <div>
            <div class="flex items-center gap-2">
              <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/></svg>
              <h2 class="text-xl font-bold text-gray-800">Campañas Meta Ads</h2>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">
              <?= date('d M Y', strtotime($from)) ?> &mdash; <?= date('d M Y', strtotime($to)) ?>
              <span class="text-gray-400">· vs período anterior <?= date('d M', strtotime($prev_from)) ?> &mdash; <?= date('d M', strtotime($prev_to)) ?></span>
            </p>
          </div>
          <a href="<?= base_url() ?>sisvent/admin/bots" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            ← Bots
          </a>
        </div>

        <?php if (!empty($api_error)): ?>
        <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
          <strong>Error API Meta:</strong> <?= htmlspecialchars($api_error) ?>
        </div>
        <?php endif; ?>

        <!-- Filtros rápidos + custom -->
        <div class="flex flex-wrap items-center gap-2 mb-5 p-3 bg-white rounded-lg border border-gray-200">
          <?php
            $now = date('Y-m-d');
            $today = date('Y-m-d');
            $yest  = date('Y-m-d', strtotime('-1 day'));
            $monthStart = date('Y-m-01');
            $periods = array(
              ['label' => 'Hoy',     'from' => $today, 'to' => $today],
              ['label' => 'Ayer',    'from' => $yest,  'to' => $yest],
              ['label' => '7 días',  'from' => date('Y-m-d', strtotime('-6 days')),  'to' => $today],
              ['label' => '14 días', 'from' => date('Y-m-d', strtotime('-13 days')), 'to' => $today],
              ['label' => '30 días', 'from' => date('Y-m-d', strtotime('-29 days')), 'to' => $today],
              ['label' => 'Este mes', 'from' => $monthStart, 'to' => $today],
            );
          ?>
          <?php foreach ($periods as $p):
            $active = ($from === $p['from'] && $to === $p['to']);
          ?>
          <a href="?from=<?= $p['from'] ?>&to=<?= $p['to'] ?>" class="chip-period <?= $active ? 'active' : '' ?>"><?= $p['label'] ?></a>
          <?php endforeach; ?>
          <form method="GET" class="flex items-center gap-2 ml-auto">
            <input type="date" name="from" value="<?= $from ?>" class="px-2 py-1 text-xs border border-gray-300 rounded">
            <span class="text-xs text-gray-400">→</span>
            <input type="date" name="to" value="<?= $to ?>" class="px-2 py-1 text-xs border border-gray-300 rounded">
            <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Aplicar</button>
          </form>
        </div>

        <!-- HERO: 3 KPIs grandes con comparación -->
        <?php
          $renderDelta = function($delta, $invert = false) {
            if ($delta == 0) return '<span class="delta-neutral text-xs font-semibold">—</span>';
            $isUp = $delta > 0;
            $cls = $invert ? ($isUp ? 'delta-down' : 'delta-up') : ($isUp ? 'delta-up' : 'delta-down');
            $arrow = $isUp ? '↑' : '↓';
            return '<span class="' . $cls . ' text-xs font-semibold">' . $arrow . ' ' . abs($delta) . '%</span>';
          };
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <!-- Inversión -->
          <div class="kpi-hero p-5 rounded-xl border border-gray-200 shadow-sm">
            <div class="kpi-label">Inversión Total</div>
            <div class="kpi-value text-gray-800 mt-1">$<?= number_format($totals['spend'], 0, ',', '.') ?></div>
            <div class="flex items-center gap-2 mt-2">
              <?= $renderDelta($compare['spend']['delta']) ?>
              <span class="text-[11px] text-gray-400">vs $<?= number_format($compare['spend']['prev'], 0, ',', '.') ?></span>
            </div>
          </div>
          <!-- Ventas -->
          <div class="kpi-hero p-5 rounded-xl border border-gray-200 shadow-sm">
            <div class="kpi-label">Ventas Generadas</div>
            <div class="kpi-value text-gray-800 mt-1">$<?= number_format($totals['ventas'] ?? 0, 0, ',', '.') ?></div>
            <div class="flex items-center gap-2 mt-2">
              <span class="text-[11px] text-gray-500"><?= number_format($totals['pedidos'] ?? 0, 0, ',', '.') ?> pedidos · ROAS <?= $totals['roas'] ?? 0 ?>x</span>
            </div>
          </div>
          <!-- ROI -->
          <div class="kpi-hero p-5 rounded-xl border <?= ($totals['roi_real'] ?? 0) >= 0 ? 'border-green-200' : 'border-red-200' ?> shadow-sm">
            <div class="kpi-label">ROI Real (sobre facturado)</div>
            <div class="kpi-value mt-1 <?= ($totals['roi_real'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $totals['roi_real'] ?? 0 ?>%</div>
            <div class="flex items-center gap-2 mt-2">
              <span class="text-[11px] text-gray-500"><?= number_format($totals['facturas'] ?? 0,0,',','.') ?> facturas · $<?= number_format($totals['facturado'] ?? 0,0,',','.') ?> · ROAS <?= $totals['roas_real'] ?? 0 ?>x</span>
            </div>
          </div>
        </div>

        <!-- POR BOT: gasto vs facturado real -->
        <?php if (!empty($per_bot)): ?>
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-gray-700">Desempeño por bot — gasto Meta vs facturas reales</h3>
            <span class="text-[11px] text-gray-400">Margen bruto 52.7%</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <?php foreach ($per_bot as $pb): ?>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
              <div class="flex items-start justify-between mb-3">
                <div class="min-w-0">
                  <div class="text-sm font-bold text-gray-800 truncate" title="<?= htmlspecialchars($pb['bot_name']) ?>">🤖 <?= htmlspecialchars($pb['bot_name']) ?></div>
                  <div class="text-[11px] text-gray-500"><?= $pb['campaigns_count'] ?> campañas · vendor <?= htmlspecialchars($pb['vendor_id']) ?></div>
                </div>
                <div class="text-right ml-2 flex-shrink-0">
                  <div class="text-xl font-bold <?= $pb['roi_real'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $pb['roi_real'] ?>%</div>
                  <div class="text-[10px] text-gray-400">ROI real</div>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2 text-[11px]">
                <div class="p-2 bg-red-50 border border-red-100 rounded">
                  <div class="text-gray-500 uppercase tracking-wide text-[10px] font-semibold">Inversión</div>
                  <div class="font-bold text-gray-800 text-sm">$<?= number_format($pb['spend'],0,',','.') ?></div>
                </div>
                <div class="p-2 bg-green-50 border border-green-100 rounded">
                  <div class="text-gray-500 uppercase tracking-wide text-[10px] font-semibold">Facturado</div>
                  <div class="font-bold text-gray-800 text-sm">$<?= number_format($pb['invoices_total'],0,',','.') ?></div>
                </div>
                <div class="p-2 bg-blue-50 border border-blue-100 rounded">
                  <div class="text-gray-500 uppercase tracking-wide text-[10px] font-semibold">Cotizados</div>
                  <div class="font-bold text-gray-800 text-sm"><?= $pb['budgets_count'] ?> · $<?= number_format($pb['budgets_total'],0,',','.') ?></div>
                </div>
                <div class="p-2 bg-purple-50 border border-purple-100 rounded">
                  <div class="text-gray-500 uppercase tracking-wide text-[10px] font-semibold">Facturas</div>
                  <div class="font-bold text-gray-800 text-sm"><?= $pb['invoices_count'] ?></div>
                </div>
              </div>
              <div class="mt-2 pt-2 border-t border-gray-100 flex items-center gap-3 text-[11px] text-gray-500">
                <span>ROAS <b class="text-gray-700"><?= $pb['roas_real'] ?>x</b></span>
                <span>Cierre <b class="text-gray-700"><?= $pb['conv_rate'] ?>%</b></span>
                <?php if ($pb['cost_per_invoice'] > 0): ?>
                <span>$/factura <b class="text-gray-700">$<?= number_format($pb['cost_per_invoice'],0,',','.') ?></b></span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Embudo + Top/Bottom -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
          <!-- Embudo -->
          <div class="lg:col-span-2 p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Embudo de Conversión</h3>
            <?php
              $impr = max(1, $funnel['impressions']);
              $widths = array(
                'impressions'  => 100,
                'clicks'       => $funnel['impressions'] > 0 ? min(100, ($funnel['clicks'] / $impr) * 100 * 80 + 5) : 0,
                'conversations' => $funnel['conversations'] > 0 ? min(100, ($funnel['conversations'] / $impr) * 100 * 80 + 3) : 0,
                'pedidos'      => $funnel['pedidos'] > 0 ? min(100, max(2, ($funnel['pedidos'] / $impr) * 100 * 80 + 1)) : 0,
              );
            ?>
            <div class="funnel-step">
              <div class="funnel-step-label">Impresiones</div>
              <div style="flex:1;"><div class="funnel-bar" style="width:<?= $widths['impressions'] ?>%; background:#3b82f6;"><?= number_format($funnel['impressions'], 0, ',', '.') ?></div></div>
              <div class="funnel-step-value">100%</div>
            </div>
            <div class="funnel-conn">↓ CTR: <?= $funnel['ctr'] ?>%</div>

            <div class="funnel-step">
              <div class="funnel-step-label">Clics</div>
              <div style="flex:1;"><div class="funnel-bar" style="width:<?= max(5,$widths['clicks']) ?>%; background:#6366f1;"><?= number_format($funnel['clicks'], 0, ',', '.') ?></div></div>
              <div class="funnel-step-value"><?= $funnel['ctr'] ?>%</div>
            </div>
            <div class="funnel-conn">↓ Clic→Conv: <?= $funnel['click_to_conv'] ?>%</div>

            <div class="funnel-step">
              <div class="funnel-step-label">Conversaciones</div>
              <div style="flex:1;"><div class="funnel-bar" style="width:<?= max(3,$widths['conversations']) ?>%; background:#8b5cf6;"><?= number_format($funnel['conversations'], 0, ',', '.') ?></div></div>
              <div class="funnel-step-value"><?= $impr > 0 ? round($funnel['conversations']/$impr*100,2) : 0 ?>%</div>
            </div>
            <div class="funnel-conn">↓ Cierre: <?= $funnel['conv_to_order'] ?>%</div>

            <div class="funnel-step">
              <div class="funnel-step-label">Pedidos</div>
              <div style="flex:1;"><div class="funnel-bar" style="width:<?= max(2,$widths['pedidos']) ?>%; background:#10b981;"><?= number_format($funnel['pedidos'], 0, ',', '.') ?></div></div>
              <div class="funnel-step-value"><?= $impr > 0 ? round($funnel['pedidos']/$impr*100,3) : 0 ?>%</div>
            </div>
          </div>

          <!-- Top / Bottom performer -->
          <div class="space-y-3">
            <?php if ($top_performer): ?>
            <div class="perf-card top">
              <div class="flex items-center gap-2 mb-1">
                <span style="font-size:18px;">🏆</span>
                <span class="text-[11px] font-bold uppercase tracking-wide text-green-800">Mejor ROI</span>
              </div>
              <div class="text-sm font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($top_performer['name']) ?>"><?= htmlspecialchars($top_performer['name']) ?></div>
              <div class="text-xs text-gray-600 mt-2 flex items-center gap-3">
                <span><b class="text-green-700"><?= $top_performer['roi'] ?>%</b> ROI</span>
                <span><b><?= $top_performer['roas'] ?>x</b> ROAS</span>
                <span class="text-gray-400">$<?= number_format($top_performer['spend'], 0, ',', '.') ?></span>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($worst_performer && $top_performer && $worst_performer['id'] !== $top_performer['id']): ?>
            <div class="perf-card worst">
              <div class="flex items-center gap-2 mb-1">
                <span style="font-size:18px;">⚠️</span>
                <span class="text-[11px] font-bold uppercase tracking-wide text-red-800">Peor ROI</span>
              </div>
              <div class="text-sm font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($worst_performer['name']) ?>"><?= htmlspecialchars($worst_performer['name']) ?></div>
              <div class="text-xs text-gray-600 mt-2 flex items-center gap-3">
                <span><b class="<?= $worst_performer['roi'] >= 0 ? 'text-green-700' : 'text-red-700' ?>"><?= $worst_performer['roi'] ?>%</b> ROI</span>
                <span><b><?= $worst_performer['roas'] ?>x</b> ROAS</span>
                <span class="text-gray-400">$<?= number_format($worst_performer['spend'], 0, ',', '.') ?></span>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- KPIs secundarios -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
          <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="text-[10px] uppercase text-gray-500 font-semibold tracking-wide">Impresiones</div>
            <div class="text-base font-bold text-gray-800"><?= number_format($totals['impressions'], 0, ',', '.') ?></div>
            <div class="text-[10px] mt-0.5"><?= $renderDelta($compare['impressions']['delta']) ?></div>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="text-[10px] uppercase text-gray-500 font-semibold tracking-wide">Clics</div>
            <div class="text-base font-bold text-gray-800"><?= number_format($totals['clicks'], 0, ',', '.') ?></div>
            <div class="text-[10px] mt-0.5"><?= $renderDelta($compare['clicks']['delta']) ?></div>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="text-[10px] uppercase text-gray-500 font-semibold tracking-wide">CTR</div>
            <div class="text-base font-bold text-gray-800"><?= $totals['ctr'] ?>%</div>
            <div class="text-[10px] mt-0.5 text-gray-400">CPC $<?= number_format($totals['cpc'], 0, ',', '.') ?></div>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="text-[10px] uppercase text-gray-500 font-semibold tracking-wide">Conversaciones</div>
            <div class="text-base font-bold text-gray-800"><?= number_format($totals['conversations'], 0, ',', '.') ?></div>
            <div class="text-[10px] mt-0.5"><?= $renderDelta($compare['conversations']['delta']) ?></div>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200">
            <div class="text-[10px] uppercase text-gray-500 font-semibold tracking-wide">CPM</div>
            <div class="text-base font-bold text-gray-800">$<?= number_format($totals['cpm'], 0, ',', '.') ?></div>
            <div class="text-[10px] mt-0.5 text-gray-400">por mil impr.</div>
          </div>
        </div>

        <!-- Tendencia diaria (SVG inline) + Ranking campañas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

          <!-- SVG: Tendencia diaria de inversión + conversaciones -->
          <div class="lg:col-span-2 p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-bold text-gray-700">Tendencia diaria</h3>
              <div class="flex items-center gap-3 text-[11px] text-gray-500">
                <span class="inline-flex items-center"><span style="display:inline-block;width:10px;height:3px;background:#dc2626;margin-right:4px;border-radius:2px;"></span>Inversión</span>
                <span class="inline-flex items-center"><span style="display:inline-block;width:10px;height:3px;background:#10b981;margin-right:4px;border-radius:2px;"></span>Conversaciones</span>
              </div>
            </div>
            <?php if (!empty($daily) && count($daily) > 1):
              $W = 700; $H = 220; $padL = 50; $padR = 25; $padT = 15; $padB = 30;
              $iw = $W - $padL - $padR; $ih = $H - $padT - $padB;
              $n = count($daily);
              $maxSpend = max(array_map(function($d){ return $d['spend']; }, $daily)) ?: 1;
              $maxConv  = max(array_map(function($d){ return $d['conversations']; }, $daily)) ?: 1;
              $xStep = $n > 1 ? $iw / ($n - 1) : 0;
              // Construir paths
              $spendPath = ''; $convPath = ''; $spendArea = ''; $convArea = '';
              foreach ($daily as $i => $d) {
                $x = $padL + $i * $xStep;
                $ys = $padT + $ih - ($d['spend'] / $maxSpend) * $ih;
                $yc = $padT + $ih - ($d['conversations'] / $maxConv) * $ih;
                $spendPath .= ($i === 0 ? 'M' : 'L') . round($x,1) . ',' . round($ys,1) . ' ';
                $convPath  .= ($i === 0 ? 'M' : 'L') . round($x,1) . ',' . round($yc,1) . ' ';
              }
              $spendArea = $spendPath . 'L' . round($padL + ($n-1) * $xStep,1) . ',' . ($padT + $ih) . ' L' . $padL . ',' . ($padT + $ih) . ' Z';
              $convArea  = $convPath  . 'L' . round($padL + ($n-1) * $xStep,1) . ',' . ($padT + $ih) . ' L' . $padL . ',' . ($padT + $ih) . ' Z';
              // Eje X: cada N días
              $xLabelEvery = max(1, intval(ceil($n / 8)));
              $fmtMoney = function($v) { return $v >= 1000000 ? '$' . number_format($v/1000000,1) . 'M' : ($v >= 1000 ? '$' . number_format($v/1000,0) . 'k' : '$' . round($v)); };
            ?>
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" style="width:100%; height:240px; display:block;">
              <!-- gridlines -->
              <?php for ($i = 0; $i <= 4; $i++): $gy = $padT + ($ih * $i / 4); ?>
              <line x1="<?= $padL ?>" y1="<?= $gy ?>" x2="<?= $W - $padR ?>" y2="<?= $gy ?>" stroke="#f1f5f9" stroke-width="1"/>
              <text x="<?= $padL - 6 ?>" y="<?= $gy + 4 ?>" font-size="9" fill="#94a3b8" text-anchor="end" font-family="sans-serif"><?= $fmtMoney($maxSpend * (1 - $i/4)) ?></text>
              <?php endfor; ?>
              <!-- area Inversión -->
              <path d="<?= $spendArea ?>" fill="rgba(220,38,38,0.10)" stroke="none"/>
              <path d="<?= $spendPath ?>" fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
              <!-- area Conversaciones -->
              <path d="<?= $convArea ?>" fill="rgba(16,185,129,0.10)" stroke="none"/>
              <path d="<?= $convPath ?>" fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
              <!-- puntos + tooltips -->
              <?php foreach ($daily as $i => $d):
                $x = $padL + $i * $xStep;
                $ys = $padT + $ih - ($d['spend'] / $maxSpend) * $ih;
                $yc = $padT + $ih - ($d['conversations'] / $maxConv) * $ih;
              ?>
              <circle cx="<?= round($x,1) ?>" cy="<?= round($ys,1) ?>" r="2.5" fill="#dc2626"><title><?= date('d/m', strtotime($d['date'])) ?>: $<?= number_format($d['spend'],0,',','.') ?></title></circle>
              <circle cx="<?= round($x,1) ?>" cy="<?= round($yc,1) ?>" r="2.5" fill="#10b981"><title><?= date('d/m', strtotime($d['date'])) ?>: <?= number_format($d['conversations'],0,',','.') ?> conv.</title></circle>
              <?php if ($i % $xLabelEvery === 0 || $i === $n-1): ?>
              <text x="<?= round($x,1) ?>" y="<?= $padT + $ih + 16 ?>" font-size="9" fill="#94a3b8" text-anchor="middle" font-family="sans-serif"><?= date('d/m', strtotime($d['date'])) ?></text>
              <?php endif; ?>
              <?php endforeach; ?>
            </svg>
            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-500">
              <span>Días: <b class="text-gray-700"><?= $n ?></b></span>
              <span>Pico: <?= $fmtMoney($maxSpend) ?> · <?= number_format($maxConv,0,',','.') ?> conv.</span>
            </div>
            <?php else: ?>
              <div class="py-12 text-center text-gray-400 text-sm">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 17l4-7 4 4 6-11"/></svg>
                Sin datos diarios en el rango
              </div>
            <?php endif; ?>
          </div>

          <!-- Ranking campañas por ROI -->
          <?php
            // Pre-calcular ranking con barras horizontales
            $ranked = $report;
            usort($ranked, function($a, $b) { return $b['roi'] <=> $a['roi']; });
            $top5 = array_slice($ranked, 0, 5);
            $maxAbsRoi = 1;
            foreach ($top5 as $r) $maxAbsRoi = max($maxAbsRoi, abs($r['roi']));
          ?>
          <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Ranking por ROI</h3>
            <?php if (!empty($top5)): ?>
              <?php foreach ($top5 as $i => $r):
                $pct = abs($r['roi']) / $maxAbsRoi * 100;
                $color = $r['roi'] >= 0 ? '#16a34a' : '#dc2626';
                $bg    = $r['roi'] >= 0 ? '#ecfdf5' : '#fef2f2';
              ?>
              <div class="mb-3" style="border-left:3px solid <?= $color ?>; padding-left:8px;">
                <div class="flex items-center justify-between text-[11px] mb-1">
                  <span class="font-semibold text-gray-700 truncate" title="<?= htmlspecialchars($r['name']) ?>" style="max-width:170px;"><?= ($i+1) ?>. <?= htmlspecialchars($r['name']) ?></span>
                  <span class="font-bold flex-shrink-0 ml-2" style="color:<?= $color ?>;"><?= $r['roi'] ?>%</span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                  <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;"></div>
                </div>
                <div class="text-[10px] text-gray-400 mt-0.5">$<?= number_format($r['spend'],0,',','.') ?> · <?= $r['roas'] ?>x ROAS · <?= $r['conversations'] ?> conv.</div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-xs text-gray-400 text-center py-6">Sin campañas</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tabla detallada -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
          <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-700">Detalle por campaña</h3>
            <div class="flex items-center gap-2">
              <input id="tblSearch" type="text" placeholder="Buscar..." class="px-3 py-1 text-xs border border-gray-300 rounded">
              <span class="text-xs text-gray-500"><?= count($report) ?> campañas</span>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table id="campTable" class="w-full tbl-pro">
              <thead>
                <tr>
                  <th data-sort="name" class="text-left">Campaña</th>
                  <th data-sort="vendor_label" class="text-center">Ciudad</th>
                  <th data-sort="status" class="text-center">Estado</th>
                  <th data-sort="spend" class="text-right">Inversión</th>
                  <th data-sort="impressions" class="text-right">Impr.</th>
                  <th data-sort="ctr" class="text-right">CTR</th>
                  <th data-sort="conversations" class="text-right">Conv.</th>
                  <th data-sort="pedidos" class="text-right">Pedidos</th>
                  <th data-sort="ventas" class="text-right">Ventas</th>
                  <th data-sort="roas" class="text-right">ROAS</th>
                  <th data-sort="roi" class="text-right sorted">ROI</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  // Pre-orden: por ROI desc
                  usort($report, function($a, $b) { return $b['roi'] <=> $a['roi']; });
                  $maxAbsRoi = 0;
                  foreach ($report as $r) { $maxAbsRoi = max($maxAbsRoi, abs($r['roi'])); }
                  $maxAbsRoi = max($maxAbsRoi, 1);
                ?>
                <?php foreach ($report as $r):
                  $cityClassMap = array('Medellín' => 'city-medellin', 'Bogotá' => 'city-bogota', 'Barranquilla' => 'city-barranquilla');
                  $cityCls = $cityClassMap[$r['vendor_label']] ?? 'pill-other';
                  $statusCls = $r['status'] === 'ACTIVE' ? 'pill-active' : ($r['status'] === 'PAUSED' ? 'pill-paused' : 'pill-other');
                  $statusLbl = $r['status'] === 'ACTIVE' ? 'Activa' : ($r['status'] === 'PAUSED' ? 'Pausada' : $r['status']);
                  $roiPct = abs($r['roi']) / $maxAbsRoi * 100;
                  $roiColor = $r['roi'] >= 0 ? '#16a34a' : '#dc2626';
                ?>
                <tr data-name="<?= htmlspecialchars(strtolower($r['name']), ENT_QUOTES) ?>">
                  <td class="font-medium text-gray-800" data-val="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"><?= htmlspecialchars($r['name']) ?></td>
                  <td class="text-center" data-val="<?= htmlspecialchars($r['vendor_label']) ?>"><span class="pill <?= $cityCls ?>"><?= $r['vendor_label'] ?></span></td>
                  <td class="text-center" data-val="<?= $r['status'] ?>"><span class="pill <?= $statusCls ?>"><?= $statusLbl ?></span></td>
                  <td class="text-right font-medium" data-val="<?= $r['spend'] ?>">$<?= number_format($r['spend'], 0, ',', '.') ?></td>
                  <td class="text-right text-gray-600" data-val="<?= $r['impressions'] ?>"><?= number_format($r['impressions'], 0, ',', '.') ?></td>
                  <td class="text-right text-gray-600" data-val="<?= $r['ctr'] ?>"><?= number_format($r['ctr'], 2) ?>%</td>
                  <td class="text-right" data-val="<?= $r['conversations'] ?>"><?= number_format($r['conversations'], 0, ',', '.') ?></td>
                  <td class="text-right font-semibold" data-val="<?= $r['pedidos'] ?>"><?= number_format($r['pedidos'], 0, ',', '.') ?></td>
                  <td class="text-right font-semibold text-blue-700" data-val="<?= $r['ventas'] ?>">$<?= number_format($r['ventas'], 0, ',', '.') ?></td>
                  <td class="text-right" data-val="<?= $r['roas'] ?>"><?= $r['roas'] ?>x</td>
                  <td class="text-right" data-val="<?= $r['roi'] ?>">
                    <div class="font-bold" style="color:<?= $roiColor ?>;"><?= $r['roi'] ?>%</div>
                    <div class="roi-bar"><span style="width:<?= $roiPct ?>%; background:<?= $roiColor ?>;"></span></div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="bg-gray-50 font-bold text-gray-800 border-t-2 border-gray-300">
                  <td class="px-3 py-3">TOTAL</td>
                  <td></td>
                  <td></td>
                  <td class="px-3 py-3 text-right">$<?= number_format($totals['spend'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['impressions'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= $totals['ctr'] ?>%</td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['conversations'], 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= number_format($totals['pedidos'] ?? 0, 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right text-blue-700">$<?= number_format($totals['ventas'] ?? 0, 0, ',', '.') ?></td>
                  <td class="px-3 py-3 text-right"><?= $totals['roas'] ?? 0 ?>x</td>
                  <td class="px-3 py-3 text-right <?= ($totals['roi'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $totals['roi'] ?? 0 ?>%</td>
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
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
  e.preventDefault(); e.stopPropagation();
  $('#ai-submenu').toggleClass('hidden');
});

// Búsqueda en tabla
document.getElementById('tblSearch').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  document.querySelectorAll('#campTable tbody tr').forEach(function(tr) {
    tr.style.display = tr.dataset.name.indexOf(q) >= 0 ? '' : 'none';
  });
});

// Sort por columna
var sortDir = 'desc';
var sortCol = 'roi';
document.querySelectorAll('#campTable thead th[data-sort]').forEach(function(th) {
  th.addEventListener('click', function() {
    var col = th.dataset.sort;
    sortDir = (sortCol === col && sortDir === 'desc') ? 'asc' : 'desc';
    sortCol = col;
    document.querySelectorAll('#campTable thead th').forEach(function(t){ t.classList.remove('sorted', 'sorted-asc'); });
    th.classList.add(sortDir === 'desc' ? 'sorted' : 'sorted-asc');
    var tbody = document.querySelector('#campTable tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));
    var idx = Array.from(th.parentNode.children).indexOf(th);
    rows.sort(function(a, b) {
      var va = a.children[idx].dataset.val || '';
      var vb = b.children[idx].dataset.val || '';
      var na = parseFloat(va), nb = parseFloat(vb);
      if (!isNaN(na) && !isNaN(nb)) return sortDir === 'desc' ? nb - na : na - nb;
      return sortDir === 'desc' ? vb.localeCompare(va) : va.localeCompare(vb);
    });
    rows.forEach(function(r) { tbody.appendChild(r); });
  });
});

// La tendencia diaria ahora se renderiza via SVG inline (server-side), sin Chart.js.
</script>
</body>
</html>
