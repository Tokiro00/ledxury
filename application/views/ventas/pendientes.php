<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Pendientes - Ledxury</title>
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
        .header a { color:rgba(255,255,255,.8); font-size:12px; text-decoration:none; }
        .header .count { background:rgba(255,255,255,.2); padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; }

        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 12px); }

        .alert-bar { background:#FEF3C7; border:1px solid #F59E0B; border-radius:var(--radius-sm); padding:10px 14px; font-size:12px; color:#92400E; font-weight:600; margin-bottom:12px; text-align:center; }

        .budget-card { background:var(--card); border-radius:var(--radius); padding:14px; margin-bottom:10px; box-shadow:var(--shadow); border-left:4px solid var(--warning); }
        .budget-card.approved { border-left-color:var(--success); opacity:.6; }
        .budget-header { display:flex; justify-content:space-between; align-items:flex-start; }
        .budget-id { font-size:10px; color:var(--text-secondary); font-weight:700; }
        .budget-total { font-size:20px; font-weight:800; color:var(--text); }
        .budget-client { font-size:14px; font-weight:700; color:var(--text); margin-top:2px; }
        .budget-phone { font-size:12px; color:var(--text-secondary); }
        .budget-tags { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
        .tag { font-size:10px; padding:3px 8px; border-radius:6px; font-weight:600; }
        .tag-pending { background:#FEF3C7; color:#92400E; }
        .tag-vendor { background:#EFF6FF; color:#1D4ED8; }
        .tag-date { background:#F3F4F6; color:#6B7280; }
        .tag-approved { background:#D1FAE5; color:#065F46; }
        .budget-comments { font-size:11px; color:var(--text-secondary); margin-top:8px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .budget-actions { display:flex; gap:8px; margin-top:12px; }
        .btn { flex:1; padding:10px; border:none; border-radius:var(--radius-sm); font-size:13px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; transition:transform .1s; }
        .btn:active { transform:scale(.97); }
        .btn-approve { background:var(--success); color:#fff; }
        .btn-view { background:#EFF6FF; color:#1D4ED8; }
        .btn-delete { background:#FEE2E2; color:var(--danger); flex:.4; }

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
        <h1>Pendientes</h1>
        <span class="count"><?= count($budgets) ?></span>
    </div>

    <div class="screen-container">
        <?php if (!empty($budgets)): ?>
        <div class="alert-bar"><?= count($budgets) ?> presupuesto<?= count($budgets) > 1 ? 's' : '' ?> por revisar</div>

        <?php foreach ($budgets as $b): ?>
        <div class="budget-card" id="budget_<?= $b->idBudget ?>">
            <div class="budget-header">
                <div>
                    <span class="budget-id">#<?= $b->idBudget ?></span>
                    <div class="budget-client"><?= $b->client_name ?: 'Sin nombre' ?></div>
                    <div class="budget-phone"><?= $b->client_phone ?: $b->client_doc ?: '' ?></div>
                </div>
                <div class="budget-total">$<?= number_format($b->total, 0, ',', '.') ?></div>
            </div>
            <div class="budget-tags">
                <span class="tag tag-pending">Pendiente</span>
                <span class="tag tag-vendor"><?= $b->vendor_name ?: 'Bot' ?></span>
                <span class="tag tag-date"><?= date('d/m H:i', strtotime($b->date)) ?></span>
            </div>
            <?php if ($b->comments): ?>
            <div class="budget-comments"><?= htmlspecialchars(mb_substr($b->comments, 0, 120)) ?></div>
            <?php endif; ?>
            <div class="budget-actions">
                <button class="btn btn-approve" onclick="aprobar(<?= $b->idBudget ?>, this)">Aprobar</button>
                <a href="<?= base_url() ?>ventas/ver/<?= $b->idBudget ?>" class="btn btn-view">Ver</a>
                <button class="btn btn-delete" onclick="eliminar(<?= $b->idBudget ?>, this)" title="Eliminar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"></path></svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php else: ?>
        <div class="empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3>Todo al dia</h3>
            <p>No hay presupuestos pendientes</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Inicio
        </a>
        <a href="<?= base_url() ?>ventas/pendientes" class="active">
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
function aprobar(id, btn) {
    if (!confirm('Aprobar presupuesto #' + id + '?')) return;
    btn.disabled = true;
    btn.textContent = 'Aprobando...';
    $.ajax({
        url: '<?= base_url() ?>ventas/aprobar',
        type: 'POST',
        data: { id: id, '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                var card = document.getElementById('budget_' + id);
                card.classList.add('approved');
                card.style.borderLeftColor = '#10b981';
                btn.textContent = 'Aprobado';
                btn.style.background = '#9ca3af';
                btn.disabled = true;
                var tag = card.querySelector('.tag-pending');
                if (tag) { tag.textContent = 'Aprobado'; tag.className = 'tag tag-approved'; }
            } else { alert(r.error || 'Error'); btn.disabled = false; btn.textContent = 'Aprobar'; }
        },
        error: function() { alert('Error de conexion'); btn.disabled = false; btn.textContent = 'Aprobar'; }
    });
}

function eliminar(id, btn) {
    if (!confirm('Eliminar presupuesto #' + id + '? Esta accion no se puede deshacer.')) return;
    btn.disabled = true;
    $.ajax({
        url: '<?= base_url() ?>ventas/eliminar',
        type: 'POST',
        data: { id: id, '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                var card = document.getElementById('budget_' + id);
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-100%)';
                setTimeout(function(){ card.remove(); }, 300);
            } else { alert(r.error || 'Error'); btn.disabled = false; }
        },
        error: function() { alert('Error de conexion'); btn.disabled = false; }
    });
}
</script>
</body>
</html>
