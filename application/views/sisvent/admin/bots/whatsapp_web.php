<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>WhatsApp - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
    .wa-container { display:flex; height:calc(100vh - 64px); overflow:hidden; }
    .wa-sidebar { width:360px; min-width:360px; border-right:1px solid #e5e7eb; display:flex; flex-direction:column; background:#fff; }
    .wa-chat { flex:1; display:flex; flex-direction:column; background:#efeae2; }
    .wa-chat-bg { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cpath d='M0 0h300v300H0z' fill='%23efeae2'/%3E%3Cpath d='M150 50c20 0 35 15 35 35s-15 35-35 35-35-15-35-35 15-35 35-35' fill='%23e4ddd6' opacity='.3'/%3E%3C/svg%3E"); }
    .wa-messages { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:4px; }
    .wa-msg { max-width:65%; padding:8px 12px; border-radius:8px; font-size:13px; line-height:1.4; word-wrap:break-word; position:relative; }
    .wa-msg-in { background:#fff; align-self:flex-start; border-top-left-radius:0; box-shadow:0 1px 1px rgba(0,0,0,.1); }
    .wa-msg-out { background:#d9fdd3; align-self:flex-end; border-top-right-radius:0; box-shadow:0 1px 1px rgba(0,0,0,.1); }
    .wa-msg-time { font-size:10px; color:#667781; margin-top:4px; text-align:right; }
    .wa-msg-sender { font-size:11px; color:#1B365D; font-weight:600; margin-bottom:2px; }
    .wa-conv { padding:12px 16px; display:flex; align-items:center; cursor:pointer; border-bottom:1px solid #f3f4f6; transition:background .15s; }
    .wa-conv:hover, .wa-conv.active { background:#f0f2f5; }
    .wa-avatar { width:48px; height:48px; border-radius:50%; background:#1B365D; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px; flex-shrink:0; }
    .wa-conv-info { flex:1; margin-left:12px; min-width:0; }
    .wa-conv-name { font-size:14px; font-weight:600; color:#111b21; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wa-conv-last { font-size:12px; color:#667781; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
    .wa-conv-meta { display:flex; flex-direction:column; align-items:flex-end; flex-shrink:0; margin-left:8px; }
    .wa-conv-time { font-size:11px; color:#667781; }
    .wa-badge { background:#25D366; color:#fff; font-size:10px; font-weight:700; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; margin-top:4px; }
    .wa-input-area { padding:10px 16px; background:#f0f2f5; display:flex; align-items:center; gap:8px; border-top:1px solid #e5e7eb; }
    .wa-input { flex:1; padding:10px 14px; border:none; border-radius:8px; font-size:14px; outline:none; background:#fff; }
    .wa-send-btn { background:#1B365D; color:#fff; border:none; border-radius:50%; width:42px; height:42px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
    .wa-send-btn:hover { background:#25D366; }
    .wa-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#8696a0; }
    .wa-search { padding:8px 12px; background:#f0f2f5; border-bottom:1px solid #e5e7eb; }
    .wa-search input { width:100%; padding:8px 12px; border:none; border-radius:8px; font-size:13px; background:#fff; outline:none; }
    .wa-header { padding:12px 16px; background:#1B365D; color:#fff; display:flex; align-items:center; justify-content:space-between; }
    .wa-chat-header { padding:10px 16px; background:#fff; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; }
    .wa-date-sep { text-align:center; margin:12px 0; }
    .wa-date-sep span { background:#e1f2fb; color:#1B365D; font-size:11px; padding:4px 12px; border-radius:6px; font-weight:500; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/bots/whatsapp_web', 'role' => $role)); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <div class="wa-container">
            <!-- SIDEBAR: Conversations -->
            <div class="wa-sidebar">
                <div class="wa-header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                        <span class="font-semibold text-sm">WhatsApp</span>
                    </div>
                    <select id="botSelector" class="text-xs bg-white text-gray-700 px-2 py-1 rounded" onchange="switchBot(this.value)">
                        <?php foreach ($bots as $b): ?>
                        <option value="<?= $b->id ?>" <?= ($selectedBot && $selectedBot->id == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wa-search">
                    <input type="text" id="searchConv" placeholder="Buscar conversacion..." oninput="searchConversations(this.value)">
                </div>

                <!-- Tag Filters -->
                <div id="tagFilters" style="padding:6px 10px; display:flex; flex-wrap:wrap; gap:4px; border-bottom:1px solid #e5e7eb; background:#fafafa;">
                    <button class="wa-tag-btn active" data-tag="all" onclick="filterByTag('all')" style="font-size:11px; padding:3px 8px; border-radius:12px; border:1px solid #d1d5db; background:#fff; cursor:pointer; white-space:nowrap;">Todos <span class="wa-tag-count"></span></button>
                    <?php foreach ($tags as $t): ?>
                    <button class="wa-tag-btn" data-tag="<?= $t->id ?>" onclick="filterByTag(<?= $t->id ?>)" style="font-size:11px; padding:3px 8px; border-radius:12px; border:1px solid <?= $t->color ?>40; background:<?= $t->color ?>15; color:<?= $t->color ?>; cursor:pointer; white-space:nowrap;"><?= $t->name ?> <span class="wa-tag-count" id="tagCount_<?= $t->id ?>"></span></button>
                    <?php endforeach; ?>
                </div>

                <div id="convList" style="flex:1; overflow-y:auto;"></div>

                <div style="padding:8px 12px; border-top:1px solid #e5e7eb;">
                    <button onclick="newChatModal()" class="w-full py-2 text-xs font-medium text-white rounded-lg" style="background:#25D366;">+ Nueva Conversacion</button>
                </div>
            </div>

            <!-- CHAT Area -->
            <div class="wa-chat" id="chatArea">
                <div class="wa-empty" id="emptyChat">
                    <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <p class="text-lg font-medium">Ledxury WhatsApp</p>
                    <p class="text-sm mt-1">Selecciona una conversacion para ver los mensajes</p>
                </div>

                <div id="activeChat" style="display:none; flex-direction:column; height:100%;">
                    <div class="wa-chat-header" id="chatHeader" style="justify-content:space-between;"></div>
                    <div class="wa-messages wa-chat-bg" id="messagesContainer"></div>
                    <div class="wa-input-area">
                        <input type="text" id="msgInput" class="wa-input" placeholder="Escribe un mensaje..." onkeydown="if(event.key==='Enter')sendMessage()">
                        <button class="wa-send-btn" onclick="sendMessage()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div id="newChatModalBg" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:999; display:none; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:24px; width:400px; max-width:90%;">
        <h3 class="text-sm font-bold text-gray-700 mb-4">Nueva Conversacion</h3>
        <input type="text" id="newChatPhone" class="w-full px-3 py-2 text-sm border rounded-lg mb-3" placeholder="Numero (ej: 3001234567)">
        <input type="text" id="newChatName" class="w-full px-3 py-2 text-sm border rounded-lg mb-4" placeholder="Nombre (opcional)">
        <div class="flex gap-2 justify-end">
            <button onclick="closeNewChatModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="createNewChat()" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#25D366;">Iniciar Chat</button>
        </div>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
var BASE = '<?= base_url() ?>';
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
var currentBotId = <?= $selectedBot ? $selectedBot->id : 0 ?>;
var currentConvId = null;
var currentConvTagId = null;
var lastMsgId = 0;
var pollTimer = null;
var currentTagFilter = 'all';
var TAGS = <?= json_encode($tags) ?>;

// Load conversations
function loadConversations(search) {
    var url = BASE + 'sisvent/admin/bots/whatsappConversations/' + currentBotId + '?_=' + Date.now();
    if (search) url += '&q=' + encodeURIComponent(search);
    if (currentTagFilter && currentTagFilter !== 'all') url += '&tag=' + currentTagFilter;
    $.getJSON(url, function(r) {
        var html = '';
        if (r.conversations && r.conversations.length) {
            r.conversations.forEach(function(c) {
                var initials = (c.client_name || c.phone).substring(0,2).toUpperCase();
                var time = c.last_message_at ? formatTime(c.last_message_at) : '';
                var lastMsg = c.last_message || '';
                if (lastMsg.length > 35) lastMsg = lastMsg.substring(0,35) + '...';
                var prefix = c.last_direction === 'out' ? '<span style="color:#667781">Tu: </span>' : '';
                var unreadBadge = c.unread_count > 0 ? '<div class="wa-badge">' + c.unread_count + '</div>' : '';
                var activeClass = (currentConvId == c.id) ? ' active' : '';
                var nameWeight = c.unread_count > 0 ? 'font-weight:700' : '';

                // Tag badge
                var tagBadge = '';
                if (c.tag_name && c.tag_color) {
                    tagBadge = '<span style="font-size:9px; padding:1px 6px; border-radius:8px; background:' + c.tag_color + '20; color:' + c.tag_color + '; font-weight:600; white-space:nowrap;">' + c.tag_name + '</span>';
                }

                html += '<div class="wa-conv' + activeClass + '" onclick="openConversation(' + c.id + ',\'' + escHtml(c.client_name || c.phone) + '\',\'' + c.phone + '\',' + (c.tag_id||1) + ')">';
                html += '  <div class="wa-avatar">' + initials + '</div>';
                html += '  <div class="wa-conv-info">';
                html += '    <div class="wa-conv-name" style="' + nameWeight + '">' + escHtml(c.client_name || c.phone) + ' ' + tagBadge + '</div>';
                html += '    <div class="wa-conv-last">' + prefix + escHtml(lastMsg) + '</div>';
                html += '  </div>';
                html += '  <div class="wa-conv-meta">';
                html += '    <span class="wa-conv-time">' + time + '</span>';
                html += unreadBadge;
                html += '  </div>';
                html += '</div>';
            });
        } else {
            html = '<div style="text-align:center; padding:40px; color:#8696a0;"><p>Sin conversaciones</p></div>';
        }
        $('#convList').html(html);

        // Update tag counts
        if (r.tag_counts) {
            var total = 0;
            TAGS.forEach(function(t) { $('#tagCount_' + t.id).text(''); });
            r.tag_counts.forEach(function(tc) {
                $('#tagCount_' + tc.tag_id).text('(' + tc.total + ')');
                total += parseInt(tc.total);
            });
        }
    });
}

// Open conversation
function openConversation(convId, name, phone, tagId) {
    currentConvId = convId;
    currentConvTagId = tagId || 1;
    $('#emptyChat').hide();
    $('#activeChat').css('display','flex');

    // Build tag selector
    var tagOpts = '';
    TAGS.forEach(function(t) {
        var sel = (t.id == currentConvTagId) ? ' selected' : '';
        tagOpts += '<option value="' + t.id + '"' + sel + ' style="color:' + t.color + '">' + t.name + '</option>';
    });

    $('#chatHeader').html(
        '<div style="display:flex;align-items:center;">' +
        '  <div class="wa-avatar" style="width:40px;height:40px;font-size:14px;">' + name.substring(0,2).toUpperCase() + '</div>' +
        '  <div style="margin-left:12px;"><div style="font-size:14px;font-weight:600;color:#111b21;">' + escHtml(name) + '</div>' +
        '  <div style="font-size:12px;color:#667781;">' + phone + '</div></div>' +
        '</div>' +
        '<select onchange="changeTag(' + convId + ', this.value)" style="font-size:11px; padding:4px 8px; border:1px solid #d1d5db; border-radius:8px; background:#fff; cursor:pointer;">' + tagOpts + '</select>'
    );
    loadMessages(convId);
    loadConversations($('#searchConv').val());

    // Start polling
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(function() { pollNewMessages(); }, 10000);
}

// Load messages
function loadMessages(convId) {
    $.getJSON(BASE + 'sisvent/admin/bots/whatsappMessages/' + convId, function(r) {
        var html = '';
        var lastDate = '';
        if (r.messages) {
            r.messages.forEach(function(m) {
                var msgDate = m.created_at ? m.created_at.substring(0,10) : '';
                if (msgDate !== lastDate) {
                    lastDate = msgDate;
                    html += '<div class="wa-date-sep"><span>' + formatDate(msgDate) + '</span></div>';
                }
                var cls = (m.direction === 'incoming') ? 'wa-msg-in' : 'wa-msg-out';
                var time = m.created_at ? m.created_at.substring(11,16) : '';
                var sender = '';
                if (m.direction === 'outgoing' && m.sent_by && m.sent_by !== 'bot') {
                    sender = '<div class="wa-msg-sender">' + m.sent_by + '</div>';
                }
                html += '<div class="wa-msg ' + cls + '" data-id="' + m.id + '">';
                html += sender;
                html += '<div>' + formatMsgContent(m.content, m.media_url) + '</div>';
                html += '<div class="wa-msg-time">' + time + '</div>';
                html += '</div>';
                lastMsgId = Math.max(lastMsgId, parseInt(m.id));
            });
        }
        $('#messagesContainer').html(html);
        scrollToBottom();
    });
}

// Send message
function sendMessage() {
    var content = $('#msgInput').val().trim();
    if (!content || !currentConvId) return;

    $('#msgInput').val('');

    $.ajax({
        url: BASE + 'sisvent/admin/bots/whatsappSend',
        type: 'POST',
        data: { conversation_id: currentConvId, content: content, [CSRF_NAME]: CSRF_HASH },
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                loadMessages(currentConvId);
                loadConversations($('#searchConv').val());
            } else {
                alert('Error: ' + (r.error || 'No se pudo enviar'));
            }
        }
    });
}

// Poll for new messages
function pollNewMessages() {
    if (!currentConvId) return;
    $.getJSON(BASE + 'sisvent/admin/bots/whatsappPoll/' + currentConvId + '/' + lastMsgId, function(r) {
        if (r.messages && r.messages.length > 0) {
            r.messages.forEach(function(m) {
                var cls = (m.direction === 'incoming') ? 'wa-msg-in' : 'wa-msg-out';
                var time = m.created_at ? m.created_at.substring(11,16) : '';
                var html = '<div class="wa-msg ' + cls + '" data-id="' + m.id + '">';
                html += '<div>' + formatMsgContent(m.content, m.media_url) + '</div>';
                html += '<div class="wa-msg-time">' + time + '</div>';
                html += '</div>';
                $('#messagesContainer').append(html);
                lastMsgId = Math.max(lastMsgId, parseInt(m.id));
            });
            scrollToBottom();
            loadConversations($('#searchConv').val());
        }
    });
}

// Tag filter
function filterByTag(tagId) {
    currentTagFilter = tagId;
    $('.wa-tag-btn').removeClass('active').css({'font-weight':'normal','box-shadow':'none'});
    $('.wa-tag-btn[data-tag="' + tagId + '"]').addClass('active').css({'font-weight':'700','box-shadow':'0 0 0 2px rgba(0,0,0,.15)'});
    loadConversations($('#searchConv').val());
}

// Change tag
function changeTag(convId, tagId) {
    $.ajax({
        url: BASE + 'sisvent/admin/bots/whatsappSetTag',
        type: 'POST',
        data: { conversation_id: convId, tag_id: tagId, [CSRF_NAME]: CSRF_HASH },
        dataType: 'json',
        success: function(r) {
            if (r.success) { currentConvTagId = tagId; loadConversations($('#searchConv').val()); }
        }
    });
}

// Helpers
function switchBot(id) { currentBotId = id; currentConvId = null; currentTagFilter = 'all'; loadConversations(''); $('#emptyChat').show(); $('#activeChat').hide(); filterByTag('all'); }
function searchConversations(q) { loadConversations(q); }
function scrollToBottom() { var el = document.getElementById('messagesContainer'); if(el) el.scrollTop = el.scrollHeight; }
function escHtml(s) { return $('<div>').text(s || '').html(); }
function formatMsgContent(text, mediaUrl) {
    var html = '';

    // Mostrar imagen/media si hay URL
    if (mediaUrl) {
        var isImage = /\.(jpg|jpeg|png|gif|webp)/i.test(mediaUrl) || mediaUrl.indexOf('image') > -1 || mediaUrl.indexOf('getMedia') > -1;
        var isAudio = /\.(ogg|oga|mp3|wav)/i.test(mediaUrl) || mediaUrl.indexOf('audio') > -1;

        if (isImage) {
            html += '<div style="margin-bottom:6px;"><img src="' + mediaUrl + '" style="max-width:280px; max-height:300px; border-radius:6px; cursor:pointer;" onclick="window.open(this.src,\'_blank\')" onerror="this.style.display=\'none\'" /></div>';
        } else if (isAudio) {
            html += '<div style="margin-bottom:6px;"><audio controls style="max-width:250px;"><source src="' + mediaUrl + '">Audio</audio></div>';
        } else {
            html += '<div style="margin-bottom:6px;"><a href="' + mediaUrl + '" target="_blank" style="color:#027eb5; font-size:12px;">📎 Ver archivo</a></div>';
        }
    }

    if (!text) return html || '';
    text = escHtml(text);
    // No mostrar _event_media_ ni _event_voice_ como texto
    text = text.replace(/_event_media__[a-f0-9-]+/g, '');
    text = text.replace(/_event_voice_note__[a-f0-9-]+/g, '');
    text = text.trim();
    text = text.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" style="color:#027eb5;">$1</a>');
    return html + text;
}
function formatTime(dt) {
    if (!dt) return '';
    var today = new Date().toISOString().substring(0,10);
    var d = dt.substring(0,10);
    var t = dt.substring(11,16);
    if (d === today) return t;
    var parts = d.split('-');
    return parts[2] + '/' + parts[1];
}
function formatDate(d) {
    if (!d) return '';
    var today = new Date().toISOString().substring(0,10);
    if (d === today) return 'Hoy';
    var parts = d.split('-');
    var months = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])] + ' ' + parts[0];
}

// New chat modal
function newChatModal() { document.getElementById('newChatModalBg').style.display = 'flex'; }
function closeNewChatModal() { document.getElementById('newChatModalBg').style.display = 'none'; }
function createNewChat() {
    var phone = $('#newChatPhone').val().trim();
    var name = $('#newChatName').val().trim();
    if (!phone) { alert('Ingresa un numero'); return; }
    $.ajax({
        url: BASE + 'sisvent/admin/bots/whatsappNewChat',
        type: 'POST',
        data: { bot_config_id: currentBotId, phone: phone, name: name, [CSRF_NAME]: CSRF_HASH },
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                closeNewChatModal();
                $('#newChatPhone').val(''); $('#newChatName').val('');
                loadConversations('');
                openConversation(r.conversation_id, name || phone, phone);
            } else { alert(r.error); }
        }
    });
}

// Init
$(function() { loadConversations(''); });
</script>
</body>
</html>
