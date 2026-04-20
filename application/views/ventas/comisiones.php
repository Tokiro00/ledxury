<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Mis Comisiones - Ledxury</title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>favicon.ico"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --petrol-dark:#236470; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --success:#10b981; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }
        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.8); font-size:12px; text-decoration:none; }
        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }
        .total-card { background:linear-gradient(135deg,var(--petrol),var(--petrol-dark)); border-radius:var(--radius); padding:24px; color:#fff; text-align:center; margin-bottom:16px; }
        .total-label { font-size:12px; opacity:.8; text-transform:uppercase; letter-spacing:1px; }
        .total-value { font-size:36px; font-weight:800; margin-top:4px; }
        .total-period { font-size:12px; opacity:.7; margin-top:4px; }
        .total-status { display:inline-block; margin-top:8px; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .month-selector { display:flex; gap:8px; margin-bottom:16px; }
        .month-selector input { flex:1; padding:10px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; }
        .month-selector button { padding:10px 16px; background:var(--petrol); color:#fff; border:none; border-radius:var(--radius-sm); font-weight:700; font-size:13px; }
        .card { background:var(--card); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow); margin-bottom:12px; }
        .card-title { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-bottom:12px; }
        .commission-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .commission-item:last-child { border:none; }
        .commission-info { flex:1; }
        .commission-bot { font-size:13px; font-weight:600; color:var(--text); }
        .commission-detail { font-size:11px; color:var(--text-secondary); margin-top:2px; }
        .commission-amount { font-size:16px; font-weight:800; color:var(--petrol); }
        .history-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .history-item:last-child { border:none; }
        .history-period { font-size:13px; font-weight:600; }
        .history-detail { font-size:11px; color:var(--text-secondary); }
        .history-amount { font-size:14px; font-weight:700; }
        .history-amount.paid { color:var(--success); }
        .history-amount.pending { color:#f59e0b; }
        .empty-state { text-align:center; padding:40px 20px; color:var(--text-secondary); }
        .empty-state p { font-size:14px; margin-top:8px; }
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
        <a href="<?= base_url() ?>ventas/dashboard">← Inicio</a>
        <h1>Mis Comisiones</h1>
        <span></span>
    </div>

    <div class="screen-container">
        <!-- Total -->
        <div class="total-card">
            <div class="total-label">Comision del periodo</div>
            <div class="total-value">$<?= number_format($total_comision, 0, ',', '.') ?></div>
            <div class="total-period"><?= $period_label ?> (<?= date('d/m', strtotime($period_start)) ?> - <?= date('d/m', strtotime($period_end)) ?>)</div>
            <?php if ($period && $period->status === 'liquidado'): ?>
            <span class="total-status" style="background:rgba(255,255,255,.2);">Liquidado</span>
            <?php else: ?>
            <span class="total-status" style="background:rgba(245,158,11,.3);">Pendiente</span>
            <?php endif; ?>
        </div>

        <!-- Month Selector -->
        <form class="month-selector" method="GET">
            <input type="month" name="month" value="<?= $month ?>">
            <button type="submit">Ver</button>
        </form>

        <!-- Current Period Detail -->
        <?php if (!empty($mis_comisiones)): ?>
        <div class="card">
            <div class="card-title">Detalle del periodo</div>
            <?php foreach ($mis_comisiones as $c): ?>
            <div class="commission-item">
                <div class="commission-info">
                    <div class="commission-bot"><?= $c->bot_name ?: 'Todos los bots' ?></div>
                    <div class="commission-detail"><?= $c->percentage ?>% sobre $<?= number_format($c->base_amount, 0, ',', '.') ?></div>
                </div>
                <div class="commission-amount">$<?= number_format($c->commission_amount, 0, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <p style="font-size:32px;">&#x1F4B0;</p>
                <p>No hay comisiones para este periodo</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- History -->
        <?php if (!empty($historial)): ?>
        <div class="card">
            <div class="card-title">Historial</div>
            <?php foreach ($historial as $h): ?>
            <div class="history-item">
                <div>
                    <div class="history-period"><?= $h->period_label ?></div>
                    <div class="history-detail"><?= $h->percentage ?>% - <?= $h->bot_name ?: 'Todos' ?></div>
                </div>
                <div class="history-amount <?= $h->status === 'pagado' ? 'paid' : 'pending' ?>">
                    $<?= number_format($h->commission_amount, 0, ',', '.') ?>
                    <div style="font-size:9px; text-align:right;"><?= $h->status === 'pagado' ? 'Pagado' : 'Pendiente' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
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
        <a href="<?= base_url() ?>ventas/comisiones" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Comisiones
        </a>
    </div>
</div>
</body>
</html>
