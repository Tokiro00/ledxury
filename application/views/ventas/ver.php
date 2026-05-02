<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>#<?= $budget->idBudget ?> - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --navy:#1B365D; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --tab-height:64px; --header-height:56px; --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.8); font-size:12px; text-decoration:none; }

        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(170px + var(--safe-bottom)); }

        .total-card { background:var(--card); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow); text-align:center; margin-bottom:12px; }
        .status-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; }
        .total-amount { font-size:32px; font-weight:800; color:var(--text); margin-top:8px; }
        .total-date { font-size:12px; color:var(--text-secondary); margin-top:4px; }

        .info-card { background:var(--card); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow); margin-bottom:12px; }
        .info-card-title { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-bottom:10px; }
        .info-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; }
        .info-row:last-child { border:none; }
        .info-label { font-size:12px; color:var(--text-secondary); }
        .info-value { font-size:13px; color:var(--text); font-weight:600; text-align:right; max-width:60%; word-break:break-word; }
        .info-value a { color:#1D4ED8; text-decoration:none; }

        .product-item { padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .product-item:last-child { border:none; }
        .product-code { font-size:13px; font-weight:700; color:var(--navy); }
        .product-detail { font-size:11px; color:var(--text-secondary); margin-top:2px; }

        .comments-text { font-size:12px; color:var(--text-secondary); line-height:1.5; white-space:pre-wrap; word-break:break-word; }

        .fixed-bottom { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); padding:12px 16px; padding-bottom:calc(12px + var(--safe-bottom)); display:flex; gap:10px; z-index:10; }
        .btn { flex:1; padding:14px; border:none; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; display:block; transition:transform .1s; }
        .btn:active { transform:scale(.97); }
        .btn-approve { background:var(--success); color:#fff; }
        .btn-edit { background:#3B82F6; color:#fff; }
        .btn-back { background:#F3F4F6; color:#374151; }
    </style>
</head>
<body>
<div id="app">
    <div class="header">
        <a href="<?= base_url() ?>ventas/pendientes">← Pendientes</a>
        <h1>Presupuesto #<?= $budget->idBudget ?></h1>
        <div style="display:flex;align-items:center;gap:8px;">
          <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
          <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
        </div>
    </div>

    <div class="screen-container">
        <!-- Total & Status -->
        <div class="total-card">
            <?php
                $states = array(0 => array('Pendiente','#FEF3C7','#92400E'), 1 => array('Parcial','#FED7AA','#9A3412'), 2 => array('Pagado','#D1FAE5','#065F46'), 3 => array('Liquidado','#DBEAFE','#1D4ED8'));
                $st = isset($states[$budget->state]) ? $states[$budget->state] : $states[0];
            ?>
            <span class="status-badge" style="background:<?= $st[1] ?>; color:<?= $st[2] ?>;"><?= $st[0] ?></span>
            <div class="total-amount">$<?= number_format($budget->total, 0, ',', '.') ?></div>
            <div class="total-date"><?= date('d/m/Y H:i', strtotime($budget->date)) ?></div>
        </div>

        <!-- Cliente -->
        <div class="info-card">
            <div class="info-card-title">Cliente</div>
            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value"><?= $client ? $client->name : '-' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Documento</span>
                <span class="info-value"><?= $client ? $client->idNum : '-' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Celular</span>
                <span class="info-value">
                    <?php if ($client && $client->cellphone): ?>
                    <a href="tel:<?= $client->cellphone ?>"><?= $client->cellphone ?></a>
                    <?php else: ?>-<?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Direccion</span>
                <span class="info-value"><?= $client ? $client->address : '-' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Ciudad</span>
                <span class="info-value"><?= $client ? ($client->city . ($client->state ? ', ' . $client->state : '')) : '-' ?></span>
            </div>
        </div>

        <!-- Productos -->
        <div class="info-card">
            <div class="info-card-title">Productos</div>
            <?php if (!empty($details)): foreach ($details as $d): ?>
            <div class="product-item">
                <div class="product-code"><?= $d->productId ?></div>
                <div class="product-detail">Cant: <?= $d->quantity ?> &middot; Unit: $<?= number_format($d->unit, 0, ',', '.') ?> &middot; Total: $<?= number_format($d->total, 0, ',', '.') ?></div>
            </div>
            <?php endforeach; else: ?>
            <p style="font-size:12px; color:var(--text-secondary); padding:8px 0;">Sin detalle</p>
            <?php endif; ?>
        </div>

        <!-- Observaciones -->
        <?php if ($budget->comments): ?>
        <div class="info-card">
            <div class="info-card-title">Observaciones</div>
            <div class="comments-text"><?= htmlspecialchars($budget->comments) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="fixed-bottom" style="flex-wrap:wrap;">
        <a href="<?= base_url() ?>ventas/pendientes" class="btn btn-back" style="flex:1;">Volver</a>
        <a href="<?= base_url() ?>ventas/editar/<?= $budget->idBudget ?>" class="btn btn-edit" style="flex:1;">Editar</a>
        <?php if ($budget->state == 0): ?>
        <button class="btn btn-review" style="flex:1; background:#6366F1; color:#fff;" onclick="revisar(<?= $budget->idBudget ?>)">Revisado</button>
        <?php endif; ?>
        <div style="width:100%; display:flex; gap:10px; margin-top:8px;">
            <button class="btn" style="flex:1; background:#F3F4F6; color:#6B7280; font-size:12px;" onclick="archivar(<?= $budget->idBudget ?>)">Archivar</button>
            <button class="btn" style="flex:1; background:#FEE2E2; color:#991B1B; font-size:12px;" onclick="eliminar(<?= $budget->idBudget ?>)">Eliminar</button>
        </div>
    </div>
</div>

<script>
var CSRF = { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' };

function revisar(id) {
    $.post('<?= base_url() ?>ventas/revisar', $.extend({id: id}, CSRF), function(r) {
        if (r.success) { alert('Marcado como revisado'); location.href = '<?= base_url() ?>ventas/pendientes'; }
        else { alert(r.error || 'Error'); }
    }, 'json').fail(function() { alert('Error de conexion'); });
}

function aprobar(id) {
    if (!confirm('Aprobar presupuesto #' + id + '?')) return;
    $.post('<?= base_url() ?>ventas/aprobar', $.extend({id: id}, CSRF), function(r) {
        if (r.success) { alert('Aprobado'); location.href = '<?= base_url() ?>ventas/pendientes'; }
        else { alert(r.error || 'Error'); }
    }, 'json').fail(function() { alert('Error de conexion'); });
}

function archivar(id) {
    if (!confirm('Archivar presupuesto #' + id + '? Se marcara como archivado.')) return;
    $.post('<?= base_url() ?>ventas/archivar', $.extend({id: id}, CSRF), function(r) {
        if (r.success) { alert('Archivado'); location.href = '<?= base_url() ?>ventas/pendientes'; }
        else { alert(r.error || 'Error'); }
    }, 'json').fail(function() { alert('Error de conexion'); });
}

function eliminar(id) {
    if (!confirm('ELIMINAR presupuesto #' + id + '? Esta accion no se puede deshacer.')) return;
    $.post('<?= base_url() ?>ventas/eliminar', $.extend({id: id}, CSRF), function(r) {
        if (r.success) { alert('Eliminado'); location.href = '<?= base_url() ?>ventas/pendientes'; }
        else { alert(r.error || 'Error'); }
    }, 'json').fail(function() { alert('Error de conexion'); });
}
</script>
</body>
</html>
