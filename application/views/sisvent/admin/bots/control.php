<?php defined('BASEPATH') OR exit('No direct script access allowed'); $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Control <?= htmlspecialchars($bot->name) ?> - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
    .ctrl-container { max-width:900px; margin:0 auto; padding:20px; }
    .ctrl-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
    .ctrl-header h2 { font-size:20px; font-weight:700; color:#1a1a2e; }
    .status-card { border-radius:14px; padding:24px; margin-bottom:24px; color:#fff; position:relative; overflow:hidden; }
    .status-card.online { background:linear-gradient(135deg,#22c55e,#16a34a); }
    .status-card.offline { background:linear-gradient(135deg,#ef4444,#b91c1c); }
    .status-card.checking { background:linear-gradient(135deg,#f59e0b,#d97706); }
    .status-content { display:flex; align-items:center; justify-content:space-between; position:relative; z-index:1; }
    .status-dot { width:12px; height:12px; border-radius:50%; background:#fff; display:inline-block; margin-right:10px; animation:blink 2s infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
    .status-text { font-size:20px; font-weight:700; }
    .status-sub { font-size:13px; opacity:.9; margin-top:4px; }
    .actions-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:24px; }
    .action-card { background:#fff; border-radius:14px; padding:24px; border:1px solid #e2e8f0; cursor:pointer; transition:all .2s; text-align:center; }
    .action-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
    .action-card:active { transform:scale(.97); }
    .action-icon { font-size:36px; margin-bottom:10px; }
    .action-title { font-size:15px; font-weight:600; color:#1a1a2e; }
    .action-desc { font-size:12px; color:#64748b; margin-top:4px; }
    .section-panel { display:none; background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:24px; margin-bottom:24px; animation:fadeIn .3s; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    .section-panel.active { display:block; }
    .section-panel h3 { font-size:16px; font-weight:700; margin-bottom:16px; color:#1a1a2e; }
    .btn-ctrl { padding:14px 24px; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:8px; margin-right:8px; margin-bottom:8px; }
    .btn-ctrl:active { transform:scale(.97); }
    .btn-start { background:#22c55e; color:#fff; }
    .btn-stop { background:#ef4444; color:#fff; }
    .btn-restart { background:#f59e0b; color:#fff; }
    .btn-qr { background:#3b82f6; color:#fff; }
    .btn-block { background:#ef4444; color:#fff; }
    .btn-unblock { background:#22c55e; color:#fff; }
    .qr-box { text-align:center; padding:24px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; }
    .qr-box img { max-width:280px; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,.1); }
    .bl-input { width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px; margin-bottom:12px; }
    .bl-input:focus { border-color:#2E7D91; outline:none; box-shadow:0 0 0 3px rgba(46,125,145,.15); }
    .alert-msg { padding:12px 16px; border-radius:10px; margin-bottom:14px; font-size:13px; display:none; }
    .alert-success { background:#d1fae5; color:#065f46; border:1px solid #10b981; }
    .alert-error { background:#fee2e2; color:#991b1b; border:1px solid #ef4444; }
    .spinner { width:24px; height:24px; border:3px solid #e2e8f0; border-top:3px solid #2E7D91; border-radius:50%; animation:spin 1s linear infinite; display:inline-block; }
    @keyframes spin { to{transform:rotate(360deg)} }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/bots/control', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="ctrl-container">

                <div class="ctrl-header">
                    <div>
                        <h2><?= htmlspecialchars($bot->name) ?></h2>
                        <p style="font-size:12px; color:#64748b;">Panel de control del bot</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/bots" style="font-size:13px; color:#2E7D91; text-decoration:none;">&larr; Volver a Bots</a>
                </div>

                <!-- Status -->
                <div class="status-card checking" id="statusCard">
                    <div class="status-content">
                        <div>
                            <div class="status-text"><span class="status-dot"></span> <span id="statusText">Verificando...</span></div>
                            <div class="status-sub" id="statusSub">Consultando estado del bot</div>
                        </div>
                        <div style="font-size:40px; opacity:.3;">&#x1F916;</div>
                    </div>
                </div>

                <!-- Alert -->
                <div class="alert-msg alert-success" id="alertSuccess"></div>
                <div class="alert-msg alert-error" id="alertError"></div>

                <!-- Actions Grid -->
                <div class="actions-grid">
                    <div class="action-card" onclick="togglePanel('controlPanel')">
                        <div class="action-icon">&#x1F7E2;</div>
                        <div class="action-title">Control del Bot</div>
                        <div class="action-desc">Activar o desactivar</div>
                    </div>
                    <div class="action-card" onclick="togglePanel('qrPanel')">
                        <div class="action-icon">&#x1F4F1;</div>
                        <div class="action-title">Codigo QR</div>
                        <div class="action-desc">Conectar WhatsApp</div>
                    </div>
                    <div class="action-card" onclick="togglePanel('restartPanel')">
                        <div class="action-icon">&#x1F504;</div>
                        <div class="action-title">Reiniciar</div>
                        <div class="action-desc">Reiniciar si no responde</div>
                    </div>
                    <div class="action-card" onclick="togglePanel('blacklistPanel')">
                        <div class="action-icon">&#x1F4F5;</div>
                        <div class="action-title">Lista Negra</div>
                        <div class="action-desc">Bloquear numeros</div>
                    </div>
                </div>

                <!-- Control Panel -->
                <div class="section-panel" id="controlPanel">
                    <h3>&#x1F7E2; Control del Bot</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:16px;">Activa o desactiva el bot de WhatsApp</p>
                    <button class="btn-ctrl btn-start" onclick="botAction('start')">&#x1F7E2; Activar Bot</button>
                    <button class="btn-ctrl btn-stop" onclick="botAction('stop')">&#x1F534; Desactivar Bot</button>
                </div>

                <!-- QR Panel -->
                <div class="section-panel" id="qrPanel">
                    <h3>&#x1F4F1; Codigo QR de WhatsApp</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:16px;">Escanea este codigo con tu telefono para conectar</p>
                    <div class="qr-box" id="qrBox">
                        <button class="btn-ctrl btn-qr" onclick="getQR()">Generar Codigo QR</button>
                    </div>
                </div>

                <!-- Restart Panel -->
                <div class="section-panel" id="restartPanel">
                    <h3>&#x1F504; Reiniciar Bot</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:16px;">Si el bot no responde, reinicialo aqui. Las conversaciones activas se interrumpiran temporalmente.</p>
                    <div style="text-align:center;">
                        <button class="btn-ctrl btn-restart" onclick="botAction('restart')" style="font-size:16px; padding:18px 32px;">&#x1F504; Reiniciar Bot</button>
                    </div>
                </div>

                <!-- Blacklist Panel -->
                <div class="section-panel" id="blacklistPanel">
                    <h3>&#x1F4F5; Lista Negra (Blacklist)</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:16px;">Bloquea numeros para que no puedan contactar tu bot. Formato: codigo pais + numero sin espacios (ej: 573001234567)</p>
                    <input type="text" class="bl-input" id="blacklistInput" placeholder="573001234567,573009876543 (separados por coma)">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn-ctrl btn-block" onclick="blacklistAction('add')">&#x2795; Agregar a Blacklist</button>
                        <button class="btn-ctrl btn-unblock" onclick="blacklistAction('remove')">&#x2796; Quitar de Blacklist</button>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
var BASE = '<?= base_url() ?>';
var BOT_ID = <?= $bot->id ?>;
var CSRF = { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' };

// Check status on load
$(function() { checkStatus(); });

function checkStatus() {
    $.getJSON(BASE + 'sisvent/admin/bots/botStatus/' + BOT_ID, function(r) {
        var card = $('#statusCard');
        if (r.http_code >= 200 && r.http_code < 300 && r.body) {
            var status = r.body.status || r.body.state || 'unknown';
            if (status === 'open' || status === 'connected' || status === 'online') {
                card.removeClass('checking offline').addClass('online');
                $('#statusText').text('En linea');
                $('#statusSub').text('Bot operativo y respondiendo');
            } else {
                card.removeClass('checking online').addClass('offline');
                $('#statusText').text('Desconectado');
                $('#statusSub').text('Estado: ' + status);
            }
        } else {
            card.removeClass('checking online').addClass('offline');
            $('#statusText').text('Sin respuesta');
            $('#statusSub').text('No se pudo verificar el estado');
        }
    }).fail(function() {
        $('#statusCard').removeClass('checking online').addClass('offline');
        $('#statusText').text('Error de conexion');
    });
}

function togglePanel(id) {
    var panel = document.getElementById(id);
    var isActive = panel.classList.contains('active');
    document.querySelectorAll('.section-panel').forEach(function(p) { p.classList.remove('active'); });
    if (!isActive) panel.classList.add('active');
}

function showAlert(type, msg) {
    var el = type === 'success' ? '#alertSuccess' : '#alertError';
    $(el).text(msg).fadeIn();
    setTimeout(function() { $(el).fadeOut(); }, 5000);
}

function botAction(action) {
    var endpoints = { start: 'botStart', stop: 'botStop', restart: 'botRestart' };
    var labels = { start: 'Activando...', stop: 'Desactivando...', restart: 'Reiniciando...' };

    showAlert('success', labels[action]);

    $.ajax({
        url: BASE + 'sisvent/admin/bots/' + endpoints[action] + '/' + BOT_ID,
        type: 'POST',
        data: CSRF,
        dataType: 'json',
        success: function(r) {
            if (r.http_code >= 200 && r.http_code < 300) {
                showAlert('success', action === 'start' ? 'Bot activado' : action === 'stop' ? 'Bot desactivado' : 'Bot reiniciado');
                setTimeout(checkStatus, 3000);
            } else {
                showAlert('error', 'Error: ' + JSON.stringify(r.body));
            }
        },
        error: function() { showAlert('error', 'Error de conexion'); }
    });
}

function getQR() {
    $('#qrBox').html('<div style="text-align:center;"><div class="spinner"></div><p style="margin-top:12px; color:#64748b;">Generando codigo QR...</p></div>');

    $.getJSON(BASE + 'sisvent/admin/bots/botQR/' + BOT_ID, function(r) {
        if (r.http_code >= 200 && r.http_code < 300 && r.body) {
            var qrData = r.body.qr || r.body.data || r.body;
            if (typeof qrData === 'string' && qrData.startsWith('data:image')) {
                $('#qrBox').html('<img src="' + qrData + '" class="qr-image" alt="QR Code"><p style="margin-top:12px; color:#64748b; font-size:12px;">Escanea con WhatsApp</p>');
            } else if (typeof qrData === 'string') {
                $('#qrBox').html('<img src="data:image/png;base64,' + qrData + '" class="qr-image" alt="QR Code"><p style="margin-top:12px; color:#64748b; font-size:12px;">Escanea con WhatsApp</p>');
            } else {
                $('#qrBox').html('<p style="color:#64748b;">El bot ya esta conectado. No necesita QR.</p><button class="btn-ctrl btn-qr" onclick="getQR()">Reintentar</button>');
            }
        } else {
            $('#qrBox').html('<p style="color:#ef4444;">No se pudo generar el QR</p><button class="btn-ctrl btn-qr" onclick="getQR()">Reintentar</button>');
        }
    }).fail(function() {
        $('#qrBox').html('<p style="color:#ef4444;">Error de conexion</p><button class="btn-ctrl btn-qr" onclick="getQR()">Reintentar</button>');
    });
}

function blacklistAction(action) {
    var numbers = $('#blacklistInput').val().trim();
    if (!numbers) { showAlert('error', 'Ingresa al menos un numero'); return; }

    var endpoint = action === 'add' ? 'botBlacklistAdd' : 'botBlacklistRemove';
    var label = action === 'add' ? 'Agregando...' : 'Eliminando...';
    showAlert('success', label);

    $.ajax({
        url: BASE + 'sisvent/admin/bots/' + endpoint + '/' + BOT_ID,
        type: 'POST',
        data: $.extend({ numbers: numbers }, CSRF),
        dataType: 'json',
        success: function(r) {
            if (r.http_code >= 200 && r.http_code < 300) {
                showAlert('success', action === 'add' ? 'Numeros bloqueados' : 'Numeros desbloqueados');
                $('#blacklistInput').val('');
            } else {
                showAlert('error', 'Error: ' + JSON.stringify(r.body));
            }
        },
        error: function() { showAlert('error', 'Error de conexion'); }
    });
}
</script>
</body>
</html>
