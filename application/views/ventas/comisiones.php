<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Mis Comisiones - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --petrol-dark:#236470; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --success:#10b981; --warning:#f59e0b; --danger:#ef4444; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }
        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.85); font-size:12px; text-decoration:none; }
        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }

        .total-card { background:linear-gradient(135deg,var(--petrol),var(--petrol-dark)); border-radius:var(--radius); padding:20px; color:#fff; margin-bottom:12px; }
        .total-card .label { font-size:11px; opacity:.85; text-transform:uppercase; letter-spacing:1px; }
        .total-card .main-value { font-size:34px; font-weight:800; margin-top:2px; line-height:1.1; }
        .total-card .period-tag { font-size:11px; opacity:.85; margin-top:4px; }
        .total-card .split { display:flex; gap:12px; margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,.2); }
        .total-card .split > div { flex:1; }
        .total-card .split .sub-label { font-size:10px; opacity:.8; text-transform:uppercase; letter-spacing:.5px; }
        .total-card .split .sub-value { font-size:18px; font-weight:700; margin-top:2px; }
        .status-chip { display:inline-block; margin-top:8px; padding:3px 10px; border-radius:12px; font-size:10px; font-weight:700; background:rgba(255,255,255,.25); }

        .filter-row { display:flex; gap:6px; margin-bottom:12px; align-items:center; flex-wrap:wrap; }
        .filter-row input[type="month"], .filter-row input[type="date"] { flex:1; min-width:0; padding:9px 10px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:13px; background:#fff; }
        .filter-row button { padding:9px 14px; background:var(--petrol); color:#fff; border:none; border-radius:var(--radius-sm); font-weight:700; font-size:12px; cursor:pointer; }
        .filter-toggle { font-size:11px; color:var(--petrol); background:none; border:none; font-weight:700; cursor:pointer; padding:4px 0; text-decoration:underline; }
        .range-display { font-size:11px; color:var(--text-secondary); margin-bottom:10px; text-align:center; }

        .tabs { display:flex; background:var(--card); border-radius:var(--radius-sm); overflow:hidden; margin-bottom:12px; box-shadow:var(--shadow); }
        .tab-btn { flex:1; padding:10px 6px; background:transparent; border:none; font-size:12px; font-weight:700; color:var(--text-secondary); cursor:pointer; position:relative; }
        .tab-btn.active { color:var(--petrol); background:rgba(46,125,145,.08); }
        .tab-btn.active::after { content:''; position:absolute; bottom:0; left:20%; right:20%; height:2px; background:var(--petrol); }
        .tab-btn .badge { display:inline-block; background:var(--border); color:var(--text); font-size:10px; padding:1px 6px; border-radius:8px; margin-left:3px; font-weight:700; }
        .tab-btn.active .badge { background:var(--petrol); color:#fff; }

        .tab-panel { display:none; }
        .tab-panel.active { display:block; }

        .card { background:var(--card); border-radius:var(--radius); padding:14px; box-shadow:var(--shadow); margin-bottom:10px; }
        .card-title { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-bottom:10px; }

        .bd-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .bd-row:last-child { border:none; }
        .bd-info { flex:1; min-width:0; }
        .bd-bot { font-size:13px; font-weight:700; color:var(--text); }
        .bd-sub { font-size:11px; color:var(--text-secondary); margin-top:1px; }
        .bd-amt { text-align:right; white-space:nowrap; }
        .bd-amt .paid { font-size:15px; font-weight:800; color:var(--success); }
        .bd-amt .pend { font-size:11px; color:var(--warning); margin-top:2px; font-weight:600; }

        .inv-item { padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .inv-item:last-child { border:none; }
        .inv-head { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
        .inv-client { font-size:13px; font-weight:700; color:var(--text); }
        .inv-meta { font-size:11px; color:var(--text-secondary); margin-top:2px; line-height:1.4; }
        .inv-total { font-size:14px; font-weight:800; text-align:right; }
        .inv-total.pagada { color:var(--success); }
        .inv-total.pendiente { color:var(--warning); }
        .inv-comm { font-size:11px; text-align:right; margin-top:1px; color:var(--text-secondary); font-weight:600; }
        .inv-tags { display:flex; gap:5px; margin-top:6px; flex-wrap:wrap; }
        .inv-tag { font-size:10px; padding:2px 7px; border-radius:5px; font-weight:600; }
        .inv-tag-bot { background:#EFF6FF; color:#1D4ED8; }
        .inv-tag-pct { background:#F3E8FF; color:#7C3AED; }

        .history-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .history-item:last-child { border:none; }
        .history-period { font-size:13px; font-weight:700; text-transform:capitalize; }
        .history-detail { font-size:11px; color:var(--text-secondary); margin-top:2px; }
        .history-amount { font-size:14px; font-weight:800; text-align:right; }
        .history-status { font-size:9px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-top:1px; }
        .history-status.paid { color:var(--success); }
        .history-status.pending { color:var(--warning); }

        .empty-state { text-align:center; padding:32px 20px; color:var(--text-secondary); }
        .empty-state .emoji { font-size:32px; margin-bottom:6px; }
        .empty-state p { font-size:13px; }

        .no-config { background:#FEF3C7; border:1px solid #F59E0B; border-radius:var(--radius); padding:14px; margin-bottom:12px; }
        .no-config p { font-size:12px; color:#92400E; font-weight:600; line-height:1.4; }

        .user-selector { margin-bottom:10px; padding:10px; background:#fff; border-radius:var(--radius-sm); box-shadow:var(--shadow); }
        .user-selector select { width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:13px; background:#fff; }
        .user-selector label { font-size:10px; color:var(--text-secondary); text-transform:uppercase; font-weight:700; letter-spacing:.5px; display:block; margin-bottom:5px; }

        .tab-bar { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); display:flex; z-index:10; padding-bottom:var(--safe-bottom); height:var(--tab-height); }
        .tab-bar a { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:10px; color:var(--text-secondary); text-decoration:none; font-weight:600; position:relative; }
        .tab-bar a svg { width:24px; height:24px; margin-bottom:2px; }
        .tab-bar a.active { color:var(--petrol); }
        .tab-bar a.active::before { content:''; position:absolute; top:0; left:25%; right:25%; height:3px; background:var(--petrol); border-radius:0 0 3px 3px; }
    </style>
</head>
<body>
<div id="app">
    <div class="header">
        <a href="<?= base_url() ?>ventas/dashboard">&larr; Inicio</a>
        <h1>Mis Comisiones</h1>
        <div style="display:flex;align-items:center;gap:8px;">
          <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
          <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
        </div>
    </div>

    <div class="screen-container">
        <?php
            $is_liquidated_period = (!$is_custom_range && $period && $period->status === 'liquidado');
            $total_ganada = $is_liquidated_period && isset($snapshot_total) ? $snapshot_total : $total_com_cobrada;
            $has_any_config = !empty($configs);
        ?>

        <?php if ($is_admin && !empty($users_with_config)): ?>
        <form class="user-selector" method="GET" id="userForm">
            <label>Viendo comisiones de</label>
            <select name="user_id" onchange="document.getElementById('userForm').submit()">
                <option value="<?= $this->session->userdata('user_data')['uname'] ?>" <?= $target_user_id == $this->session->userdata('user_data')['uname'] ? 'selected' : '' ?>>Yo (<?= htmlspecialchars($vendor->name) ?>)</option>
                <?php foreach ($users_with_config as $u): ?>
                    <?php if ($u->user_id != $this->session->userdata('user_data')['uname']): ?>
                    <option value="<?= htmlspecialchars($u->user_id) ?>" <?= $target_user_id == $u->user_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u->name ?: $u->user_id) ?>
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
            <?php if ($is_custom_range): ?>
            <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
            <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <?php if (!$has_any_config): ?>
        <div class="no-config">
            <p>No tienes comisiones configuradas. Habla con tu administrador para definir tu % y bot asignado.</p>
        </div>
        <?php endif; ?>

        <!-- Totales globales (igualan Liquidaciones) -->
        <div class="total-card">
            <div class="label">Mis comisiones &middot; Liquidables hoy</div>
            <div class="main-value">$<?= number_format($liq_pagada, 0, ',', '.') ?></div>
            <div class="period-tag">Total que coincide con Liquidaciones</div>
            <div class="split">
                <div>
                    <div class="sub-label">Pagadas</div>
                    <div class="sub-value">$<?= number_format($liq_pagada, 0, ',', '.') ?></div>
                </div>
                <div>
                    <div class="sub-label">Pendientes</div>
                    <div class="sub-value">$<?= number_format($liq_pendiente, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- Period picker con flechas -->
        <?php $uid_qs = ($is_admin && $target_user_id != $this->session->userdata('user_data')['uname']) ? ('&user_id=' . urlencode($target_user_id)) : ''; ?>
        <div class="filter-row" style="align-items:stretch;">
            <a href="?month=<?= $prev_month . $uid_qs ?>" style="padding:9px 12px; background:var(--card); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); text-decoration:none; font-weight:700; display:flex; align-items:center;">&larr;</a>
            <form method="GET" id="monthForm" style="flex:1; display:flex; gap:6px;">
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="document.getElementById('monthForm').submit()" style="flex:1;">
                <?php if ($is_admin && $target_user_id != $this->session->userdata('user_data')['uname']): ?>
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($target_user_id) ?>">
                <?php endif; ?>
            </form>
            <a href="?month=<?= $next_month . $uid_qs ?>" style="padding:9px 12px; background:var(--card); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); text-decoration:none; font-weight:700; display:flex; align-items:center;">&rarr;</a>
        </div>
        <?php if ($month !== $this_month): ?>
        <div style="text-align:center; margin-bottom:8px;">
            <a href="?<?= 'month=' . $this_month . $uid_qs ?>" style="font-size:11px; color:var(--petrol); text-decoration:underline; font-weight:700;">&middot; volver a periodo actual &middot;</a>
        </div>
        <?php endif; ?>

        <!-- Total del periodo seleccionado -->
        <div class="card" style="margin-bottom:10px;">
            <div class="card-title">
                <?php if ($is_custom_range): ?>
                    Periodo: <?= date('d/m/Y', strtotime($from)) ?> &mdash; <?= date('d/m/Y', strtotime($to)) ?>
                <?php else: ?>
                    Periodo: <?= ucfirst($period_label) ?> (<?= date('d/m', strtotime($period_start)) ?> &mdash; <?= date('d/m', strtotime($period_end)) ?>)
                <?php endif; ?>
                <?php if ($is_liquidated_period): ?>
                    <span style="font-size:10px; background:var(--success); color:#fff; padding:2px 8px; border-radius:8px; margin-left:6px;">Liquidado</span>
                <?php endif; ?>
            </div>
            <div style="display:flex; justify-content:space-around; text-align:center; gap:8px;">
                <div style="flex:1;">
                    <div style="font-size:20px; font-weight:800; color:var(--success);">$<?= number_format($total_ganada, 0, ',', '.') ?></div>
                    <div style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-top:2px;">Ganada en periodo</div>
                </div>
                <div style="flex:1; border-left:1px solid var(--border);">
                    <div style="font-size:20px; font-weight:800; color:var(--warning);">$<?= number_format($total_com_pendiente, 0, ',', '.') ?></div>
                    <div style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-top:2px;">Proyección</div>
                </div>
            </div>
            <div style="text-align:center; margin-top:8px;">
                <button type="button" class="filter-toggle" onclick="toggleRange()">Rango personalizado</button>
            </div>
        </div>

        <form class="filter-row" method="GET" id="rangeForm" style="display:<?= $is_custom_range ? 'flex' : 'none' ?>; margin-bottom:12px;">
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
            <?php if ($is_admin && $target_user_id != $this->session->userdata('user_data')['uname']): ?>
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($target_user_id) ?>">
            <?php endif; ?>
            <button type="submit">Aplicar</button>
        </form>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="resumen">Resumen</button>
            <button class="tab-btn" data-tab="cobradas">Cobradas <span class="badge"><?= count($cobradas) ?></span></button>
            <button class="tab-btn" data-tab="pendientes">Pendientes <span class="badge"><?= count($pendientes) ?></span></button>
            <button class="tab-btn" data-tab="historial">Historial</button>
        </div>

        <!-- RESUMEN -->
        <div class="tab-panel active" data-panel="resumen">
            <?php if ($is_liquidated_period && !empty($liquidated_details)): ?>
            <div class="card">
                <div class="card-title">Liquidación del periodo</div>
                <?php foreach ($liquidated_details as $d): ?>
                <div class="bd-row">
                    <div class="bd-info">
                        <div class="bd-bot"><?= htmlspecialchars($d->bot_name ?: 'Todos los bots') ?></div>
                        <div class="bd-sub"><?= $d->percentage ?>% sobre $<?= number_format($d->base_amount, 0, ',', '.') ?></div>
                    </div>
                    <div class="bd-amt">
                        <div class="paid">$<?= number_format($d->commission_amount, 0, ',', '.') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($snapshot_items ?? null)): ?>
            <div class="card">
                <details>
                    <summary class="card-title" style="cursor:pointer; list-style:none; display:flex; justify-content:space-between; align-items:center;">
                        <span>Detalle por factura &middot; <?= count($snapshot_items) ?></span>
                        <span style="font-size:11px; color:#888;">tocar para expandir</span>
                    </summary>
                    <div style="margin-top:8px;">
                        <?php foreach ($snapshot_items as $it): ?>
                        <div class="inv-item">
                            <div class="inv-head">
                                <div style="flex:1; min-width:0;">
                                    <div class="inv-client"><?= htmlspecialchars($it->client_name ?: ('Cliente #' . $it->client_id)) ?></div>
                                    <div class="inv-meta">
                                        #<?= $it->invoice_id ?>
                                        <?php if ($it->invoice_date): ?>
                                            &middot; <?= date('d/m/Y', strtotime($it->invoice_date)) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($it->vendor_name)): ?>
                                            <br>Vendedor: <?= htmlspecialchars($it->vendor_name) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="inv-tags">
                                        <span class="inv-tag inv-tag-pct"><?= rtrim(rtrim(number_format((float)$it->percentage, 2, ',', '.'), '0'), ',') ?>%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="inv-total pagada">$<?= number_format((float)$it->invoice_total, 0, ',', '.') ?></div>
                                    <div class="inv-comm">+ $<?= number_format((float)$it->commission_amount, 0, ',', '.') ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>
            <?php endif; ?>

            <?php if (!empty($breakdown)): ?>
            <div class="card">
                <div class="card-title"><?= $is_liquidated_period ? 'Actividad actual del rango' : 'Desglose por bot' ?></div>
                <?php foreach ($breakdown as $b): ?>
                <div class="bd-row">
                    <div class="bd-info">
                        <div class="bd-bot"><?= htmlspecialchars($b->bot_name) ?></div>
                        <div class="bd-sub"><?= $b->percentage ?>% &middot; base cobrada $<?= number_format($b->base_pagada, 0, ',', '.') ?></div>
                    </div>
                    <div class="bd-amt">
                        <div class="paid">$<?= number_format($b->com_pagada, 0, ',', '.') ?></div>
                        <?php if ($b->com_pendiente > 0): ?>
                        <div class="pend">+$<?= number_format($b->com_pendiente, 0, ',', '.') ?> proy.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!$is_liquidated_period && $has_any_config): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="emoji">&#128202;</div>
                    <p>Sin actividad en este rango</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- COBRADAS -->
        <div class="tab-panel" data-panel="cobradas">
            <div class="card">
                <div class="card-title">Facturas cobradas &middot; <?= count($cobradas) ?></div>
                <?php if (empty($cobradas)): ?>
                <div class="empty-state">
                    <div class="emoji">&#128176;</div>
                    <p>Sin facturas cobradas en este rango</p>
                </div>
                <?php else: ?>
                <?php foreach ($cobradas as $inv): ?>
                <div class="inv-item">
                    <div class="inv-head">
                        <div style="flex:1; min-width:0;">
                            <div class="inv-client"><?= htmlspecialchars($inv->client_name ?: 'Cliente #' . $inv->clientId) ?></div>
                            <div class="inv-meta">
                                #<?= $inv->idInvoice ?><?= $inv->invoice_number ? ' &middot; FT ' . htmlspecialchars($inv->invoice_number) : '' ?> &middot; <?= date('d/m/Y', strtotime($inv->date)) ?><br>
                                Vendedor: <?= htmlspecialchars($inv->vendor_name ?: $inv->vendorId) ?>
                            </div>
                            <div class="inv-tags">
                                <span class="inv-tag inv-tag-bot"><?= htmlspecialchars($inv->bot_name) ?></span>
                                <span class="inv-tag inv-tag-pct"><?= $inv->percentage ?>%</span>
                            </div>
                        </div>
                        <div>
                            <div class="inv-total pagada">$<?= number_format($inv->total, 0, ',', '.') ?></div>
                            <div class="inv-comm">+ $<?= number_format($inv->commission, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PENDIENTES -->
        <div class="tab-panel" data-panel="pendientes">
            <div class="card">
                <div class="card-title">Facturas por cobrar &middot; <?= count($pendientes) ?></div>
                <?php if (empty($pendientes)): ?>
                <div class="empty-state">
                    <div class="emoji">&#9203;</div>
                    <p>No hay facturas pendientes en este rango</p>
                </div>
                <?php else: ?>
                <?php foreach ($pendientes as $inv): ?>
                <div class="inv-item">
                    <div class="inv-head">
                        <div style="flex:1; min-width:0;">
                            <div class="inv-client"><?= htmlspecialchars($inv->client_name ?: 'Cliente #' . $inv->clientId) ?></div>
                            <div class="inv-meta">
                                #<?= $inv->idInvoice ?><?= $inv->invoice_number ? ' &middot; FT ' . htmlspecialchars($inv->invoice_number) : '' ?> &middot; <?= date('d/m/Y', strtotime($inv->date)) ?><br>
                                Vendedor: <?= htmlspecialchars($inv->vendor_name ?: $inv->vendorId) ?>
                            </div>
                            <div class="inv-tags">
                                <span class="inv-tag inv-tag-bot"><?= htmlspecialchars($inv->bot_name) ?></span>
                                <span class="inv-tag inv-tag-pct"><?= $inv->percentage ?>%</span>
                            </div>
                        </div>
                        <div>
                            <div class="inv-total pendiente">$<?= number_format($inv->total, 0, ',', '.') ?></div>
                            <div class="inv-comm">~ $<?= number_format($inv->commission, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- HISTORIAL -->
        <div class="tab-panel" data-panel="historial">
            <div class="card">
                <div class="card-title">Últimos 12 periodos</div>
                <?php if (empty($historial)): ?>
                <div class="empty-state">
                    <div class="emoji">&#128198;</div>
                    <p>Aún sin periodos liquidados</p>
                </div>
                <?php else: ?>
                <?php
                    // Agrupar por período
                    $by_period = [];
                    foreach ($historial as $h) {
                        $key = $h->period_id;
                        if (!isset($by_period[$key])) {
                            $by_period[$key] = (object)[
                                'period_label' => $h->period_label,
                                'period_start' => $h->period_start,
                                'period_end' => $h->period_end,
                                'period_status' => $h->period_status,
                                'total' => 0,
                                'items' => [],
                            ];
                        }
                        $by_period[$key]->total += $h->commission_amount;
                        $by_period[$key]->items[] = $h;
                    }
                ?>
                <?php foreach ($by_period as $pid => $p): ?>
                <div class="history-item">
                    <a href="?month=<?= date('Y-m', strtotime($p->period_end)) ?><?= $is_admin && $target_user_id != $this->session->userdata('user_data')['uname'] ? '&user_id=' . urlencode($target_user_id) : '' ?>" style="text-decoration:none; color:inherit; display:flex; justify-content:space-between; width:100%; align-items:center;">
                        <div>
                            <div class="history-period"><?= $p->period_label ?></div>
                            <div class="history-detail"><?= date('d/m', strtotime($p->period_start)) ?> &mdash; <?= date('d/m', strtotime($p->period_end)) ?> &middot; <?= count($p->items) ?> concepto<?= count($p->items) > 1 ? 's' : '' ?></div>
                        </div>
                        <div>
                            <div class="history-amount">$<?= number_format($p->total, 0, ',', '.') ?></div>
                            <?php
                                $all_paid = true;
                                foreach ($p->items as $it) if ($it->status !== 'pagado') { $all_paid = false; break; }
                            ?>
                            <div class="history-status <?= $all_paid ? 'paid' : 'pending' ?>"><?= $all_paid ? 'Pagado' : 'Pendiente' ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Inicio
        </a>
        <a href="<?= base_url() ?>ventas/pendientes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            Pendientes
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tab = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.querySelector('.tab-panel[data-panel="'+tab+'"]');
        if (panel) panel.classList.add('active');
    });
});

function toggleRange() {
    var r = document.getElementById('rangeForm');
    r.style.display = (r.style.display === 'none' || !r.style.display) ? 'flex' : 'none';
}
</script>
</body>
</html>
