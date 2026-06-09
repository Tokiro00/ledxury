<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso × Ledxury — Dashboard (Inicio)
 * Inspirado en reports/v2/daily_sales: período + KPIs comparativos + chart
 * + indicadores BOTS y GUÍAS + top vendedores + feed + WhatsApp.
 */
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function ($n) {
    $n = (float) $n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};
$ago = function($dt) {
    if (empty($dt)) return '—';
    $ts = is_numeric($dt) ? (int)$dt : strtotime($dt);
    if (!$ts) return '—';
    $d = time() - $ts;
    if ($d < 60)   return 'hace ' . max(1, $d) . 's';
    if ($d < 3600) return 'hace ' . floor($d / 60) . ' min';
    if ($d < 86400)return 'hace ' . floor($d / 3600) . 'h';
    if ($d < 86400*2) return 'ayer';
    return 'hace ' . floor($d / 86400) . ' días';
};

// Helper: pinta delta como "▲ 11.4%" o "▼ 5.2%"
$deltaBadge = function($pct, $label = 'vs anterior') {
    if ($pct == 0) return '<span class="pulso-kpi-delta-vs">sin cambio · ' . $label . '</span>';
    $up = $pct > 0;
    $cls = $up ? 'pulso-kpi-delta--up' : 'pulso-kpi-delta--dn';
    $arr = $up ? '▲' : '▼';
    return '<span class="pulso-kpi-delta-val ' . $cls . '">' . $arr . ' ' . number_format(abs($pct), 1) . '%</span>'
        . '<span class="pulso-kpi-delta-vs">' . htmlspecialchars($label) . '</span>';
};

$baseUrl = base_url('sisvent/dashboard');

// ===========================================================
// SVG chart helper — área + línea, periodo actual + anterior
// ===========================================================
$buildChart = function($cur, $prev, $labels) {
    if (empty($cur)) return '';
    $W = 1200; $H = 280;
    $padL = 50; $padR = 20; $padT = 16; $padB = 30;
    $max = max(max($cur), max($prev), 1);
    // Redondear max a próximo nice number
    $niceMax = pow(10, floor(log10($max)));
    while ($niceMax < $max) $niceMax += $niceMax / 2;
    $max = $niceMax;
    $n = count($cur);
    $stepX = $n > 1 ? ($W - $padL - $padR) / ($n - 1) : 0;
    $pt = function($i, $v) use ($padL, $padT, $H, $padB, $stepX, $max) {
        $x = $padL + $i * $stepX;
        $y = $H - $padB - ($v / $max) * ($H - $padT - $padB);
        return $x . ',' . $y;
    };
    // Curva actual
    $curPts = array(); foreach ($cur  as $i => $v) $curPts[]  = $pt($i, $v);
    $prevPts = array(); foreach ($prev as $i => $v) $prevPts[] = $pt($i, $v);
    $curPath  = 'M ' . implode(' L ', $curPts);
    $prevPath = 'M ' . implode(' L ', $prevPts);
    $areaPath = $curPath . ' L ' . ($padL + ($n-1) * $stepX) . ',' . ($H - $padB) . ' L ' . $padL . ',' . ($H - $padB) . ' Z';
    // Y axis ticks
    $ticks = '';
    for ($i = 0; $i <= 4; $i++) {
        $v = $max * (1 - $i / 4);
        $y = $padT + $i * (($H - $padT - $padB) / 4);
        $vLabel = $v >= 1000000 ? '$' . rtrim(rtrim(number_format($v / 1000000, 1, '.', ''), '0'), '.') . 'M'
                 : ($v >= 1000 ? '$' . round($v / 1000) . 'K' : '$' . round($v));
        $ticks .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($W - $padR) . '" y2="' . $y . '" stroke="#F0E8D6" stroke-width="1" />';
        $ticks .= '<text x="' . ($padL - 8) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#8B887E" font-family="Manrope">' . $vLabel . '</text>';
    }
    // X axis labels (cada N etiquetas para no saturar)
    $xLabels = '';
    $stepLabels = max(1, floor($n / 12));
    foreach ($labels as $i => $lbl) {
        if ($i % $stepLabels !== 0 && $i !== $n - 1) continue;
        $x = $padL + $i * $stepX;
        $xLabels .= '<text x="' . $x . '" y="' . ($H - 8) . '" text-anchor="middle" font-size="10" fill="#8B887E" font-family="Manrope">' . htmlspecialchars($lbl) . '</text>';
    }
    return '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%; height:100%;" preserveAspectRatio="xMidYMid meet">'
        . $ticks
        . '<path d="' . $areaPath . '" fill="#FF5A36" fill-opacity="0.07" />'
        . '<path d="' . $prevPath . '" stroke="#E3D9C4" stroke-width="1.5" stroke-dasharray="4 4" fill="none" />'
        . '<path d="' . $curPath  . '" stroke="#FF5A36" stroke-width="2.4" fill="none" stroke-linejoin="round" stroke-linecap="round" />'
        . $xLabels
        . '</svg>';
};

$presets = array(
    'hoy'        => 'Hoy',
    'ayer'       => 'Ayer',
    'semana'     => 'Esta semana',
    'mes'        => 'Este mes',
    'mes_pasado' => 'Mes pasado',
    'ytd'        => 'Año',
);
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Inicio · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">

    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <!-- Topbar custom: hero + selector período -->
        <header class="pulso-topbar" style="flex-wrap:wrap; gap:12px;">
            <div class="pulso-topbar-titles">
                <div class="pulso-hero-greet">Hola, <?= htmlspecialchars($firstName) ?> 👋</div>
                <div class="pulso-hero-title">
                    <?php if ($totalCur > 0): ?>
                        Vendiste <span class="pulso-hl"><?= $fmtShort($totalCur) ?></span> · <?= htmlspecialchars($periodLabel) ?>
                    <?php else: ?>
                        Sin ventas en <?= htmlspecialchars($periodLabel) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pulso-topbar-actions">
                <a href="<?= base_url('sisvent/commercial/budgets') ?>" class="pulso-btn pulso-btn--primary pulso-btn--pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14 M12 5v14"/>
                    </svg>
                    Nuevo
                </a>
            </div>

            <!-- Quick filters de período -->
            <div style="flex-basis:100%; display:flex; gap:6px; align-items:center; flex-wrap:wrap; padding-top:6px;">
                <?php foreach ($presets as $k => $v): ?>
                <a href="<?= $baseUrl . '?p=' . $k ?>"
                   class="pulso-chip <?= $preset === $k ? 'is-active' : '' ?>">
                    <?= htmlspecialchars($v) ?>
                </a>
                <?php endforeach; ?>
                <form method="GET" action="<?= $baseUrl ?>" style="display:inline-flex; align-items:center; gap:6px; margin-left:8px;">
                    <input type="hidden" name="p" value="custom">
                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" style="padding:6px 8px; border:1px solid var(--pulso-line); border-radius:8px; font-family:inherit; font-size:12px;">
                    <span style="color:var(--pulso-ink3);">→</span>
                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" style="padding:6px 8px; border:1px solid var(--pulso-line); border-radius:8px; font-family:inherit; font-size:12px;">
                    <button type="submit" class="pulso-btn pulso-btn--secondary pulso-btn--sm">Aplicar</button>
                </form>
                <span style="margin-left:auto; font-size:11px; color:var(--pulso-ink3); font-weight:600;">
                    Comparando vs <?= date('d/m', strtotime($prevFrom)) ?> – <?= date('d/m', strtotime($prevTo)) ?>
                </span>
            </div>
        </header>

        <div class="pulso-content">

            <!-- KPI strip — espejo reporte v1 Rendimiento Vendedores
                 Ventas (todas facturas emitidas) · # Facturas · Recaudo · Cartera · Ticket -->
            <div class="pulso-kpi-grid" style="grid-template-columns: 1.4fr 1fr 1fr 1fr 1fr;">

                <div class="pulso-kpi pulso-kpi--big">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Ventas</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($totalCur) ?></div>
                            <div class="pulso-kpi-sub"><?= number_format($factsCur, 0, ',', '.') ?> facturas emitidas</div>
                        </div>
                    </div>
                    <div class="pulso-kpi-delta">
                        <?= $deltaBadge($deltaPct, 'vs ' . $fmtShort($totalPrev) . ' (' . date('d/m', strtotime($prevFrom)) . '–' . date('d/m', strtotime($prevTo)) . ')') ?>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow"># Facturas</div>
                            <div class="pulso-kpi-value"><?= number_format($factsCur, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">Anterior: <?= number_format($factsPrev, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="pulso-kpi-delta"><?= $deltaBadge($factsDeltaPct) ?></div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Recaudo</div>
                            <div class="pulso-kpi-value" style="color:var(--pulso-mint);"><?= $fmtShort($recaudoCur) ?></div>
                            <div class="pulso-kpi-sub"><?= $totalCur > 0 ? round(($recaudoCur / $totalCur) * 100, 1) . '% de las ventas' : '—' ?></div>
                        </div>
                    </div>
                    <div class="pulso-kpi-delta"><?= $deltaBadge($recaudoDeltaPct) ?></div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Cartera pendiente</div>
                            <div class="pulso-kpi-value" style="color:<?= $carteraCur > 0 ? 'var(--pulso-warning)' : 'var(--pulso-ink3)' ?>;"><?= $fmtShort($carteraCur) ?></div>
                            <div class="pulso-kpi-sub">vendido por cobrar</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Ticket promedio</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($ticketCur) ?></div>
                            <div class="pulso-kpi-sub">vs <?= $fmtShort($ticketPrev) ?></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Chart Ventas por día -->
            <div class="pulso-card" style="margin-bottom: 20px;">
                <div style="display:flex; align-items:center; margin-bottom: 16px;">
                    <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                        Ventas por día
                    </div>
                    <div style="margin-left:auto; display:flex; gap:16px; font-size:11px; font-weight:600;">
                        <span style="display:inline-flex; align-items:center; gap:6px;">
                            <span style="width:14px; height:2.4px; background:var(--pulso-accent); border-radius:2px;"></span>
                            Período actual
                        </span>
                        <span style="display:inline-flex; align-items:center; gap:6px; color:var(--pulso-ink3);">
                            <span style="width:14px; height:1.5px; background:var(--pulso-line-strong); border-radius:2px; border-bottom:1.5px dashed var(--pulso-line-strong); position:relative;"></span>
                            Anterior
                        </span>
                    </div>
                </div>
                <div style="height:280px;">
                    <?= $buildChart($chartCur, $chartPrev, $chartLabels) ?>
                </div>
            </div>

            <!-- Indicadores BOTS · GUÍAS -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;" class="pulso-dash-grid">

                <!-- BOTS -->
                <div class="pulso-card" style="position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-50px; right:-50px; width:180px; height:180px; border-radius:50%; background: radial-gradient(circle, rgba(255,90,54,0.10), transparent 70%); pointer-events:none;"></div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px; position:relative;">
                        <svg width="28" height="28" viewBox="0 0 48 48" fill="none">
                            <rect width="48" height="48" rx="12" fill="var(--pulso-accent)"/>
                            <path d="M8 24 H14 L17 17 L22 31 L26 14 L31 28 L34 24 H40"
                                  stroke="var(--pulso-bg)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <div>
                            <div style="font-family:var(--pulso-font-display); font-size:20px; letter-spacing:-0.01em; line-height:1;">Bots WhatsApp</div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;"><?= $convsActivasHoy ?> activos hoy · <?= $convs ?> en el período</div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; position:relative;">
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Conversaciones</div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= number_format($convs, 0, ',', '.') ?></div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Cerradas en venta</div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1; color:var(--pulso-accent);"><?= number_format($convCerr, 0, ',', '.') ?></div>
                            <div style="font-size:11px; color:var(--pulso-mint); font-weight:700; margin-top:4px;"><?= number_format($tasaCierre, 1) ?>% conversión</div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Cobros vía bot</div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= $fmtShort($cobrosBotV) ?></div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:4px;"><?= number_format($cobrosBotN, 0, ',', '.') ?> facturas</div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Resueltas sin humano</div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= number_format($convResu, 0, ',', '.') ?></div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:4px;"><?= number_format($tasaResol, 1) ?>% del total</div>
                        </div>
                    </div>
                </div>

                <!-- GUÍAS -->
                <div class="pulso-card" style="position:relative; overflow:hidden;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                        <div style="width:28px; height:28px; border-radius:12px; background:var(--pulso-ink); color:var(--pulso-bg); display:grid; place-items:center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                                <path d="M15 18H9 M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                                <circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-family:var(--pulso-font-display); font-size:20px; letter-spacing:-0.01em; line-height:1;">Guías Interrapidísimo</div>
                            <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;"><?= number_format($gTotal, 0, ',', '.') ?> generadas en el período · <?= number_format($tasaEntrega, 1) ?>% entregadas</div>
                        </div>
                    </div>

                    <!-- Barra apilada -->
                    <?php if ($gTotal > 0):
                        $pE = round($gEntregadas / $gTotal * 100, 1);
                        $pT = round($gTransito   / $gTotal * 100, 1);
                        $pP = round($gPend       / $gTotal * 100, 1);
                        $pA = round($gAnuladas   / $gTotal * 100, 1);
                    ?>
                    <div style="display:flex; height:12px; border-radius:99px; overflow:hidden; margin-bottom:14px; background:var(--pulso-bg);">
                        <?php if ($pE > 0): ?><div style="width:<?= $pE ?>%; background:var(--pulso-mint);" title="Entregadas: <?= $gEntregadas ?>"></div><?php endif; ?>
                        <?php if ($pT > 0): ?><div style="width:<?= $pT ?>%; background:var(--pulso-accent);" title="Tránsito: <?= $gTransito ?>"></div><?php endif; ?>
                        <?php if ($pP > 0): ?><div style="width:<?= $pP ?>%; background:var(--pulso-butter);" title="Pendientes: <?= $gPend ?>"></div><?php endif; ?>
                        <?php if ($pA > 0): ?><div style="width:<?= $pA ?>%; background:var(--pulso-danger);" title="Anuladas: <?= $gAnuladas ?>"></div><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:99px; background:var(--pulso-mint);"></span>
                                <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Entregadas</div>
                            </div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= number_format($gEntregadas, 0, ',', '.') ?></div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:99px; background:var(--pulso-accent);"></span>
                                <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">En tránsito</div>
                            </div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= number_format($gTransito, 0, ',', '.') ?></div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:99px; background:var(--pulso-butter);"></span>
                                <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Pendientes</div>
                            </div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1;"><?= number_format($gPend, 0, ',', '.') ?></div>
                        </div>
                        <div style="padding:12px; background:var(--pulso-bg); border-radius:10px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:99px; background:var(--pulso-danger);"></span>
                                <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600;">Anuladas</div>
                            </div>
                            <div style="font-family:var(--pulso-font-display); font-size:24px; margin-top:4px; line-height:1; color:<?= $gAnuladas > 0 ? 'var(--pulso-danger)' : 'var(--pulso-ink)' ?>;"><?= number_format($gAnuladas, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Ventas por mes · por bot integrado -->
            <?php if (!empty($botsData)): ?>
            <div class="pulso-card" style="margin-bottom: 20px;">
                <div style="display:flex; align-items:center; margin-bottom: 16px;">
                    <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                        Ventas por mes · bots integrados
                    </div>
                    <div style="margin-left:auto; font-size:11px; color: var(--pulso-ink3); font-weight:600;">
                        Últimos 6 meses · por fecha de emisión
                    </div>
                </div>

                <?php
                // Calcular max global para escalar sparklines proporcionalmente
                $globalMax = 1;
                foreach ($botsData as $bd) {
                    if (!empty($bd['serie'])) {
                        $m = max($bd['serie']);
                        if ($m > $globalMax) $globalMax = $m;
                    }
                }
                ?>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
                    <?php foreach ($botsData as $bot):
                        $delta = 0;
                        if ($bot['prev_month'] > 0) {
                            $delta = round((($bot['last_month'] - $bot['prev_month']) / $bot['prev_month']) * 100, 1);
                        }
                        $sparkData = implode(',', array_map(function($v) { return round($v / 1000); }, $bot['serie']));
                        // Color por bot (rotación)
                        $palette = array('#FF5A36', '#FFD66B', '#2BB673', '#1A1B23');
                        $color = $palette[($bot['id'] - 1) % count($palette)];
                    ?>
                    <div style="padding:16px; background:var(--pulso-bg); border-radius:12px; border:1px solid var(--pulso-line);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <div>
                                <div style="font-size:13px; font-weight:700; color:var(--pulso-ink); line-height:1.1;">
                                    <?= htmlspecialchars($bot['name']) ?>
                                </div>
                                <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;">
                                    vendor: <?= htmlspecialchars($bot['vendor_id']) ?>
                                </div>
                            </div>
                            <?php if ($delta != 0): ?>
                            <span style="font-size:11px; font-weight:700; color:<?= $delta >= 0 ? 'var(--pulso-mint)' : 'var(--pulso-danger)' ?>;">
                                <?= $delta >= 0 ? '▲' : '▼' ?> <?= number_format(abs($delta), 1) ?>%
                            </span>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:8px; margin-top:10px;">
                            <div>
                                <div style="font-family:var(--pulso-font-display); font-size:24px; line-height:1; color:var(--pulso-ink);">
                                    <?= $fmtShort($bot['total']) ?>
                                </div>
                                <div style="font-size:11px; color:var(--pulso-ink3); margin-top:4px;">
                                    <?= number_format($bot['facts'], 0, ',', '.') ?> facturas · 6m
                                </div>
                            </div>
                            <?php if ($sparkData !== '' && count($bot['serie']) > 1): ?>
                            <span data-pulso-sparkline="<?= $sparkData ?>" data-color="<?= $color ?>" data-width="130" data-height="40"></span>
                            <?php endif; ?>
                        </div>

                        <!-- Mini barras por mes -->
                        <div style="display:grid; grid-template-columns: repeat(<?= count($bot['serie']) ?>, 1fr); gap:3px; margin-top:12px; height:24px; align-items:flex-end;">
                            <?php foreach ($bot['serie'] as $i => $val):
                                $h = $globalMax > 0 ? max(3, round(($val / $globalMax) * 24)) : 3;
                                $monthLabel = !empty($botMonths[$i]) ? date('M', strtotime($botMonths[$i] . '-01')) : '';
                                $isLast = ($i === count($bot['serie']) - 1);
                            ?>
                            <div title="<?= $monthLabel ?> · <?= $fmtShort($val) ?>" style="background:<?= $isLast ? $color : 'rgba(0,0,0,0.10)' ?>; height:<?= $h ?>px; border-radius:2px;"></div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:grid; grid-template-columns: repeat(<?= count($botMonths) ?>, 1fr); gap:3px; margin-top:4px; font-size:9.5px; color:var(--pulso-ink3); text-align:center;">
                            <?php foreach ($botMonths as $m): ?>
                            <div><?= date('M', strtotime($m . '-01')) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top vendedores + Saldos -->
            <div style="display:grid; grid-template-columns: 1.3fr 1fr; gap: 16px; margin-bottom: 20px;" class="pulso-dash-grid">

                <div class="pulso-card" style="padding:0; overflow:hidden;">
                    <div style="display:flex; align-items:center; padding:18px 20px 12px;">
                        <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                            Rendimiento vendedores · <?= htmlspecialchars($periodLabel) ?>
                        </div>
                        <a href="<?= base_url('sisvent/admin/reports/v2/vendor_performance') ?>" style="margin-left:auto; font-size:12px; color:var(--pulso-accent); font-weight:600; text-decoration:none;">
                            Reporte completo →
                        </a>
                    </div>
                    <?php if (empty($topVendedores)): ?>
                        <div style="padding: 24px; text-align:center; color:var(--pulso-ink3); font-size:13px;">
                            Sin ventas en este período.
                        </div>
                    <?php else:
                        $maxVol = max(array_map(function($v) { return (float)$v->volumen; }, $topVendedores));
                    ?>
                    <table class="pulso-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th class="pulso-text-right">Facts · Clientes</th>
                                <th class="pulso-text-right">Ventas</th>
                                <th class="pulso-text-right">Recaudo</th>
                                <th class="pulso-text-right">Cartera</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topVendedores as $i => $v):
                            $vendorName = $v->name ?: 'Sin asignar';
                            $words = preg_split('/\s+/', trim($vendorName));
                            $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                            $pct = $maxVol > 0 ? round((float)$v->volumen / $maxVol * 100) : 0;
                            $recPct = ($v->volumen ?? 0) > 0 ? round(((float)$v->recaudo / (float)$v->volumen) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="pulso-avatar" style="width:30px;height:30px;font-size:11px;"><?= htmlspecialchars($initials) ?: '?' ?></span>
                                    <div style="min-width:0;">
                                        <div style="font-size:13px; font-weight:600; color:var(--pulso-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                                            <?= htmlspecialchars($vendorName) ?>
                                        </div>
                                        <div style="margin-top:4px; height:3px; background:var(--pulso-bg); border-radius:99px; overflow:hidden; width:140px;">
                                            <div style="height:100%; width:<?= $pct ?>%; background:var(--pulso-accent); border-radius:99px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="pulso-text-right" style="font-size:12px;color:var(--pulso-ink3);">
                                <?= number_format($v->n_facts, 0, ',', '.') ?> · <?= number_format($v->n_clients ?? 0, 0, ',', '.') ?>
                            </td>
                            <td class="pulso-text-right">
                                <div style="font-family:var(--pulso-font-display); font-size:15px; color:var(--pulso-ink); line-height:1;">
                                    <?= $fmtShort($v->volumen) ?>
                                </div>
                            </td>
                            <td class="pulso-text-right">
                                <div style="font-family:var(--pulso-font-display); font-size:15px; color:var(--pulso-mint); line-height:1;">
                                    <?= $fmtShort($v->recaudo ?? 0) ?>
                                </div>
                                <div style="font-size:10px; color:var(--pulso-ink3); margin-top:3px;"><?= $recPct ?>%</div>
                            </td>
                            <td class="pulso-text-right">
                                <div style="font-family:var(--pulso-font-display); font-size:15px; color:var(--pulso-warning); line-height:1;">
                                    <?= $fmtShort($v->cartera ?? 0) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <div class="pulso-card">
                    <div style="display:flex; align-items:center; margin-bottom: 14px;">
                        <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                            Saldos
                        </div>
                        <a href="<?= base_url('sisvent/admin/financialdashboard') ?>" style="margin-left:auto; font-size:12px; color:var(--pulso-accent); font-weight:600; text-decoration:none;">
                            Ver detalle →
                        </a>
                    </div>
                    <?php
                    $cuentas = array();
                    foreach ($cajasActivas as $c) $cuentas[] = array('name'=>$c->name,'balance'=>(float)$c->currentBalance,'type'=>'caja');
                    foreach ($bancosActivos as $b) $cuentas[] = array('name'=>$b->name,'balance'=>(float)$b->currentBalance,'type'=>'banco');
                    if (empty($cuentas)): ?>
                        <div style="padding: 24px; text-align:center; color:var(--pulso-ink3); font-size:13px;">Sin cuentas.</div>
                    <?php else: $top6 = array_slice($cuentas, 0, 6);
                        foreach ($top6 as $i => $c): ?>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 0; <?= $i < count($top6) - 1 ? 'border-bottom:1px solid var(--pulso-line);' : '' ?>">
                        <div style="width:8px; height:8px; border-radius:99px; background:<?= $c['type'] === 'banco' ? 'var(--pulso-mint)' : 'var(--pulso-butter)' ?>; flex:0 0 auto;"></div>
                        <div style="flex:1; min-width:0; font-size:13px; color:var(--pulso-ink2); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($c['name']) ?></div>
                        <div style="font-family:var(--pulso-font-mono); font-size:13px; font-weight:600; color:var(--pulso-ink); flex:0 0 auto;"><?= $fmtShort($c['balance']) ?></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

            </div>

            <!-- Feed + WhatsApp -->
            <div class="pulso-dash-grid" style="display:grid; grid-template-columns: 1.3fr 1fr; gap: 16px;">

                <div class="pulso-card">
                    <div style="display:flex; align-items:center; margin-bottom: 14px;">
                        <div style="font-family: var(--pulso-font-display); font-size: 22px; letter-spacing: -0.01em; color: var(--pulso-ink);">
                            Lo que está pasando
                        </div>
                        <div style="margin-left:auto; font-size:12px; color:var(--pulso-accent); font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                            <span style="width:7px; height:7px; border-radius:99px; background: var(--pulso-accent); animation: pulse 1.6s infinite;"></span>
                            en vivo
                        </div>
                    </div>

                    <?php if (empty($feed)): ?>
                        <div style="padding: 32px; text-align:center; color:var(--pulso-ink3); font-size:13px;">Sin actividad reciente.</div>
                    <?php else:
                        foreach ($feed as $i => $a):
                            $iconSvg = '';
                            if ($a['icon'] === 'card') {
                                $iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>';
                            } elseif ($a['icon'] === 'truck') {
                                $iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9 M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>';
                            } else {
                                $iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
                            }
                            $bgColor = array('mint'=>'#2BB673','ink'=>'#1A1B23','butter'=>'#FFD66B','danger'=>'#D63A1A','coral'=>'#FF5A36')[$a['tone']] ?? '#1A1B23';
                            $fgColor = ($a['tone'] === 'butter') ? '#1A1B23' : '#FFF7EE';
                    ?>
                        <div style="display:flex; align-items:center; gap:14px; padding:12px 0; <?= $i < count($feed) - 1 ? 'border-bottom: 1px solid var(--pulso-line);' : '' ?>">
                            <div style="width:38px; height:38px; border-radius:12px; background:<?= $bgColor ?>; color:<?= $fgColor ?>; display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                                <?= $iconSvg ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:14px; font-weight:700; color:var(--pulso-ink); letter-spacing:-0.005em;"><?= htmlspecialchars($a['title']) ?></div>
                                <div style="font-size:12px; color:var(--pulso-ink3); margin-top:2px;"><?= htmlspecialchars($a['sub']) ?></div>
                            </div>
                            <div style="font-size:11px; color:var(--pulso-ink3); font-weight:600; flex:0 0 auto;"><?= $ago($a['when']) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div style="background: var(--pulso-ink); color: var(--pulso-bg); border-radius: var(--pulso-radius-xl); padding: 18px; display:flex; flex-direction:column; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-60px; right:-60px; width:200px; height:200px; border-radius:50%; background: radial-gradient(circle, rgba(255,90,54,0.33), transparent 70%); pointer-events:none;"></div>

                    <div style="display:flex; align-items:center; gap:10px; position:relative;">
                        <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                            <rect width="48" height="48" rx="14" fill="var(--pulso-accent)"/>
                            <path d="M8 24 H14 L17 17 L22 31 L26 14 L31 28 L34 24 H40"
                                  stroke="var(--pulso-bg)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <div>
                            <div style="font-size:14px; font-weight:700;">WhatsApp · ventas</div>
                            <div style="font-size:11px; color:#9C988E;"><?= number_format($convsActivasHoy, 0, ',', '.') ?> activos hoy</div>
                        </div>
                    </div>

                    <div style="margin-top:16px; display:flex; flex-direction:column; gap:8px; position:relative; flex:1; min-height:170px;">
                        <?php if (empty($activeConvs)): ?>
                            <div style="padding:32px; text-align:center; color:#9C988E; font-size:13px;">Sin chats activos.</div>
                        <?php else: foreach ($activeConvs as $c):
                                $msg = $c->last_message ?: '';
                                if (mb_strlen($msg) > 75) $msg = mb_substr($msg, 0, 72) . '…';
                                $isOut = ($c->last_direction === 'out');
                        ?>
                            <div style="background:<?= $isOut ? 'var(--pulso-accent)' : '#2E2F3A' ?>; padding:9px 12px; border-radius:<?= $isOut ? '12px 12px 3px 12px' : '12px 12px 12px 3px' ?>; font-size:12px; align-self:<?= $isOut ? 'flex-end' : 'flex-start' ?>; max-width:85%; color:<?= $isOut ? 'var(--pulso-bg)' : '#FFF7EE' ?>;">
                                <?php if (!$isOut && !empty($c->client_name)): ?>
                                    <div style="font-size:10px; opacity:0.6; margin-bottom:2px;"><?= htmlspecialchars($c->client_name) ?></div>
                                <?php endif; ?>
                                <?= htmlspecialchars($msg) ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <div style="margin-top:auto; padding-top:14px; display:flex; gap:10px; font-size:11px; position:relative;">
                        <div style="flex:1; padding:10px; background:#2E2F3A; border-radius:10px;">
                            <div style="color:#9C988E;">Cobros bot</div>
                            <div style="font-family: var(--pulso-font-display); font-size:20px; margin-top:2px;"><?= $fmtShort($cobrosBotV) ?></div>
                        </div>
                        <div style="flex:1; padding:10px; background:#2E2F3A; border-radius:10px;">
                            <div style="color:#9C988E;">Conversión</div>
                            <div style="font-family: var(--pulso-font-display); font-size:20px; margin-top:2px; color: var(--pulso-butter);">
                                <?= number_format($tasaCierre, 1) ?>%
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div><!-- /pulso-content -->

    </main>

</div><!-- /pulso-shell -->

<style>
@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.4); }
    100% { opacity: 1; transform: scale(1); }
}
@media (max-width: 1100px) {
    .pulso-dash-grid { grid-template-columns: 1fr !important; }
    .pulso-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>

</body>
</html>
