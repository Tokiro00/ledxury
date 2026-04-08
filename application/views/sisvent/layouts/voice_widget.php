<?php
  $voiceUserName = '';
  $voiceUserRole = 0;
  $vud = $this->session->userdata('user_data');
  if ($vud && isset($vud['name'])) {
    $parts = explode(' ', trim($vud['name']));
    $voiceUserName = $parts[0];
  }
  if ($vud && isset($vud['role'])) {
    $voiceUserRole = (int) $vud['role'];
  }
  // Roles que auto-activan micrófono: Logística (9), SuperAdminBots (10)
  $autoActivateVoice = in_array($voiceUserRole, [9, 10]);

  // Saludo: solo si NO se ha mostrado ya en esta sesión
  $shouldGreet = false;
  if ($this->session->userdata('germam_should_greet') && !$this->session->userdata('germam_greeted')) {
    $shouldGreet = true;
    $this->session->set_userdata('germam_greeted', true);
    $this->session->unset_userdata('germam_should_greet');
  }
?>
<!-- GerMAM Voice Assistant Widget -->
<div id="voiceWidget" style="position:fixed; bottom:24px; right:24px; z-index:9999;">
  <button id="voiceToggle" title="Asistente de voz GerMAM" style="
    width:56px; height:56px; border-radius:50%; border:none; cursor:pointer;
    background: linear-gradient(135deg, #1a1a2e, #E63946);
    box-shadow: 0 4px 20px rgba(230,57,70,0.3);
    display:flex; align-items:center; justify-content:center;
    transition: all 0.3s ease; position:relative;
  ">
    <svg id="voiceIconMic" width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
    <span id="voicePulse" style="display:none; position:absolute; inset:-4px; border-radius:50%; border:2px solid #E63946; animation:vPulse 1.5s infinite;"></span>
  </button>

  <div id="voicePanel" style="
    display:none; position:absolute; bottom:68px; right:0;
    width:340px; background:white; border-radius:16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15); border:1px solid #e5e7eb;
    overflow:hidden;
  ">
    <!-- Header -->
    <div style="background:linear-gradient(135deg, #1a1a2e, #16213e); padding:14px 16px; color:white;">
      <div style="display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center;">
          <div id="voiceOrb" style="width:36px; height:36px; border-radius:50%; background:radial-gradient(circle,#E63946,#8b1a22); margin-right:10px; transition:all 0.3s; display:flex; align-items:center; justify-content:center;">
            <div id="orbBars" style="display:flex; align-items:center; gap:2px; height:16px;">
              <span class="oBr" style="width:3px; height:4px; background:white; border-radius:1px; transition:height 0.1s;"></span>
              <span class="oBr" style="width:3px; height:8px; background:white; border-radius:1px; transition:height 0.1s;"></span>
              <span class="oBr" style="width:3px; height:4px; background:white; border-radius:1px; transition:height 0.1s;"></span>
              <span class="oBr" style="width:3px; height:6px; background:white; border-radius:1px; transition:height 0.1s;"></span>
            </div>
          </div>
          <div>
            <p style="font-size:14px; font-weight:700; margin:0;">GerMAM</p>
            <p id="voiceStatus" style="font-size:11px; color:#9ca3af; margin:0;">Toca para activar</p>
          </div>
        </div>
        <button id="voiceClose" style="background:none; border:none; color:#9ca3af; cursor:pointer; font-size:20px; line-height:1;">&times;</button>
      </div>
      <!-- Texto en tiempo real -->
      <div id="liveText" style="display:none; margin-top:10px; padding:8px 10px; background:rgba(255,255,255,0.1); border-radius:8px; font-size:12px; color:#d1d5db; min-height:20px;"></div>
    </div>

    <!-- Log -->
    <div style="padding:10px 14px; max-height:220px; overflow-y:auto;">
      <div id="voiceLog" style="font-size:12px; color:#6b7280;">
        <p style="text-align:center; color:#9ca3af; margin:8px 0;">Di <strong>"GerMAM"</strong> seguido de tu pregunta</p>
      </div>
    </div>

    <!-- Controls -->
    <div style="padding:8px 14px 12px; border-top:1px solid #f3f4f6; display:flex; gap:8px;">
      <button id="voiceStartBtn" style="flex:1; padding:10px; font-size:12px; font-weight:600; border:none; border-radius:8px; cursor:pointer; background:#E63946; color:white;">Activar escucha</button>
      <button id="voiceStopBtn" style="display:none; flex:1; padding:10px; font-size:12px; font-weight:600; border:none; border-radius:8px; cursor:pointer; background:#6b7280; color:white;">Detener</button>
    </div>
  </div>
</div>

<style>
@keyframes vPulse { 0%{transform:scale(1);opacity:.7} 100%{transform:scale(1.5);opacity:0} }
#voiceOrb.listening { box-shadow: 0 0 12px #E63946; }
#voiceOrb.thinking { background: radial-gradient(circle,#3b82f6,#1e40af)!important; box-shadow: 0 0 16px #3b82f6; }
#voiceOrb.speaking { background: radial-gradient(circle,#22c55e,#15803d)!important; box-shadow: 0 0 16px #22c55e; }
#voiceOrb.heard { background: radial-gradient(circle,#f59e0b,#d97706)!important; box-shadow: 0 0 20px #f59e0b; }
</style>

<script>
(function() {
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    document.getElementById('voiceWidget').style.display = 'none';
    return;
  }

  var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  var rec = new SR();
  rec.lang = 'es-CO';
  rec.continuous = true;
  rec.interimResults = true;

  var synth = window.speechSynthesis;
  var isOn = false, isSpeaking = false, isProcessing = false;
  var isConversation = false; // Modo conversación activo
  var convTimer = null; // Timer para salir del modo conversación
  var convId = null, currentAudio = null;

  var panel = document.getElementById('voicePanel');
  var logEl = document.getElementById('voiceLog');
  var statusEl = document.getElementById('voiceStatus');
  var orbEl = document.getElementById('voiceOrb');
  var orbBars = document.querySelectorAll('.oBr');
  var liveText = document.getElementById('liveText');
  var pulseEl = document.getElementById('voicePulse');
  var startBtn = document.getElementById('voiceStartBtn');
  var stopBtn = document.getElementById('voiceStopBtn');

  // === PANEL ===
  document.getElementById('voiceToggle').addEventListener('click', function() {
    if (panel.style.display === 'none' || !panel.style.display) {
      panel.style.display = 'block';
      if (!isOn) startMic();
    } else {
      panel.style.display = 'none';
    }
  });
  document.getElementById('voiceClose').addEventListener('click', function() { panel.style.display = 'none'; });
  startBtn.addEventListener('click', startMic);
  stopBtn.addEventListener('click', stopMic);

  // === MIC CONTROL ===
  function startMic() {
    try { rec.start(); } catch(e) {}
    isOn = true;
    startBtn.style.display = 'none';
    stopBtn.style.display = 'block';
    pulseEl.style.display = 'block';
    liveText.style.display = 'block';
    liveText.textContent = '';
    setState('waiting');
    startAudioMonitor();
  }

  function stopMic() {
    rec.stop();
    isOn = false;
    isSpeaking = false;
    startBtn.style.display = 'block';
    stopBtn.style.display = 'none';
    pulseEl.style.display = 'none';
    liveText.style.display = 'none';
    synth.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }
    setState('off');
    stopAudioMonitor();
  }

  function setState(s) {
    orbEl.className = '';
    if (s === 'off') { statusEl.textContent = 'Inactivo'; }
    else if (s === 'waiting') { statusEl.textContent = 'Di "GerMAM" + tu pregunta'; orbEl.classList.add('listening'); }
    else if (s === 'conversation') { statusEl.textContent = 'Preguntame algo mas...'; orbEl.classList.add('heard'); }
    else if (s === 'heard') { statusEl.textContent = 'Te escucho...'; orbEl.classList.add('heard'); }
    else if (s === 'thinking') { statusEl.textContent = 'Pensando...'; orbEl.classList.add('thinking'); }
    else if (s === 'speaking') { statusEl.textContent = 'Respondiendo...'; orbEl.classList.add('speaking'); }
  }

  // === AUDIO LEVEL MONITOR (barras del orbe) ===
  var audioCtx, analyser, micStream, monitorRAF;
  function startAudioMonitor() {
    if (audioCtx) return;
    navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream) {
      micStream = stream;
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      analyser = audioCtx.createAnalyser();
      analyser.fftSize = 256;
      var src = audioCtx.createMediaStreamSource(stream);
      src.connect(analyser);
      animateBars();
    }).catch(function(){});
  }

  function stopAudioMonitor() {
    if (monitorRAF) cancelAnimationFrame(monitorRAF);
    if (micStream) micStream.getTracks().forEach(function(t){t.stop();});
    if (audioCtx) { audioCtx.close(); audioCtx = null; }
  }

  function animateBars() {
    if (!analyser) return;
    var data = new Uint8Array(analyser.frequencyBinCount);
    analyser.getByteFrequencyData(data);
    // Promedio de las frecuencias de voz (300-3000Hz aprox)
    var sum = 0;
    for (var i = 2; i < 20; i++) sum += data[i];
    var level = sum / 18 / 255;

    orbBars.forEach(function(bar, idx) {
      var h = 4 + level * (12 + idx * 3) * (isSpeaking ? 0.3 : 1);
      bar.style.height = Math.min(h, 16) + 'px';
    });

    monitorRAF = requestAnimationFrame(animateBars);
  }

  // === LOG ===
  function addLog(type, text) {
    var el = document.createElement('div');
    el.style.cssText = 'margin-bottom:6px; padding:6px 10px; border-radius:8px; font-size:12px; line-height:1.4;';
    if (type === 'user') { el.style.background = '#eff6ff'; el.style.color = '#1e40af'; el.innerHTML = '<b>Tu:</b> ' + text; }
    else if (type === 'assistant') { el.style.background = '#f0fdf4'; el.style.color = '#166534'; el.innerHTML = '<b>GerMAM:</b> ' + text; }
    else if (type === 'error') { el.style.background = '#fef2f2'; el.style.color = '#991b1b'; el.innerHTML = text; }
    else { el.style.color = '#9ca3af'; el.style.textAlign = 'center'; el.style.fontStyle = 'italic'; el.innerHTML = text; }
    logEl.appendChild(el);
    logEl.scrollTop = logEl.scrollHeight;
  }

  // === SPEAK (ElevenLabs + fallback) ===
  function speak(text, callback) {
    synth.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }

    var clean = text.replace(/\*\*/g,'').replace(/\*/g,'').replace(/#{1,6}\s/g,'')
      .replace(/```[\s\S]*?```/g,'').replace(/`[^`]+`/g,'').replace(/\[([^\]]+)\]\([^)]+\)/g,'$1')
      .replace(/\n{2,}/g,'. ').replace(/\n/g,'. ').replace(/\|[^\n]+/g,'')
      .replace(/\.\s*\./g,'.').replace(/\s+/g,' ').trim();

    if (clean.length > 350) clean = clean.substring(0, 350) + '. Mas detalles en pantalla.';

    isSpeaking = true;
    setState('speaking');

    function onDone() {
      isSpeaking = false;
      liveText.textContent = '';
      // Activar modo conversación por 10 segundos
      isConversation = true;
      setState('conversation');
      clearTimeout(convTimer);
      convTimer = setTimeout(function() {
        isConversation = false;
        setState('waiting');
      }, 10000);
      if (callback) callback();
    }

    // ElevenLabs
    fetch('https://api.elevenlabs.io/v1/text-to-speech/onwK4e9ZLuTAKqWW03F9?output_format=mp3_22050_32', {
      method: 'POST',
      headers: { 'xi-api-key': 'sk_563cc7e05cde5073eddf8ee585b81fd44d66d54e26fae5a2', 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: clean, model_id: 'eleven_multilingual_v2', voice_settings: { stability: 0.5, similarity_boost: 0.75 } })
    })
    .then(function(r) { if (!r.ok) throw new Error(r.status); return r.blob(); })
    .then(function(blob) {
      var url = URL.createObjectURL(blob);
      currentAudio = new Audio(url);
      currentAudio.onended = function() { URL.revokeObjectURL(url); currentAudio = null; onDone(); };
      currentAudio.onerror = function() { onDone(); };
      currentAudio.play();
    })
    .catch(function() {
      // Fallback navegador
      var u = new SpeechSynthesisUtterance(clean);
      u.lang = 'es-CO'; u.rate = 0.95; u.pitch = 0.9;
      u.onend = onDone;
      synth.speak(u);
    });
  }

  // === NAVEGACIÓN POR VOZ ===
  var navRoutes = {
    // Internas Ledxury
    'bots': base_url + 'sisvent/admin/bots',
    'bot': base_url + 'sisvent/admin/bots',
    'whatsapp bots': base_url + 'sisvent/admin/bots',
    'presupuestos': base_url + 'sisvent/commercial/budgets',
    'facturas': base_url + 'sisvent/commercial/invoices',
    'clientes': base_url + 'sisvent/business/clients',
    'productos': base_url + 'sisvent/business/products',
    'inventario': base_url + 'sisvent/store/inventory',
    'proveedores': base_url + 'sisvent/business/providers',
    'envios': base_url + 'sisvent/admin/envios',
    'cartera': base_url + 'sisvent/admin/accountsreceivable',
    'reporte de ventas': base_url + 'sisvent/admin/bots/report/0',
    'reporte ventas': base_url + 'sisvent/admin/bots/report/0',
    'reportes': base_url + 'sisvent/admin/bots/report/0',
    'meta ads': base_url + 'sisvent/admin/bots/ads',
    'campanas': base_url + 'sisvent/admin/bots/ads',
    'perfil': base_url + 'sisvent/dashboard/profile',
    'mi perfil': base_url + 'sisvent/dashboard/profile',
    'configuracion': base_url + 'sisvent/business/users',
    'usuarios': base_url + 'sisvent/business/users',
    'roles': base_url + 'sisvent/business/roles',
    'inicio': base_url + 'sisvent/dashboard',
    'dashboard': base_url + 'sisvent/dashboard',
    'contabilidad': base_url + 'sisvent/accounting/entries',
    'asientos': base_url + 'sisvent/accounting/entries',
    'plan de cuentas': base_url + 'sisvent/accounting/plandecuentas',
    'cajas': base_url + 'sisvent/admin/cashboxes',
    'tesoreria': base_url + 'sisvent/admin/cashboxes',
    'bancos': base_url + 'sisvent/admin/bankaccounts',
    'gastos': base_url + 'sisvent/admin/expenses',
    'compras': base_url + 'sisvent/commercial/purchases',
    'logistica': base_url + 'sisvent/admin/logistics/report',
    'actividad': base_url + 'sisvent/dashboard/userActivity',
    'actividad usuarios': base_url + 'sisvent/dashboard/userActivity',
    'asistente': base_url + 'sisvent/admin/aiassistant',
    'chat': base_url + 'sisvent/admin/aiassistant',
    'notas credito': base_url + 'sisvent/commercial/creditnotes',
    'devoluciones': base_url + 'sisvent/commercial/creditnotes',
    'liquidaciones': base_url + 'sisvent/admin/settlements',
    'estado de cuenta': base_url + 'sisvent/admin/clientstatement',
    'traspasos': base_url + 'sisvent/store/transfers',
    'desempeno': base_url + 'sisvent/admin/tracking/semanal',
    'tracking': base_url + 'sisvent/admin/tracking/semanal',
    // Externas
    'whatsapp': 'https://web.whatsapp.com',
    'correo': 'https://mail.google.com',
    'gmail': 'https://mail.google.com',
    'email': 'https://mail.google.com',
    'youtube': 'https://www.youtube.com',
    'facebook': 'https://www.facebook.com',
    'instagram': 'https://www.instagram.com',
    'google': 'https://www.google.com',
    'ads manager': 'https://adsmanager.facebook.com',
    'meta business': 'https://business.facebook.com',
    'builderbot': 'https://app.builderbot.cloud',
    'interrapidisimo': 'https://www.interrapidisimo.com',
    'rastreo': 'https://www.interrapidisimo.com/rastreo/',
    'google sheets': 'https://docs.google.com/spreadsheets',
    'sheets': 'https://docs.google.com/spreadsheets',
    'drive': 'https://drive.google.com',
    'calendar': 'https://calendar.google.com',
    'calendario': 'https://calendar.google.com',
    'maps': 'https://maps.google.com',
    'twitter': 'https://x.com',
    'tiktok': 'https://www.tiktok.com',
    'mercadolibre': 'https://www.mercadolibre.com.co',
    'amazon': 'https://www.amazon.com',
    'chatgpt': 'https://chat.openai.com',
    'claude': 'https://claude.ai',
  };

  function tryNavigate(txt) {
    var norm = txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    // Detectar intención de abrir/navegar
    var openPatterns = ['abr', 'abreme', 'abrir', 'abre', 'llevame', 'ir a', 've a', 'muestrame', 'muestra', 'mostrar', 'navega', 'open'];
    var isOpen = false;
    for (var i = 0; i < openPatterns.length; i++) {
      if (norm.indexOf(openPatterns[i]) !== -1) { isOpen = true; break; }
    }
    if (!isOpen) return false;

    // Buscar coincidencia más larga primero
    var keys = Object.keys(navRoutes).sort(function(a, b) { return b.length - a.length; });
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i].normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      if (norm.indexOf(key) !== -1) {
        var url = navRoutes[keys[i]];
        var isExternal = url.indexOf('http') === 0 && url.indexOf(base_url) !== 0;
        var nombre = keys[i].charAt(0).toUpperCase() + keys[i].slice(1);

        addLog('assistant', (isExternal ? 'Abriendo ' : 'Yendo a ') + nombre + '...');
        speak('Listo, abriendo ' + nombre + '.', function() {
          if (isExternal) {
            window.open(url, '_blank');
          } else {
            window.location.href = url;
          }
        });
        return true;
      }
    }
    return false;
  }

  // === ENVIAR MENSAJES POR VOZ ===
  function trySendMessage(txt) {
    var norm = txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    // Detectar intención de enviar mensaje
    var msgPatterns = ['enviale', 'envia', 'mandale', 'manda', 'escribele', 'escribile', 'dile a', 'mensaje a', 'enviar mensaje'];
    var isMsg = false;
    for (var i = 0; i < msgPatterns.length; i++) {
      if (norm.indexOf(msgPatterns[i]) !== -1) { isMsg = true; break; }
    }
    if (!isMsg) return false;

    // Extraer nombre y mensaje
    // Patrones: "enviale a [nombre] que [mensaje]", "dile a [nombre] que [mensaje]", "mandale mensaje a [nombre] diciendo [mensaje]"
    var match = null;
    var patterns = [
      /(?:enviale|mandale|escribele|escribile|envia|manda)\s+(?:un\s+)?(?:mensaje\s+)?a\s+(\w+(?:\s+\w+)?)\s+(?:que|diciendo|el mensaje)\s+(.+)/i,
      /(?:dile)\s+a\s+(\w+(?:\s+\w+)?)\s+que\s+(.+)/i,
      /(?:mensaje\s+a)\s+(\w+(?:\s+\w+)?)\s*[:\-]?\s*(.+)/i,
      /(?:enviale|mandale|escribele)\s+a\s+(\w+(?:\s+\w+)?)\s+(.+)/i,
    ];

    for (var i = 0; i < patterns.length; i++) {
      match = norm.match(patterns[i]);
      if (match) break;
    }

    if (!match || !match[1] || !match[2]) return false;

    var targetName = match[1].trim();
    var mensaje = match[2].trim();

    if (mensaje.length < 2) return false;

    setState('thinking');
    addLog('assistant', 'Buscando a ' + targetName + '...');

    // Buscar usuario por nombre
    $.get(base_url + 'sisvent/dashboard/chatUsers', function(r) {
      if (!r.users) {
        addLog('error', 'No pude obtener la lista de usuarios.');
        setState('waiting');
        return;
      }

      var found = null;
      var searchName = targetName.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      for (var i = 0; i < r.users.length; i++) {
        var uName = r.users[i].name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        if (uName.indexOf(searchName) !== -1 || searchName.indexOf(uName.split(' ')[0]) !== -1) {
          found = r.users[i];
          break;
        }
      }

      if (!found) {
        addLog('error', 'No encontre a "' + targetName + '" en el sistema.');
        speak('No encontre a ' + targetName + ' en el sistema.');
        return;
      }

      // Enviar mensaje
      $.post(base_url + 'sisvent/dashboard/chatSend', {
        to: found.idUser,
        message: mensaje
      }, function(res) {
        if (res.success) {
          var nombre = found.name.split(' ')[0];
          addLog('assistant', 'Mensaje enviado a ' + nombre + ': "' + mensaje + '"');
          speak('Listo, le envie el mensaje a ' + nombre + '.');
        } else {
          addLog('error', 'Error al enviar el mensaje.');
          speak('No pude enviar el mensaje.');
        }
      }, 'json').fail(function() {
        addLog('error', 'Error de conexion al enviar mensaje.');
        setState('waiting');
      });
    }, 'json');

    return true;
  }

  // === NOTICIAS POR VOZ ===
  function tryNews(txt) {
    var norm = txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    // Detectar intención de noticias
    var newsPatterns = ['noticias de', 'noticias sobre', 'titulares de', 'titulares sobre', 'novedades de', 'novedades en', 'que hay de nuevo en', 'que hay nuevo en', 'resumen de noticias', 'ultimas noticias'];
    var topic = '';
    for (var i = 0; i < newsPatterns.length; i++) {
      var idx = norm.indexOf(newsPatterns[i]);
      if (idx !== -1) {
        topic = norm.substring(idx + newsPatterns[i].length).trim();
        break;
      }
    }
    if (!topic) return false;

    setState('thinking');
    addLog('assistant', 'Buscando noticias de ' + topic + '...');

    $.get(base_url + 'sisvent/dashboard/news', { q: topic }, function(r) {
      if (!r.success || !r.news || r.news.length === 0) {
        addLog('error', 'No encontre noticias sobre ' + topic);
        speak('No encontre noticias sobre ' + topic + ' en este momento.');
        return;
      }

      // Construir resumen para voz
      var voz = 'Estas son las ultimas noticias sobre ' + topic + '. ';
      var logHtml = '<b>Noticias de ' + topic + ':</b><br>';
      r.news.forEach(function(n, i) {
        voz += (i + 1) + '. ' + n.title + '. ';
        logHtml += '<br>' + (i + 1) + '. ' + n.title + ' <span style="color:#9ca3af;font-size:10px;">(' + n.source + ')</span>';
      });
      voz += 'Eso es todo por ahora.';

      addLog('assistant', logHtml);
      speak(voz);
    }, 'json').fail(function() {
      addLog('error', 'Error buscando noticias.');
      setState('waiting');
    });

    return true;
  }

  // === ACCIONES EN PRESUPUESTOS POR VOZ ===
  var pendingAction = null; // Para confirmación por voz

  function tryBudgetAction(txt) {
    var norm = txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    // === PRODUCTO AGOTADO ===
    // "el producto 3LED-12V-C del pedido 3720 esta agotado"
    // "agotado 3LED-12V-C pedido 3720"
    var agotadoMatch = norm.match(/(?:agotado|no hay|no tiene|se agoto|esta agotado)\s+(?:el\s+)?(?:producto\s+)?([a-z0-9-]+).*?(?:pedido|presupuesto)\s+(\d+)/i)
      || norm.match(/(?:pedido|presupuesto)\s+(\d+).*?(?:producto\s+)?([a-z0-9-]+).*?(?:agotado|no hay|se agoto)/i)
      || norm.match(/(?:producto\s+)?([a-z0-9]+-[a-z0-9]+-[a-z0-9]+).*?(?:pedido|presupuesto)\s+(\d+).*?(?:agotado|no hay)/i);

    if (agotadoMatch) {
      var product, budget;
      // Detectar cuál grupo es el producto y cuál el pedido
      if (/^\d+$/.test(agotadoMatch[1])) {
        budget = agotadoMatch[1]; product = agotadoMatch[2];
      } else {
        product = agotadoMatch[1]; budget = agotadoMatch[2];
      }
      product = product.toUpperCase();

      pendingAction = { type: 'agotado', budget: budget, product: product };
      addLog('assistant', 'Voy a marcar ' + product + ' como agotado en pedido #' + budget + ' y notificar al cliente. Confirmas?');
      speak('Voy a marcar ' + product + ' como agotado en el pedido ' + budget + ' y notificar al cliente por WhatsApp. Confirmas?');
      isConversation = true;
      clearTimeout(convTimer);
      convTimer = setTimeout(function() { pendingAction = null; isConversation = false; setState('waiting'); }, 15000);
      return true;
    }

    // === CAMBIAR PRECIO ===
    // "cambia el precio del pedido 3720 producto 3LED-12V-I a 1800"
    // "precio 3LED-12V-I pedido 3720 a 1800"
    var precioMatch = norm.match(/(?:cambia|cambiar|modifica|actualiza|corrige).*?(?:precio|valor).*?(?:pedido|presupuesto)\s+(\d+).*?(?:producto\s+)?([a-z0-9]+-[a-z0-9]+-[a-z0-9]+).*?(?:a|por)\s+(\d+)/i)
      || norm.match(/(?:cambia|cambiar|modifica|actualiza|corrige).*?(?:precio|valor).*?(?:producto\s+)?([a-z0-9]+-[a-z0-9]+-[a-z0-9]+).*?(?:pedido|presupuesto)\s+(\d+).*?(?:a|por)\s+(\d+)/i)
      || norm.match(/(?:precio|valor)\s+(?:del?\s+)?([a-z0-9]+-[a-z0-9]+-[a-z0-9]+).*?(?:pedido|presupuesto)\s+(\d+).*?(?:a|por)\s+(\d+)/i);

    if (precioMatch) {
      var pBudget, pProduct, pPrice;
      if (/^\d+$/.test(precioMatch[1]) && precioMatch[1].length < 6) {
        pBudget = precioMatch[1]; pProduct = precioMatch[2].toUpperCase(); pPrice = precioMatch[3];
      } else {
        pProduct = precioMatch[1].toUpperCase(); pBudget = precioMatch[2]; pPrice = precioMatch[3];
      }

      pendingAction = { type: 'precio', budget: pBudget, product: pProduct, price: pPrice };
      var priceFormatted = Number(pPrice).toLocaleString('es-CO');
      addLog('assistant', 'Voy a cambiar el precio de ' + pProduct + ' a $' + priceFormatted + ' en pedido #' + pBudget + '. Confirmas?');
      speak('Voy a cambiar el precio de ' + pProduct + ' a ' + priceFormatted + ' pesos en el pedido ' + pBudget + '. Confirmas?');
      isConversation = true;
      clearTimeout(convTimer);
      convTimer = setTimeout(function() { pendingAction = null; isConversation = false; setState('waiting'); }, 15000);
      return true;
    }

    // === APROBAR PRESUPUESTO ===
    // "aprueba el pedido 3720", "factura el presupuesto 3720", "aprueba el 3720"
    var approveMatch = norm.match(/(?:aprueba|aprobar|aprobame|factura|facturar|facturame|pasa a factura)\s+(?:el\s+)?(?:pedido|presupuesto)?\s*(?:#)?(\d{3,5})/i);
    if (approveMatch) {
      var appBudget = approveMatch[1];
      pendingAction = { type: 'aprobar', budget: appBudget };
      addLog('assistant', 'Voy a aprobar el presupuesto #' + appBudget + ' y crear la factura. Confirmas?');
      speak('Voy a aprobar el presupuesto ' + appBudget + ' y pasarlo a factura. Confirmas?');
      isConversation = true;
      clearTimeout(convTimer);
      convTimer = setTimeout(function() { pendingAction = null; isConversation = false; setState('waiting'); }, 15000);
      return true;
    }

    // === CONFIRMACIÓN ===
    if (pendingAction) {
      var siWords = ['si', 'confirmo', 'dale', 'hazlo', 'listo', 'correcto', 'afirmativo', 'ok'];
      var noWords = ['no', 'cancela', 'cancelar', 'dejalo', 'olvida'];
      var isSi = false, isNo = false;
      for (var i = 0; i < siWords.length; i++) { if (norm.indexOf(siWords[i]) !== -1) { isSi = true; break; } }
      for (var i = 0; i < noWords.length; i++) { if (norm.indexOf(noWords[i]) !== -1) { isNo = true; break; } }

      if (isSi) {
        var action = pendingAction;
        pendingAction = null;
        if (action.type === 'agotado') {
          executarAgotado(action.budget, action.product);
        } else if (action.type === 'precio') {
          executarPrecio(action.budget, action.product, action.price);
        } else if (action.type === 'aprobar') {
          executarAprobar(action.budget);
        }
        return true;
      } else if (isNo) {
        pendingAction = null;
        addLog('system', 'Accion cancelada.');
        speak('Entendido, cancelado.');
        return true;
      }
    }

    return false;
  }

  function executarAgotado(budgetId, productId) {
    setState('thinking');
    $.post(base_url + 'sisvent/dashboard/markOutOfStock', {
      budget_id: budgetId,
      product_id: productId
    }, function(r) {
      if (r.success) {
        var msg = r.message;
        if (r.whatsapp_sent) msg += '. WhatsApp enviado a ' + r.client_name;
        if (r.alternativas) msg += '. Alternativas: ' + r.alternativas;
        addLog('assistant', msg);
        speak('Listo. ' + (r.whatsapp_sent ? 'Le notifique al cliente por WhatsApp.' : 'Marcado como agotado.') + (r.alternativas ? ' Hay alternativas disponibles: ' + r.alternativas : ''));
      } else {
        addLog('error', r.error);
        speak('Error: ' + r.error);
      }
    }, 'json').fail(function() { speak('Error de conexion.'); setState('waiting'); });
  }

  function executarPrecio(budgetId, productId, newPrice) {
    setState('thinking');
    $.post(base_url + 'sisvent/dashboard/updateBudgetPrice', {
      budget_id: budgetId,
      product_id: productId,
      new_price: newPrice
    }, function(r) {
      if (r.success) {
        addLog('assistant', r.message + '. Nuevo total del pedido: $' + Number(r.new_total).toLocaleString('es-CO'));
        speak('Listo. ' + r.message + '. El nuevo total del pedido es ' + Number(r.new_total).toLocaleString('es-CO') + ' pesos.');
      } else {
        addLog('error', r.error);
        speak('Error: ' + r.error);
      }
    }, 'json').fail(function() { speak('Error de conexion.'); setState('waiting'); });
  }

  function executarAprobar(budgetId) {
    setState('thinking');
    $.post(base_url + 'sisvent/dashboard/approveBudget', {
      budget_id: budgetId
    }, function(r) {
      if (r.success) {
        addLog('assistant', r.message + ' — Cliente: ' + r.client_name + ' — Total: $' + Number(r.total).toLocaleString('es-CO'));
        speak('Listo. Presupuesto ' + budgetId + ' aprobado. Se creo la factura numero ' + r.invoice_id + ' por ' + Number(r.total).toLocaleString('es-CO') + ' pesos para ' + r.client_name + '.');
      } else {
        addLog('error', r.error);
        speak('No se pudo aprobar: ' + r.error);
      }
    }, 'json').fail(function() { speak('Error de conexion.'); setState('waiting'); });
  }

  // === ENVIAR CARTA POR VOZ ===
  function tryLetter(txt) {
    var norm = txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    // Detectar intención de carta/email
    var letterPatterns = ['hazme una carta', 'hacer una carta', 'envia una carta', 'escribir una carta', 'redacta una carta', 'mandar una carta', 'enviar un correo', 'envia un correo', 'mandar un correo', 'manda un correo'];
    var isLetter = false;
    for (var i = 0; i < letterPatterns.length; i++) {
      if (norm.indexOf(letterPatterns[i]) !== -1) { isLetter = true; break; }
    }
    if (!isLetter) return false;

    // Extraer datos con patrones flexibles
    var toName = '', toEmail = '', company = '', body = '', subject = '';

    // Nombre: "a Juan Perez", "para Juan Perez", "dirigida a Juan Perez"
    var nameMatch = norm.match(/(?:a|para|dirigida a)\s+([a-z]+(?:\s+[a-z]+){0,3}?)(?:\s+de la empresa|\s+de\s+|\s+al correo|\s+diciendo|\s+que diga|\s+con el mensaje|\s+el asunto|$)/);
    if (nameMatch) toName = nameMatch[1].trim();

    // Email: buscar patron de email
    var emailMatch = txt.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
    if (emailMatch) toEmail = emailMatch[0];

    // Empresa: "de la empresa X"
    var compMatch = norm.match(/(?:de la empresa|empresa)\s+([a-z0-9]+(?:\s+[a-z0-9]+){0,3})/);
    if (compMatch) company = compMatch[1].trim();

    // Contenido: "diciendo que...", "que diga...", "con el mensaje..."
    var bodyMatch = norm.match(/(?:diciendo|que diga|con el mensaje|el contenido es|dile que|informandole que|comunicandole que)\s+(.+)/);
    if (bodyMatch) body = bodyMatch[1].trim();

    // Asunto: "con asunto...", "el asunto es..."
    var subMatch = norm.match(/(?:con asunto|asunto|el asunto es|asunto es)\s+([^,]+)/);
    if (subMatch) subject = subMatch[1].trim();

    // Si no tenemos suficiente info, pedir datos interactivamente
    if (!toName && !toEmail && !body) return false;

    setState('thinking');

    // Si falta email, preguntar
    if (!toEmail) {
      addLog('assistant', 'Entendido, voy a preparar la carta para ' + (toName || 'el destinatario') + '. Necesito el correo electronico. Puedes escribirlo en el campo de abajo.');

      // Mostrar campo de email temporal
      var emailInput = document.createElement('div');
      emailInput.style.cssText = 'padding:8px 14px; border-top:1px solid #f3f4f6;';
      emailInput.innerHTML = '<div style="display:flex;gap:6px;">'
        + '<input id="letterEmail" type="email" placeholder="correo@ejemplo.com" style="flex:1;padding:8px;font-size:12px;border:1px solid #e5e7eb;border-radius:6px;outline:none;">'
        + '<button id="letterSend" style="padding:8px 12px;background:#E63946;color:white;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Enviar</button>'
        + '</div>';
      logEl.parentElement.appendChild(emailInput);

      speak('Necesito el correo electronico del destinatario. Escribelo en el campo que aparece abajo.');

      document.getElementById('letterSend').addEventListener('click', function() {
        var email = document.getElementById('letterEmail').value.trim();
        if (!email) return;
        emailInput.remove();
        sendTheLetter(toName, email, company, subject || 'Carta de Ledxury', body);
      });

      return true;
    }

    // Tenemos todo, enviar
    sendTheLetter(toName, toEmail, company, subject || 'Carta de Ledxury', body);
    return true;
  }

  function sendTheLetter(toName, toEmail, company, subject, body) {
    setState('thinking');
    addLog('assistant', 'Generando carta para ' + toName + ' y enviando a ' + toEmail + '...');

    // Capitalizar nombre
    toName = toName.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    // Capitalizar primera letra del body
    if (body) body = body.charAt(0).toUpperCase() + body.slice(1);

    $.post(base_url + 'sisvent/dashboard/sendLetter', {
      to_name: toName,
      to_email: toEmail,
      subject: subject,
      body: body,
      company: company
    }, function(r) {
      if (r.success) {
        addLog('assistant', 'Carta enviada exitosamente a ' + toEmail);
        speak('Listo, la carta fue enviada a ' + toName + ' al correo ' + toEmail + '.');
      } else {
        addLog('error', r.error || 'Error enviando carta');
        if (r.pdf) {
          addLog('assistant', 'El PDF se genero pero fallo el envio. <a href="' + base_url + r.pdf + '" target="_blank" style="color:#3b82f6;">Descargar PDF</a>');
          speak('Genere la carta pero no pude enviar el correo. Puedes descargar el PDF desde el panel.');
        } else {
          speak('Hubo un error generando la carta. ' + (r.error || ''));
        }
      }
    }, 'json').fail(function() {
      addLog('error', 'Error de conexion');
      speak('Error de conexion al enviar la carta.');
      setState('waiting');
    });
  }

  // === ASK GERMAM ===
  function askGerMAM(question) {
    if (isProcessing) return;
    isProcessing = true;
    setState('thinking');
    liveText.textContent = '';

    $.post(base_url + 'sisvent/admin/aiassistant/ask', {
      question: question,
      conversationId: convId || ''
    }, function(r) {
      isProcessing = false;
      if (r.success && r.response) {
        convId = r.conversationId;
        var short = r.response.length > 250 ? r.response.substring(0, 250) + '...' : r.response;
        addLog('assistant', short);
        speak(r.response);
      } else {
        addLog('error', r.error || 'Sin respuesta');
        setState('waiting');
      }
    }, 'json').fail(function() {
      isProcessing = false;
      addLog('error', 'Error de conexion');
      setState('waiting');
    });
  }

  // === SPEECH RECOGNITION ===
  var wakeWords = ['germam', 'german', 'germa', 'herma', 'ger mam'];

  rec.onresult = function(e) {
    if (isSpeaking || isProcessing) return;

    var last = e.results[e.results.length - 1];
    var raw = last[0].transcript.trim().toLowerCase();
    var txt = raw.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // Mostrar texto en tiempo real
    liveText.textContent = raw;

    if (!last.isFinal) return; // Solo procesar resultados finales

    // Detectar wake word en cualquier parte del texto
    var wakeIdx = -1, wakeLen = 0;
    for (var i = 0; i < wakeWords.length; i++) {
      var pos = txt.indexOf(wakeWords[i]);
      if (pos !== -1) { wakeIdx = pos; wakeLen = wakeWords[i].length; break; }
    }

    // Modo conversación: no necesita wake word
    if (isConversation && wakeIdx === -1 && txt.length > 3) {
      clearTimeout(convTimer);
      isConversation = false;
      setState('heard');
      addLog('user', raw);
      if (!tryBudgetAction(txt) && !tryNavigate(txt) && !trySendMessage(txt) && !tryNews(txt) && !tryLetter(txt)) {
        setTimeout(function() { askGerMAM(raw); }, 200);
      }
      return;
    }

    if (wakeIdx === -1) {
      // No dijo GerMAM y no está en conversación — ignorar
      liveText.textContent = '';
      return;
    }

    // Extraer pregunta después del wake word
    var pregunta = txt.substring(wakeIdx + wakeLen).trim();
    pregunta = pregunta.replace(/^(hola|oye|hey|por favor|dime|me puedes)\s*/i, '').trim();

    clearTimeout(convTimer);
    isConversation = false;

    if (pregunta.length > 3) {
      setState('heard');
      addLog('user', pregunta);
      // Intentar navegación, mensaje, noticias, luego AI
      if (!tryBudgetAction(pregunta) && !tryNavigate(pregunta) && !trySendMessage(pregunta) && !tryNews(pregunta) && !tryLetter(pregunta)) {
        setTimeout(function() { askGerMAM(pregunta); }, 200);
      }
    } else {
      setState('heard');
      speak('Dime, <?= $voiceUserName ?>.', function() {
        // Modo conversación se activa en onDone de speak
      });
    }
  };

  rec.onerror = function(e) {
    if (e.error === 'no-speech' || e.error === 'aborted') return;
    if (e.error === 'not-allowed') { addLog('error', 'Permiso de microfono denegado.'); stopMic(); }
  };

  rec.onend = function() {
    if (isOn) { try { rec.start(); } catch(e) {} }
  };

  synth.onvoiceschanged = function() { synth.getVoices(); };

  // Saludo automático al iniciar sesión (solo una vez)
  <?php if ($shouldGreet): ?>
  (function() {
    var todasLasFrases = [
      // Lunes — Motivación y arranque
      {texto: 'El exito no es la clave de la felicidad. La felicidad es la clave del exito.', autor: 'Albert Schweitzer'},
      {texto: 'Cree que puedes y ya estaras a medio camino.', autor: 'Theodore Roosevelt'},
      {texto: 'La unica forma de hacer un gran trabajo es amar lo que haces.', autor: 'Steve Jobs'},
      {texto: 'El secreto de salir adelante es comenzar.', autor: 'Mark Twain'},
      {texto: 'Hoy es un buen dia para tener un gran dia.', autor: 'Anonimo'},
      {texto: 'Tu actitud determina tu direccion.', autor: 'Anonimo'},
      {texto: 'Empieza donde estas, usa lo que tienes, haz lo que puedas.', autor: 'Arthur Ashe'},
      // Martes — Perseverancia
      {texto: 'No cuentes los dias, haz que los dias cuenten.', autor: 'Muhammad Ali'},
      {texto: 'La persistencia es el camino del exito.', autor: 'Charles Chaplin'},
      {texto: 'No importa lo lento que vayas, siempre y cuando no te detengas.', autor: 'Confucio'},
      {texto: 'El exito es ir de fracaso en fracaso sin perder el entusiasmo.', autor: 'Winston Churchill'},
      {texto: 'Caerse esta permitido, levantarse es obligatorio.', autor: 'Proverbio ruso'},
      {texto: 'Los grandes logros requieren tiempo y paciencia.', autor: 'Anonimo'},
      {texto: 'La disciplina es el puente entre metas y logros.', autor: 'Jim Rohn'},
      // Miércoles — Superación
      {texto: 'Lo que no te mata te hace mas fuerte.', autor: 'Friedrich Nietzsche'},
      {texto: 'El futuro pertenece a quienes creen en la belleza de sus suenos.', autor: 'Eleanor Roosevelt'},
      {texto: 'Nunca es tarde para ser lo que podrias haber sido.', autor: 'George Eliot'},
      {texto: 'La vida comienza donde termina tu zona de confort.', autor: 'Neale Donald Walsch'},
      {texto: 'No busques el momento perfecto, toma el momento y hazlo perfecto.', autor: 'Anonimo'},
      {texto: 'El dolor que sientes hoy sera la fuerza que sentiras manana.', autor: 'Anonimo'},
      {texto: 'Soy el amo de mi destino, soy el capitan de mi alma.', autor: 'William Ernest Henley'},
      // Jueves — Trabajo en equipo y liderazgo
      {texto: 'Solos podemos hacer poco, juntos podemos hacer mucho.', autor: 'Helen Keller'},
      {texto: 'El talento gana partidos, pero el trabajo en equipo gana campeonatos.', autor: 'Michael Jordan'},
      {texto: 'Un lider es aquel que conoce el camino, anda el camino y muestra el camino.', autor: 'John Maxwell'},
      {texto: 'La fuerza del equipo esta en cada miembro, y la fuerza de cada miembro esta en el equipo.', autor: 'Phil Jackson'},
      {texto: 'Rodéate de personas que te impulsen a ser mejor.', autor: 'Anonimo'},
      {texto: 'El mejor lider es aquel que sabe servir.', autor: 'Lao Tzu'},
      {texto: 'Ninguno de nosotros es tan inteligente como todos nosotros juntos.', autor: 'Ken Blanchard'},
      // Viernes — Logro y gratitud
      {texto: 'La mejor venganza es un exito masivo.', autor: 'Frank Sinatra'},
      {texto: 'Trabaja duro en silencio y deja que tu exito haga el ruido.', autor: 'Frank Ocean'},
      {texto: 'Cada dia es una nueva oportunidad para cambiar tu vida.', autor: 'Paulo Coelho'},
      {texto: 'La gratitud convierte lo que tenemos en suficiente.', autor: 'Melody Beattie'},
      {texto: 'Celebra cada pequeno triunfo, te acerca al grande.', autor: 'Anonimo'},
      {texto: 'El exito no es el final, el fracaso no es fatal, lo que cuenta es el coraje para continuar.', autor: 'Winston Churchill'},
      {texto: 'Haz de cada dia tu obra maestra.', autor: 'John Wooden'},
      // Sábado — Creatividad e innovación
      {texto: 'La innovacion distingue al lider del seguidor.', autor: 'Steve Jobs'},
      {texto: 'La creatividad es la inteligencia divirtiendose.', autor: 'Albert Einstein'},
      {texto: 'El que no arriesga, no gana.', autor: 'Proverbio popular'},
      {texto: 'Piensa diferente.', autor: 'Apple'},
      {texto: 'Las ideas son el comienzo de todos los logros.', autor: 'Napoleon Hill'},
      {texto: 'Si puedes sonarlo, puedes lograrlo.', autor: 'Walt Disney'},
      {texto: 'La imaginacion es mas importante que el conocimiento.', autor: 'Albert Einstein'},
      // Domingo — Reflexión y descanso
      {texto: 'La vida es lo que pasa mientras estas ocupado haciendo otros planes.', autor: 'John Lennon'},
      {texto: 'El descanso es parte del trabajo.', autor: 'Ovidio'},
      {texto: 'A veces la cosa mas productiva que puedes hacer es relajarte.', autor: 'Mark Black'},
      {texto: 'Tu cuerpo es un templo, cuidalo.', autor: 'Anonimo'},
      {texto: 'La paz interior comienza cuando dejas de permitir que otros controlen tus emociones.', autor: 'Anonimo'},
      {texto: 'Descansa, pero nunca te rindas.', autor: 'Anonimo'},
      {texto: 'Recarga energias hoy para conquistar la semana.', autor: 'Anonimo'},
    ];

    // Seleccionar frase según el día de la semana (7 frases por día, rota cada semana)
    var diaSemana = new Date().getDay(); // 0=dom, 1=lun, ..., 6=sab
    var semanaAnio = Math.floor((Date.now() - new Date(new Date().getFullYear(),0,1)) / 604800000);
    var inicio = diaSemana * 7;
    var frasesHoy = todasLasFrases.slice(inicio, inicio + 7);
    var fraseIdx = semanaAnio % frasesHoy.length;
    var frases = frasesHoy;
    // Cada semana muestra una frase diferente del mismo día
    var frase = frasesHoy[fraseIdx];
    var hora = new Date().getHours();
    var saludo = hora < 12 ? 'Buenos dias' : (hora < 18 ? 'Buenas tardes' : 'Buenas noches');
    var cafe = hora < 14 ? ' No olvides tomarte un buen cafe.' : ' No olvides hidratarte.';

    var mensaje = saludo + ', <?= $voiceUserName ?>. Bienvenido a Ledxury. '
      + 'Hoy sera un gran dia.' + cafe + ' '
      + 'Recuerda: ' + frase.texto + ' ' + frase.autor + '. '
      <?php if ($autoActivateVoice): ?>
      + 'Tu microfono ya esta activo, solo di GerMAM cuando me necesites.';
      <?php else: ?>
      + 'Si quieres que conversemos, activa el microfono y di GerMAM.';
      <?php endif; ?>

    setTimeout(function() {
      panel.style.display = 'block';
      addLog('assistant', mensaje);
      speak(mensaje, function() {
        <?php if ($autoActivateVoice): ?>
        // Auto-activar micrófono para logística y adminbots
        if (!isOn) startMic();
        <?php endif; ?>
      });
    }, 1500);
  })();
  <?php endif; ?>

  // Auto-activar mic silenciosamente (sin abrir panel ni saludar)
  <?php if ($autoActivateVoice): ?>
  setTimeout(function() {
    if (!isOn) {
      try { rec.start(); } catch(e) {}
      isOn = true;
      startBtn.style.display = 'none';
      stopBtn.style.display = 'block';
      pulseEl.style.display = 'block';
      setState('waiting');
    }
  }, 2000);
  <?php endif; ?>
})();
</script>
