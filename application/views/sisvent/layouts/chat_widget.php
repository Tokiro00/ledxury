<?php
  $chat_user_id = $this->session->userdata('user_data')['uname'];
  $chat_user_name = $this->session->userdata('user_data')['name'];
?>
<!-- Chat Widget -->
<div id="chatWidget" style="position:fixed; bottom:24px; right:90px; z-index:9998;">
  <!-- Botón flotante -->
  <button id="chatToggle" title="Chat interno" style="
    width:48px; height:48px; border-radius:50%; border:none; cursor:pointer;
    background: #3b82f6; box-shadow: 0 4px 16px rgba(59,130,246,0.3);
    display:flex; align-items:center; justify-content:center; position:relative;
    transition: all 0.3s;
  ">
    <svg width="22" height="22" fill="white" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"/></svg>
    <span id="chatBadge" style="
      display:none; position:absolute; top:-4px; right:-4px;
      min-width:18px; height:18px; border-radius:9px; padding:0 5px;
      background:#E63946; color:white; font-size:10px; font-weight:700;
      line-height:18px; text-align:center;
    ">0</span>
  </button>

  <!-- Panel de chat -->
  <div id="chatPanel" style="
    display:none; position:absolute; bottom:58px; right:0;
    width:350px; height:480px; background:white; border-radius:16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15); border:1px solid #e5e7eb;
    overflow:hidden; display:none; flex-direction:column;
  ">
    <!-- Header -->
    <div style="background:#3b82f6; padding:12px 16px; color:white; display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex; align-items:center;">
        <svg width="18" height="18" fill="white" viewBox="0 0 24 24" style="margin-right:8px;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"/></svg>
        <span style="font-size:14px; font-weight:600;" id="chatTitle">Chat Ledxury</span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <button id="chatBackBtn" style="display:none; background:none; border:none; color:white; cursor:pointer; font-size:16px;">&#8592;</button>
        <button id="chatCloseBtn" style="background:none; border:none; color:rgba(255,255,255,0.7); cursor:pointer; font-size:18px;">&times;</button>
      </div>
    </div>

    <!-- Lista de usuarios / Conversación -->
    <div id="chatUserList" style="flex:1; overflow-y:auto; padding:8px;"></div>
    <div id="chatMessages" style="flex:1; overflow-y:auto; padding:8px 12px; display:none;"></div>

    <!-- Input -->
    <div id="chatInputArea" style="display:none; padding:8px 12px; border-top:1px solid #f3f4f6;">
      <div style="display:flex; gap:8px;">
        <input id="chatInput" type="text" placeholder="Escribe un mensaje..."
          style="flex:1; padding:8px 12px; font-size:13px; border:1px solid #e5e7eb; border-radius:8px; outline:none;"
          autocomplete="off">
        <button id="chatSendBtn" style="
          padding:8px 14px; background:#3b82f6; color:white; border:none; border-radius:8px;
          cursor:pointer; font-size:13px; font-weight:600;
        ">Enviar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var userId = '<?= $chat_user_id ?>';
  var userName = '<?= addslashes($chat_user_name) ?>';
  var currentChat = null; // null = user list, 'general' or a userId
  var pollTimer = null;

  var toggle = document.getElementById('chatToggle');
  var panel = document.getElementById('chatPanel');
  var badge = document.getElementById('chatBadge');
  var userList = document.getElementById('chatUserList');
  var messages = document.getElementById('chatMessages');
  var inputArea = document.getElementById('chatInputArea');
  var input = document.getElementById('chatInput');
  var backBtn = document.getElementById('chatBackBtn');
  var title = document.getElementById('chatTitle');

  // Toggle panel
  toggle.addEventListener('click', function() {
    if (panel.style.display === 'flex') {
      panel.style.display = 'none';
      stopPolling();
    } else {
      panel.style.display = 'flex';
      showUserList();
      checkUnread();
    }
  });
  document.getElementById('chatCloseBtn').addEventListener('click', function() {
    panel.style.display = 'none';
    stopPolling();
  });

  // Back button
  backBtn.addEventListener('click', function() {
    showUserList();
  });

  // Send message
  document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendMessage();
  });

  function showUserList() {
    currentChat = null;
    userList.style.display = 'block';
    messages.style.display = 'none';
    inputArea.style.display = 'none';
    backBtn.style.display = 'none';
    title.textContent = 'Chat Ledxury';
    stopPolling();

    $.get(base_url + 'sisvent/dashboard/chatUsers', function(r) {
      if (!r.users) return;
      var html = '';
      // General chat
      html += '<div class="chat-user-item" data-id="general" style="display:flex; align-items:center; padding:10px; border-radius:10px; cursor:pointer; margin-bottom:4px; transition:background 0.2s;"'
        + ' onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'transparent\'">'
        + '<div style="width:36px;height:36px;border-radius:50%;background:#3b82f6;display:flex;align-items:center;justify-content:center;margin-right:10px;color:white;font-weight:700;font-size:14px;">G</div>'
        + '<div style="flex:1;"><p style="font-size:13px;font-weight:600;color:#1f2937;margin:0;">Chat General</p>'
        + '<p style="font-size:11px;color:#9ca3af;margin:0;">Todos los usuarios</p></div>'
        + (r.unread_general > 0 ? '<span style="background:#E63946;color:white;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;">' + r.unread_general + '</span>' : '')
        + '</div>';

      // Users
      r.users.forEach(function(u) {
        if (u.idUser === userId) return;
        var initials = u.name.split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
        var online = u.is_online ? '<span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;margin-left:4px;"></span>' : '';
        html += '<div class="chat-user-item" data-id="' + u.idUser + '" data-name="' + u.name + '" style="display:flex; align-items:center; padding:10px; border-radius:10px; cursor:pointer; margin-bottom:4px; transition:background 0.2s;"'
          + ' onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'transparent\'">'
          + '<div style="width:36px;height:36px;border-radius:50%;background:#6b7280;display:flex;align-items:center;justify-content:center;margin-right:10px;color:white;font-weight:700;font-size:12px;">' + initials + '</div>'
          + '<div style="flex:1;"><p style="font-size:13px;font-weight:600;color:#1f2937;margin:0;">' + u.name + online + '</p>'
          + '<p style="font-size:11px;color:#9ca3af;margin:0;">' + (u.role_name || '') + '</p></div>'
          + (u.unread > 0 ? '<span style="background:#E63946;color:white;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;">' + u.unread + '</span>' : '')
          + '</div>';
      });
      userList.innerHTML = html;
    }, 'json');
  }

  // Click on user
  $(document).on('click', '.chat-user-item', function() {
    var id = $(this).data('id');
    var name = id === 'general' ? 'Chat General' : $(this).data('name');
    openChat(id, name);
  });

  function openChat(chatId, chatName) {
    currentChat = chatId;
    userList.style.display = 'none';
    messages.style.display = 'block';
    inputArea.style.display = 'block';
    backBtn.style.display = 'block';
    title.textContent = chatName;
    input.focus();
    loadMessages();
    startPolling();
  }

  function loadMessages() {
    if (!currentChat) return;
    $.get(base_url + 'sisvent/dashboard/chatMessages', { chat: currentChat }, function(r) {
      if (!r.messages) return;
      var html = '';
      r.messages.forEach(function(m) {
        var isMine = m.from_user === userId;
        var align = isMine ? 'flex-end' : 'flex-start';
        var bg = isMine ? '#3b82f6' : '#f3f4f6';
        var color = isMine ? 'white' : '#1f2937';
        var nameDisplay = isMine ? '' : '<p style="font-size:10px;color:#9ca3af;margin:0 0 2px;">' + m.from_name + '</p>';
        html += '<div style="display:flex;justify-content:' + align + ';margin-bottom:8px;">'
          + '<div style="max-width:80%;padding:8px 12px;border-radius:12px;background:' + bg + ';color:' + color + ';font-size:13px;">'
          + nameDisplay
          + '<p style="margin:0;word-wrap:break-word;">' + m.message + '</p>'
          + '<p style="font-size:9px;margin:2px 0 0;opacity:0.6;">' + m.time + '</p>'
          + '</div></div>';
      });
      messages.innerHTML = html;
      messages.scrollTop = messages.scrollHeight;
    }, 'json');
  }

  function sendMessage() {
    var text = input.value.trim();
    if (!text || !currentChat) return;
    input.value = '';

    $.post(base_url + 'sisvent/dashboard/chatSend', {
      to: currentChat,
      message: text
    }, function(r) {
      if (r.success) loadMessages();
    }, 'json');
  }

  function startPolling() {
    stopPolling();
    pollTimer = setInterval(function() {
      if (currentChat) loadMessages();
    }, 3000);
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  // Check unread every 10s
  function checkUnread() {
    $.get(base_url + 'sisvent/dashboard/chatUnread', function(r) {
      if (r.count > 0) {
        badge.textContent = r.count;
        badge.style.display = 'block';
      } else {
        badge.style.display = 'none';
      }
    }, 'json');
  }
  setInterval(checkUnread, 10000);
  checkUnread();
})();
</script>
