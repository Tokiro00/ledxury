<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Mis Presupuestos - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --petrol-dark:#236470; --navy:#1B365D; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --danger:#ef4444; --warning:#f59e0b; --success:#10b981; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.85); font-size:12px; text-decoration:none; }
        .header .count { background:rgba(255,255,255,.2); padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }

        .toolbar { padding:10px 12px 0; flex-shrink:0; }
        .search-input { width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:24px; font-size:13px; outline:none; background:#fff; }
        .search-input:focus { border-color:var(--petrol); box-shadow:0 0 0 2px rgba(46,125,145,.12); }
        .chips { display:flex; gap:6px; overflow-x:auto; padding:10px 0 8px; -webkit-overflow-scrolling:touch; }
        .chips::-webkit-scrollbar { display:none; }
        .chip { flex-shrink:0; padding:7px 14px; border-radius:999px; font-size:12px; font-weight:600; background:#fff; border:1px solid var(--border); color:var(--text-secondary); text-decoration:none; transition:all .15s; }
        .chip.active { background:var(--petrol); color:#fff; border-color:var(--petrol); }
        .chip-count { font-size:10px; opacity:.75; margin-left:4px; }

        .screen-container { flex:1; overflow-y:auto; padding:0 12px 12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }

        .budget-card { background:var(--card); border-radius:var(--radius); padding:14px; margin-bottom:10px; box-shadow:var(--shadow); border-left:4px solid var(--warning); }
        .budget-card.state-2 { border-left-color:#6366F1; }
        .budget-card.state-1 { border-left-color:var(--success); }
        .budget-card.state-archived { border-left-color:#9ca3af; opacity:.75; }
        .budget-header { display:flex; justify-content:space-between; align-items:flex-start; }
        .budget-id { font-size:10px; color:var(--text-secondary); font-weight:700; }
        .budget-total { font-size:18px; font-weight:800; color:var(--text); }
        .budget-client { font-size:14px; font-weight:700; color:var(--text); margin-top:2px; }
        .budget-phone { font-size:12px; color:var(--text-secondary); }
        .budget-tags { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
        .tag { font-size:10px; padding:3px 8px; border-radius:6px; font-weight:600; }
        .tag-pending { background:#FEF3C7; color:#92400E; }
        .tag-reviewed { background:#E0E7FF; color:#3730A3; }
        .tag-approved { background:#D1FAE5; color:#065F46; }
        .tag-archived { background:#F3F4F6; color:#4B5563; }
        .tag-vendor { background:#EFF6FF; color:#1D4ED8; }
        .tag-date { background:#F3F4F6; color:#6B7280; }
        .budget-comments { font-size:11px; color:var(--text-secondary); margin-top:8px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .budget-actions { display:flex; gap:8px; margin-top:12px; }
        .btn { flex:1; padding:8px; border:none; border-radius:var(--radius-sm); font-size:12px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; }
        .btn:active { transform:scale(.97); }
        .btn-view { background:#EFF6FF; color:#1D4ED8; }
        .btn-edit { background:#F3F4F6; color:#374151; }

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
        <a href="<?= base_url() ?>ventas/dashboard">← Inicio</a>
        <h1>Mis Presupuestos</h1>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="count"><?= $total_count ?></span>
          <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
          <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
        </div>
    </div>

    <div class="toolbar">
        <form method="GET" action="<?= base_url() ?>ventas/presupuestos">
            <input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar #, cliente, telefono..." class="search-input" onchange="this.form.submit()">
        </form>
        <div class="chips">
            <?php
              $chips = array(
                'todos'      => array('label'=>'Todos',      'count'=>$counts['todos']),
                'pendientes' => array('label'=>'Pendientes', 'count'=>$counts['pendientes']),
                'revisados'  => array('label'=>'Revisados',  'count'=>$counts['revisados']),
                'aprobados'  => array('label'=>'Aprobados',  'count'=>$counts['aprobados']),
                'archivados' => array('label'=>'Archivados', 'count'=>$counts['archivados']),
              );
              foreach ($chips as $key => $c):
                $active = $state === $key;
                $href = base_url() . 'ventas/presupuestos?state=' . $key . ($q !== '' ? '&q=' . urlencode($q) : '');
            ?>
            <a class="chip <?= $active ? 'active' : '' ?>" href="<?= $href ?>"><?= $c['label'] ?><span class="chip-count"><?= $c['count'] ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="screen-container">
        <?php if (!empty($budgets)): ?>
        <?php foreach ($budgets as $b):
            $isArchived = (int)$b->archived === 1;
            $st = (int)$b->state;
            $cardCls = $isArchived ? 'state-archived' : ('state-' . $st);
            if ($isArchived) { $stTagCls = 'tag-archived'; $stLabel = 'Archivado'; }
            elseif ($st === 0) { $stTagCls = 'tag-pending'; $stLabel = 'Pendiente'; }
            elseif ($st === 2) { $stTagCls = 'tag-reviewed'; $stLabel = 'Revisado'; }
            elseif ($st === 1) { $stTagCls = 'tag-approved'; $stLabel = 'Aprobado'; }
            else { $stTagCls = 'tag-vendor'; $stLabel = 'Estado ' . $st; }
        ?>
        <div class="budget-card <?= $cardCls ?>">
            <div class="budget-header">
                <div>
                    <span class="budget-id">#<?= $b->idBudget ?></span>
                    <div class="budget-client"><?= htmlspecialchars($b->client_name ?: 'Sin nombre') ?></div>
                    <div class="budget-phone"><?= htmlspecialchars($b->client_phone ?: $b->client_doc ?: '') ?></div>
                </div>
                <div class="budget-total">$<?= number_format($b->total, 0, ',', '.') ?></div>
            </div>
            <div class="budget-tags">
                <span class="tag <?= $stTagCls ?>"><?= $stLabel ?></span>
                <?php if (!empty($b->vendor_name)): ?>
                <span class="tag tag-vendor"><?= htmlspecialchars($b->vendor_name) ?></span>
                <?php endif; ?>
                <span class="tag tag-date"><?= date('d/m/y H:i', strtotime($b->date)) ?></span>
                <?php if (!empty($b->invoice_id)): ?>
                <span class="tag tag-approved">Fact #<?= $b->invoice_id ?></span>
                <?php endif; ?>
            </div>
            <?php if ($b->comments): ?>
            <div class="budget-comments"><?= htmlspecialchars(mb_substr($b->comments, 0, 140)) ?></div>
            <?php endif; ?>
            <div class="budget-actions">
                <a href="<?= base_url() ?>ventas/ver/<?= $b->idBudget ?>" class="btn btn-view">Ver</a>
                <?php if (!$isArchived && $st !== 1): ?>
                <a href="<?= base_url() ?>ventas/editar/<?= $b->idBudget ?>" class="btn btn-edit">Editar</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (count($budgets) >= 100): ?>
        <p style="text-align:center; padding:14px; font-size:11px; color:var(--text-secondary);">
            Mostrando los 100 mas recientes. Usa la busqueda para filtrar.
        </p>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <h3>Sin resultados</h3>
            <p>No hay presupuestos con este filtro</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab Bar -->
    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Inicio
        </a>
        <a href="<?= base_url() ?>ventas/presupuestos" class="active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            Presupuestos
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>
</body>
</html>
