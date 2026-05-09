<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Renderiza un form GET con todos los filterDefinitions del reporte.
 *
 * Tipos soportados:
 *   - text, number             — input nativo
 *   - date                     — input type=date
 *   - select, radio            — <select>
 *   - client, vendor, provider — autocomplete via picker.js (Odoo many2one)
 *   - date_range               — Desde/Hasta + chips de presets (Hoy, Esta semana, etc.)
 *
 * @var ReportInterface $report
 * @var array $params
 */

$baseUrl = base_url() . 'sisvent/admin/reports/v2/' . $report->id();
?>
<form method="GET" action="<?= $baseUrl ?>" id="rep-filters-form" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;background:var(--bg-surface,#fff);padding:14px 16px;border:1px solid var(--border-default,#DDDFE8);border-radius:8px;margin-bottom:18px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <?php
    // Date range pre-render: si hay un filtro tipo 'date_range', dibujamos los presets antes
    // de los Desde/Hasta. Si no, cada filtro se renderiza individualmente.
    $hasDateRange = false;
    foreach ($report->filterDefinitions() as $f) {
        if (($f['type'] ?? '') === 'date_range') { $hasDateRange = true; break; }
    }
    ?>

    <?php if ($hasDateRange): ?>
        <?php
        $desde = $params['desde'] ?? date('Y-01-01');
        $hasta = $params['hasta'] ?? date('Y-m-d');
        $today = date('Y-m-d');
        $presets = [
            'Hoy' => [$today, $today],
            'Ayer' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
            'Esta semana' => [date('Y-m-d', strtotime('monday this week')), $today],
            'Este mes' => [date('Y-m-01'), $today],
            'Mes pasado' => [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))],
            'YTD' => [date('Y-01-01'), $today],
        ];
        ?>
        <div style="display:flex;flex-direction:column;gap:6px;">
            <span style="font-size:11px;font-weight:700;color:var(--fg-3,#AEAAA6);text-transform:uppercase;letter-spacing:0.5px;">Periodo</span>
            <div style="display:inline-flex;gap:4px;flex-wrap:wrap;">
                <?php foreach ($presets as $label => [$d, $h]):
                    $active = ($d === $desde && $h === $hasta);
                ?>
                    <button type="button" data-preset-d="<?= $d ?>" data-preset-h="<?= $h ?>"
                        style="padding:5px 10px;font-size:11px;font-weight:600;border-radius:9999px;cursor:pointer;border:1px solid <?= $active ? 'var(--mam-blue-petroleo,#4487A0)' : 'var(--border-default,#DDDFE8)' ?>;background:<?= $active ? 'var(--mam-blue-petroleo,#4487A0)' : 'var(--bg-surface,#fff)' ?>;color:<?= $active ? '#fff' : 'var(--mam-blue-dark,#2B3164)' ?>;">
                        <?= htmlspecialchars($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:11px;font-weight:700;color:var(--fg-3,#AEAAA6);text-transform:uppercase;letter-spacing:0.5px;">
            <span>Desde</span>
            <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" id="rep-filter-desde" style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;">
        </label>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:11px;font-weight:700;color:var(--fg-3,#AEAAA6);text-transform:uppercase;letter-spacing:0.5px;">
            <span>Hasta</span>
            <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" id="rep-filter-hasta" style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;">
        </label>
    <?php endif; ?>

    <?php foreach ($report->filterDefinitions() as $f):
        $name = $f['name'];
        $label = $f['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $type = $f['type'] ?? 'text';
        $value = $params[$name] ?? ($f['default'] ?? '');
        $required = !empty($f['required']);

        // Si ya renderizamos date_range arriba, skip los desde/hasta individuales del array
        if ($hasDateRange && in_array($name, ['desde', 'hasta'], true)) continue;
        if ($type === 'date_range') continue; // metadata only, no input own
    ?>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:11px;font-weight:700;color:var(--fg-3,#AEAAA6);text-transform:uppercase;letter-spacing:0.5px;">
            <span><?= htmlspecialchars($label) ?><?= $required ? ' *' : '' ?></span>
            <?php
            if (in_array($type, ['client', 'vendor', 'provider', 'product'], true)):
                $placeholders = [
                    'client'   => 'Nombre, NIT o teléfono...',
                    'provider' => 'Nombre o NIT del proveedor...',
                    'vendor'   => 'Nombre del vendedor...',
                    'product'  => 'Código o descripción del producto...',
                ];
                $ph = $placeholders[$type] ?? 'Escribir para buscar...';
            ?>
                <!-- Autocomplete picker (Odoo many2one) — flex-row layout, sin absolute positioning -->
                <div class="rep-picker" data-picker-type="<?= htmlspecialchars($type) ?>" style="position:relative;width:280px;">
                    <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>">
                    <div class="rep-picker__row" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;transition:border-color .15s, box-shadow .15s;">
                        <svg class="rep-picker__icon" style="width:14px;height:14px;flex-shrink:0;color:var(--fg-3,#AEAAA6);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" class="rep-picker__input" placeholder="<?= htmlspecialchars($ph) ?>" autocomplete="off" <?= $required ? 'required' : '' ?>
                            style="border:0;outline:0;flex:1;padding:0;font-size:14px;font-weight:500;color:var(--mam-blue-dark,#2B3164);background:transparent;min-width:0;">
                        <span class="rep-picker__spinner" hidden aria-hidden="true" style="width:14px;height:14px;flex-shrink:0;border:2px solid var(--border-default,#DDDFE8);border-top-color:var(--mam-blue-petroleo,#4487A0);border-radius:50%;animation:rep-spin .7s linear infinite;"></span>
                        <button type="button" class="rep-picker__clear" hidden aria-label="Limpiar"
                            style="background:transparent;border:0;cursor:pointer;padding:2px;flex-shrink:0;color:var(--fg-3,#AEAAA6);border-radius:4px;display:flex;align-items:center;justify-content:center;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="rep-picker__dropdown" hidden></div>
                </div>
            <?php elseif (in_array($type, ['select', 'radio'], true)): ?>
                <select name="<?= htmlspecialchars($name) ?>" style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;background:var(--bg-surface,#fff);min-width:140px;">
                    <?php if (empty($required)): ?><option value="">— Todos —</option><?php endif; ?>
                    <?php foreach ($f['options'] ?? [] as $optVal => $optLabel): ?>
                        <option value="<?= htmlspecialchars((string)$optVal) ?>" <?= ((string)$value === (string)$optVal) ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'date'): ?>
                <input type="date" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>" <?= $required ? 'required' : '' ?> style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;">
            <?php elseif ($type === 'number'): ?>
                <input type="number" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>" <?= $required ? 'required' : '' ?> style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;width:120px;">
            <?php else: ?>
                <input type="text" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>" <?= $required ? 'required' : '' ?> placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>" style="padding:6px 10px;font-size:13px;font-weight:500;color:var(--mam-blue-dark,#2B3164);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;min-width:160px;">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>

    <button type="submit" style="padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:var(--mam-blue-petroleo,#4487A0);border:0;border-radius:6px;cursor:pointer;box-shadow:0 1px 3px rgba(68,135,160,.25);">Aplicar filtros</button>
</form>

<?php
// Flags para que _layout.php cargue los assets del picker FUERA del Vue
// mount (#bars). Vue rechaza <script> y <style> con side-effects dentro
// del template — los movemos al final del body, después del cierre de #bars.
$GLOBALS['__rep_picker_assets_needed'] = true;
$GLOBALS['__rep_filters_has_date_range'] = $hasDateRange;
?>
