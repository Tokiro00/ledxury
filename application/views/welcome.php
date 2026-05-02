<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ledxury — Iluminación LED de Alta Tecnología</title>
<link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg"/>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>
<style>
  body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
  .grad-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #2E7D91 100%); }
  .text-gradient { background: linear-gradient(90deg, #fbbf24, #ef4444); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
</style>
</head>
<body class="bg-white text-slate-800">

<!-- Header -->
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
    <a href="<?= base_url() ?>" class="flex items-center gap-2">
      <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-black text-lg" style="background:linear-gradient(135deg,#E63946,#1B365D);">L</div>
      <span class="text-xl font-extrabold tracking-tight">LEDXURY</span>
    </a>
    <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-600">
      <a href="<?= base_url() ?>tienda" class="hover:text-slate-900">Tienda</a>
      <a href="#por-que" class="hover:text-slate-900">¿Por qué nosotros?</a>
      <a href="#contacto" class="hover:text-slate-900">Contacto</a>
    </nav>
    <div class="flex items-center gap-3">
      <a href="<?= base_url() ?>tienda" class="hidden md:inline-flex items-center gap-1 px-3 py-2 text-sm font-semibold text-slate-700 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Comprar
      </a>
      <a href="<?= base_url() ?>sisvent/login" class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white rounded-lg shadow-sm" style="background:#1B365D;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        Ingresar
      </a>
    </div>
  </div>
</header>

<!-- Hero -->
<section class="grad-hero text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 grid md:grid-cols-2 gap-10 items-center">
    <div>
      <p class="text-sm font-semibold uppercase tracking-widest text-amber-300 mb-3">Iluminación premium para tu vehículo</p>
      <h1 class="text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-[1.05]">
        LED de alta potencia<br><span class="text-gradient">para verte y ser visto.</span>
      </h1>
      <p class="mt-5 text-lg text-slate-200 max-w-lg">Módulos, bombillos, exploradoras, farolas y accesorios. Stock real desde Medellín, envío a toda Colombia.</p>
      <div class="mt-7 flex flex-wrap gap-3">
        <a href="<?= base_url() ?>tienda" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-bold text-slate-900 bg-amber-300 hover:bg-amber-400 shadow-lg">
          Ver catálogo
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <a href="https://wa.me/573226951481" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-bold text-white border border-white/30 hover:bg-white/10">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
          WhatsApp
        </a>
      </div>
      <div class="mt-9 grid grid-cols-3 gap-3 max-w-md">
        <div><div class="text-2xl font-extrabold">1.000+</div><div class="text-xs text-slate-300">productos</div></div>
        <div><div class="text-2xl font-extrabold">24h</div><div class="text-xs text-slate-300">despacho</div></div>
        <div><div class="text-2xl font-extrabold">10+</div><div class="text-xs text-slate-300">años de experiencia</div></div>
      </div>
    </div>
    <!-- Featured grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <?php foreach ($featured as $p): ?>
      <a href="<?= base_url() ?>tienda/producto/<?= rawurlencode($p['id']) ?>" class="bg-white rounded-xl overflow-hidden shadow-lg ring-1 ring-white/20 hover:ring-amber-300 transition group">
        <div class="aspect-square overflow-hidden bg-slate-50">
          <img src="<?= base_url() . $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-contain p-2 group-hover:scale-105 transition" loading="lazy" onerror="this.parentNode.parentNode.style.display='none'">
        </div>
        <div class="p-2">
          <p class="text-[10px] font-bold text-slate-900 truncate"><?= htmlspecialchars($p['id']) ?></p>
          <p class="text-[10px] text-slate-500 truncate"><?= htmlspecialchars($p['name']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Por qué nosotros -->
<section id="por-que" class="py-16 lg:py-24 bg-slate-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl md:text-4xl font-extrabold text-center text-slate-900 mb-3">¿Por qué Ledxury?</h2>
    <p class="text-center text-slate-600 max-w-2xl mx-auto">Productos premium, atención directa por WhatsApp y envío rápido a todo el país.</p>
    <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-slate-100">
        <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center mb-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Alta tecnología</h3>
        <p class="text-sm text-slate-600 mt-2">Solo trabajamos con LEDs de marcas líderes con disipación térmica óptima y vida útil garantizada.</p>
      </div>
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-slate-100">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Stock real</h3>
        <p class="text-sm text-slate-600 mt-2">Lo que ves en el catálogo está disponible. Despachamos en menos de 24 horas hábiles desde Medellín.</p>
      </div>
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-slate-100">
        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Atención personalizada</h3>
        <p class="text-sm text-slate-600 mt-2">Te asesoramos por WhatsApp y confirmamos cada pedido. Sin tiempos de espera robóticos.</p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer id="contacto" class="bg-slate-900 text-slate-300 py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-3 gap-8">
    <div>
      <div class="flex items-center gap-2 mb-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-black text-lg" style="background:linear-gradient(135deg,#E63946,#1B365D);">L</div>
        <span class="text-xl font-extrabold tracking-tight text-white">LEDXURY</span>
      </div>
      <p class="text-sm">Iluminación LED de alta tecnología desde Medellín, Colombia.</p>
    </div>
    <div>
      <h4 class="text-white font-bold mb-3">Catálogo</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="<?= base_url() ?>tienda" class="hover:text-white">Ver todos</a></li>
        <li><a href="<?= base_url() ?>tienda#fam-7" class="hover:text-white">Módulos LED</a></li>
        <li><a href="<?= base_url() ?>tienda#fam-5" class="hover:text-white">Bombillos</a></li>
        <li><a href="<?= base_url() ?>tienda#fam-6" class="hover:text-white">Farolas</a></li>
      </ul>
    </div>
    <div>
      <h4 class="text-white font-bold mb-3">Contacto</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="https://wa.me/573226951481" target="_blank" rel="noopener" class="inline-flex items-center gap-2 hover:text-white"><svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>WhatsApp ventas</a></li>
        <li>Medellín, Colombia</li>
      </ul>
    </div>
  </div>
  <div class="mt-10 pt-6 border-t border-slate-800 text-center text-xs text-slate-500">© <?= date('Y') ?> Ledxury. Todos los derechos reservados.</div>
</footer>

</body>
</html>
