<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Ledxury Dashboard</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
  #hero3d { position: relative; min-height: calc(100vh - 64px); overflow: hidden; background: #ffffff; }
  #hero3d canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: block; }
  .hero-content { position: relative; z-index: 10; }
  .glass-card {
    background: rgba(255,255,255,0.9);
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  .glass-card:hover { border-color: #E63946; box-shadow: 0 4px 12px rgba(230,57,70,0.1); }
  .glow-text { color: #1a1a2e; text-shadow: none; }
  .kpi-value { font-family: 'Inter', sans-serif; font-variant-numeric: tabular-nums; }
  #searchResults a:hover { background: #f9fafb; }
</style>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div id="hero3d">
        <!-- Canvas 3D -->
        <canvas id="ledCanvas"></canvas>

        <div class="hero-content flex flex-col items-center justify-center" style="min-height: calc(100vh - 64px); padding: 40px 24px;">

          <!-- Logo + Texto -->
          <div class="text-center mb-8">
            <h1 class="text-6xl font-extrabold glow-text tracking-tight mb-2">LEDXURY</h1>
            <p class="text-gray-400 text-sm tracking-widest uppercase">Iluminacion LED de Alta Tecnologia</p>
          </div>

          <!-- KPI Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5 w-full max-w-3xl">
            <!-- Hoy -->
            <div class="glass-card p-5 text-center">
              <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Ventas Hoy</p>
              <p class="text-3xl font-bold text-gray-800 kpi-value"><?= isset($bot_ventas_hoy) ? $bot_ventas_hoy : 0 ?></p>
              <p class="text-sm font-semibold mt-1" style="color: #E63946;">$<?= number_format(isset($bot_total_hoy) ? $bot_total_hoy : 0, 0, ',', '.') ?></p>
            </div>
            <!-- Mes -->
            <div class="glass-card p-5 text-center">
              <p class="text-xs text-gray-400 uppercase tracking-widest mb-2"><?= date('F') ?> <?= date('Y') ?></p>
              <p class="text-3xl font-bold text-gray-800 kpi-value"><?= isset($bot_ventas_mes) ? number_format($bot_ventas_mes, 0, ',', '.') : 0 ?></p>
              <p class="text-sm font-semibold mt-1" style="color: #22c55e;">$<?= number_format(isset($bot_total_mes) ? $bot_total_mes : 0, 0, ',', '.') ?></p>
            </div>
            <!-- Año -->
            <div class="glass-card p-5 text-center">
              <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Acumulado <?= date('Y') ?></p>
              <p class="text-3xl font-bold text-gray-800 kpi-value"><?= isset($bot_ventas_anio) ? number_format($bot_ventas_anio, 0, ',', '.') : 0 ?></p>
              <p class="text-sm font-semibold mt-1" style="color: #3b82f6;">$<?= number_format(isset($bot_total_anio) ? $bot_total_anio : 0, 0, ',', '.') ?></p>
            </div>
          </div>

          <!-- Accesos rápidos -->
          <div class="flex items-center space-x-3 mt-8">
            <a href="<?= base_url() ?>sisvent/admin/bots" class="glass-card px-4 py-2 text-xs text-gray-500 hover:text-gray-800 hover:border-green-500 transition-colors flex items-center">
              <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
              Bots WhatsApp
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/ads" class="glass-card px-4 py-2 text-xs text-gray-500 hover:text-gray-800 hover:border-blue-500 transition-colors flex items-center">
              <svg class="w-4 h-4 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/></svg>
              Meta Ads
            </a>
            <a href="<?= base_url() ?>sisvent/admin/bots/report/0" class="glass-card px-4 py-2 text-xs text-gray-500 hover:text-gray-800 hover:border-orange-500 transition-colors flex items-center">
              <svg class="w-4 h-4 mr-2 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              Reportes
            </a>
            <a href="<?= base_url() ?>sisvent/commercial/budgets" class="glass-card px-4 py-2 text-xs text-gray-500 hover:text-gray-800 hover:border-red-500 transition-colors flex items-center">
              <svg class="w-4 h-4 mr-2" style="color:#E63946" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Presupuestos
            </a>
          </div>

        </div>
      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/voice_widget'); ?>

<!-- AI menu toggle + Búsqueda Universal Navbar -->
<script>
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});

(function() {
  var input = document.getElementById('navbar-universal-search');
  var results = document.getElementById('navbarSearchResults');
  if (!input || !results) return;
  var timer = null;
  var icons = {
    user: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
    box: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
    doc: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
    users: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197V21"></path></svg>'
  };
  var colors = { Cliente: '#22c55e', Producto: '#3b82f6', Factura: '#f59e0b', Usuario: '#8b5cf6' };
  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { results.classList.add('hidden'); return; }
    timer = setTimeout(function() {
      $.get(base_url + 'sisvent/dashboard/search', { q: q }, function(r) {
        if (!r.results || !r.results.length) { results.innerHTML = '<div class="p-4 text-sm text-gray-400 text-center">Sin resultados</div>'; results.classList.remove('hidden'); return; }
        var html = '';
        r.results.forEach(function(item) {
          var c = colors[item.type] || '#666'; var ic = icons[item.icon] || icons.box;
          html += '<a href="' + item.url + '" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b border-gray-100"><div class="p-2 rounded-lg mr-3" style="background:' + c + '15;color:' + c + '">' + ic + '</div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-800 truncate">' + item.title + '</p><p class="text-xs text-gray-400 truncate">' + item.subtitle + '</p></div><span class="text-xs font-medium px-2 py-0.5 rounded-full ml-2" style="background:' + c + '15;color:' + c + '">' + item.type + '</span></a>';
        });
        results.innerHTML = html; results.classList.remove('hidden');
      }, 'json');
    }, 300);
  });
  $(document).on('click', function(e) { if (!$(e.target).closest('#navbar-universal-search, #navbarSearchResults').length) results.classList.add('hidden'); });
})();
</script>

<!-- Animación 3D LED — se activa tras 30s de inactividad -->
<script>
window.addEventListener('load', function() {
  var canvas = document.getElementById('ledCanvas');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var W, H, particles = [];
  var mouse = { x: -1000, y: -1000 };
  var hero = document.getElementById('hero3d');
  var animating = false;
  var animFrame = null;
  var idleTimer = null;
  var IDLE_DELAY = 30000; // 30 segundos

  canvas.style.opacity = '0';
  canvas.style.transition = 'opacity 1.5s ease';

  function resetIdle() {
    // Si el usuario interactúa, ocultar animación y reiniciar timer
    if (animating) {
      animating = false;
      canvas.style.opacity = '0';
      if (animFrame) { cancelAnimationFrame(animFrame); animFrame = null; }
      // Limpiar canvas
      if (ctx && W && H) ctx.clearRect(0, 0, W, H);
    }
    clearTimeout(idleTimer);
    idleTimer = setTimeout(startAnimation, IDLE_DELAY);
  }

  function startAnimation() {
    animating = true;
    canvas.style.opacity = '1';
    animate();
  }

  // Detectar actividad del usuario
  ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(evt) {
    document.addEventListener(evt, resetIdle, { passive: true });
  });

  // Iniciar el primer timer
  idleTimer = setTimeout(startAnimation, IDLE_DELAY);

  function resize() {
    W = canvas.width = hero.offsetWidth || window.innerWidth;
    H = canvas.height = hero.offsetHeight || window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  hero.addEventListener('mousemove', function(e) {
    if (!animating) return;
    var rect = hero.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
    // No resetear idle por mover el mouse sobre la animación
  });

  // Crear módulos LED como partículas 3D
  var ledColors = [
    { r: 230, g: 57, b: 70 },    // Rojo Ledxury
    { r: 59, g: 130, b: 246 },   // Azul
    { r: 34, g: 197, b: 94 },    // Verde
    { r: 245, g: 158, b: 11 },   // Amarillo
    { r: 168, g: 85, b: 247 },   // Morado
    { r: 255, g: 255, b: 255 },  // Blanco
    { r: 56, g: 189, b: 248 },   // Azul hielo
    { r: 244, g: 114, b: 182 },  // Rosado
  ];

  for (var i = 0; i < 60; i++) {
    var color = ledColors[Math.floor(Math.random() * ledColors.length)];
    particles.push({
      x: Math.random() * 2000,
      y: Math.random() * 1200,
      z: Math.random() * 600 + 100,
      r: color.r, g: color.g, b: color.b,
      size: Math.random() * 3 + 1.5,
      vx: (Math.random() - 0.5) * 0.4,
      vy: (Math.random() - 0.5) * 0.3,
      vz: (Math.random() - 0.5) * 0.2,
      pulse: Math.random() * Math.PI * 2,
      pulseSpeed: Math.random() * 0.03 + 0.01,
    });
  }

  function project(p) {
    var perspective = 800;
    var scale = perspective / (perspective + p.z);
    return {
      x: W / 2 + (p.x - 1000) * scale,
      y: H / 2 + (p.y - 600) * scale,
      s: scale
    };
  }

  function animate() {
    ctx.fillStyle = 'rgba(255, 255, 255, 0.12)';
    ctx.fillRect(0, 0, W, H);

    // Update + project
    var projected = [];
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      p.z += p.vz;
      p.pulse += p.pulseSpeed;

      // Bounce
      if (p.x < 0 || p.x > 2000) p.vx *= -1;
      if (p.y < 0 || p.y > 1200) p.vy *= -1;
      if (p.z < 50 || p.z > 700) p.vz *= -1;

      // Mouse repulsion
      var pr = project(p);
      var dx = pr.x - mouse.x;
      var dy = pr.y - mouse.y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < 150) {
        p.vx += dx * 0.0003;
        p.vy += dy * 0.0003;
      }

      projected.push({ idx: i, x: pr.x, y: pr.y, s: pr.s, z: p.z });
    }

    // Sort by Z (far to near)
    projected.sort(function(a, b) { return b.z - a.z; });

    // Draw connections
    for (var i = 0; i < projected.length; i++) {
      for (var j = i + 1; j < projected.length; j++) {
        var dx = projected[i].x - projected[j].x;
        var dy = projected[i].y - projected[j].y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d < 100) {
          var pi = particles[projected[i].idx];
          ctx.beginPath();
          ctx.moveTo(projected[i].x, projected[i].y);
          ctx.lineTo(projected[j].x, projected[j].y);
          ctx.strokeStyle = 'rgba(' + pi.r + ',' + pi.g + ',' + pi.b + ',' + (0.08 * (1 - d / 100)) + ')';
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }

    // Draw particles
    for (var i = 0; i < projected.length; i++) {
      var pr = projected[i];
      var p = particles[pr.idx];
      var brightness = 0.4 + Math.sin(p.pulse) * 0.3;
      var size = p.size * pr.s;

      // Outer glow
      var grad = ctx.createRadialGradient(pr.x, pr.y, 0, pr.x, pr.y, size * 8);
      grad.addColorStop(0, 'rgba(' + p.r + ',' + p.g + ',' + p.b + ',' + (brightness * 0.3) + ')');
      grad.addColorStop(1, 'transparent');
      ctx.fillStyle = grad;
      ctx.beginPath();
      ctx.arc(pr.x, pr.y, size * 8, 0, Math.PI * 2);
      ctx.fill();

      // Core
      ctx.beginPath();
      ctx.arc(pr.x, pr.y, size, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + p.r + ',' + p.g + ',' + p.b + ',' + (brightness + 0.3) + ')';
      ctx.fill();

      // Bright center
      ctx.beginPath();
      ctx.arc(pr.x, pr.y, size * 0.4, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,' + (brightness * 0.6) + ')';
      ctx.fill();
    }

    if (animating) animFrame = requestAnimationFrame(animate);
  }
});
</script>

</body>
</html>
