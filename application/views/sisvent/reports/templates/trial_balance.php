<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template trial_balance — Balance de Comprobación.
 *
 * Tabla tipo libro contable: una fila por subcuenta con saldo inicial,
 * débitos+créditos del período, y saldo final separado en columnas D/C.
 * Validador visual de partida doble (Σ D del período == Σ C del período).
 */

$kpis    = $data['kpis']    ?? [];
$columns = $data['columns'] ?? [];
$rows    = $data['rows']    ?? [];
$totals  = $data['totals']  ?? null;
$isPdf   = !empty($pdf_mode);

$fmt = function ($v) {
    if (!is_numeric($v)) return $v;
    return number_format((float)$v, 0, ',', '.');
};
$fmtMoney = function ($v) use ($fmt) {
    if (!is_numeric($v) || $v == 0) return '';
    $sign = $v < 0 ? '-' : '';
    return $sign . '$' . $fmt(abs($v));
};

$pdOk = !empty($kpis['partida_doble_ok']);
$diff = (float)($kpis['diferencia_partida_doble'] ?? 0);
$cuadreFinal = !empty($kpis['cuadre_final']);
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow'=>'Subcuentas con movimiento', 'value'=>$fmt($kpis['total_subaccounts'] ?? 0), 'delta'=>'En el período', 'delta_tone'=>'fg4', 'accent'=>'#4487A0', 'pdf_color'=>'#4487A0'],
    ['eyebrow'=>'Σ Débitos del período', 'value'=>'$' . $fmt($kpis['total_debit_period'] ?? 0), 'delta'=>'Movimientos deudores', 'delta_tone'=>'fg4', 'accent'=>'#2B3164', 'pdf_color'=>'#2B3164'],
    ['eyebrow'=>'Σ Créditos del período', 'value'=>'$' . $fmt($kpis['total_credit_period'] ?? 0), 'delta'=>'Movimientos acreedores', 'delta_tone'=>'fg4', 'accent'=>'#2B3164', 'pdf_color'=>'#2B3164'],
    ['eyebrow'=>'Partida doble', 'value'=>$pdOk ? '✓ Cuadra' : '✗ Difiere', 'delta'=>$pdOk ? 'Σ D = Σ C' : 'Δ $' . $fmt(abs($diff)), 'delta_tone'=>$pdOk ? 'up' : 'dn', 'accent'=>$pdOk ? '#5EBA47' : '#C0392B', 'pdf_color'=>$pdOk ? '#5EBA47' : '#C0392B'],
];
?>
<?php if ($isPdf): ?>
    <table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:10px;border-collapse:separate;border-spacing:5px 0;">
        <tr><?php foreach ($kpiTiles as $t): ?>
            <td style="width:25%;border:1px solid #DDDFE8;border-left:3px solid <?= $t['pdf_color'] ?>;padding:7px 10px;background:#FAFBFC;">
                <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;"><?= htmlspecialchars($t['eyebrow']) ?></div>
                <div style="font-size:14px;font-weight:800;color:#2B3164;margin-top:2px;"><?= $t['value'] ?></div>
                <div style="font-size:8px;color:#7F8392;margin-top:1px;"><?= htmlspecialchars($t['delta']) ?></div>
            </td>
        <?php endforeach; ?></tr>
    </table>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
        <?php foreach ($kpiTiles as $t): $this->load->view('sisvent/design-system/_kpi_tile', $t); endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$pdOk): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:10px 14px;border-radius:6px;margin-bottom:12px;font-size:12px;">
    ⚠ <strong>Partida doble NO cuadra.</strong> Σ Débitos − Σ Créditos = $<?= $fmt(abs($diff)) ?>. Hay un asiento descuadrado — revisá el libro diario.
</div>
<?php endif; ?>

<!-- ============== TABLA ============== -->
<div style="background:#fff;border:1px solid #DDDFE8;border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr style="background:#2B3164;color:#fff;">
                <?php foreach ($columns as $col):
                    $align = ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left';
                ?>
                <th style="padding:7px 10px;text-align:<?= $align ?>;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($columns) ?>" style="padding:30px;text-align:center;color:#AEAAA6;">No hay subcuentas con movimiento en el período.</td></tr>
            <?php else:
                $lastClase = null;
                foreach ($rows as $r):
                    // Separador visual cuando cambia la clase
                    if ($lastClase !== null && $lastClase !== ($r['clase'] ?? null)) {
                        echo '<tr><td colspan="' . count($columns) . '" style="padding:3px;background:#F1F3F5;"></td></tr>';
                    }
                    $lastClase = $r['clase'] ?? null;
            ?>
            <tr style="border-bottom:1px solid #F1F3F5;">
                <?php foreach ($columns as $col):
                    $key = $col['key']; $val = $r[$key] ?? '';
                    $type = $col['type'] ?? 'text';
                    $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                    $style = 'padding:4px 10px;text-align:' . $align . ';';
                    if ($type === 'currency' || $type === 'number') $style .= 'font-family:monospace;';

                    if ($type === 'currency') {
                        $color = '#2B3164';
                        if ($key === 'debit_period') $color = '#2B3164';
                        if ($key === 'credit_period') $color = '#2B3164';
                        if ($key === 'saldo_debito_final') $color = '#1B365D';
                        if ($key === 'saldo_credito_final') $color = '#991B1B';
                        echo '<td style="' . $style . 'color:' . $color . ';">' . htmlspecialchars($fmtMoney($val)) . '</td>';
                    } else {
                        echo '<td style="' . $style . '">' . htmlspecialchars((string)$val) . '</td>';
                    }
                endforeach; ?>
            </tr>
            <?php endforeach; ?>

            <?php if ($totals): ?>
            <tr style="background:<?= $cuadreFinal ? '#1B365D' : '#991B1B' ?>;color:#fff;font-weight:900;border-top:2px solid #2B3164;">
                <?php foreach ($columns as $col):
                    $key = $col['key']; $val = $totals[$key] ?? '';
                    $type = $col['type'] ?? 'text';
                    $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                    $style = 'padding:8px 10px;text-align:' . $align . ';font-weight:900;';
                    if ($type === 'currency' || $type === 'number') $style .= 'font-family:monospace;';
                    if ($type === 'currency') {
                        echo '<td style="' . $style . '">' . htmlspecialchars($fmtMoney($val)) . '</td>';
                    } else {
                        echo '<td style="' . $style . '">' . htmlspecialchars((string)$val) . '</td>';
                    }
                endforeach; ?>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
