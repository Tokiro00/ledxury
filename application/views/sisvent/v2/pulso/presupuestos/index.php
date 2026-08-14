<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso × Ledxury — Presupuestos (listado)
 *
 * Port directo de rebrand/Pedidos.jsx + pulso-screens.jsx → PulsoPresupuestosScreen.
 * KPI strip + filter chips + tabla con avatar/estado/monto.
 *
 * Inputs del controller:
 *   $pageTitle, $breadcrumbs, $activeRoute
 *   $budgets (array de objetos del Budgets_model)
 *   $estado, $page, $lastPage, $total, $counts
 *   $series (sparklines), $deltas, $valorTotal
 *
 * Estados Ledxury: 0=nuevo, 1=preparando, 2=guia, 3=transito, 4=entregado, 5=incidencia
 */
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function ($n) {
    $n = (float) $n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};

$estadoMap = array(
    'nuevo'      => array('label' => 'nuevo',       'tone' => 'warning'),
    'preparando' => array('label' => 'preparando',  'tone' => 'info'),
    'guia'       => array('label' => 'con guía',    'tone' => 'neutral'),
    'transito'   => array('label' => 'en tránsito', 'tone' => 'info'),
    'entregado'  => array('label' => 'entregado',   'tone' => 'success'),
    'incidencia' => array('label' => 'incidencia',  'tone' => 'danger'),
);
$stateNumToKey = array(0=>'nuevo', 1=>'preparando', 2=>'guia', 3=>'transito', 4=>'entregado', 5=>'incidencia');

// Sparklines (controller pasa $series)
$sparkTodos     = isset($series['todos'])      ? implode(',', $series['todos'])      : '';
$sparkNuevo     = isset($series['nuevo'])      ? implode(',', $series['nuevo'])      : '';
$sparkPreparado = isset($series['preparando']) ? implode(',', $series['preparando']) : '';
$sparkEntregado = isset($series['entregado'])  ? implode(',', $series['entregado'])  : '';

$totPct       = isset($deltas['total_pct']) ? (float)$deltas['total_pct'] : 0;
$valorPct     = isset($deltas['valor_pct']) ? (float)$deltas['valor_pct'] : 0;
$baseUrl      = base_url('sisvent/commercial/budgets');
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title><?= htmlspecialchars($pageTitle) ?> · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">

    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <?php
        // Topbar actions — estilo Pulso: "+ Nuevo" en NEGRO pill, sin Exportar
        $topbarActions = '
            <a href="' . base_url('sisvent/commercial/budgets/add') . '" class="pulso-btn pulso-btn--primary pulso-btn--pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14 M12 5v14"/>
                </svg>
                Nuevo
            </a>
        ';
        $this->load->view('sisvent/v2/pulso/layouts/topbar', array(
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'topbarActions' => $topbarActions,
        ));
        ?>

        <div class="pulso-content">

            <!-- Hero -->
            <?php
                $ud = $this->session->userdata('user_data') ?: array();
                $firstName = !empty($ud['name']) ? strtok($ud['name'], ' ') : ($ud['uname'] ?? 'Equipo');
            ?>
            <div class="pulso-hero" style="display:flex; align-items:flex-end; justify-content:space-between; gap:24px; flex-wrap:wrap;">
                <div>
                    <div class="pulso-hero-greet">Hola, <?= htmlspecialchars($firstName) ?> 👋</div>
                    <div class="pulso-hero-title">
                        Llevas <span class="pulso-hl"><?= str_replace('$', '$', call_user_func($fmtShort, $valorTotal)) ?></span> en <?= number_format($counts['todos'] ?? 0, 0, ',', '.') ?> presupuestos
                    </div>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="pulso-kpi-grid" style="grid-template-columns: 1.4fr 1fr 1fr 1fr;">

                <div class="pulso-kpi pulso-kpi--big">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Volumen del mes</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($valorTotal) ?></div>
                            <div class="pulso-kpi-sub"><?= number_format($counts['todos'], 0, ',', '.') ?> presupuestos creados</div>
                        </div>
                        <?php if ($sparkTodos): ?>
                        <span data-pulso-sparkline="<?= $sparkTodos ?>" data-color="#FF5A36"></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($totPct !== 0.0): ?>
                    <div class="pulso-kpi-delta">
                        <span class="pulso-kpi-delta-val <?= $totPct >= 0 ? 'pulso-kpi-delta--up' : 'pulso-kpi-delta--dn' ?>">
                            <?= $totPct >= 0 ? '▲' : '▼' ?> <?= number_format(abs($totPct), 1) ?>%
                        </span>
                        <span class="pulso-kpi-delta-vs">vs período anterior</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Nuevos</div>
                            <div class="pulso-kpi-value"><?= number_format($counts['nuevo'] ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">esperan atención</div>
                        </div>
                        <?php if ($sparkNuevo): ?>
                        <span data-pulso-sparkline="<?= $sparkNuevo ?>" data-color="#E8A12A"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Preparando</div>
                            <div class="pulso-kpi-value"><?= number_format($counts['preparando'] ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">en proceso bodega</div>
                        </div>
                        <?php if ($sparkPreparado): ?>
                        <span data-pulso-sparkline="<?= $sparkPreparado ?>" data-color="#1A1B23"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Con guía / despachados</div>
                            <div class="pulso-kpi-value"><?= number_format($counts['guia'] ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">listos para envío</div>
                        </div>
                        <?php if ($sparkEntregado): ?>
                        <span data-pulso-sparkline="<?= $sparkEntregado ?>" data-color="#2BB673"></span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Filter chips -->
            <div class="pulso-filters">
                <?php
                $chips = array(
                    'todos'      => array('Todos',       $counts['todos']      ?? 0),
                    'nuevo'      => array('Nuevos',      $counts['nuevo']      ?? 0),
                    'preparando' => array('Preparando',  $counts['preparando'] ?? 0),
                    'guia'       => array('Con guía',    $counts['guia']       ?? 0),
                );
                // Solo agregar transito/entregado si hay datos (Ledxury en general no usa estos)
                if (($counts['transito'] ?? 0) > 0) $chips['transito']  = array('En tránsito', $counts['transito']);
                if (($counts['entregado'] ?? 0) > 0) $chips['entregado'] = array('Entregados',  $counts['entregado']);
                foreach ($chips as $key => $info):
                    list($label, $count) = $info;
                    $active = ($estado === $key);
                ?>
                <a href="<?= $baseUrl . '?estado=' . $key ?>" class="pulso-chip <?= $active ? 'is-active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                    <span class="pulso-chip-count"><?= number_format($count, 0, ',', '.') ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Tabla de presupuestos -->
            <div class="pulso-table-wrap">
                <table class="pulso-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Ciudad</th>
                            <th class="pulso-text-right">Items</th>
                            <th class="pulso-text-right">Valor</th>
                            <th>Estado</th>
                            <th>Tiempo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($budgets)): ?>
                        <tr>
                            <td colspan="8" style="padding:48px;text-align:center;color:var(--pulso-ink3);">
                                Sin presupuestos en este estado.
                            </td>
                        </tr>
                        <?php else: foreach ($budgets as $b):
                            $estadoKey = $stateNumToKey[(int)$b->state] ?? 'nuevo';
                            $estadoInfo = $estadoMap[$estadoKey];
                            $clientName = !empty($b->client_name) ? $b->client_name : 'Cliente sin nombre';
                            $words = preg_split('/\s+/', trim($clientName));
                            $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                            $ciudad = $b->city ?? '—';
                            $fecha = $b->date ?? $b->created_at ?? '';
                            $tiempo = $fecha ? human_diff($fecha) : '—';
                            $detalleUrl = base_url('sisvent/commercial/budgets/' . (int)$b->idBudget);
                        ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= $detalleUrl ?>'">
                            <td>
                                <span class="pulso-mono" style="color:var(--pulso-ink);">#<?= str_pad($b->idBudget, 6, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span class="pulso-avatar" style="width:28px;height:28px;font-size:11px;">
                                        <?= htmlspecialchars($initials) ?: '?' ?>
                                    </span>
                                    <div>
                                        <div style="font-weight:500;color:var(--pulso-ink);"><?= htmlspecialchars($clientName) ?></div>
                                        <?php if (!empty($b->vendor_name)): ?>
                                        <div style="font-size:11px;color:var(--pulso-ink3);">Vendedor: <?= htmlspecialchars($b->vendor_name) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--pulso-ink2);"><?= htmlspecialchars($ciudad) ?></td>
                            <td class="pulso-text-right pulso-num"><?= (int)($b->items ?? 0) ?></td>
                            <td class="pulso-text-right pulso-num" style="font-weight:500;color:var(--pulso-ink);">
                                <?= $fmt($b->total ?? 0) ?>
                            </td>
                            <td>
                                <span class="pulso-pill pulso-pill--<?= $estadoInfo['tone'] ?>">
                                    <span class="pulso-dot"></span>
                                    <?= htmlspecialchars($estadoInfo['label']) ?>
                                </span>
                            </td>
                            <td style="color:var(--pulso-ink3);font-size:12px;"><?= htmlspecialchars($tiempo) ?></td>
                            <td class="pulso-text-right">
                                <button type="button" class="pulso-icon-btn" style="width:28px;height:28px;" onclick="event.stopPropagation();">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($lastPage > 1): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding:0 4px;">
                <div style="font-size:12px;color:var(--pulso-ink3);">
                    Página <?= $page ?> de <?= $lastPage ?> · <?= number_format($total, 0, ',', '.') ?> en total
                </div>
                <div style="display:flex;gap:6px;">
                    <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl ?>?estado=<?= $estado ?>&p=<?= $page - 1 ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $lastPage): ?>
                    <a href="<?= $baseUrl ?>?estado=<?= $estado ?>&p=<?= $page + 1 ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">Siguiente →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /pulso-content -->

    </main><!-- /pulso-main -->

</div><!-- /pulso-shell -->

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>

</body>
</html>

<?php
// Helper local: tiempo humano (sin namespace porque la vista es inline)
function human_diff($datetime) {
    if (empty($datetime)) return '—';
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)        return 'Hace ' . $diff . 's';
    if ($diff < 3600)      return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)     return 'Hace ' . floor($diff / 3600) . 'h';
    if ($diff < 86400 * 2) return 'Ayer';
    if ($diff < 86400 * 7) return 'Hace ' . floor($diff / 86400) . ' días';
    return date('d M', $ts);
}
?>
