<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Mis guías - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --petrol:#2E7D91; --petrol-dark:#236470;
            --bg:#f4f6f8; --card:#fff;
            --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0;
            --danger:#ef4444; --warning:#f59e0b; --success:#10b981; --info:#3b82f6;
            --radius:12px; --radius-sm:8px;
            --shadow:0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            --tab-height:64px; --header-height:56px;
            --safe-bottom:env(safe-area-inset-bottom, 0px);
        }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; min-height:100%; max-width:480px; margin:0 auto; background:var(--bg); position:relative; }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); }
        .header a.back { color:#fff; text-decoration:none; font-size:20px; margin-right:12px; }
        .header h1 { font-size:16px; font-weight:700; flex:1; }

        .filters { background:#fff; padding:10px 12px; border-bottom:1px solid var(--border); display:flex; gap:8px; flex-wrap:wrap; }
        .filters input[type=search] { flex:1; min-width:140px; padding:9px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:13px; background:#f8fafc; }
        .filters input[type=search]:focus { border-color:var(--petrol); outline:none; background:#fff; }
        .filters .chips { display:flex; gap:6px; overflow-x:auto; width:100%; padding-bottom:2px; }
        .filters .chip { padding:6px 10px; border:1px solid var(--border); background:#fff; color:var(--text-secondary); border-radius:99px; font-size:11px; font-weight:600; text-decoration:none; white-space:nowrap; }
        .filters .chip.active { background:var(--petrol); color:#fff; border-color:var(--petrol); }

        .screen-container { flex:1; padding:12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 16px); }

        .empty-state { text-align:center; padding:48px 20px; color:var(--text-secondary); }
        .empty-state svg { width:80px; height:80px; margin-bottom:12px; opacity:.4; }
        .empty-state p { font-size:14px; }

        .guia-card { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); margin-bottom:10px; overflow:hidden; border-left:4px solid var(--petrol); }
        .guia-card.s-entregado { border-left-color:var(--success); }
        .guia-card.s-novedad { border-left-color:var(--danger); }
        .guia-card.s-transito { border-left-color:var(--info); }
        .guia-card.s-cotizado { border-left-color:var(--warning); }

        .guia-row1 { display:flex; justify-content:space-between; align-items:flex-start; padding:12px 14px 6px; gap:8px; }
        .guia-num { font-size:13px; font-weight:700; color:var(--text); font-family:'SF Mono', Menlo, monospace; word-break:break-all; }
        .guia-carrier { font-size:11px; color:var(--text-secondary); margin-top:1px; }
        .guia-status { font-size:10px; font-weight:700; padding:4px 10px; border-radius:99px; white-space:nowrap; flex-shrink:0; text-transform:uppercase; letter-spacing:.3px; }
        .st-cotizado { background:#fef3c7; color:#92400e; }
        .st-creado { background:#dbeafe; color:#1e40af; }
        .st-recogida { background:#e0e7ff; color:#3730a3; }
        .st-transito { background:#dbeafe; color:#1e40af; }
        .st-reparto { background:#cffafe; color:#155e75; }
        .st-entregado { background:#d1fae5; color:#065f46; }
        .st-novedad { background:#fee2e2; color:#991b1b; }
        .st-default { background:#f1f5f9; color:#475569; }

        .guia-body { padding:0 14px 12px; font-size:12px; color:var(--text-secondary); line-height:1.5; }
        .guia-body strong { color:var(--text); font-weight:600; }
        .guia-body .row { display:flex; gap:6px; margin-top:3px; }
        .guia-body .row .label { font-size:10px; text-transform:uppercase; letter-spacing:.3px; color:#94a3b8; min-width:60px; padding-top:1px; }

        .guia-foot { display:flex; gap:6px; padding:8px 14px; border-top:1px solid var(--border); background:#fafbfc; }
        .guia-foot a { flex:1; padding:8px; text-align:center; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:4px; }
        .btn-track { background:#dbeafe; color:#1e40af; }
        .btn-wa { background:#dcfce7; color:#166534; }

        .summary { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); padding:10px 14px; margin-bottom:10px; font-size:11px; color:var(--text-secondary); display:flex; justify-content:space-between; }
        .summary strong { color:var(--text); font-size:13px; }

        .tab-bar { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); display:flex; z-index:10; padding-bottom:var(--safe-bottom); height:var(--tab-height); }
        .tab-bar a { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:10px; color:var(--text-secondary); text-decoration:none; font-weight:600; position:relative; }
        .tab-bar a svg { width:24px; height:24px; margin-bottom:2px; }
        .tab-bar a.active { color:var(--petrol); }
        .tab-bar a.active::before { content:''; position:absolute; top:0; left:25%; right:25%; height:3px; background:var(--petrol); border-radius:0 0 3px 3px; }
    </style>
</head>
<body>
<?php
function _statusInfo($g) {
    $estadoCode = isset($g->estadoGuia) ? (int)$g->estadoGuia : 0;
    $status = $g->status ?? '';
    if ($estadoCode === 11) return ['label' => 'Entregado', 'cls' => 'st-entregado', 'card' => 's-entregado'];
    if (in_array($estadoCode, [7, 8, 10])) return ['label' => 'Novedad', 'cls' => 'st-novedad', 'card' => 's-novedad'];
    if (in_array($estadoCode, [6, 31])) return ['label' => 'En reparto', 'cls' => 'st-reparto', 'card' => 's-transito'];
    if (in_array($estadoCode, [2, 3, 4, 18])) return ['label' => 'En transito', 'cls' => 'st-transito', 'card' => 's-transito'];
    if ($status === 'recogida_solicitada') return ['label' => 'Por recoger', 'cls' => 'st-recogida', 'card' => 's-transito'];
    if ($status === 'creado') return ['label' => 'Creado', 'cls' => 'st-creado', 'card' => ''];
    if ($status === 'cotizado') return ['label' => 'Cotizado', 'cls' => 'st-cotizado', 'card' => 's-cotizado'];
    if (!empty($g->estadoNombre)) return ['label' => htmlspecialchars($g->estadoNombre), 'cls' => 'st-default', 'card' => ''];
    return ['label' => htmlspecialchars($status ?: '—'), 'cls' => 'st-default', 'card' => ''];
}
?>
<div id="app">
    <div class="header">
        <a class="back" href="<?= base_url() ?>ventas/dashboard">&larr;</a>
        <h1>Mis guías<?= $is_admin ? ' (todas)' : '' ?></h1>
        <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
          <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
          <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
        </div>
    </div>

    <form class="filters" method="get" action="<?= base_url() ?>ventas/guias">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar guía, cliente, ciudad..." onchange="this.form.submit()">
        <div class="chips">
            <?php
                $chips = [
                    'all' => 'Todas',
                    'cotizado' => 'Cotizadas',
                    'creado' => 'Creadas',
                    'recogida_solicitada' => 'Por recoger',
                ];
                foreach ($chips as $val => $lbl):
                    $cls = ($status === $val) ? 'chip active' : 'chip';
                    $href = base_url() . 'ventas/guias?status=' . urlencode($val) . ($q ? '&q=' . urlencode($q) : '');
            ?>
                <a class="<?= $cls ?>" href="<?= $href ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="screen-container">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a1 1 0 001 1h1"/></svg>
                <p>No hay guías de envío<?= $q ? ' para "' . htmlspecialchars($q) . '"' : '' ?>.</p>
            </div>
        <?php else: ?>
            <div class="summary">
                <span>Mostrando <strong><?= count($items) ?></strong> guía<?= count($items) === 1 ? '' : 's' ?></span>
            </div>
            <?php foreach ($items as $g):
                $info = _statusInfo($g);
                $tracking = '';
                if (!empty($g->numeroPreenvio) && stripos((string)$g->carrierName, 'inter') !== false) {
                    $tracking = 'https://interrapidisimo.com/sigue-tu-envio/?guia=' . urlencode($g->numeroPreenvio);
                }
                $phone = preg_replace('/\D+/', '', (string)($g->client_phone ?? ''));
                if ($phone && strlen($phone) === 10) $phone = '57' . $phone;
                $waMsg = 'Hola ' . ($g->client_name ?? '') . ', tu envío';
                if (!empty($g->numeroPreenvio)) $waMsg .= ' #' . $g->numeroPreenvio;
                $waMsg .= ' está en estado: ' . $info['label'];
            ?>
            <div class="guia-card <?= $info['card'] ?>">
                <div class="guia-row1">
                    <div style="flex:1; min-width:0;">
                        <div class="guia-num"><?= htmlspecialchars($g->numeroPreenvio ?: '(sin guía)') ?></div>
                        <div class="guia-carrier"><?= htmlspecialchars($g->carrierName ?: '—') ?> &middot; <?= !empty($g->created_at) ? date('d M Y', strtotime($g->created_at)) : '' ?></div>
                    </div>
                    <span class="guia-status <?= $info['cls'] ?>"><?= $info['label'] ?></span>
                </div>
                <div class="guia-body">
                    <div class="row"><span class="label">Cliente</span><span><strong><?= htmlspecialchars($g->client_name ?: ($g->recipientName ?: '—')) ?></strong></span></div>
                    <div class="row"><span class="label">Destino</span><span><?= htmlspecialchars($g->ciudadDestinoNombre ?: ($g->client_city ?: '—')) ?></span></div>
                    <?php if (!empty($g->valorTotal)): ?>
                    <div class="row"><span class="label">Valor</span><span>$<?= number_format((float)$g->valorTotal, 0, ',', '.') ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($g->estadoNombre) && !in_array($info['label'], [$g->estadoNombre, htmlspecialchars($g->estadoNombre)])): ?>
                    <div class="row"><span class="label">Detalle</span><span><?= htmlspecialchars($g->estadoNombre) ?></span></div>
                    <?php endif; ?>
                    <?php if ($is_admin && !empty($g->vendor_name)): ?>
                    <div class="row"><span class="label">Vendedor</span><span><?= htmlspecialchars($g->vendor_name) ?></span></div>
                    <?php endif; ?>
                </div>
                <?php if ($tracking || $phone): ?>
                <div class="guia-foot">
                    <?php if ($tracking): ?>
                    <a class="btn-track" href="<?= $tracking ?>" target="_blank" rel="noopener">Rastrear</a>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                    <a class="btn-wa" href="https://wa.me/<?= $phone ?>?text=<?= urlencode($waMsg) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
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
        <a href="<?= base_url() ?>ventas/guias" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/></svg>
            Guías
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>
</body>
</html>
