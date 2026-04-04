<!-- GerMAM Voice Assistant Widget -->
<div id="voiceWidget" style="position:fixed; bottom:24px; right:24px; z-index:9999;">
  <!-- Botón flotante -->
  <button id="voiceToggle" title="Asistente de voz GerMAM" style="
    width:56px; height:56px; border-radius:50%; border:none; cursor:pointer;
    background: linear-gradient(135deg, #1a1a2e, #E63946);
    box-shadow: 0 4px 20px rgba(230,57,70,0.3);
    display:flex; align-items:center; justify-content:center;
    transition: all 0.3s ease; position:relative; overflow:visible;
  ">
    <svg id="voiceIconMic" width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
    <svg id="voiceIconWave" width="24" height="24" fill="white" viewBox="0 0 24 24" style="display:none;"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
    <!-- Pulse ring -->
    <span id="voicePulse" style="
      display:none; position:absolute; top:-4px; left:-4px; right:-4px; bottom:-4px;
      border-radius:50%; border:2px solid #E63946; animation: voicePulseAnim 1.5s infinite;
    "></span>
  </button>

  <!-- Panel de estado -->
  <div id="voicePanel" style="
    display:none; position:absolute; bottom:68px; right:0;
    width:320px; background:white; border-radius:16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15); border:1px solid #e5e7eb;
    overflow:hidden;
  ">
    <div style="background:linear-gradient(135deg, #1a1a2e, #16213e); padding:16px; color:white;">
      <div style="display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center;">
          <div id="voiceOrb" style="
            width:32px; height:32px; border-radius:50%;
            background: radial-gradient(circle, #E63946, #8b1a22);
            margin-right:10px; transition: all 0.3s;
          "></div>
          <div>
            <p style="font-size:13px; font-weight:700; margin:0;">GerMAM</p>
            <p id="voiceStatus" style="font-size:11px; color:#9ca3af; margin:0;">Inactivo</p>
          </div>
        </div>
        <button id="voiceClose" style="background:none; border:none; color:#9ca3af; cursor:pointer; font-size:18px;">&times;</button>
      </div>
    </div>
    <div style="padding:12px 16px; max-height:250px; overflow-y:auto;">
      <div id="voiceLog" style="font-size:12px; color:#6b7280;">
        <p style="text-align:center; color:#9ca3af; margin:8px 0;">Di <strong>"Hola GerMAM"</strong> para activar</p>
      </div>
    </div>
    <div style="padding:8px 16px 12px; border-top:1px solid #f3f4f6; display:flex; gap:8px;">
      <button id="voiceStartBtn" style="
        flex:1; padding:8px; font-size:12px; font-weight:600; border:none; border-radius:8px; cursor:pointer;
        background:#E63946; color:white; transition:background 0.2s;
      ">Activar escucha</button>
      <button id="voiceStopBtn" style="
        display:none; flex:1; padding:8px; font-size:12px; font-weight:600; border:none; border-radius:8px; cursor:pointer;
        background:#6b7280; color:white;
      ">Detener</button>
    </div>
  </div>
</div>

<style>
@keyframes voicePulseAnim {
  0% { transform:scale(1); opacity:0.8; }
  100% { transform:scale(1.5); opacity:0; }
}
@keyframes voiceOrbPulse {
  0%,100% { box-shadow:0 0 8px #E63946; }
  50% { box-shadow:0 0 24px #E63946, 0 0 48px rgba(230,57,70,0.3); }
}
#voiceOrb.listening { animation: voiceOrbPulse 1.5s infinite; }
#voiceOrb.thinking { background: radial-gradient(circle, #3b82f6, #1e40af) !important; animation: voiceOrbPulse 0.8s infinite; }
#voiceOrb.speaking { background: radial-gradient(circle, #22c55e, #15803d) !important; animation: voiceOrbPulse 1s infinite; }
</style>

<script>
(function() {
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    document.getElementById('voiceWidget').style.display = 'none';
    return;
  }

  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  var recognition = new SpeechRecognition();
  recognition.lang = 'es-CO';
  recognition.continuous = true;
  recognition.interimResults = false;

  var synth = window.speechSynthesis;
  var isListening = false;
  var isAwake = false;
  var isProcessing = false;
  var conversationId = null;
  var silenceTimer = null;

  var panel = document.getElementById('voicePanel');
  var toggle = document.getElementById('voiceToggle');
  var log = document.getElementById('voiceLog');
  var status = document.getElementById('voiceStatus');
  var orb = document.getElementById('voiceOrb');
  var pulse = document.getElementById('voicePulse');
  var startBtn = document.getElementById('voiceStartBtn');
  var stopBtn = document.getElementById('voiceStopBtn');
  var iconMic = document.getElementById('voiceIconMic');
  var iconWave = document.getElementById('voiceIconWave');

  // Toggle panel
  toggle.addEventListener('click', function() {
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  });
  document.getElementById('voiceClose').addEventListener('click', function() {
    panel.style.display = 'none';
  });

  // Start listening
  startBtn.addEventListener('click', function() {
    startListening();
  });
  stopBtn.addEventListener('click', function() {
    stopListening();
  });

  function startListening() {
    try {
      recognition.start();
      isListening = true;
      startBtn.style.display = 'none';
      stopBtn.style.display = 'block';
      setStatus('Escuchando...', 'listening');
      pulse.style.display = 'block';
      iconMic.style.display = 'none';
      iconWave.style.display = 'block';
      addLog('system', 'Escucha activada. Di "Hola GerMAM" para comenzar.');
    } catch(e) {
      addLog('error', 'Error al iniciar microfono: ' + e.message);
    }
  }

  function stopListening() {
    recognition.stop();
    isListening = false;
    isAwake = false;
    startBtn.style.display = 'block';
    stopBtn.style.display = 'none';
    setStatus('Inactivo', '');
    pulse.style.display = 'none';
    iconMic.style.display = 'block';
    iconWave.style.display = 'none';
    synth.cancel();
    clearTimeout(silenceTimer);
  }

  function setStatus(text, state) {
    status.textContent = text;
    orb.className = '';
    if (state) orb.classList.add(state);
  }

  function addLog(type, text) {
    var el = document.createElement('div');
    el.style.marginBottom = '8px';
    el.style.padding = '6px 10px';
    el.style.borderRadius = '8px';
    el.style.fontSize = '12px';
    el.style.lineHeight = '1.4';

    if (type === 'user') {
      el.style.background = '#eff6ff';
      el.style.color = '#1e40af';
      el.innerHTML = '<strong>Tu:</strong> ' + text;
    } else if (type === 'assistant') {
      el.style.background = '#f0fdf4';
      el.style.color = '#166534';
      el.innerHTML = '<strong>GerMAM:</strong> ' + text;
    } else if (type === 'error') {
      el.style.background = '#fef2f2';
      el.style.color = '#991b1b';
      el.innerHTML = text;
    } else {
      el.style.color = '#9ca3af';
      el.style.textAlign = 'center';
      el.style.fontStyle = 'italic';
      el.innerHTML = text;
    }

    log.appendChild(el);
    log.scrollTop = log.scrollHeight;
  }

  var isSpeaking = false;
  var currentAudio = null;

  function speak(text) {
    synth.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }

    // Limpiar markdown para voz
    var clean = text.replace(/\*\*/g, '').replace(/\*/g, '').replace(/#{1,6}\s/g, '')
      .replace(/```[\s\S]*?```/g, '').replace(/`[^`]+`/g, '').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
      .replace(/\n{2,}/g, '. ').replace(/\n/g, '. ').replace(/\|[^\n]+/g, '')
      .replace(/\.\s*\./g, '.').replace(/\s+/g, ' ').trim();

    if (clean.length > 400) clean = clean.substring(0, 400) + '. Mas detalles en pantalla.';

    // Usar ElevenLabs API
    isSpeaking = true;
    setStatus('Hablando...', 'speaking');

    fetch('https://api.elevenlabs.io/v1/text-to-speech/onwK4e9ZLuTAKqWW03F9?output_format=mp3_44100_128', {
      method: 'POST',
      headers: {
        'xi-api-key': 'sk_563cc7e05cde5073eddf8ee585b81fd44d66d54e26fae5a2',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        text: clean,
        model_id: 'eleven_multilingual_v2',
        voice_settings: { stability: 0.5, similarity_boost: 0.75 }
      })
    })
    .then(function(response) {
      if (!response.ok) throw new Error('ElevenLabs HTTP ' + response.status);
      return response.blob();
    })
    .then(function(blob) {
      var url = URL.createObjectURL(blob);
      currentAudio = new Audio(url);
      currentAudio.onended = function() {
        isSpeaking = false;
        isAwake = false;
        setStatus('Esperando "GerMAM"...', 'listening');
        URL.revokeObjectURL(url);
        currentAudio = null;
      };
      currentAudio.play();
    })
    .catch(function(err) {
      console.log('ElevenLabs error, fallback to browser voice:', err);
      // Fallback a voz del navegador
      var utterance = new SpeechSynthesisUtterance(clean);
      utterance.lang = 'es-CO';
      utterance.rate = 0.95;
      utterance.pitch = 0.9;
      utterance.onstart = function() { isSpeaking = true; setStatus('Hablando...', 'speaking'); };
      utterance.onend = function() { isSpeaking = false; isAwake = false; setStatus('Esperando "GerMAM"...', 'listening'); };
      synth.speak(utterance);
    });
  }

  function askGerMAM(question) {
    if (isProcessing) return;
    isProcessing = true;
    setStatus('Pensando...', 'thinking');

    $.post(base_url + 'sisvent/admin/aiassistant/ask', {
      question: question,
      conversationId: conversationId || ''
    }, function(r) {
      isProcessing = false;
      if (r.success && r.response) {
        conversationId = r.conversationId;
        addLog('assistant', r.response.substring(0, 300) + (r.response.length > 300 ? '...' : ''));
        speak(r.response); // speak() maneja pausa/reanudación del mic
      } else {
        addLog('error', 'Error: ' + (r.error || 'Sin respuesta'));
        setStatus('Esperando "GerMAM"...', 'listening');
      }
    }, 'json').fail(function() {
      isProcessing = false;
      addLog('error', 'Error de conexion');
      setStatus('Esperando "GerMAM"...', 'listening');
    });
  }

  // Speech recognition events
  recognition.onresult = function(event) {
    var last = event.results[event.results.length - 1];
    if (!last.isFinal) return;

    if (isSpeaking) return; // Ignorar mientras GerMAM habla

    var transcriptRaw = last[0].transcript.trim().toLowerCase();
    var transcript = transcriptRaw.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    console.log('Escuche:', transcriptRaw, '->', transcript);

    if (!isAwake) {
      // Check for wake word (sin acentos)
      var wakeWords = ['hola germam', 'hola german', 'ola germam', 'ola german', 'hola ger mam', 'oye germam', 'oye german', 'hola germa', 'hola herma'];
      var detected = false;
      for (var i = 0; i < wakeWords.length; i++) {
        if (transcript.indexOf(wakeWords[i]) !== -1) { detected = true; break; }
      }

      if (detected) {
        // Extraer pregunta después del wake word (ej: "germam cuantas ventas hay")
        var pregunta = '';
        var wakePatterns = ['hola germam', 'hola german', 'ola germam', 'ola german', 'oye germam', 'oye german', 'germam', 'german'];
        for (var w = 0; w < wakePatterns.length; w++) {
          var idx = transcript.indexOf(wakePatterns[w]);
          if (idx !== -1) {
            pregunta = transcript.substring(idx + wakePatterns[w].length).trim();
            break;
          }
        }

        if (pregunta.length > 3) {
          // Wake word + pregunta en la misma frase: responder directo
          addLog('user', pregunta);
          askGerMAM(pregunta);
        } else {
          // Solo wake word: activar y esperar pregunta
          isAwake = true;
          addLog('system', 'Activado!');
          speak('Dime.');
          clearTimeout(silenceTimer);
          silenceTimer = setTimeout(function() {
            isAwake = false;
            setStatus('Esperando "GerMAM"...', 'listening');
          }, 10000);
        }
        return;
      }
    } else {
      // GerMAM está activo, procesar la pregunta
      if (transcript.length < 3) return;

      // Despedida
      var byeWords = ['adios', 'gracias', 'chao', 'hasta luego'];
      var isBye = false;
      for (var i = 0; i < byeWords.length; i++) {
        if (transcript.indexOf(byeWords[i]) !== -1) { isBye = true; break; }
      }
      if (isBye) {
        isAwake = false;
        addLog('user', transcript);
        speak('Listo.');
        conversationId = null;
        return;
      }

      isAwake = false; // Volver a modo espera después de procesar
      addLog('user', transcript);
      askGerMAM(transcript);
    }
  };

  recognition.onerror = function(event) {
    if (event.error === 'no-speech') return; // Normal, just silence
    if (event.error === 'aborted') return;
    console.log('Speech error:', event.error);
    if (event.error === 'not-allowed') {
      addLog('error', 'Permiso de microfono denegado. Habilita el microfono en tu navegador.');
      stopListening();
    }
  };

  recognition.onend = function() {
    // Siempre reiniciar si está en modo escucha
    if (isListening) {
      try { recognition.start(); } catch(e) {}
    }
  };

  // Load voices
  synth.onvoiceschanged = function() { synth.getVoices(); };
})();
</script>
