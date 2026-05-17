<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Mapeo state numeric → label + tone
$estadoMap = [
    '0' => ['label' => 'borrador',    'tone' => 'neutral'],
    '1' => ['label' => 'aprobado',    'tone' => 'info'],
    '2' => ['label' => 'con guía',    'tone' => 'neutral'],
    '3' => ['label' => 'en tránsito', 'tone' => 'info'],
    '4' => ['label' => 'entregado',   'tone' => 'success'],
    '5' => ['label' => 'incidencia',  'tone' => 'danger'],
];

$fmtMoney = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
$fmtCompact = function($v) {
    $v = (float) $v;
    if ($v >= 1_000_000_000) return '$' . round($v / 1_000_000_000, 1) . 'B';
    if ($v >= 1_000_000)     return '$' . round($v / 1_000_000, 1) . 'M';
    if ($v >= 1_000)         return '$' . round($v / 1_000) . 'K';
    return '$' . round($v);
};
$fmtDate = function($dt) {
    if (empty($dt) || $dt === '0000-00-00 00:00:00') return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)        return 'Hace ' . $diff . 's';
    if ($diff < 3600)      return 'Hace ' . floor($diff/60) . ' min';
    if ($diff < 86400)     return 'Hace ' . floor($diff/3600) . 'h';
    if ($diff < 86400 * 2) return 'Ayer';
    if ($diff < 86400 * 7) return 'Hace ' . floor($diff/86400) . 'd';
    return date('d/m/Y', strtotime($dt));
};

// Topbar CTAs
ob_start();
$this->load->view('sisvent/v2/_components/button', [
    'variant' => 'secondary',
    'size' => 'md',
    'label' => 'Exportar',
    'href'  => base_url('sisvent/commercial/budgets'),
    'title' => 'Exportar a Excel (en v1)',
    'icon'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>',
]);
$this->load->view('sisvent/v2/_components/button', [
    'variant' => 'primary',
    'size' => 'md',
    'label' => 'Nuevo presupuesto',
    'href'  => base_url('sisvent/commercial/budgets/add'),
    'icon'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
]);
$topbarRight = ob_get_clean();
?>
<?php $this->load->view('sisvent/v2/layouts/meta_header'); ?>

<div style="display:flex;height:100vh;">
  <?php $this->load->view('sisvent/v2/layouts/sidebar', ['activeRoute' => $activeRoute]); ?>

  <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
    <?php $this->load->view('sisvent/v2/layouts/topbar', [
        'pageTitle'   => htmlspecialchars($pageTitle),
        'breadcrumbs' => $breadcrumbs,
        'topbarRight' => $topbarRight,
        'v1Url'       => $v1Url,
    ]); ?>

    <div style="flex:1;overflow-y:auto;padding:32px 36px 48px; max-width: var(--lx-content-max); width:100%;">

      <!-- KPI strip (4 tiles editorial con sparkline) -->
      <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:20px;margin-bottom:36px;">
        <?php
        $deltaTotal = (float) ($deltas['total_pct'] ?? 0);
        $deltaValor = (float) ($deltas['valor_pct'] ?? 0);
        $tones = [
            'todos'      => 'var(--lx-accent)',
            'nuevo'      => 'var(--lx-warning)',
            'preparando' => 'var(--lx-navy)',
            'entregado'  => 'var(--lx-success)',
        ];
        $this->load->view('sisvent/v2/_components/kpi_tile', [
            'eyebrow' => 'Presupuestos · 14 días',
            'value'   => number_format((int) ($deltas['curr_total'] ?? 0), 0, ',', '.'),
            'sub'     => $fmtCompact($valorTotal) . ' valor total',
            'delta'   => abs($deltaTotal) . '%',
            'deltaTone' => $deltaTotal >= 0 ? 'up' : 'dn',
            'spark'   => $series['todos'] ?? null,
            'accent'  => $tones['todos'],
        ]);
        $this->load->view('sisvent/v2/_components/kpi_tile', [
            'eyebrow' => 'Borradores',
            'value'   => number_format((int) $counts['nuevo'], 0, ',', '.'),
            'sub'     => 'Esperando aprobación',
            'spark'   => $series['nuevo'] ?? null,
            'accent'  => $tones['nuevo'],
        ]);
        $this->load->view('sisvent/v2/_components/kpi_tile', [
            'eyebrow' => 'En preparación',
            'value'   => number_format((int) $counts['preparando'], 0, ',', '.'),
            'sub'     => 'Aprobados, sin despachar',
            'spark'   => $series['preparando'] ?? null,
            'accent'  => $tones['preparando'],
        ]);
        $this->load->view('sisvent/v2/_components/kpi_tile', [
            'eyebrow' => 'Entregados',
            'value'   => number_format((int) $counts['entregado'], 0, ',', '.'),
            'sub'     => 'Cerrados ' . abs($deltaValor) . '% ' . ($deltaValor >= 0 ? '↑' : '↓') . ' valor',
            'spark'   => $series['entregado'] ?? null,
            'accent'  => $tones['entregado'],
        ]);
        ?>
      </div>

      <!-- Section divider con título + filtros -->
      <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px;">
        <div>
          <h2 style="font-size: var(--lx-text-xl); margin:0 0 4px;">Listado</h2>
          <p style="font-size:12.5px;color:var(--lx-ink3);margin:0;">
            <?= number_format($total, 0, ',', '.') ?> presupuesto<?= $total === 1 ? '' : 's' ?>
            <?php if ($estado !== 'todos'): ?>
              · filtrado por <strong style="color:var(--lx-ink2);font-weight:500;"><?= htmlspecialchars($estado) ?></strong>
            <?php endif; ?>
          </p>
        </div>
      </div>

      <!-- Filter chips -->
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
        <?php
        $chips = [
            ['todos',      'Todos',         $counts['todos']],
            ['nuevo',      'Borradores',    $counts['nuevo']],
            ['preparando', 'Preparando',    $counts['preparando']],
            ['guia',       'Con guía',      $counts['guia']],
            ['transito',   'En tránsito',   $counts['transito']],
            ['entregado',  'Entregados',    $counts['entregado']],
        ];
        foreach ($chips as $c) {
            $this->load->view('sisvent/v2/_components/filter_chip', [
                'label'  => $c[1],
                'count'  => $c[2],
                'active' => $estado === $c[0],
                'href'   => base_url('sisvent/v2/presupuestos') . '?estado=' . $c[0],
            ]);
        }
        ?>
      </div>

      <!-- Table -->
      <div style="background:var(--lx-surface);border:1px solid var(--lx-line);border-radius:var(--lx-radius-lg);overflow:hidden;box-shadow: var(--lx-shadow-sm);">

        <!-- Head -->
        <div style="
          display:grid;
          grid-template-columns: 110px 1fr 140px 90px 150px 130px 40px;
          padding:14px 22px; border-bottom:1px solid var(--lx-line);
          font-size:10.5px; font-weight:600; letter-spacing:0.05em;
          text-transform:uppercase; color:var(--lx-ink3);
          background:var(--lx-bg);
        ">
          <div>Presupuesto</div>
          <div>Cliente</div>
          <div>Ciudad</div>
          <div style="text-align:right;">Ítems</div>
          <div style="text-align:right;">Valor</div>
          <div>Estado</div>
          <div></div>
        </div>

        <?php if (empty($budgets)): ?>
          <div style="padding:48px 24px;text-align:center;">
            <?php $this->load->view('sisvent/v2/_components/empty_state', [
                'title' => 'Sin presupuestos',
                'text'  => 'No se encontraron presupuestos con ese filtro.',
                'ctaLabel' => 'Crear presupuesto',
                'ctaHref'  => base_url('sisvent/commercial/budgets/add'),
            ]); ?>
          </div>
        <?php else: foreach ($budgets as $b):
          $stMeta = $estadoMap[(string) $b->state] ?? ['label' => 'desconocido', 'tone' => 'neutral'];
          $itemsCount = isset($b->total_items) ? (int) $b->total_items : 0;
        ?>
          <a href="<?= base_url('sisvent/v2/presupuestos/' . $b->idBudget) ?>" style="
            display:grid;
            grid-template-columns: 110px 1fr 140px 90px 150px 130px 40px;
            padding: 16px 22px; align-items:center;
            border-bottom:1px solid var(--lx-line);
            font-size:13.5px; color:var(--lx-ink); text-decoration:none;
            transition: background .12s;
          " onmouseover="this.style.background='var(--lx-bg)'"
             onmouseout="this.style.background='transparent'">
            <div class="mono" style="font-size:12.5px;font-weight:600;color:var(--lx-ink);letter-spacing:-0.01em;">
              #<?= (int) $b->idBudget ?>
            </div>
            <div style="min-width:0;">
              <div style="font-weight:500;color:var(--lx-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:-0.005em;">
                <?= htmlspecialchars($b->client_name ?? '—') ?>
              </div>
              <div style="font-size:11.5px;color:var(--lx-ink3);margin-top:3px;">
                <?= htmlspecialchars($b->vendor_name ?? '') ?>
                <span style="opacity:0.5;margin:0 4px;">·</span>
                <?= $fmtDate($b->date ?? '') ?>
              </div>
            </div>
            <div style="font-size:12.5px;color:var(--lx-ink2);">
              <?= htmlspecialchars($b->client_state ?? '—') ?>
            </div>
            <div class="tabular" style="text-align:right;font-size:13px;color:var(--lx-ink2);">
              <?= $itemsCount ?>
            </div>
            <div class="tabular" style="text-align:right;font-weight:600;font-size:13.5px;color:var(--lx-ink);letter-spacing:-0.01em;">
              <?= $fmtMoney($b->total) ?>
            </div>
            <div>
              <?php $this->load->view('sisvent/v2/_components/pill', [
                  'tone' => $stMeta['tone'], 'dot' => true, 'label' => $stMeta['label'],
              ]); ?>
            </div>
            <div style="color:var(--lx-ink3);text-align:right;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </div>
          </a>
        <?php endforeach; endif; ?>

        <!-- Paginación -->
        <?php if ($lastPage > 1): ?>
          <div style="
            display:flex;align-items:center;justify-content:space-between;
            padding:14px 22px; background:var(--lx-bg); font-size:12px; color:var(--lx-ink3);
            border-top: 1px solid var(--lx-line);
          ">
            <div>Página <?= $page ?> de <?= $lastPage ?> · <?= number_format($total, 0, ',', '.') ?> resultados</div>
            <div style="display:flex;gap:6px;">
              <?php if ($page > 1): ?>
                <a href="?estado=<?= urlencode($estado) ?>&p=<?= $page - 1 ?>" style="
                  padding:6px 12px;border:1px solid var(--lx-line);border-radius:6px;
                  color:var(--lx-ink2);text-decoration:none;font-weight:500;
                  background:var(--lx-surface);
                ">← Anterior</a>
              <?php endif; ?>
              <?php if ($page < $lastPage): ?>
                <a href="?estado=<?= urlencode($estado) ?>&p=<?= $page + 1 ?>" style="
                  padding:6px 12px;border:1px solid var(--lx-line);border-radius:6px;
                  color:var(--lx-ink2);text-decoration:none;font-weight:500;
                  background:var(--lx-surface);
                ">Siguiente →</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php $this->load->view('sisvent/v2/layouts/footer'); ?>
