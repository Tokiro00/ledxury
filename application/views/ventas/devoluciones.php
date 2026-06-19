<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Devoluciones - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --danger:#dc2626; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }
        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.85); font-size:12px; text-decoration:none; }
        .header .count { background:rgba(255,255,255,.2); padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }
        .section-title { font-size:11px; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin:4px 2px 8px; display:flex; justify-content:space-between; }
        .dev-card { background:var(--card); border-radius:var(--radius); padding:12px 14px; margin-bottom:8px; box-shadow:var(--shadow); border-left:4px solid var(--danger); display:flex; justify-content:space-between; align-items:flex-start; gap:8px; text-decoration:none; color:inherit; }
        .dev-card .cli { font-size:14px; font-weight:700; }
        .dev-card .meta { font-size:11px; color:var(--text-secondary); margin-top:2px; }
        .dev-card .badge { display:inline-block; margin-top:6px; font-size:10px; font-weight:700; color:#fff; background:var(--danger); padding:2px 8px; border-radius:6px; }
        .dev-card .amt { text-align:right; white-space:nowrap; }
        .dev-card .amt .t { font-size:15px; font-weight:800; color:var(--danger); }
        .dev-card .amt .v { font-size:11px; color:var(--petrol); font-weight:600; margin-top:2px; }
        .empty { text-align:center; padding:60px 20px; color:var(--text-secondary); }
        .empty svg { width:56px; height:56px; margin-bottom:12px; color:#d1d5db; }
        .empty h3 { font-size:16px; font-weight:700; color:var(--text); }
        .empty p { font-size:13px; margin-top:4px; }
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
        <a href="<?= base_url() ?>ventas/cartera">← Cartera</a>
        <h1>Devoluciones</h1>
        <span class="count"><?= count($items) ?></span>
    </div>

    <div class="screen-container">
        <?php if (!empty($items)): ?>
        <div class="section-title"><span>Facturas devueltas por la transportadora</span></div>
        <?php foreach ($items as $d): ?>
        <a class="dev-card" href="<?= base_url() ?>ventas/factura/<?= (int)$d->invoice_id ?>" style="border-left-color:<?= $d->status_color ?>;">
            <div style="flex:1; min-width:0;">
                <div class="cli"><?= htmlspecialchars($d->client_name ?: 'Cliente') ?></div>
                <div class="meta">#<?= (int)$d->invoice_id ?><?php if (!empty($d->detected_at)): ?> &middot; <?= date('d/m/Y', strtotime($d->detected_at)) ?><?php endif; ?><?php if (!empty($d->ciudadDestinoNombre)): ?> &middot; <?= htmlspecialchars($d->ciudadDestinoNombre) ?><?php endif; ?></div>
                <span class="badge" style="background:<?= $d->status_color ?>;"><?= htmlspecialchars(mb_strtoupper($d->status_label)) ?></span>
            </div>
            <div class="amt">
                <div class="t" style="color:<?= $d->status_color ?>;">$<?= number_format($d->total, 0, ',', '.') ?></div>
                <div class="v">ver ›</div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3>Sin devoluciones</h3>
            <p>No tienes facturas devueltas</p>
        </div>
        <?php endif; ?>
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
        <a href="<?= base_url() ?>ventas/cartera" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m3 0h1M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            Cartera
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>
</body>
</html>
