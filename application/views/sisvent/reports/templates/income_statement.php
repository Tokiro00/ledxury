<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template income_statement — Estado de Resultados PUC Colombia.
 *
 * Estructura:
 *   1. KPI strip (4 tiles): Ingresos, U.Bruta+%, U.Operacional+%, U.Neta+%
 *   2. Tabla jerárquica con secciones (Ingresos, Costos, Gastos, etc.)
 *   3. Cada grupo expandible a subcuentas (drill-down con <details>)
 */

$kpis           = $data['kpis']            ?? [];
$groupsByClass  = $data['groups_by_class'] ?? [];
$sections       = $data['sections']        ?? [];
$utilidadBruta  = $data['utilidad_bruta']  ?? 0;
$utilidadOp     = $data['utilidad_op']     ?? 0;
$utilidadNeta   = $data['utilidad_neta']   ?? 0;
$isPdf          = !empty($pdf_mode);

$fmt = function ($v) {
    if (!is_numeric($v)) return $v;
    return number_format((float)$v, 0, ',', '.');
};
$fmtMoney = function ($v) use ($fmt) {
    if (!is_numeric($v)) return '—';
    $sign = $v < 0 ? '-' : '';
    return $sign . '$' . $fmt(abs($v));
};
$fmtCompact = function ($v) use ($fmt) {
    if (!is_numeric($v)) return $v;
    $abs = abs((float)$v); $sign = $v < 0 ? '-' : '';
    if ($abs >= 1000000000) return $sign . '$' . $fmt($abs/1000000000) . 'B';
    if ($abs >= 1000000)    return $sign . '$' . $fmt($abs/1000000) . 'M';
    if ($abs >= 1000)       return $sign . '$' . $fmt($abs/1000) . 'K';
    return $sign . '$' . $fmt($abs);
};
$growthPct = function ($curr, $prior) {
    if ($prior == 0) return null;
    return round((($curr - $prior) / abs($prior)) * 100, 1);
};

$compare = !empty($kpis['compare']);
$ingresos = (float)($kpis['ingresos'] ?? 0);
$utNeta   = (float)($kpis['utilidad_neta'] ?? 0);
$margenNetoCol = $utNeta >= 0 ? '#5EBA47' : '#C0392B';
$margenBruto = (float)($kpis['margen_bruto_pct'] ?? 0);
$bgr = $margenBruto >= 30 ? '#5EBA47' : ($margenBruto >= 15 ? '#F39C12' : '#C0392B');
?>

<!-- ============== KPI STRIP ============== -->
<?php
$gIng = $compare ? $growthPct($ingresos, $kpis['ingresos_prior']) : null;
$gNeta = $compare ? $growthPct($utNeta, $kpis['utilidad_neta_prior']) : null;
$kpiTiles = [
    [
        'eyebrow' => 'Ingresos',
        'value'   => $fmtCompact($ingresos),
        'delta'   => $compare && $gIng !== null ? ($gIng >= 0 ? '▲ ' : '▼ ') . abs($gIng) . '% vs anterior' : 'Período actual',
        'delta_tone' => $compare && $gIng !== null ? ($gIng >= 0 ? 'up' : 'dn') : 'fg4',
        'accent'  => 'var(--mam-blue-petroleo, #4487A0)',
        'pdf_color' => '#4487A0',
    ],
    [
        'eyebrow' => 'Utilidad Bruta',
        'value'   => $fmtCompact($utilidadBruta),
        'delta'   => $fmt($margenBruto) . '% margen',
        'delta_tone' => $margenBruto >= 30 ? 'up' : ($margenBruto >= 15 ? 'mid' : 'dn'),
        'accent'  => $bgr,
        'pdf_color' => $bgr,
    ],
    [
        'eyebrow' => 'Utilidad Operacional',
        'value'   => $fmtCompact($utilidadOp),
        'delta'   => $fmt($kpis['margen_op_pct'] ?? 0) . '% margen',
        'delta_tone' => 'fg4',
        'accent'  => 'var(--mam-blue-dark, #2B3164)',
        'pdf_color' => '#2B3164',
    ],
    [
        'eyebrow' => 'Utilidad Neta',
        'value'   => $fmtCompact($utNeta),
        'delta'   => $compare && $gNeta !== null ? ($gNeta >= 0 ? '▲ ' : '▼ ') . abs($gNeta) . '% vs anterior' : $fmt($kpis['margen_neto_pct'] ?? 0) . '% margen',
        'delta_tone' => $utNeta >= 0 ? 'up' : 'dn',
        'accent'  => $margenNetoCol,
        'pdf_color' => $margenNetoCol,
    ],
];
?>
<?php if ($isPdf): ?>
    <table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:10px;border-collapse:separate;border-spacing:5px 0;">
        <tr><?php foreach ($kpiTiles as $t): ?>
            <td style="width:25%;border:1px solid #DDDFE8;border-left:3px solid <?= $t['pdf_color'] ?>;padding:7px 10px;background:#FAFBFC;">
                <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;"><?= htmlspecialchars($t['eyebrow']) ?></div>
                <div style="font-size:15px;font-weight:800;color:#2B3164;margin-top:2px;"><?= $t['value'] ?></div>
                <div style="font-size:8px;color:#7F8392;margin-top:1px;"><?= htmlspecialchars($t['delta']) ?></div>
            </td>
        <?php endforeach; ?></tr>
    </table>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
        <?php foreach ($kpiTiles as $t): $this->load->view('sisvent/design-system/_kpi_tile', $t); endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============== TABLA P&L JERÁRQUICA ============== -->
<div style="background:#fff;border:1px solid #DDDFE8;border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#2B3164;color:#fff;">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Concepto</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:140px;">Período</th>
                <?php if ($compare): ?>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:140px;">Anterior</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:80px;">Δ %</th>
                <?php endif; ?>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:60px;">% Ing</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Para el rendering, iteramos las secciones y construimos rows
            $colspan = $compare ? 5 : 3;
            $renderRow = function($label, $saldo, $saldoPrior, $level, $isSubtotal = false, $isFinal = false) use ($fmtMoney, $fmt, $compare, $growthPct, $ingresos) {
                $indent  = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                $bg      = $isFinal ? '#1B365D' : ($isSubtotal ? '#F1F3F5' : 'transparent');
                $color   = $isFinal ? '#fff' : ($isSubtotal ? '#2B3164' : '#575964');
                $weight  = $isFinal ? '900' : ($isSubtotal ? '700' : '400');
                $border  = $isFinal ? 'border-top:2px solid #2B3164;border-bottom:2px solid #2B3164;' : ($isSubtotal ? 'border-top:1px solid #DDDFE8;' : '');
                $fontSize = $isFinal ? '13px' : '12px';
                $pctIng = $ingresos > 0 ? round(($saldo / $ingresos) * 100, 1) : 0;
                $delta = $compare && $saldoPrior != 0 ? $growthPct($saldo, $saldoPrior) : null;
                $deltaColor = $delta !== null && $delta < 0 ? '#C0392B' : '#5EBA47';
                ?>
                <tr style="background:<?= $bg ?>;color:<?= $color ?>;font-weight:<?= $weight ?>;font-size:<?= $fontSize ?>;<?= $border ?>">
                    <td style="padding:5px 12px;"><?= $indent . $label ?></td>
                    <td style="padding:5px 12px;text-align:right;font-family:monospace;"><?= $fmtMoney($saldo) ?></td>
                    <?php if ($compare): ?>
                    <td style="padding:5px 12px;text-align:right;font-family:monospace;color:<?= $isFinal ? '#fff' : '#7F8392' ?>;font-size:11px;"><?= $fmtMoney($saldoPrior) ?></td>
                    <td style="padding:5px 12px;text-align:right;font-family:monospace;font-size:11px;color:<?= $delta === null ? '#AEAAA6' : $deltaColor ?>;"><?= $delta === null ? '—' : ($delta > 0 ? '+' : '') . $fmt($delta) . '%' ?></td>
                    <?php endif; ?>
                    <td style="padding:5px 12px;text-align:right;font-family:monospace;font-size:11px;color:<?= $isFinal ? '#fff' : '#7F8392' ?>;"><?= $fmt($pctIng) ?>%</td>
                </tr>
                <?php
            };
            ?>

            <?php foreach ($sections as $sec):
                $sectionSaldo = 0;
                $sectionPrior = 0;
                // Calcular saldo de la sección agregando grupos relevantes
                foreach ($sec['classes'] as $cls):
                    if (!isset($groupsByClass[$cls])) continue;
                    foreach ($groupsByClass[$cls]['groups'] as $grpCode => $grp):
                        if (isset($sec['groups_filter']) && !in_array($grpCode, $sec['groups_filter'])) continue;
                        $sectionSaldo += $grp['saldo'];
                        $sectionPrior += $grp['saldo_prior'];
                    endforeach;
                endforeach;

                if ($sectionSaldo == 0 && $sectionPrior == 0) continue;

                // Header de sección
                $renderRow($sec['title'], $sectionSaldo, $sectionPrior, 0, true, false);

                // Grupos de la sección
                foreach ($sec['classes'] as $cls):
                    if (!isset($groupsByClass[$cls])) continue;
                    foreach ($groupsByClass[$cls]['groups'] as $grpCode => $grp):
                        if (isset($sec['groups_filter']) && !in_array($grpCode, $sec['groups_filter'])) continue;
                        if ($grp['saldo'] == 0 && $grp['saldo_prior'] == 0) continue;

                        // Encabezado del grupo
                        $renderRow('<strong>' . $grpCode . ' · ' . htmlspecialchars($grp['name']) . '</strong>', $grp['saldo'], $grp['saldo_prior'], 1, false, false);

                        // Subcuentas (siempre visibles en este diseño; PDF las mantiene)
                        foreach ($grp['subaccounts'] as $sub) {
                            if ($sub['saldo'] == 0 && $sub['saldo_prior'] == 0) continue;
                            $renderRow(
                                '<span style="font-size:10px;color:#7F8392;">' . $sub['pucCode'] . ' · ' . htmlspecialchars($sub['name']) . '</span>',
                                $sub['saldo'], $sub['saldo_prior'], 2, false, false
                            );
                        }
                    endforeach;
                endforeach;

                // Después de la primera sección (Ingresos), mostrar Utilidad Bruta
                if ($sec['title'] === 'COSTOS DE VENTAS') {
                    $priorUtBruta = ($groupsByClass['4']['saldo_prior'] ?? 0) - ($groupsByClass['6']['saldo_prior'] ?? 0);
                    $renderRow('UTILIDAD BRUTA', $utilidadBruta, $priorUtBruta, 0, true, false);
                }
                if ($sec['title'] === 'GASTOS OPERACIONALES') {
                    $g51p = $groupsByClass['5']['groups']['51']['saldo_prior'] ?? 0;
                    $g52p = $groupsByClass['5']['groups']['52']['saldo_prior'] ?? 0;
                    $priorUtBruta = ($groupsByClass['4']['saldo_prior'] ?? 0) - ($groupsByClass['6']['saldo_prior'] ?? 0);
                    $priorUtOp = $priorUtBruta - $g51p - $g52p;
                    $renderRow('UTILIDAD OPERACIONAL', $utilidadOp, $priorUtOp, 0, true, false);
                }
            endforeach;

            // Utilidad Neta final
            $renderRow('UTILIDAD NETA', $utilidadNeta, (float)($kpis['utilidad_neta_prior'] ?? 0), 0, false, true);
            ?>

            <?php if (empty($groupsByClass)): ?>
            <tr><td colspan="<?= $colspan ?>" style="padding:30px;text-align:center;color:#AEAAA6;">
                No hay movimientos contables en el período seleccionado.
            </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
