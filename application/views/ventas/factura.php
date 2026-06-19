<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Factura #<?= (int)$inv->idInvoice ?> - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --warning:#f59e0b; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }
        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.85); font-size:12px; text-decoration:none; }
        .screen-container { flex:1; overflow-y:auto; padding:14px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }
        .card { background:var(--card); border-radius:var(--radius); padding:14px; margin-bottom:12px; box-shadow:var(--shadow); }
        .card h3 { font-size:11px; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
        .estado-badge { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:var(--radius-sm); color:#fff; font-weight:800; font-size:15px; width:100%; justify-content:center; }
        .estado-sub { font-size:12px; color:var(--text-secondary); margin-top:8px; line-height:1.5; }
        .row { display:flex; justify-content:space-between; gap:10px; font-size:13px; padding:4px 0; }
        .row .k { color:var(--text-secondary); }
        .row .v { font-weight:600; text-align:right; }
        .prod { display:flex; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .prod:last-child { border-bottom:none; }
        .prod .code { font-weight:700; }
        .prod .desc { font-size:11px; color:var(--text-secondary); }
        .prod .amt { white-space:nowrap; font-weight:700; }
        .total-line { display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:2px solid #e2e8f0; }
        .total-line .v { font-size:20px; font-weight:800; color:var(--warning); }
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
        <h1>Factura #<?= (int)$inv->idInvoice ?></h1>
        <a href="<?= base_url() ?>ventas/logout">Salir</a>
    </div>

    <div class="screen-container">

        <!-- Estado de envío -->
        <div class="card">
            <h3>Estado del envío (Interrapidísimo)</h3>
            <div class="estado-badge" style="background:<?= $estado['color'] ?>;">
                <?= htmlspecialchars($estado['label']) ?>
            </div>
            <?php if ($guia): ?>
            <div class="estado-sub">
                <?php if (!empty($guia->numeroPreenvio)): ?>Guía: <strong><?= htmlspecialchars($guia->numeroPreenvio) ?></strong><br><?php endif; ?>
                <?php if (!empty($guia->ciudadDestinoNombre)): ?>Destino: <?= htmlspecialchars($guia->ciudadDestinoNombre) ?><br><?php endif; ?>
                <?php if (!empty($estado['raw']) && $estado['raw'] !== $estado['label']): ?>Detalle Interrapidísimo: <?= htmlspecialchars($estado['raw']) ?><br><?php endif; ?>
                <?php if (!empty($guia->actualDelivery)): ?>Entregado: <?= date('d/m/Y H:i', strtotime($guia->actualDelivery)) ?><br><?php endif; ?>
                <?php if (!empty($guia->fechaEstado)): ?>Últ. actualización: <?= date('d/m/Y H:i', strtotime($guia->fechaEstado)) ?><?php endif; ?>
            </div>
            <?php else: ?>
            <div class="estado-sub">Esta factura aún no tiene guía de envío generada.</div>
            <?php endif; ?>
        </div>

        <!-- Cliente -->
        <div class="card">
            <h3>Cliente</h3>
            <div class="row"><span class="k">Nombre</span><span class="v"><?= htmlspecialchars($inv->client_name ?: 'Cliente #' . $inv->clientId) ?></span></div>
            <?php if (!empty($inv->client_doc)): ?><div class="row"><span class="k">Documento</span><span class="v"><?= htmlspecialchars($inv->client_doc) ?></span></div><?php endif; ?>
            <?php if (!empty($inv->client_phone)): ?><div class="row"><span class="k">Celular</span><span class="v"><?= htmlspecialchars($inv->client_phone) ?></span></div><?php endif; ?>
            <?php if (!empty($inv->client_address)): ?><div class="row"><span class="k">Dirección</span><span class="v"><?= htmlspecialchars($inv->client_address) ?></span></div><?php endif; ?>
            <?php if (!empty($inv->client_city) || !empty($inv->client_state)): ?><div class="row"><span class="k">Ciudad</span><span class="v"><?= htmlspecialchars(trim(($inv->client_city ?: '') . ' ' . ($inv->client_state ?: ''))) ?></span></div><?php endif; ?>
            <div class="row"><span class="k">Fecha</span><span class="v"><?= $inv->date ? date('d/m/Y', strtotime($inv->date)) : '—' ?></span></div>
        </div>

        <!-- Productos -->
        <div class="card">
            <h3>Productos</h3>
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $p): ?>
                <div class="prod">
                    <div>
                        <div class="code"><?= (int)$p->quantity ?>x <?= htmlspecialchars($p->productId) ?></div>
                        <?php if (!empty($p->description)): ?><div class="desc"><?= htmlspecialchars($p->description) ?></div><?php endif; ?>
                    </div>
                    <div class="amt">$<?= number_format($p->total, 0, ',', '.') ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="desc">Sin detalle de productos.</div>
            <?php endif; ?>
            <div class="total-line"><span class="k">Total factura</span><span class="v">$<?= number_format($inv->total, 0, ',', '.') ?></span></div>
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
        <a href="<?= base_url() ?>ventas/cartera" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m3 0h1M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            Cartera
        </a>
        <a href="<?= base_url() ?>ventas/comisiones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
            Comisiones
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>
</body>
</html>
