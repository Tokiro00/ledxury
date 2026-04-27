<?php
  $chat_user_id = $this->session->userdata('user_data')['uname'];
  $chat_user_name = $this->session->userdata('user_data')['name'];
?>
<!-- Chat Widget -->
<div id="chatWidget" style="position:fixed; bottom:24px; right:90px; z-index:9998; cursor:grab; touch-action:none;">
  <!-- Botón flotante -->
  <button id="chatToggle" title="Chat interno" style="
    width:48px; height:48px; border-radius:50%; border:none; cursor:pointer;
    background: #3b82f6; box-shadow: 0 4px 16px rgba(59,130,246,0.3);
    display:flex; align-items:center; justify-content:center; position:relative;
    transition: all 0.3s;
    touch-action:none; -webkit-user-select:none; user-select:none;
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
      <div style="display:flex; gap:6px; align-items:center;">
        <input id="chatFileInput" type="file" accept="image/*,audio/*,video/*,application/pdf" style="display:none;">
        <button id="chatAttachBtn" title="Adjuntar" style="background:none;border:none;color:#6b7280;cursor:pointer;padding:6px;border-radius:6px;display:flex;">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        </button>
        <button id="chatMicBtn" title="Grabar audio" style="background:none;border:none;color:#6b7280;cursor:pointer;padding:6px;border-radius:6px;display:flex;">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v4m0-4a4 4 0 004-4V6a4 4 0 00-8 0v8a4 4 0 004 4z"/></svg>
        </button>
        <input id="chatInput" type="text" placeholder="Escribe un mensaje..."
          style="flex:1; padding:8px 12px; font-size:13px; border:1px solid #e5e7eb; border-radius:8px; outline:none; min-width:0;"
          autocomplete="off">
        <div id="chatRecBar" style="display:none; flex:1; align-items:center; gap:6px; background:#fee2e2; padding:6px 10px; border-radius:8px; color:#991b1b; font-size:12px;">
          <span style="width:8px;height:8px;border-radius:50%;background:#ef4444;animation:cwPulse 1s infinite;"></span>
          <span id="chatRecTime">0:00</span>
          <button id="chatRecCancel" style="margin-left:auto;background:none;border:none;color:#991b1b;cursor:pointer;font-size:14px;">&times;</button>
        </div>
        <button id="chatSendBtn" style="
          padding:8px 14px; background:#3b82f6; color:white; border:none; border-radius:8px;
          cursor:pointer; font-size:13px; font-weight:600;
        ">Enviar</button>
      </div>
    </div>
    <div id="chatUploadOverlay" style="display:none; position:absolute; inset:0; background:rgba(0,0,0,.4); align-items:center; justify-content:center; z-index:10;">
      <div style="background:#fff; padding:12px 20px; border-radius:8px; font-size:13px;">Subiendo...</div>
    </div>
  </div>
</div>
<style>@keyframes cwPulse { 0%,100%{opacity:1;} 50%{opacity:.5;} }</style>

<script>
// Fix duración de audios WebM grabados con MediaRecorder.
// Estrategia: si duration===Infinity, hacer seek a un valor enorme; el browser
// debe leer el archivo hasta el final para clamp el seek, y al hacerlo dispara
// 'durationchange' con la duración real. Ahí reseteamos currentTime a 0.
window.cwFixWebmDur = function(a) {
  if (!a || a.dataset.cwDurFixed === '1') return;
  if (a.duration !== Infinity && !isNaN(a.duration) && a.duration > 0) { a.dataset.cwDurFixed = '1'; return; }
  a.dataset.cwDurFixed = '1';
  var done = false;
  var onDC = function() {
    if (done) return;
    if (a.duration !== Infinity && !isNaN(a.duration) && a.duration > 0) {
      done = true;
      a.removeEventListener('durationchange', onDC);
      try { a.currentTime = 0; } catch(e){}
    }
  };
  a.addEventListener('durationchange', onDC);
  // Trigger: seek a un valor altísimo para forzar al browser a calcular duración real
  try { a.currentTime = 1e101; } catch(e){}
  // Fallback: si en 4s no se actualizó, reset
  setTimeout(function() { if (!done) { done = true; a.removeEventListener('durationchange', onDC); try { a.currentTime = 0; } catch(e){} } }, 4000);
};
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

  // Paste de imagen (Ctrl+V con captura de pantalla u otra imagen del portapapeles)
  input.addEventListener('paste', function(e) {
    if (!e.clipboardData || !e.clipboardData.items) return;
    var items = e.clipboardData.items;
    for (var i = 0; i < items.length; i++) {
      var it = items[i];
      if (it.kind === 'file' && it.type.indexOf('image/') === 0) {
        e.preventDefault();
        if (!currentChat) { alert('Abre una conversación primero'); return; }
        var blob = it.getAsFile();
        if (!blob) return;
        var ext = (blob.type.split('/')[1] || 'png').replace(/[^a-z0-9]/gi,'');
        var file = new File([blob], 'screenshot_' + Date.now() + '.' + ext, { type: blob.type });
        uploadFile(file, function(r) {
          sendMessage({ media_url: r.url, media_type: 'image', media_name: r.name });
        });
        break;
      }
    }
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

  function renderMedia(m) {
    if (!m.media_url) return '';
    var url = m.media_url;
    if (m.media_type === 'image') return '<a href="'+url+'" target="_blank"><img src="'+url+'" style="max-width:200px;max-height:200px;border-radius:6px;display:block;cursor:pointer;"></a>';
    if (m.media_type === 'audio') return '<audio controls preload="metadata" style="max-width:220px;display:block;"><source src="'+url+'" type="audio/webm"></audio>';
    if (m.media_type === 'video') return '<video controls preload="metadata" style="max-width:220px;border-radius:6px;display:block;"><source src="'+url+'"></video>';
    var name = (m.media_name || 'Archivo').replace(/[<>]/g,'');
    return '<a href="'+url+'" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:5px 8px;background:rgba(0,0,0,0.08);border-radius:6px;color:inherit;text-decoration:none;font-size:12px;">📎 '+name+'</a>';
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
        var mediaHtml = renderMedia(m);
        var msgText = m.message ? '<p style="margin:0;word-wrap:break-word;">' + m.message + '</p>' : '';
        html += '<div style="display:flex;justify-content:' + align + ';margin-bottom:8px;">'
          + '<div style="max-width:80%;padding:8px 12px;border-radius:12px;background:' + bg + ';color:' + color + ';font-size:13px;">'
          + nameDisplay
          + (mediaHtml ? '<div style="margin-bottom:4px;">'+mediaHtml+'</div>' : '')
          + msgText
          + '<p style="font-size:9px;margin:2px 0 0;opacity:0.6;">' + m.time + '</p>'
          + '</div></div>';
      });
      messages.innerHTML = html;
      messages.scrollTop = messages.scrollHeight;
    }, 'json');
  }

  function sendMessage(extra) {
    var text = input.value.trim();
    extra = extra || {};
    if (!extra.media_url && !text) return;
    if (!currentChat) return;
    input.value = '';

    $.post(base_url + 'sisvent/dashboard/chatSend', {
      to: currentChat,
      message: text,
      media_url: extra.media_url || '',
      media_type: extra.media_type || '',
      media_name: extra.media_name || ''
    }, function(r) {
      if (r.success) loadMessages();
    }, 'json');
  }

  // ===== Media upload =====
  var fileInput = document.getElementById('chatFileInput');
  var attachBtn = document.getElementById('chatAttachBtn');
  var micBtn = document.getElementById('chatMicBtn');
  var recBar = document.getElementById('chatRecBar');
  var recTimeEl = document.getElementById('chatRecTime');
  var recCancel = document.getElementById('chatRecCancel');
  var uploadOverlay = document.getElementById('chatUploadOverlay');

  attachBtn.addEventListener('click', function() { fileInput.click(); });
  fileInput.addEventListener('change', function() {
    if (!fileInput.files || !fileInput.files[0]) return;
    var f = fileInput.files[0];
    fileInput.value = '';
    if (!currentChat) { alert('Abre una conversación primero'); return; }
    uploadFile(f, function(r) {
      sendMessage({ media_url: r.url, media_type: r.type, media_name: r.name });
    });
  });

  function uploadFile(file, onDone) {
    if (file.size > 15 * 1024 * 1024) { alert('Archivo excede 15MB'); return; }
    var fd = new FormData();
    fd.append('file', file);
    uploadOverlay.style.display = 'flex';
    $.ajax({
      url: base_url + 'sisvent/message/uploadMedia',
      type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
      success: function(r) {
        uploadOverlay.style.display = 'none';
        if (!r.ok) { alert('Error: ' + (r.error || 'no se pudo subir')); return; }
        // r.url es relativo (ej "public/uploads/chat/X/file.webm").
        // Lo guardamos así. El server prefija base_url al servir mensajes en chatMessages.
        onDone(r);
      },
      error: function() { uploadOverlay.style.display = 'none'; alert('Error de conexión al subir'); }
    });
  }

  // ===== Audio recording =====
  var mr = null, chunks = [], recStart = 0, recTimer = null;

  micBtn.addEventListener('click', function() {
    if (mr && mr.state === 'recording') { mr.stop(); return; }
    if (!currentChat) { alert('Abre una conversación primero'); return; }
    if (!navigator.mediaDevices || !window.MediaRecorder) { alert('Tu navegador no soporta grabación'); return; }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
      chunks = [];
      var mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '');
      mr = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
      mr.ondataavailable = function(e) { if (e.data.size > 0) chunks.push(e.data); };
      mr.onstop = function() {
        stream.getTracks().forEach(function(t){ t.stop(); });
        recBar.style.display = 'none';
        input.style.display = '';
        clearInterval(recTimer);
        if (!chunks.length) return;
        var blob = new Blob(chunks, { type: mr.mimeType || 'audio/webm' });
        var ext = (mr.mimeType||'').indexOf('mp4') >= 0 ? 'm4a' : 'webm';
        var durMs = Date.now() - recStart;
        var sendBlob = function(finalBlob) {
          var file = new File([finalBlob], 'audio_' + Date.now() + '.' + ext, { type: finalBlob.type || blob.type });
          uploadFile(file, function(r) { sendMessage({ media_url: r.url, media_type: 'audio', media_name: r.name }); });
        };
        // Parchear duración del WebM antes de subir (si la lib está disponible).
        // ysFixWebmDuration usa callback: (blob, durationMs, callback)
        if (ext === 'webm' && typeof window.ysFixWebmDuration === 'function') {
          try { window.ysFixWebmDuration(blob, durMs, sendBlob); }
          catch (e) { sendBlob(blob); }
        } else {
          sendBlob(blob);
        }
      };
      mr.start();
      recStart = Date.now();
      recBar.style.display = 'flex';
      input.style.display = 'none';
      recTimer = setInterval(function() {
        var s = Math.floor((Date.now() - recStart)/1000);
        recTimeEl.textContent = Math.floor(s/60) + ':' + (s%60 < 10 ? '0' : '') + (s%60);
        if (s >= 120) mr.stop();
      }, 250);
    }).catch(function() { alert('No se pudo acceder al micrófono'); });
  });

  recCancel.addEventListener('click', function() {
    if (mr && mr.state === 'recording') { chunks = []; mr.stop(); }
  });

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
