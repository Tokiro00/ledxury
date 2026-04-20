<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ledxury Ventas</title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>favicon.ico"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --petrol:#2E7D91; --petrol-dark:#236470; --navy:#1B365D;
            --green:#8DC63F; --bg:#f4f6f8; --card:#ffffff;
            --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0;
            --danger:#ef4444; --warning:#f59e0b; --success:#10b981;
            --radius:12px; --radius-sm:8px;
            --shadow:0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            --tab-height:64px; --header-height:56px;
            --safe-bottom:env(safe-area-inset-bottom, 0px);
        }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); position:relative; }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); }
        .header h1 { font-size:18px; font-weight:700; letter-spacing:.5px; }
        .header .subtitle { font-size:11px; opacity:.8; font-weight:400; margin-left:8px; }
        .header-right { margin-left:auto; }
        .user-avatar { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; }
        .btn-logout { background:none; border:none; color:rgba(255,255,255,.7); font-size:11px; cursor:pointer; padding:4px 8px; }

        .screen-container { flex:1; overflow-y:auto; padding:16px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 16px); }

        .greeting { margin-bottom:16px; }
        .greeting h2 { font-size:20px; font-weight:700; color:var(--text); }
        .greeting p { font-size:13px; color:var(--text-secondary); margin-top:2px; }

        .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
        .stat-card { background:var(--card); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow); text-align:center; }
        .stat-value { font-size:28px; font-weight:800; color:var(--petrol); }
        .stat-label { font-size:12px; color:var(--text-secondary); margin-top:4px; font-weight:600; }
        .stat-sub { font-size:11px; color:var(--text-secondary); margin-top:2px; opacity:.7; }
        .stat-card.highlight { background:linear-gradient(135deg, var(--petrol), var(--petrol-dark)); }
        .stat-card.highlight .stat-value { color:#fff; }
        .stat-card.highlight .stat-label, .stat-card.highlight .stat-sub { color:rgba(255,255,255,.8); }
        .stat-card.danger { background:linear-gradient(135deg, var(--danger), #dc2626); }
        .stat-card.danger .stat-value { color:#fff; }
        .stat-card.danger .stat-label, .stat-card.danger .stat-sub { color:rgba(255,255,255,.8); }

        .section-title { font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; }
        .section-title a { font-size:12px; color:var(--petrol); text-decoration:none; font-weight:600; }

        .quick-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
        .quick-action { background:var(--card); border-radius:var(--radius); padding:16px 12px; box-shadow:var(--shadow); display:flex; align-items:center; text-decoration:none; color:var(--text); transition:transform .1s; position:relative; }
        .quick-action:active { transform:scale(.97); background:#f8fafc; }
        .qa-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-right:10px; }
        .qa-icon svg { width:20px; height:20px; }
        .qa-label { font-size:12px; font-weight:600; line-height:1.3; }
        .qa-badge { position:absolute; top:8px; right:8px; background:var(--danger); color:#fff; font-size:10px; font-weight:700; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; }

        .pending-alert { background:#FEF3C7; border:1px solid #F59E0B; border-radius:var(--radius); padding:14px 16px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; }
        .pending-alert p { font-size:13px; color:#92400E; font-weight:600; }
        .pending-alert a { background:var(--warning); color:#fff; border:none; padding:8px 16px; border-radius:var(--radius-sm); font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; }

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
        <div class="header-right" style="display:flex; align-items:center; gap:8px;">
            <div class="user-avatar"><?= strtoupper(substr($vendor->name, 0, 2)) ?></div>
            <a href="<?= base_url() ?>ventas/logout" class="btn-logout">Salir</a>
        </div>
    </div>

    <!-- Content -->
    <div class="screen-container">
        <!-- Greeting -->
        <div class="greeting">
            <h2>Hola, <?= explode(' ', $vendor->name)[0] ?></h2>
            <p><?= $is_admin ? 'Administrador' : 'Vendedor' ?> &middot; <?= date('d M Y') ?></p>
        </div>

        <!-- Pending Alert -->
        <?php if ($pending_count > 0): ?>
        <div class="pending-alert">
            <p><?= $pending_count ?> pendiente<?= $pending_count > 1 ? 's' : '' ?></p>
            <a href="<?= base_url() ?>ventas/pendientes">Revisar</a>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($sales_today->count) ?></div>
                <div class="stat-label">Ventas hoy</div>
                <div class="stat-sub">$<?= number_format($sales_today->total, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-value"><?= number_format($sales_week->count) ?></div>
                <div class="stat-label">Esta semana</div>
                <div class="stat-sub">$<?= number_format($sales_week->total, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($sales_month->count) ?></div>
                <div class="stat-label">Este mes</div>
                <div class="stat-sub">$<?= number_format($sales_month->total, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card <?= $pending_count > 0 ? 'danger' : '' ?>">
                <div class="stat-value"><?= $pending_count ?></div>
                <div class="stat-label">Pendientes</div>
                <div class="stat-sub">Por aprobar</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-title">Acciones rapidas</div>
        <div class="quick-actions">
            <a href="<?= base_url() ?>ventas/pendientes" class="quick-action">
                <div class="qa-icon" style="background:#FEF3C7;">
                    <svg fill="none" stroke="#F59E0B" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <div class="qa-label">Revisar<br>Pendientes</div>
                <?php if ($pending_count > 0): ?><div class="qa-badge"><?= $pending_count ?></div><?php endif; ?>
            </a>
            <a href="<?= base_url() ?>ventas/crear" class="quick-action">
                <div class="qa-icon" style="background:#DBEAFE;">
                    <svg fill="none" stroke="#3B82F6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
                <div class="qa-label">Crear<br>Presupuesto</div>
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/whatsapp" class="quick-action">
                <div class="qa-icon" style="background:#D1FAE5;">
                    <svg fill="#25D366" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                </div>
                <div class="qa-label">WhatsApp<br>Web</div>
            </a>
            <a href="<?= base_url() ?>ventas/comisiones" class="quick-action">
                <div class="qa-icon" style="background:#EDE9FE;">
                    <svg fill="none" stroke="#7C3AED" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="qa-label">Mis<br>Comisiones</div>
            </a>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard" class="active">
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
</body>
</html>
