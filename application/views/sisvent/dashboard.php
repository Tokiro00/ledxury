<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$panel = isset($panel) ? $panel : array('ranking' => array(), 'ventas_mes' => 0, 'cobros_mes' => 0, 'facturas' => 0, 'ticket_prom' => 0, 'budgets' => 0, 'conversion' => 0, 'metas' => array(), 'cumpliendo' => 0, 'total_vendedores' => 0);

$fmtMoney = function($v) {
    if ($v >= 1000000000) return '$' . number_format($v/1000000000, 1, '.', '') . 'B';
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1, '.', '') . 'M';
    if ($v >= 1000) return '$' . number_format($v/1000, 0, '.', '') . 'K';
    return '$' . number_format($v, 0, ',', '.');
};
$mNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Ledxury Dashboard</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
  .glass-card { background: rgba(255,255,255,0.95); border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
  .glass-card:hover { border-color: #E63946; box-shadow: 0 4px 12px rgba(230,57,70,0.1); }
  .kpi-tile { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; }
  .kpi-eyebrow { font-size: 10px; font-weight: 700; color: #7F8392; text-transform: uppercase; letter-spacing: 0.05em; }
  .kpi-value { font-size: 22px; font-weight: 800; color: #2B3164; font-variant-numeric: tabular-nums; line-height: 1.1; }
  .kpi-meta { font-size: 11px; color: #7F8392; margin-top: 2px; }
  .kpi-tile.tile-blue   { border-left: 3px solid #4487A0; }
  .kpi-tile.tile-yellow { border-left: 3px solid #FEAB2F; }
  .kpi-tile.tile-green  { border-left: 3px solid #31AB20; }
  .kpi-tile.tile-purple { border-left: 3px solid #5D41CC; }
  .kpi-tile.tile-red    { border-left: 3px solid #ef0d0d; }
  .ranking-row td { padding: 8px 12px; border-bottom: 1px solid #F1F3F5; }
  .ranking-row:hover { background: #F8F8F8; }
  .pct-bar-bg { background: #F1F3F5; height: 5px; border-radius: 3px; overflow: hidden; position: relative; }
  .pct-bar-fill { height: 100%; border-radius: 3px; }
  .rank-badge { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border-radius: 50%; flex-shrink: 0; }
  .rank-1 { background: #FEAB2F; color: #fff; }
  .rank-2 { background: #AEAAA6; color: #fff; }
  .rank-3 { background: #C9805A; color: #fff; }
  .rank-other { background: #DDDFE8; color: #575964; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto bg-gray-50">
      <div class="px-6 py-5 w-full max-w-7xl mx-auto">

        <!-- Header con logo + accesos rápidos -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5 gap-3">
          <div>
            <h1 class="text-3xl font-black tracking-tight" style="color:#1a1a2e;">LEDXURY</h1>
            <p class="text-xs text-gray-400 uppercase tracking-widest"><?= $mNames[(int)date('n')-1] ?> <?= date('Y') ?> · Día <?= date('j') ?></p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="glass-card px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 hover:border-green-500 transition flex items-center">
              <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
              Bots WhatsApp
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/ads" class="glass-card px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 hover:border-blue-500 transition flex items-center">
              <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/></svg>
              Meta Ads
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/report/0" class="glass-card px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 transition flex items-center">
              <svg class="w-4 h-4 mr-1.5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
              Reportes
            </a>
            <a href="<?= base_url() ?>sisvent/commercial/budgets" class="glass-card px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 transition flex items-center">
              <svg class="w-4 h-4 mr-1.5" style="color:#E63946" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Presupuestos
            </a>
            <a href="<?= base_url() ?>sisvent/admin/salesboard" class="glass-card px-3 py-1.5 text-xs font-bold text-white transition flex items-center" style="background:#2B3164; border-color:#2B3164;">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
              Panel completo →
            </a>
          </div>
        </div>

        <!-- KPI strip: Bot stats (preservados) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <div class="kpi-tile tile-red">
            <p class="kpi-eyebrow">Bots — Hoy</p>
            <p class="kpi-value mt-1"><?= number_format($bot_ventas_hoy ?? 0, 0, ',', '.') ?></p>
            <p class="kpi-meta">Total: <span style="color:#E63946;font-weight:600;">$<?= number_format($bot_total_hoy ?? 0, 0, ',', '.') ?></span></p>
          </div>
          <div class="kpi-tile tile-green">
            <p class="kpi-eyebrow">Bots — <?= $mNames[(int)date('n')-1] ?> <?= date('Y') ?></p>
            <p class="kpi-value mt-1"><?= number_format($bot_ventas_mes ?? 0, 0, ',', '.') ?></p>
            <p class="kpi-meta">Total: <span style="color:#22c55e;font-weight:600;">$<?= number_format($bot_total_mes ?? 0, 0, ',', '.') ?></span></p>
          </div>
          <div class="kpi-tile tile-blue">
            <p class="kpi-eyebrow">Bots — Año <?= date('Y') ?></p>
            <p class="kpi-value mt-1"><?= number_format($bot_ventas_anio ?? 0, 0, ',', '.') ?></p>
            <p class="kpi-meta">Total: <span style="color:#3b82f6;font-weight:600;">$<?= number_format($bot_total_anio ?? 0, 0, ',', '.') ?></span></p>
          </div>
        </div>

        <!-- Panel de vendedores compacto -->
        <div class="bg-white rounded-lg border p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h2 class="text-lg font-black tracking-tight" style="color:#2B3164;">Panel de Vendedores</h2>
              <p class="text-xs text-gray-400 uppercase tracking-widest">Mes en curso · <?= count($panel['ranking']) ?> vendedores con ventas</p>
            </div>
            <a href="<?= base_url() ?>sisvent/admin/salesboard" class="text-xs font-semibold" style="color:#4487A0;">Ver panel completo →</a>
          </div>

          <!-- 5 KPIs del mes -->
          <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-4">
            <div class="kpi-tile tile-blue">
              <p class="kpi-eyebrow">Ventas Mes</p>
              <p class="kpi-value mt-1"><?= $fmtMoney($panel['ventas_mes']) ?></p>
              <p class="kpi-meta"><?= number_format($panel['facturas'], 0, ',', '.') ?> facturas</p>
            </div>
            <div class="kpi-tile tile-yellow">
              <p class="kpi-eyebrow">Cobros Mes</p>
              <p class="kpi-value mt-1"><?= $fmtMoney($panel['cobros_mes']) ?></p>
              <p class="kpi-meta"><?= $panel['ventas_mes'] > 0 ? round(($panel['cobros_mes']/$panel['ventas_mes'])*100,1) : 0 ?>% de ventas</p>
            </div>
            <div class="kpi-tile tile-green">
              <p class="kpi-eyebrow">Cumplen Meta</p>
              <p class="kpi-value mt-1"><?= $panel['cumpliendo'] ?>/<?= $panel['total_vendedores'] ?></p>
              <p class="kpi-meta">vendedores</p>
            </div>
            <div class="kpi-tile tile-purple">
              <p class="kpi-eyebrow">Ticket Prom.</p>
              <p class="kpi-value mt-1"><?= $fmtMoney($panel['ticket_prom']) ?></p>
              <p class="kpi-meta">por factura</p>
            </div>
            <div class="kpi-tile tile-red">
              <p class="kpi-eyebrow">Conversión</p>
              <p class="kpi-value mt-1"><?= $panel['conversion'] ?>%</p>
              <p class="kpi-meta"><?= $panel['budgets'] ?> presupuestos</p>
            </div>
          </div>

          <!-- Ranking de vendedores -->
          <?php if (empty($panel['ranking'])): ?>
            <div class="text-center text-gray-400 text-sm py-8">Sin ventas este mes.</div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr style="background:#2B3164;color:#fff;">
                    <th class="px-3 py-2.5 text-left font-semibold">#</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Vendedor</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Ventas</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Meta</th>
                    <th class="px-3 py-2.5 text-left font-semibold" style="min-width:140px;">% Meta</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Cobros</th>
                    <th class="px-3 py-2.5 text-center font-semibold">Facturas</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($panel['ranking'] as $i => $v):
                    $rankClass = $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-other'));
                    $meta = isset($panel['metas'][$v->vendorId]) ? (float)$panel['metas'][$v->vendorId] : 0;
                    $ventas = (float)$v->total_ventas;
                    $pct = $meta > 0 ? min(round(($ventas / $meta) * 100), 999) : 0;
                    $barColor = $pct >= 80 ? '#31AB20' : ($pct >= 50 ? '#FEAB2F' : '#ef0d0d');
                  ?>
                  <tr class="ranking-row text-gray-700">
                    <td><span class="rank-badge <?= $rankClass ?>"><?= $i + 1 ?></span></td>
                    <td class="font-semibold"><?= htmlspecialchars($v->vendor_name ?: '—') ?></td>
                    <td class="text-right font-bold" style="color:#2B3164;"><?= $fmtMoney($ventas) ?></td>
                    <td class="text-right text-gray-500"><?= $meta > 0 ? $fmtMoney($meta) : '—' ?></td>
                    <td>
                      <?php if ($meta > 0): ?>
                        <div class="flex items-center gap-2">
                          <div class="pct-bar-bg flex-1"><div class="pct-bar-fill" style="width:<?= min($pct, 100) ?>%;background:<?= $barColor ?>;"></div></div>
                          <span class="text-xxs font-bold" style="color:<?= $barColor ?>;min-width:35px;text-align:right;"><?= $pct ?>%</span>
                        </div>
                      <?php else: ?>
                        <span class="text-xxs text-gray-400">Sin meta</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right" style="color:#FEAB2F;"><?= $fmtMoney((float)$v->total_collected) ?></td>
                    <td class="text-center text-gray-600"><?= number_format((int)$v->invoice_count, 0, ',', '.') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/voice_widget'); ?>
<?php $this->load->view('sisvent/layouts/chat_widget'); ?>
<?php $this->load->view('sisvent/layouts/screensaver'); ?>

<script>
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});
$(document).on('click', '#btn-toggle-profile-menu', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#profile-dropdown').toggleClass('hidden');
    $('#notif-dropdown').addClass('hidden');
});
$(document).on('click', '#btn-toggle-notif', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#notif-dropdown').toggleClass('hidden');
    $('#profile-dropdown').addClass('hidden');
    $.get(base_url + 'sisvent/dashboard/chatUnread', function(r) {
        if (r.count > 0) { $('#notif-chat-count').text(r.count).removeClass('hidden'); $('#noti-badge').show(); }
        else { $('#notif-chat-count').addClass('hidden'); }
    }, 'json');
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('#btn-toggle-profile-menu, #profile-dropdown').length) $('#profile-dropdown').addClass('hidden');
    if (!$(e.target).closest('#btn-toggle-notif, #notif-dropdown').length) $('#notif-dropdown').addClass('hidden');
});

(function() {
  var input = document.getElementById('navbar-universal-search');
  var results = document.getElementById('navbarSearchResults');
  if (!input || !results) return;
  var timer = null;
  var icons = {
    user: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    box: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    doc: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    users: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197V21"/></svg>'
  };
  var colors = { Cliente: '#22c55e', Producto: '#3b82f6', Factura: '#f59e0b', Usuario: '#8b5cf6' };
  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { results.classList.add('hidden'); return; }
    timer = setTimeout(function() {
      $.get(base_url + 'sisvent/dashboard/search', { q: q }, function(r) {
        if (!r.results || !r.results.length) { results.innerHTML = '<div class="p-4 text-sm text-gray-400 text-center">Sin resultados</div>'; results.classList.remove('hidden'); return; }
        var html = '';
        r.results.forEach(function(item) {
          var c = colors[item.type] || '#666'; var ic = icons[item.icon] || icons.box;
          html += '<a href="' + item.url + '" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b border-gray-100"><div class="p-2 rounded-lg mr-3" style="background:' + c + '15;color:' + c + '">' + ic + '</div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-800 truncate">' + item.title + '</p><p class="text-xs text-gray-400 truncate">' + item.subtitle + '</p></div><span class="text-xs font-medium px-2 py-0.5 rounded-full ml-2" style="background:' + c + '15;color:' + c + '">' + item.type + '</span></a>';
        });
        results.innerHTML = html; results.classList.remove('hidden');
      }, 'json');
    }, 300);
  });
  $(document).on('click', function(e) { if (!$(e.target).closest('#navbar-universal-search, #navbarSearchResults').length) results.classList.add('hidden'); });
})();
</script>

</body>
</html>
