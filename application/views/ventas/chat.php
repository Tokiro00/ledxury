<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Chat - Ledxury</title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>favicon.ico"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
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

        .input-area { padding:8px 12px; background:#f0f2f5; display:flex; align-items:center; gap:8px; border-top:1px solid var(--border); padding-bottom:calc(8px + var(--safe-bottom)); }
        .msg-input { flex:1; padding:10px 14px; border:none; border-radius:20px; font-size:14px; outline:none; background:#fff; }
        .send-btn { width:42px; height:42px; border-radius:50%; border:none; background:var(--petrol); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .send-btn:active { background:#236470; }

        .empty-chat { flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); text-align:center; padding:40px; }
    </style>
</head>
<body>
<div id="app">
    <!-- USER LIST -->
    <div id="userListView">
        <div class="header">
            <a href="<?= base_url() ?>ventas/dashboard">← Inicio</a>
            <h1>Chat Interno</h1>
            <span style="font-size:12px;"><?= $user->name ?></span>
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
            <input type="text" class="msg-input" id="msgInput" placeholder="Escribe un mensaje..." onkeydown="if(event.key==='Enter')sendMsg()">
            <button class="send-btn" onclick="sendMsg()">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </div>
    </div>
</div>

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
            // Parse messages from HTML
            var $el = $('<div>').html(html);
            var messages = [];

            $el.find('.message-item, .msg-item, [class*="message"]').each(function() {
                var isMine = $(this).hasClass('sent') || $(this).hasClass('outgoing') || $(this).find('.outgoing').length > 0;
                messages.push({
                    text: $(this).find('.msg-text, .message-text, p').first().text().trim(),
                    time: $(this).find('.msg-time, .time, small').first().text().trim(),
                    mine: isMine
                });
            });

            // If parsing fails, try raw text extraction
            if (messages.length === 0) {
                var rawText = $el.text().trim();
                if (rawText && rawText !== 'No hay mensajes') {
                    // Fallback: show raw HTML
                    var fallbackHtml = '';
                    $el.find('p, div').each(function() {
                        var t = $(this).text().trim();
                        if (t && t.length > 0 && t.length < 500) {
                            fallbackHtml += '<div class="msg msg-in"><div>' + t + '</div></div>';
                        }
                    });
                    if (fallbackHtml) {
                        $('#messagesContainer').html(fallbackHtml);
                        scrollChat();
                        return;
                    }
                }
                $('#messagesContainer').html('<div class="empty-chat"><p>No hay mensajes aun.<br>Escribe el primero.</p></div>');
                return;
            }

            var msgHtml = '';
            messages.forEach(function(m) {
                if (!m.text) return;
                var cls = m.mine ? 'msg-out' : 'msg-in';
                msgHtml += '<div class="msg ' + cls + '"><div>' + escHtml(m.text) + '</div>';
                if (m.time) msgHtml += '<div class="msg-time">' + m.time + '</div>';
                msgHtml += '</div>';
            });
            $('#messagesContainer').html(msgHtml || '<div class="empty-chat"><p>No hay mensajes aun</p></div>');
            scrollChat();
        }
    });
}

function sendMsg() {
    var text = $('#msgInput').val().trim();
    if (!text || !currentChatUser) return;
    $('#msgInput').val('');

    var now = new Date();
    var datetime = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

    var payload = JSON.stringify({ uniq: currentChatUser, message: text, datetime: datetime });

    // Add message to UI immediately
    $('#messagesContainer').find('.empty-chat').remove();
    $('#messagesContainer').append('<div class="msg msg-out"><div>' + escHtml(text) + '</div><div class="msg-time">' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + '</div></div>');
    scrollChat();

    $.post(BASE + 'sisvent/message/sendMessage', { data: payload });
}

function scrollChat() {
    var el = document.getElementById('messagesContainer');
    if (el) el.scrollTop = el.scrollHeight;
}

function escHtml(s) { return $('<div>').text(s || '').html(); }
function pad(n) { return n < 10 ? '0' + n : n; }
</script>
</body>
</html>
