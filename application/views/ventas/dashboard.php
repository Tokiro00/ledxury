<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ledxury Ventas</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --petrol:#2E7D91; --petrol-dark:#236470; --navy:#1B365D;
            --green:#8DC63F; --bg:#f4f6f8; --card:#ffffff;
            --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0;
            --danger:#ef4444; --warning:#f59e0b; --success:#10b981;
            --radius:14px; --radius-sm:10px;
            --shadow:0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-hero:0 8px 24px rgba(27,54,93,.22);
            --tab-height:64px; --header-height:56px;
            --safe-bottom:env(safe-area-inset-bottom, 0px);
        }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); position:relative; }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); }
        .header h1 { font-size:18px; font-weight:700; letter-spacing:.5px; }
        .header .subtitle { font-size:11px; opacity:.8; font-weight:400; margin-left:8px; }
        .header-right { margin-left:auto; display:flex; align-items:center; gap:8px; }
        .user-avatar { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; }
        .btn-logout { background:none; border:none; color:rgba(255,255,255,.7); font-size:11px; cursor:pointer; padding:4px 8px; text-decoration:none; }

        .screen-container { flex:1; overflow-y:auto; padding:16px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 16px); }

        /* Hero welcome */
        .hero { position:relative; background:linear-gradient(135deg, var(--navy), var(--petrol)); color:#fff; border-radius:var(--radius); padding:18px 18px 16px; box-shadow:var(--shadow-hero); overflow:hidden; margin-bottom:14px; }
        .hero::before { content:''; position:absolute; width:140px; height:140px; border-radius:50%; background:rgba(255,255,255,.08); top:-50px; right:-40px; }
        .hero::after { content:''; position:absolute; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.06); bottom:-30px; left:40%; }
        .hero-head { display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:1; }
        .hero-hi { font-size:12px; opacity:.85; font-weight:500; }
        .hero-name { font-size:20px; font-weight:800; margin-top:2px; }
        .hero-meta { font-size:11px; opacity:.8; margin-top:2px; }
        .hero-days { text-align:right; background:rgba(255,255,255,.14); padding:6px 10px; border-radius:10px; }
        .hero-days .n { font-size:18px; font-weight:800; line-height:1; }
        .hero-days .l { font-size:9px; text-transform:uppercase; letter-spacing:.5px; opacity:.85; margin-top:2px; }
        .hero-kpi { position:relative; z-index:1; margin-top:14px; padding-top:12px; border-top:1px solid rgba(255,255,255,.15); display:flex; justify-content:space-between; align-items:flex-end; }
        .hero-kpi .k { font-size:10px; opacity:.8; text-transform:uppercase; letter-spacing:.5px; font-weight:700; }
        .hero-kpi .v { font-size:24px; font-weight:800; margin-top:2px; }
        .hero-kpi .sub { font-size:11px; opacity:.8; }

        /* Alerts */
        .alert { border-radius:var(--radius); padding:12px 14px; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; border:1px solid; }
        .alert p { font-size:13px; font-weight:600; margin:0; }
        .alert a { padding:8px 14px; border-radius:var(--radius-sm); font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; color:#fff; }
        .alert-warn { background:#FEF3C7; border-color:#F59E0B; }
        .alert-warn p { color:#92400E; }
        .alert-warn a { background:var(--warning); }
        .alert-danger { background:#FEE2E2; border-color:#EF4444; }
        .alert-danger p { color:#991B1B; }
        .alert-danger a { background:var(--danger); }

        /* Commission card */
        .commission-card { display:block; background:linear-gradient(135deg,#6D28D9,#4C1D95); color:#fff; border-radius:var(--radius); padding:14px 16px; margin-bottom:14px; text-decoration:none; box-shadow:0 2px 8px rgba(76,29,149,.25); }
        .commission-card .label { font-size:10px; text-transform:uppercase; letter-spacing:1px; opacity:.85; font-weight:700; }
        .commission-card .main { display:flex; justify-content:space-between; align-items:baseline; margin-top:2px; }
        .commission-card .amount { font-size:24px; font-weight:800; }
        .commission-card .split { display:flex; gap:12px; margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,.2); font-size:11px; }
        .commission-card .split > div { flex:1; }
        .commission-card .split .k { opacity:.7; }
        .commission-card .split .v { font-weight:700; font-size:13px; margin-top:1px; }

        /* Stat rows */
        .section-title { font-size:12px; font-weight:700; color:var(--text-secondary); margin:12px 0 8px; text-transform:uppercase; letter-spacing:.5px; }

        .stat-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:12px; }
        .stat-chip { background:var(--card); border-radius:var(--radius-sm); padding:10px 8px; box-shadow:var(--shadow); border:1px solid var(--border); text-align:center; }
        .stat-chip .n { font-size:20px; font-weight:800; color:var(--text); line-height:1.1; }
        .stat-chip .l { font-size:10px; color:var(--text-secondary); font-weight:600; margin-top:3px; text-transform:uppercase; letter-spacing:.3px; }
        .stat-chip .s { font-size:10px; color:var(--text-secondary); opacity:.75; margin-top:1px; }
        .stat-chip.pending .n { color:var(--warning); }
        .stat-chip.approved .n { color:var(--success); }
        .stat-chip.today .n { color:var(--petrol); }

        .metric-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
        .metric-card { background:var(--card); border-radius:var(--radius); padding:12px; box-shadow:var(--shadow); border:1px solid var(--border); display:flex; gap:10px; align-items:center; text-decoration:none; color:var(--text); }
        .metric-card:active { transform:scale(.98); }
        .metric-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .metric-icon svg { width:18px; height:18px; }
        .metric-body { min-width:0; flex:1; }
        .metric-value { font-size:17px; font-weight:800; color:var(--text); line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .metric-label { font-size:10px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-top:2px; }

        /* Quick actions */
        .quick-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px; }
        .quick-action { background:var(--card); border-radius:var(--radius); padding:14px 12px; box-shadow:var(--shadow); border:1px solid var(--border); display:flex; align-items:center; text-decoration:none; color:var(--text); position:relative; }
        .quick-action:active { transform:scale(.97); background:#f8fafc; }
        .qa-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-right:10px; }
        .qa-icon svg { width:18px; height:18px; }
        .qa-label { font-size:12px; font-weight:600; line-height:1.3; }
        .qa-badge { position:absolute; top:8px; right:8px; background:var(--danger); color:#fff; font-size:10px; font-weight:700; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; }

        .primary-cta { grid-column:1 / -1; background:linear-gradient(135deg, var(--navy), var(--petrol)); color:#fff; border-radius:var(--radius); padding:14px; display:flex; align-items:center; text-decoration:none; box-shadow:0 4px 12px rgba(46,125,145,.28); }
        .primary-cta .qa-icon { background:rgba(255,255,255,.18); }
        .primary-cta .qa-icon svg { stroke:#fff; }
        .primary-cta .qa-label { color:#fff; font-size:14px; font-weight:700; }

        .tab-bar { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); display:flex; z-index:10; padding-bottom:var(--safe-bottom); height:var(--tab-height); }
        .tab-bar a { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:10px; color:var(--text-secondary); text-decoration:none; font-weight:600; position:relative; }
        .tab-bar a svg { width:24px; height:24px; margin-bottom:2px; }
        .tab-bar a.active { color:var(--petrol); }
        .tab-bar a.active::before { content:''; position:absolute; top:0; left:25%; right:25%; height:3px; background:var(--petrol); border-radius:0 0 3px 3px; }
        .tab-bar a:active { background:rgba(46,125,145,.06); }
    </style>
</head>
<body>
<div id="app">
    <!-- Header -->
    <div class="header">
        <div>
            <h1>Ledxury<span class="subtitle">Ventas</span></h1>
        </div>
        <div class="header-right">
            <div class="user-avatar"><?= strtoupper(substr($vendor->name, 0, 2)) ?></div>
            <a href="<?= base_url() ?>ventas/logout" class="btn-logout">Salir</a>
        </div>
    </div>

    <!-- Content -->
    <div class="screen-container">
        <!-- Hero -->
        <div class="hero">
            <div class="hero-head">
                <div>
                    <div class="hero-hi">Hola,</div>
                    <div class="hero-name"><?= htmlspecialchars(explode(' ', $vendor->name)[0]) ?></div>
                    <div class="hero-meta"><?= $is_admin ? 'Administrador' : 'Vendedor' ?> &middot; <?= date('d M Y') ?></div>
                </div>
                <div class="hero-days">
                    <div class="n"><?= $days_left ?></div>
                    <div class="l">Dias habiles</div>
                </div>
            </div>
            <div class="hero-kpi">
                <div>
                    <div class="k">Ventas del mes</div>
                    <div class="v">$<?= number_format($sales_month->total, 0, ',', '.') ?></div>
                    <div class="sub"><?= number_format($sales_month->count) ?> presupuesto<?= $sales_month->count == 1 ? '' : 's' ?></div>
                </div>
                <?php if (!$is_admin && $ranking_position > 0): ?>
                <div style="text-align:right;">
                    <div class="k">Ranking</div>
                    <div class="v">#<?= $ranking_position ?></div>
                    <div class="sub">de <?= $ranking_total ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Failed Alert -->
        <?php if ($failed_count > 0): ?>
        <div class="alert alert-danger">
            <p><?= $failed_count ?> venta<?= $failed_count > 1 ? 's' : '' ?> del bot con error</p>
            <a href="<?= base_url() ?>ventas/fallidos">Corregir</a>
        </div>
        <?php endif; ?>

        <!-- Pending Alert -->
        <?php if ($pending_count > 0): ?>
        <div class="alert alert-warn">
            <p><?= $pending_count ?> presupuesto<?= $pending_count > 1 ? 's' : '' ?> pendiente<?= $pending_count > 1 ? 's' : '' ?></p>
            <a href="<?= base_url() ?>ventas/pendientes">Revisar</a>
        </div>
        <?php endif; ?>

        <!-- Commission -->
        <?php if (!empty($has_commission_config)): ?>
        <a href="<?= base_url() ?>ventas/comisiones" class="commission-card">
            <div class="label">Mi comision &middot; <?= date('d/m', strtotime($commission_period_from)) ?> &mdash; <?= date('d/m', strtotime($commission_period_to)) ?></div>
            <div class="main">
                <div class="amount">$<?= number_format($commission_total_cobrada, 0, ',', '.') ?></div>
                <span>&rarr;</span>
            </div>
            <div class="split">
                <div>
                    <div class="k">Ganada</div>
                    <div class="v">$<?= number_format($commission_total_cobrada, 0, ',', '.') ?></div>
                </div>
                <div>
                    <div class="k">Proyectada</div>
                    <div class="v">$<?= number_format($commission_total_pendiente, 0, ',', '.') ?></div>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <!-- Stat row (3 chips) -->
        <div class="section-title">Resumen</div>
        <div class="stat-row-3">
            <div class="stat-chip today">
                <div class="n"><?= number_format($sales_today->count) ?></div>
                <div class="l">Hoy</div>
                <div class="s">$<?= number_format($sales_today->total/1000, 0, ',', '.') ?>K</div>
            </div>
            <div class="stat-chip pending">
                <div class="n"><?= (int)$pending_count ?></div>
                <div class="l">Pendientes</div>
                <div class="s">Por revisar</div>
            </div>
            <div class="stat-chip approved">
                <div class="n"><?= (int)$approved_month ?></div>
                <div class="l">Aprobados</div>
                <div class="s">Este mes</div>
            </div>
        </div>

        <!-- Metric grid -->
        <div class="section-title">Indicadores del mes</div>
        <div class="metric-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background:#DCFCE7;">
                    <svg fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="metric-body">
                    <div class="metric-value">$<?= number_format($collected_month/1000, 0, ',', '.') ?>K</div>
                    <div class="metric-label">Recaudado</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background:#DBEAFE;">
                    <svg fill="none" stroke="#3B82F6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="metric-body">
                    <div class="metric-value">$<?= number_format($billed_month->total/1000, 0, ',', '.') ?>K</div>
                    <div class="metric-label">Facturado</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background:#FEF3C7;">
                    <svg fill="none" stroke="#F59E0B" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div class="metric-body">
                    <div class="metric-value">$<?= number_format($avg_ticket/1000, 0, ',', '.') ?>K</div>
                    <div class="metric-label">Ticket promedio</div>
                </div>
            </div>
            <a href="<?= base_url() ?>sisvent/commercial/clients" class="metric-card">
                <div class="metric-icon" style="background:#FEE2E2;">
                    <svg fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="metric-body">
                    <div class="metric-value"><?= (int)$inactive_clients ?></div>
                    <div class="metric-label">Clientes inactivos</div>
                </div>
            </a>
        </div>

        <!-- Quick Actions -->
        <div class="section-title">Acciones rapidas</div>
        <div class="quick-actions">
            <a href="<?= base_url() ?>ventas/crear" class="primary-cta">
                <div class="qa-icon">
                    <svg fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div class="qa-label">Nuevo presupuesto</div>
            </a>
            <a href="<?= base_url() ?>ventas/fallidos" class="quick-action">
                <div class="qa-icon" style="background:#FEE2E2;">
                    <svg fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div class="qa-label">Revisar<br>fallidos</div>
                <?php if ($failed_count > 0): ?><div class="qa-badge"><?= $failed_count ?></div><?php endif; ?>
            </a>
            <a href="<?= base_url() ?>ventas/comisiones" class="quick-action">
                <div class="qa-icon" style="background:#EDE9FE;">
                    <svg fill="none" stroke="#7C3AED" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="qa-label">Mis<br>comisiones</div>
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/whatsapp" class="quick-action">
                <div class="qa-icon" style="background:#D1FAE5;">
                    <svg fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                </div>
                <div class="qa-label">WhatsApp<br>Web</div>
            </a>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Inicio
        </a>
        <a href="<?= base_url() ?>ventas/pendientes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Pendientes
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Chat
        </a>
    </div>
</div>
</body>
</html>
