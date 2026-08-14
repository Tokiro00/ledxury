<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template genérico: tabla simple con columns + rows + totals.
 * Lo usan reportes que no necesitan layout custom (la mayoría de reportes
 * tabulares).
 *
 * @var ReportInterface $report
 * @var array $data
 * @var bool $pdf_mode
 */

$columns = $data['columns'] ?? [];
$rows = $data['rows'] ?? [];
$totals = $data['totals'] ?? null;
$isPdf = !empty($pdf_mode);

if (!$isPdf) {
    // HTML interactivo: card con sombra
    echo '<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">';
}
?>
<div style="<?= $isPdf ? '' : 'overflow-x:auto;' ?>">
    <table style="width:100%;border-collapse:collapse;font-size:<?= $isPdf ? '10.5px' : '13px' ?>;">
        <thead>
            <tr style="background:var(--mam-blue-dark,#2B3164);color:#fff;">
                <?php foreach ($columns as $col): ?>
                    <th style="padding:<?= $isPdf ? '6px 8px' : '10px 12px' ?>;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:<?= $isPdf ? '10px' : '11px' ?>;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                        <?= htmlspecialchars($col['label']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($columns) ?>" style="padding:24px;text-align:center;color:var(--fg-3,#AEAAA6);font-size:13px;">No hay datos para mostrar con los filtros aplicados.</td></tr>
            <?php else: foreach ($rows as $row): ?>
                <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                    <?php foreach ($columns as $col):
                        $key = $col['key'];
                        $val = $row[$key] ?? '';
                        $type = $col['type'] ?? 'text';
                        $decimals = $col['decimals'] ?? 0;
                        $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                        $formatted = $val;
                        if ($type === 'currency' && is_numeric($val)) {
                            $formatted = '$' . number_format((float)$val, $decimals, ',', '.');
                        } elseif ($type === 'number' && is_numeric($val)) {
                            $formatted = number_format((float)$val, $decimals, ',', '.');
                        } elseif ($type === 'date' && $val) {
                            $formatted = date('d/m/Y', strtotime($val));
                        }
                    ?>
                        <td style="padding:<?= $isPdf ? '6px 8px' : '10px 12px' ?>;text-align:<?= $align ?>;color:var(--fg-1,#2C2721);">
                            <?= htmlspecialchars((string) $formatted) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; endif; ?>
            <?php if ($totals && !empty($rows)): ?>
                <tr style="background:var(--bg-subtle,#F1F3F5);font-weight:700;border-top:2px solid var(--mam-blue-dark,#2B3164);">
                    <?php foreach ($columns as $col):
                        $key = $col['key'];
                        $val = $totals[$key] ?? '';
                        $type = $col['type'] ?? 'text';
                        $decimals = $col['decimals'] ?? 0;
                        $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                        $formatted = $val;
                        if ($val !== '' && $type === 'currency' && is_numeric($val)) {
                            $formatted = '$' . number_format((float)$val, $decimals, ',', '.');
                        } elseif ($val !== '' && $type === 'number' && is_numeric($val)) {
                            $formatted = number_format((float)$val, $decimals, ',', '.');
                        }
                    ?>
                        <td style="padding:<?= $isPdf ? '6px 8px' : '10px 12px' ?>;text-align:<?= $align ?>;color:var(--mam-blue-dark,#2B3164);">
                            <?= htmlspecialchars((string) $formatted) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if (!$isPdf) echo '</div>'; ?>
