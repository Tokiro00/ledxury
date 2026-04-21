<!-- Screensaver LED - se activa tras 30s de inactividad -->
<div id="screensaverOverlay" style="
  display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  z-index:9990; background:#0a0e1a; opacity:0; transition: opacity 1.5s ease;
  cursor:pointer;
">
  <canvas id="ssCanvas" style="position:absolute;top:0;left:0;width:100%;height:100%;display:block;"></canvas>
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
    <h1 style="font-size:56px;font-weight:900;color:white;text-shadow:0 0 30px rgba(230,57,70,0.6),0 0 60px rgba(230,57,70,0.3);letter-spacing:2px;margin:0;">LEDXURY</h1>
    <p style="color:#6b7280;font-size:12px;margin-top:8px;letter-spacing:3px;text-transform:uppercase;">Iluminacion LED de Alta Tecnologia</p>
  </div>
  <p style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:#4b5563;font-size:11px;">Mueve el mouse o presiona una tecla para continuar</p>
</div>

<script>
(function() {
  var overlay = document.getElementById('screensaverOverlay');
  var canvas = document.getElementById('ssCanvas');
  if (!overlay || !canvas) return;

  var ctx = canvas.getContext('2d');
  var W, H, particles = [], animFrame = null;
  var isActive = false;
  var idleTimer = null;
  var IDLE_DELAY = 30000;

  var ledColors = [
    {r:230,g:57,b:70},{r:59,g:130,b:246},{r:34,g:197,b:94},{r:245,g:158,b:11},
    {r:168,g:85,b:247},{r:255,g:255,b:255},{r:56,g:189,b:248},{r:244,g:114,b:182}
  ];

  function initParticles() {
    particles = [];
    for (var i = 0; i < 50; i++) {
      var c = ledColors[Math.floor(Math.random() * ledColors.length)];
      particles.push({
        x:Math.random()*2000, y:Math.random()*1200, z:Math.random()*600+100,
        r:c.r, g:c.g, b:c.b, size:Math.random()*3+1.5,
        vx:(Math.random()-0.5)*0.4, vy:(Math.random()-0.5)*0.3, vz:(Math.random()-0.5)*0.2,
        pulse:Math.random()*Math.PI*2, pulseSpeed:Math.random()*0.03+0.01
      });
    }
  }

  function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function project(p) {
    var s = 800 / (800 + p.z);
    return { x: W/2 + (p.x-1000)*s, y: H/2 + (p.y-600)*s, s:s };
  }

  function animate() {
    ctx.fillStyle = 'rgba(10,14,26,0.12)';
    ctx.fillRect(0,0,W,H);

    var proj = [];
    for (var i=0;i<particles.length;i++) {
      var p = particles[i];
      p.x+=p.vx; p.y+=p.vy; p.z+=p.vz; p.pulse+=p.pulseSpeed;
      if(p.x<0||p.x>2000) p.vx*=-1;
      if(p.y<0||p.y>1200) p.vy*=-1;
      if(p.z<50||p.z>700) p.vz*=-1;
      var pr=project(p);
      proj.push({idx:i,x:pr.x,y:pr.y,s:pr.s,z:p.z});
    }
    proj.sort(function(a,b){return b.z-a.z;});

    // Connections
    for(var i=0;i<proj.length;i++){
      for(var j=i+1;j<proj.length;j++){
        var dx=proj[i].x-proj[j].x,dy=proj[i].y-proj[j].y,d=Math.sqrt(dx*dx+dy*dy);
        if(d<100){
          var pi=particles[proj[i].idx];
          ctx.beginPath();ctx.moveTo(proj[i].x,proj[i].y);ctx.lineTo(proj[j].x,proj[j].y);
          ctx.strokeStyle='rgba('+pi.r+','+pi.g+','+pi.b+','+(0.08*(1-d/100))+')';
          ctx.lineWidth=0.5;ctx.stroke();
        }
      }
    }

    // Particles
    for(var i=0;i<proj.length;i++){
      var pr=proj[i],p=particles[pr.idx];
      var br=0.4+Math.sin(p.pulse)*0.3, sz=p.size*pr.s;
      // Glow
      ctx.beginPath();ctx.arc(pr.x,pr.y,sz*8,0,Math.PI*2);
      ctx.fillStyle='rgba('+p.r+','+p.g+','+p.b+','+(br*0.2)+')';ctx.fill();
      // Core
      ctx.beginPath();ctx.arc(pr.x,pr.y,sz,0,Math.PI*2);
      ctx.fillStyle='rgba('+p.r+','+p.g+','+p.b+','+(br+0.3)+')';ctx.fill();
      // Center
      ctx.beginPath();ctx.arc(pr.x,pr.y,sz*0.4,0,Math.PI*2);
      ctx.fillStyle='rgba(255,255,255,'+(br*0.6)+')';ctx.fill();
    }

    if(isActive) animFrame=requestAnimationFrame(animate);
  }

  function startScreensaver() {
    if(isActive) return;
    isActive = true;
    resize();
    initParticles();
    overlay.style.display = 'block';
    setTimeout(function(){ overlay.style.opacity = '1'; }, 50);
    animate();
  }

  function stopScreensaver() {
    if(!isActive) return;
    isActive = false;
    overlay.style.opacity = '0';
    setTimeout(function(){
      overlay.style.display = 'none';
      if(animFrame) cancelAnimationFrame(animFrame);
      ctx.clearRect(0,0,W,H);
    }, 500);
    resetIdle();
  }

  function resetIdle() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(startScreensaver, IDLE_DELAY);
  }

  // User activity stops screensaver
  ['mousemove','mousedown','keydown','scroll','touchstart','click'].forEach(function(evt) {
    document.addEventListener(evt, function() {
      if(isActive) stopScreensaver();
      else resetIdle();
    }, {passive:true});
  });

  overlay.addEventListener('click', stopScreensaver);
  window.addEventListener('resize', function(){ if(isActive) resize(); });

  // Start idle timer
  resetIdle();
})();
</script>
