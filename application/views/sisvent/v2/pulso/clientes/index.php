<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmt = function($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function($n) {
    $n = (float)$n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};
// Detecta la URL canónica: si el request vino por /v2/clientes vs /business/clients
$baseUrl = strpos($_SERVER['REQUEST_URI'] ?? '', '/v2/clientes') !== false
    ? base_url('sisvent/v2/clientes')
    : base_url('sisvent/business/clients');
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Clientes · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">

    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <?php
        $topbarActions = '
            <a href="' . base_url('sisvent/business/clients/add') . '" class="pulso-btn pulso-btn--primary pulso-btn--pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14 M12 5v14"/>
                </svg>
                Nuevo cliente
            </a>
        ';
        $this->load->view('sisvent/v2/pulso/layouts/topbar', array(
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'topbarActions' => $topbarActions,
        ));
        ?>

        <div class="pulso-content">

            <!-- KPI strip -->
            <div class="pulso-kpi-grid" style="grid-template-columns: 1.4fr 1fr 1fr 1fr;">

                <div class="pulso-kpi pulso-kpi--big">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Total clientes</div>
                            <div class="pulso-kpi-value"><?= number_format($kpis->total ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">en base de datos</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Nuevos este mes</div>
                            <div class="pulso-kpi-value"><?= number_format($kpis->nuevos_mes ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">recién creados</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Pueden facturar</div>
                            <div class="pulso-kpi-value"><?= number_format($kpis->pueden_facturar ?? 0, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">
                                <?= $kpis->total > 0 ? round(($kpis->pueden_facturar / $kpis->total) * 100, 0) . '% del total' : '—' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Lista negra</div>
                            <div class="pulso-kpi-value" style="color:<?= ($kpis->blacklisted ?? 0) > 0 ? 'var(--pulso-danger)' : 'var(--pulso-ink)' ?>;">
                                <?= number_format($kpis->blacklisted ?? 0, 0, ',', '.') ?>
                            </div>
                            <div class="pulso-kpi-sub">bloqueados</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Search + filtros -->
            <form method="GET" action="<?= $baseUrl ?>" style="margin-bottom: 16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div class="pulso-search-input" style="flex:1; max-width:420px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="<?= htmlspecialchars($term) ?>" placeholder="Buscar por nombre, NIT, teléfono…" style="width:100%;">
                </div>
                <button type="submit" class="pulso-btn pulso-btn--primary pulso-btn--sm">Buscar</button>
                <?php if ($term !== ''): ?>
                <a href="<?= $baseUrl ?>" class="pulso-btn pulso-btn--ghost pulso-btn--sm">Limpiar ✕</a>
                <?php endif; ?>

                <?php if (!empty($topCities)): ?>
                <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:11px; color:var(--pulso-ink3); text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Top ciudades:</span>
                    <?php foreach ($topCities as $ct): ?>
                        <span class="pulso-pill pulso-pill--neutral"><?= htmlspecialchars($ct->city) ?> · <?= $ct->n ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </form>

            <!-- Tabla -->
            <div class="pulso-table-wrap">
                <table class="pulso-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Identificación</th>
                            <th>Contacto</th>
                            <th>Ciudad</th>
                            <th>Vendedor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="6" style="padding:48px;text-align:center;color:var(--pulso-ink3);">
                                <?= $term !== '' ? 'Sin coincidencias para "' . htmlspecialchars($term) . '"' : 'Sin clientes registrados.' ?>
                            </td>
                        </tr>
                        <?php else: foreach ($clients as $c):
                            $name = $c->name ?: '(sin nombre)';
                            $words = preg_split('/\s+/', trim($name));
                            $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                            $editUrl = base_url('sisvent/business/clients/edit/' . (int)$c->idClient);
                            $estado = !empty($c->blacklisted) ? array('label'=>'lista negra','tone'=>'danger')
                                    : (!empty($c->can_bill) ? array('label'=>'puede facturar','tone'=>'success')
                                    : array('label'=>'activo','tone'=>'neutral'));
                        ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= $editUrl ?>'">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="pulso-avatar" style="width:32px; height:32px; font-size:11px;">
                                        <?= htmlspecialchars($initials) ?: '?' ?>
                                    </span>
                                    <div>
                                        <div style="font-weight:600; color:var(--pulso-ink);"><?= htmlspecialchars($name) ?></div>
                                        <?php if (!empty($c->commercial_name) && $c->commercial_name !== $c->name): ?>
                                        <div style="font-size:11px; color:var(--pulso-ink3); margin-top:2px;"><?= htmlspecialchars($c->commercial_name) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--pulso-ink2); font-size:12px;" class="pulso-mono">
                                <?= htmlspecialchars($c->idNum ?: '—') ?>
                            </td>
                            <td style="color:var(--pulso-ink2); font-size:12px;">
                                <?= htmlspecialchars($c->cellphone ?: ($c->phone ?: '—')) ?>
                                <?php if (!empty($c->email)): ?>
                                <div style="color:var(--pulso-ink3); font-size:11px; margin-top:2px;"><?= htmlspecialchars($c->email) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--pulso-ink2); font-size:12px;">
                                <?= htmlspecialchars($c->city ?: '—') ?>
                            </td>
                            <td style="color:var(--pulso-ink2); font-size:12px;">
                                <?= htmlspecialchars($c->vendor ?: '—') ?>
                            </td>
                            <td>
                                <span class="pulso-pill pulso-pill--<?= $estado['tone'] ?>">
                                    <span class="pulso-dot"></span>
                                    <?= htmlspecialchars($estado['label']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($lastPage > 1): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:20px; padding:0 4px;">
                <div style="font-size:12px; color:var(--pulso-ink3);">
                    Página <?= $page ?> de <?= $lastPage ?> · <?= number_format($total, 0, ',', '.') ?> en total
                </div>
                <div style="display:flex; gap:6px;">
                    <?php $qStr = $term !== '' ? '&q=' . urlencode($term) : ''; ?>
                    <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl . '?p=' . ($page - 1) . $qStr ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $lastPage): ?>
                    <a href="<?= $baseUrl . '?p=' . ($page + 1) . $qStr ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">Siguiente →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>
</body>
</html>
