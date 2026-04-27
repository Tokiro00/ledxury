<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Chat - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fix-webm-duration@1.0.5/fix-webm-duration.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --success:#10b981; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; overflow:hidden; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }

        .header { height:56px; background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.8); font-size:12px; text-decoration:none; }

        /* User List */
        .user-list-view { flex:1; overflow-y:auto; }
        .search-box { padding:10px 12px; background:#fff; border-bottom:1px solid var(--border); }
        .search-box input { width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:20px; font-size:14px; outline:none; background:#f8fafc; }
        .search-box input:focus { border-color:var(--petrol); }
        .user-item { display:flex; align-items:center; padding:12px 16px; border-bottom:1px solid #f3f4f6; cursor:pointer; transition:background .15s; }
        .user-item:active { background:#f0f9ff; }
        .user-avatar { width:44px; height:44px; border-radius:50%; background:var(--petrol); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; flex-shrink:0; position:relative; }
        .user-avatar .online-dot { position:absolute; bottom:1px; right:1px; width:10px; height:10px; border-radius:50%; background:var(--success); border:2px solid #fff; }
        .user-info { flex:1; margin-left:12px; min-width:0; }
        .user-name { font-size:14px; font-weight:600; color:var(--text); }
        .user-last-msg { font-size:12px; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
        .user-meta { display:flex; flex-direction:column; align-items:flex-end; flex-shrink:0; margin-left:8px; }
        .user-time { font-size:10px; color:var(--text-secondary); }
        .unread-badge { background:#ef4444; color:#fff; font-size:10px; font-weight:700; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; margin-top:4px; }

        /* Chat View */
        .chat-view { display:none; flex-direction:column; height:100%; }
        .chat-header { height:56px; background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 12px; flex-shrink:0; gap:10px; }
        .chat-header .back-btn { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; padding:4px 8px; }
        .chat-header .chat-user-avatar { width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }
        .chat-header .chat-user-name { font-size:15px; font-weight:600; }
        .chat-header .chat-user-status { font-size:11px; opacity:.7; }

        .messages-container { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:6px; background:#efeae2; }
        .msg { max-width:75%; padding:8px 12px; border-radius:10px; font-size:13px; line-height:1.4; word-wrap:break-word; position:relative; }
        .msg-in { background:#fff; align-self:flex-start; border-top-left-radius:0; box-shadow:0 1px 1px rgba(0,0,0,.08); }
        .msg-out { background:#d9fdd3; align-self:flex-end; border-top-right-radius:0; box-shadow:0 1px 1px rgba(0,0,0,.08); }
        .msg-time { font-size:10px; color:#667781; margin-top:4px; text-align:right; }
        .msg-date { text-align:center; margin:8px 0; }
        .msg-date span { background:#e1f2fb; color:var(--petrol); font-size:11px; padding:3px 12px; border-radius:6px; font-weight:500; }

        .input-area { padding:8px 12px; background:#f0f2f5; display:flex; align-items:center; gap:6px; border-top:1px solid var(--border); padding-bottom:calc(8px + var(--safe-bottom)); }
        .msg-input { flex:1; padding:10px 14px; border:none; border-radius:20px; font-size:14px; outline:none; background:#fff; min-width:0; }
        .icon-btn { width:38px; height:38px; border-radius:50%; border:none; background:transparent; color:var(--text-secondary); cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .icon-btn:active { background:#e5e7eb; }
        .icon-btn.recording { background:#ef4444; color:#fff; animation:pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.6;} }
        .send-btn { width:42px; height:42px; border-radius:50%; border:none; background:var(--petrol); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .send-btn:active { background:#236470; }

        .empty-chat { flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); text-align:center; padding:40px; }

        .msg img.media-img { max-width:200px; max-height:200px; border-radius:8px; display:block; cursor:pointer; }
        .msg audio, .msg video { max-width:200px; display:block; }
        .msg .file-link { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; background:rgba(0,0,0,.05); border-radius:6px; color:inherit; text-decoration:none; font-size:12px; }
        .upload-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); display:none; align-items:center; justify-content:center; z-index:100; }
        .upload-overlay.active { display:flex; }
        .upload-box { background:#fff; padding:20px 28px; border-radius:12px; font-size:14px; color:var(--text); }
        .rec-bar { display:none; flex:1; align-items:center; gap:8px; background:#fee2e2; padding:8px 12px; border-radius:20px; color:#991b1b; font-size:13px; }
        .rec-bar.active { display:flex; }
        .rec-dot { width:10px; height:10px; border-radius:50%; background:#ef4444; animation:pulse 1s infinite; }
    </style>
</head>
<body>
<div id="app">
    <!-- USER LIST -->
    <div id="userListView">
        <div class="header">
            <a href="<?= base_url() ?>ventas/dashboard">← Inicio</a>
            <h1>Chat Interno</h1>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:12px;"><?= $user->name ?></span>
              <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
              <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
            </div>
        </div>
        <div class="search-box">
            <input type="text" id="searchUser" placeholder="Buscar usuario..." oninput="filterUsers(this.value)">
        </div>
        <div class="user-list-view" id="userList"></div>
    </div>

    <!-- CHAT -->
    <div class="chat-view" id="chatView">
        <div class="chat-header">
            <button class="back-btn" onclick="backToList()">←</button>
            <div class="chat-user-avatar" id="chatAvatar"></div>
            <div>
                <div class="chat-user-name" id="chatUserName"></div>
                <div class="chat-user-status" id="chatUserStatus"></div>
            </div>
        </div>
        <div class="messages-container" id="messagesContainer">
            <div class="empty-chat">Selecciona un usuario para chatear</div>
        </div>
        <div class="input-area">
            <input type="file" id="fileInput" accept="image/*,audio/*,video/*,application/pdf" style="display:none;" onchange="onFileChosen(this)">
            <button class="icon-btn" onclick="document.getElementById('fileInput').click()" title="Adjuntar">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </button>
            <button class="icon-btn" id="micBtn" onclick="toggleRecord()" title="Grabar audio">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v4m0-4a4 4 0 004-4V6a4 4 0 00-8 0v8a4 4 0 004 4z"/></svg>
            </button>
            <input type="text" class="msg-input" id="msgInput" placeholder="Escribe un mensaje..." onkeydown="if(event.key==='Enter')sendMsg()">
            <div class="rec-bar" id="recBar">
                <span class="rec-dot"></span>
                <span id="recTime">0:00</span>
                <button class="icon-btn" onclick="cancelRecord()" style="width:28px;height:28px;color:#991b1b;">&times;</button>
            </div>
            <button class="send-btn" onclick="sendMsg()">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </div>
    </div>
</div>

<div class="upload-overlay" id="uploadOverlay"><div class="upload-box">Subiendo archivo...</div></div>

<script>
var BASE = '<?= base_url() ?>';
var MY_ID = '<?= $user->idUser ?>';
var MY_NAME = '<?= addslashes($user->name) ?>';
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
var currentChatUser = null;
var chatPollTimer = null;
var allUsers = [];

// Load users
function loadUsers() {
    $.post(BASE + 'sisvent/message/allUser', function(html) {
        // Parse the HTML response to extract user data
        var $html = $(html);
        var users = [];
        $html.find('.content-side').each(function() {
            // fallback: load via AJAX
        });
        // Use direct endpoint instead
        $('#userList').html(html);
    });
}

// Actually, let's use the existing controller but render our own UI
$(function() {
    fetchUsers();
    setInterval(fetchUsers, 15000);
});

function fetchUsers() {
    $.ajax({
        url: BASE + 'sisvent/message/allUser',
        type: 'GET',
        success: function(html) {
            // Extract user info from the HTML
            var $el = $('<div>').html(html);
            var users = [];
            $el.find('[data-uid]').each(function() {
                users.push({
                    id: $(this).attr('data-uid'),
                    name: $(this).find('.user-name, .name').first().text().trim(),
                    image: $(this).find('img').attr('src') || '',
                    online: $(this).find('.online, .status-online').length > 0,
                    lastMsg: $(this).find('.last-msg, .message-preview').first().text().trim(),
                    time: $(this).find('.time').first().text().trim(),
                    unread: parseInt($(this).find('.unread, .badge').first().text()) || 0,
                });
            });

            // If parsing failed, just get users list from our controller
            if (users.length === 0) {
                $.getJSON(BASE + 'ventas/chatUsers', function(data) {
                    allUsers = data;
                    renderUsers(data);
                });
                return;
            }
            allUsers = users;
            renderUsers(users);
        },
        error: function() {
            $.getJSON(BASE + 'ventas/chatUsers', function(data) {
                allUsers = data;
                renderUsers(data);
            });
        }
    });
}

function renderUsers(users) {
    var html = '';
    users.forEach(function(u) {
        var initials = u.name.substring(0, 2).toUpperCase();
        var onlineDot = u.online ? '<div class="online-dot"></div>' : '';
        var unreadBadge = u.unread > 0 ? '<div class="unread-badge">' + u.unread + '</div>' : '';
        var nameWeight = u.unread > 0 ? 'font-weight:700' : '';

        html += '<div class="user-item" onclick="openChat(\'' + u.id + '\',\'' + u.name.replace(/'/g, "\\'") + '\',' + u.online + ')">';
        html += '<div class="user-avatar">' + initials + onlineDot + '</div>';
        html += '<div class="user-info"><div class="user-name" style="' + nameWeight + '">' + u.name + '</div>';
        html += '<div class="user-last-msg">' + (u.lastMsg || '') + '</div></div>';
        html += '<div class="user-meta"><span class="user-time">' + (u.time || '') + '</span>' + unreadBadge + '</div>';
        html += '</div>';
    });
    if (!html) html = '<div style="text-align:center; padding:40px; color:#9ca3af;">No hay usuarios</div>';
    $('#userList').html(html);
}

function filterUsers(q) {
    if (!q) { renderUsers(allUsers); return; }
    q = q.toLowerCase();
    renderUsers(allUsers.filter(function(u) { return u.name.toLowerCase().indexOf(q) >= 0; }));
}

function openChat(userId, userName, online) {
    currentChatUser = userId;
    $('#userListView').hide();
    $('#chatView').css('display', 'flex');
    $('#chatAvatar').text(userName.substring(0, 2).toUpperCase());
    $('#chatUserName').text(userName);
    $('#chatUserStatus').text(online ? 'En linea' : 'Desconectado');
    loadMessages(userId);

    if (chatPollTimer) clearInterval(chatPollTimer);
    chatPollTimer = setInterval(function() { loadMessages(userId); }, 5000);
}

function backToList() {
    currentChatUser = null;
    if (chatPollTimer) clearInterval(chatPollTimer);
    $('#chatView').hide();
    $('#userListView').show();
    fetchUsers();
}

function loadMessages(userId) {
    $.ajax({
        url: BASE + 'sisvent/message/getMessage',
        type: 'POST',
        data: { data: userId, image: '', clear: true },
        success: function(html) {
            // Backend returns receiver_msg_container (mine) and sender_msg_container (theirs).
            // Parse rendered media + text out of those blocks.
            var $el = $('<div>').html(html);
            var blocks = $el.find('#receiver_msg_container, #sender_msg_container');
            if (!blocks.length) {
                $('#messagesContainer').html('<div class="empty-chat"><p>No hay mensajes aun.<br>Escribe el primero.</p></div>');
                return;
            }
            var msgHtml = '';
            blocks.each(function() {
                var mine = this.id === 'receiver_msg_container';
                var msgId = $(this).attr('data-msg-id') || '';
                var isRead = $(this).attr('data-is-read') === '1';
                var inner = $(this).find('#receiver_msg, #sender_msg').first();
                var text = inner.find('p#receiver_ptag').first().text();
                var time = inner.find('p#receiver_pdate').first().text();
                // Extract media (img/audio/video/file-link) by re-using the rendered HTML
                var mediaHtml = '';
                inner.children('div').first().each(function() {
                    var $c = $(this);
                    if ($c.find('img,audio,video,a').length) {
                        mediaHtml = $c.html();
                    }
                });
                var cls = mine ? 'msg-out' : 'msg-in';
                msgHtml += '<div class="msg ' + cls + '" data-msg-id="' + msgId + '">';
                if (mediaHtml) msgHtml += '<div style="margin-bottom:4px;">' + mediaHtml + '</div>';
                if (text) msgHtml += '<div>' + escHtml(text) + '</div>';
                // Footer: tiempo + (si es mío) receipt + botón eliminar
                msgHtml += '<div class="msg-footer" style="display:flex;align-items:center;justify-content:flex-end;gap:4px;margin-top:2px;">';
                if (time) msgHtml += '<span class="msg-time" style="font-size:10px;color:#667781;">' + time + '</span>';
                if (mine) {
                    if (isRead) {
                        msgHtml += '<span style="color:#0ea5e9;font-size:11px;font-weight:700;" title="Leído">✓✓</span>';
                    } else {
                        msgHtml += '<span style="color:#667781;font-size:11px;" title="Enviado">✓</span>';
                    }
                    if (msgId) {
                        msgHtml += '<button class="vc-del-btn" data-id="' + msgId + '" title="Eliminar" style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:0 2px;font-size:11px;">🗑</button>';
                    }
                }
                msgHtml += '</div></div>';
            });
            $('#messagesContainer').html(msgHtml || '<div class="empty-chat"><p>No hay mensajes aun</p></div>');
            // Style adjustments for embedded media
            $('#messagesContainer img').css({maxWidth:'200px',maxHeight:'200px',borderRadius:'8px',display:'block',cursor:'pointer'});
            $('#messagesContainer audio, #messagesContainer video').css({maxWidth:'200px',display:'block'});
            scrollChat();
        }
    });
}

function sendMsg(extra) {
    var text = $('#msgInput').val().trim();
    extra = extra || {};
    if (!extra.media_url && !text) return;
    if (!currentChatUser) return;
    $('#msgInput').val('');

    var now = new Date();
    var datetime = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

    var payload = JSON.stringify({
        uniq: currentChatUser,
        message: text,
        datetime: datetime,
        media_url: extra.media_url || null,
        media_type: extra.media_type || null,
        media_name: extra.media_name || null,
        media_size: extra.media_size || null
    });

    // Optimistic UI
    $('#messagesContainer').find('.empty-chat').remove();
    var optHtml = '<div class="msg msg-out">';
    if (extra.media_url) {
        var url = BASE + extra.media_url.replace(/^\//,'');
        if (extra.media_type === 'image') optHtml += '<div style="margin-bottom:4px;"><img src="'+url+'" style="max-width:200px;max-height:200px;border-radius:8px;display:block;"></div>';
        else if (extra.media_type === 'audio') optHtml += '<div style="margin-bottom:4px;"><audio controls preload="metadata" style="max-width:200px;display:block;"><source src="'+url+'" type="audio/webm"></audio></div>';
        else if (extra.media_type === 'video') optHtml += '<div style="margin-bottom:4px;"><video controls src="'+url+'" style="max-width:200px;display:block;border-radius:8px;"></video></div>';
        else optHtml += '<div style="margin-bottom:4px;"><a href="'+url+'" target="_blank" class="file-link">📎 '+escHtml(extra.media_name||'Archivo')+'</a></div>';
    }
    if (text) optHtml += '<div>' + escHtml(text) + '</div>';
    optHtml += '<div class="msg-time">' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + '</div></div>';
    $('#messagesContainer').append(optHtml);
    scrollChat();

    $.post(BASE + 'sisvent/message/sendMessage', { data: payload });
}

// ===== Media upload =====
function uploadFile(file, onDone) {
    if (file.size > 15 * 1024 * 1024) { alert('Archivo excede 15MB'); return; }
    var fd = new FormData();
    fd.append('file', file);
    $('#uploadOverlay').addClass('active');
    $.ajax({
        url: BASE + 'sisvent/message/uploadMedia',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(r) {
            $('#uploadOverlay').removeClass('active');
            if (!r.ok) { alert('Error: ' + (r.error || 'no se pudo subir')); return; }
            onDone(r);
        },
        error: function() { $('#uploadOverlay').removeClass('active'); alert('Error de conexión al subir'); }
    });
}

function onFileChosen(input) {
    if (!input.files || !input.files[0]) return;
    var f = input.files[0];
    input.value = '';
    if (!currentChatUser) { alert('Selecciona un usuario primero'); return; }
    uploadFile(f, function(r) {
        sendMsg({ media_url: r.url, media_type: r.type, media_name: r.name, media_size: r.size });
    });
}

// ===== Audio recording (MediaRecorder) =====
var mediaRecorder = null;
var recordedChunks = [];
var recStart = 0;
var recTimer = null;

function toggleRecord() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    } else {
        startRecord();
    }
}

function startRecord() {
    if (!currentChatUser) { alert('Selecciona un usuario primero'); return; }
    if (!navigator.mediaDevices || !window.MediaRecorder) { alert('Tu navegador no soporta grabación de audio'); return; }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        recordedChunks = [];
        var mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '');
        mediaRecorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
        mediaRecorder.ondataavailable = function(e) { if (e.data.size > 0) recordedChunks.push(e.data); };
        mediaRecorder.onstop = function() {
            stream.getTracks().forEach(function(t){ t.stop(); });
            $('#micBtn').removeClass('recording');
            $('#recBar').removeClass('active');
            clearInterval(recTimer);
            if (!recordedChunks.length) return;
            var blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
            var ext = (mediaRecorder.mimeType || '').indexOf('mp4') >= 0 ? 'm4a' : 'webm';
            var durMs = Date.now() - recStart;
            var sendBlob = function(finalBlob) {
                var file = new File([finalBlob], 'audio_' + Date.now() + '.' + ext, { type: finalBlob.type || blob.type });
                uploadFile(file, function(r) {
                    sendMsg({ media_url: r.url, media_type: 'audio', media_name: r.name, media_size: r.size });
                });
            };
            if (ext === 'webm' && typeof window.ysFixWebmDuration === 'function') {
                try { window.ysFixWebmDuration(blob, durMs, sendBlob); }
                catch (e) { sendBlob(blob); }
            } else {
                sendBlob(blob);
            }
        };
        mediaRecorder.start();
        recStart = Date.now();
        $('#micBtn').addClass('recording');
        $('#recBar').addClass('active');
        recTimer = setInterval(function() {
            var s = Math.floor((Date.now() - recStart) / 1000);
            $('#recTime').text(Math.floor(s/60) + ':' + pad(s%60));
            if (s >= 120) toggleRecord(); // cap 2 min
        }, 250);
    }).catch(function() { alert('No se pudo acceder al micrófono. Revisa permisos.'); });
}

function cancelRecord() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        recordedChunks = [];
        mediaRecorder.stop();
    }
}

function scrollChat() {
    var el = document.getElementById('messagesContainer');
    if (el) el.scrollTop = el.scrollHeight;
}

function escHtml(s) { return $('<div>').text(s || '').html(); }
function pad(n) { return n < 10 ? '0' + n : n; }

// ===== Eliminar mensaje =====
$(document).on('click', '.vc-del-btn', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    if (!id) return;
    if (!confirm('¿Eliminar este mensaje? También se borra el archivo si tiene audio/imagen.')) return;
    $.post(BASE + 'sisvent/message/deleteMessage', { id: id }, function(r) {
        if (r && r.ok) {
            if (currentChatUser) loadMessages(currentChatUser);
        } else {
            alert('Error: ' + ((r && r.error) || 'no se pudo eliminar'));
        }
    }, 'json').fail(function() { alert('Error de conexión'); });
});

// ===== Paste de imagen (capturas de pantalla) =====
$(document).on('paste', '#msgInput', function(e) {
    var ev = e.originalEvent || e;
    if (!ev.clipboardData || !ev.clipboardData.items) return;
    var items = ev.clipboardData.items;
    for (var i = 0; i < items.length; i++) {
        var it = items[i];
        if (it.kind === 'file' && it.type.indexOf('image/') === 0) {
            ev.preventDefault();
            if (!currentChatUser) { alert('Selecciona un usuario primero'); return; }
            var blob = it.getAsFile();
            if (!blob) return;
            var ext = (blob.type.split('/')[1] || 'png').replace(/[^a-z0-9]/gi,'');
            var file = new File([blob], 'screenshot_' + Date.now() + '.' + ext, { type: blob.type });
            uploadFile(file, function(r) {
                sendMsg({ media_url: r.url, media_type: 'image', media_name: r.name, media_size: r.size });
            });
            break;
        }
    }
});
</script>
</body>
</html>
