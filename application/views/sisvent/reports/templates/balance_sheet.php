<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template balance_sheet — Balance General PUC Colombia.
 *
 * Estructura visual:
 *   - 4 KPI tiles arriba: Total Activo, Total Pasivo, Patrimonio, Diferencia
 *   - 2 columnas:
 *       IZQUIERDA: Activo (clases 1)
 *       DERECHA:   Pasivo (2) + Patrimonio (3) + Utilidad ejercicio
 *   - Footer: Totales + indicador de cuadre
 */

$kpis = $data['kpis'] ?? [];
$tree = $data['tree'] ?? ['1'=>['saldo'=>0,'groups'=>[]],'2'=>['saldo'=>0,'groups'=>[]],'3'=>['saldo'=>0,'groups'=>[]]];
$isPdf = !empty($pdf_mode);

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

$cuadrado = !empty($kpis['cuadrado']);
$diferencia = (float)($kpis['diferencia'] ?? 0);
$resultadoEj = (float)($kpis['resultado_ejercicio'] ?? 0);
$incluirUt = !empty($kpis['incluir_utilidad']);
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow'=>'Total Activo', 'value'=>$fmtCompact($kpis['total_activo'] ?? 0), 'delta'=>'Recursos económicos', 'delta_tone'=>'fg4', 'accent'=>'#4487A0', 'pdf_color'=>'#4487A0'],
    ['eyebrow'=>'Total Pasivo', 'value'=>$fmtCompact($kpis['total_pasivo'] ?? 0), 'delta'=>'Obligaciones', 'delta_tone'=>'fg4', 'accent'=>'#C0392B', 'pdf_color'=>'#C0392B'],
    ['eyebrow'=>'Patrimonio', 'value'=>$fmtCompact($kpis['total_patrimonio'] ?? 0), 'delta'=>$incluirUt ? 'Incluye Utilidad: ' . $fmtCompact($resultadoEj) : 'Sin utilidad del ejercicio', 'delta_tone'=>$resultadoEj >= 0 ? 'up' : 'dn', 'accent'=>'#5EBA47', 'pdf_color'=>'#5EBA47'],
    ['eyebrow'=>'Cuadre', 'value'=>$cuadrado ? '✓ OK' : '✗ DIFIERE', 'delta'=>$cuadrado ? 'Activo = Pasivo + Patrimonio' : 'Δ ' . $fmtMoney($diferencia), 'delta_tone'=>$cuadrado ? 'up' : 'dn', 'accent'=>$cuadrado ? '#5EBA47' : '#C0392B', 'pdf_color'=>$cuadrado ? '#5EBA47' : '#C0392B'],
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

<?php if (!$cuadrado): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:10px 14px;border-radius:6px;margin-bottom:12px;font-size:12px;">
    ⚠ <strong>El Balance no cuadra.</strong> Diferencia: <?= $fmtMoney($diferencia) ?>. Verificá asientos contables del período.
</div>
<?php endif; ?>

<!-- ============== 2 COLUMNAS ============== -->
<?php
$renderColumn = function($title, $totalLabel, $totalValue, $sections, $color) use ($fmtMoney, $fmt) {
    ?>
    <div style="background:#fff;border:1px solid #DDDFE8;border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <div style="padding:10px 14px;background:<?= $color ?>;color:#fff;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;">
            <?= htmlspecialchars($title) ?>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <tbody>
                <?php foreach ($sections as $sec):
                    if ($sec['saldo'] == 0 && empty($sec['subaccounts'])) continue;
                ?>
                <tr style="background:#F8F8FA;border-top:1px solid #DDDFE8;font-weight:700;color:#2B3164;">
                    <td style="padding:6px 14px;"><?= htmlspecialchars($sec['code'] . ' ' . $sec['name']) ?></td>
                    <td style="padding:6px 14px;text-align:right;font-family:monospace;"><?= $fmtMoney($sec['saldo']) ?></td>
                </tr>
                <?php foreach ($sec['subaccounts'] as $sub):
                    if ($sub['saldo'] == 0) continue;
                ?>
                <tr style="border-top:1px solid #F1F3F5;color:#575964;">
                    <td style="padding:4px 14px 4px 28px;font-size:10px;">
                        <span style="font-family:monospace;color:#7F8392;"><?= $sub['pucCode'] ?></span> · <?= htmlspecialchars($sub['name']) ?>
                    </td>
                    <td style="padding:4px 14px;text-align:right;font-family:monospace;color:#7F8392;font-size:10px;"><?= $fmtMoney($sub['saldo']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <tr style="background:<?= $color ?>;color:#fff;font-weight:900;border-top:2px solid #2B3164;">
                    <td style="padding:8px 14px;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($totalLabel) ?></td>
                    <td style="padding:8px 14px;text-align:right;font-family:monospace;font-size:13px;"><?= $fmtMoney($totalValue) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
};

// Activo
$activoSections = [];
foreach ($tree['1']['groups'] as $code => $g) {
    $activoSections[] = ['code'=>$code, 'name'=>$g['name'], 'saldo'=>$g['saldo'], 'subaccounts'=>$g['subaccounts']];
}

// Pasivo + Patrimonio (mezclado)
$pasivoSections = [];
foreach ($tree['2']['groups'] as $code => $g) {
    $pasivoSections[] = ['code'=>$code, 'name'=>$g['name'], 'saldo'=>$g['saldo'], 'subaccounts'=>$g['subaccounts']];
}
// Header de Patrimonio inline
$patrimonioSections = [];
foreach ($tree['3']['groups'] as $code => $g) {
    $patrimonioSections[] = ['code'=>$code, 'name'=>$g['name'], 'saldo'=>$g['saldo'], 'subaccounts'=>$g['subaccounts']];
}
if ($incluirUt && $resultadoEj != 0) {
    $patrimonioSections[] = [
        'code' => '36',
        'name' => 'Utilidad del ejercicio (calculada)',
        'saldo' => $resultadoEj,
        'subaccounts' => [],
    ];
}
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <?php $renderColumn('Activo', 'TOTAL ACTIVO', $kpis['total_activo'] ?? 0, $activoSections, '#1B365D'); ?>
    <div>
        <?php $renderColumn('Pasivo', 'TOTAL PASIVO', $kpis['total_pasivo'] ?? 0, $pasivoSections, '#991B1B'); ?>
        <div style="height:12px;"></div>
        <?php $renderColumn('Patrimonio', 'TOTAL PATRIMONIO', $kpis['total_patrimonio'] ?? 0, $patrimonioSections, '#166534'); ?>
        <div style="height:12px;"></div>
        <div style="background:<?= $cuadrado ? '#1B365D' : '#991B1B' ?>;color:#fff;padding:12px 14px;border-radius:6px;font-weight:900;display:flex;justify-content:space-between;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;">
            <span>Total Pasivo + Patrimonio</span>
            <span style="font-family:monospace;"><?= $fmtMoney($kpis['total_pasivo_pat'] ?? 0) ?></span>
        </div>
    </div>
</div>
