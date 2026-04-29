<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Ledxury' : 'Tienda Ledxury' ?></title>
<link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg"/>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
  .card-shadow { box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 8px 16px -8px rgba(0,0,0,.06); }
  .price { font-feature-settings: "tnum"; }
</style>
</head>
<body class="bg-slate-50 text-slate-800">

<header class="sticky top-0 z-40 bg-white border-b border-slate-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
    <a href="<?= base_url() ?>" class="flex items-center gap-2 flex-shrink-0">
      <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-black text-lg" style="background:linear-gradient(135deg,#E63946,#1B365D);">L</div>
      <span class="hidden sm:inline text-lg font-extrabold tracking-tight">LEDXURY</span>
    </a>
    <nav class="flex items-center gap-2 sm:gap-4">
      <a href="<?= base_url() ?>tienda" class="text-sm font-medium text-slate-700 hover:text-red-600 hidden sm:inline">Catálogo</a>
      <a href="<?= base_url() ?>tienda/mis-pedidos" class="text-sm font-medium text-slate-700 hover:text-red-600 hidden sm:inline">Mis pedidos</a>
      <a href="<?= base_url() ?>tienda/carrito" class="relative inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-100" id="header-cart-link">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Carrito
        <span id="cart-count-badge" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] text-[10px] font-bold rounded-full bg-red-500 text-white flex items-center justify-center px-1">0</span>
      </a>
      <a href="<?= base_url() ?>sisvent/login" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-semibold text-white rounded-lg" style="background:#1B365D;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        <span class="hidden sm:inline">Ingresar</span>
      </a>
    </nav>
  </div>
</header>

<script>
  // Carrito en localStorage
  window.LedxCart = {
    KEY: 'ledxury_cart_v1',
    get: function() { try { return JSON.parse(localStorage.getItem(this.KEY)) || []; } catch(e) { return []; } },
    set: function(arr) { localStorage.setItem(this.KEY, JSON.stringify(arr)); this.refreshBadge(); },
    add: function(item) {
      var c = this.get();
      var f = c.find(function(x){ return x.id === item.id; });
      if (f) f.qty = Math.min((f.qty||0) + (item.qty||1), item.stock || 999);
      else c.push({ id: item.id, name: item.name, price: item.price, qty: item.qty||1, image: item.image, stock: item.stock || 999 });
      this.set(c);
    },
    update: function(id, qty) {
      var c = this.get();
      var f = c.find(function(x){ return x.id === id; });
      if (!f) return;
      if (qty <= 0) c = c.filter(function(x){ return x.id !== id; });
      else f.qty = Math.min(qty, f.stock || 999);
      this.set(c);
    },
    remove: function(id) {
      var c = this.get().filter(function(x){ return x.id !== id; });
      this.set(c);
    },
    clear: function() { this.set([]); },
    count: function() { return this.get().reduce(function(a, b) { return a + (b.qty||0); }, 0); },
    total: function() { return this.get().reduce(function(a, b) { return a + ((b.qty||0) * (b.price||0)); }, 0); },

    // === Regla de envío gratis ===
    // Subtotal en MÓDULOS > $60.000 → envío gratis
    isModule: function(code) {
      if (!code) return false;
      var c = String(code).toUpperCase();
      // Módulos LED 3LED/6LED/12LED, 2835 alta potencia, JS-COB y COB 7CM (M_-12V/24V, MBI, etc.)
      return /^(3LED|6LED|12LED|2835|JS-COB)-/.test(c)
          || /^M[A-Z]{1,2}-(12|24)V$/.test(c);
    },
    modulesTotal: function() {
      var self = this;
      return this.get().reduce(function(a, it) {
        return a + (self.isModule(it.id) ? (it.qty||0) * (it.price||0) : 0);
      }, 0);
    },
    freeShipping: function() {
      return this.modulesTotal() > 60000;
    },
    freeShippingReason: function() {
      return this.modulesTotal() > 60000 ? 'compra de módulos > $60.000' : null;
    },

    refreshBadge: function() {
      var n = this.count();
      var b = document.getElementById('cart-count-badge');
      if (b) { if (n > 0) { b.textContent = n; b.classList.remove('hidden'); } else { b.classList.add('hidden'); } }
    }
  };
  document.addEventListener('DOMContentLoaded', function() { window.LedxCart.refreshBadge(); });

  function fmtPrice(n) { return '$' + new Intl.NumberFormat('es-CO').format(Math.round(n)); }
  function toast(msg, type) {
    type = type || 'success';
    var bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#1B365D');
    var el = document.createElement('div');
    el.style = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:'+bg+';color:#fff;padding:10px 18px;border-radius:8px;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2);';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function() { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(function(){ el.remove(); }, 300); }, 2000);
  }
</script>
