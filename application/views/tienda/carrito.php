<?php $this->load->view('tienda/_layout_head', array('pageTitle' => 'Tu carrito')); ?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
  <h1 class="text-2xl font-extrabold text-slate-900 mb-4">Tu carrito</h1>
  <div id="cart-empty" class="hidden bg-white rounded-2xl border border-slate-200 p-10 text-center">
    <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    <p class="text-slate-500 mb-4">Tu carrito está vacío.</p>
    <a href="<?= base_url() ?>tienda" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg" style="background:#E63946;">Volver al catálogo</a>
  </div>
  <div id="cart-grid" class="grid md:grid-cols-3 gap-5 hidden">
    <div class="md:col-span-2 space-y-3" id="cart-items"></div>
    <aside class="bg-white rounded-2xl border border-slate-200 p-5 self-start sticky top-20">
      <h3 class="font-bold text-slate-900 mb-4">Resumen</h3>
      <div class="flex justify-between text-sm py-2"><span class="text-slate-600">Subtotal</span><span id="cart-subtotal" class="font-semibold price">$0</span></div>
      <div class="flex justify-between text-sm py-2 border-b border-slate-100">
        <span class="text-slate-600">Envío</span>
        <span id="cart-shipping" class="text-slate-500">Interrapidísimo · contra entrega</span>
      </div>
      <div class="flex justify-between py-3"><span class="font-bold text-slate-900">Total</span><span id="cart-total" class="font-extrabold text-lg price">$0</span></div>

      <div id="ship-applied" class="hidden bg-emerald-50 border border-emerald-300 rounded-lg p-3 mb-3 text-xs text-emerald-800">
        🚚 <b>¡Envío GRATIS aplicado!</b> <span id="ship-reason"></span>
      </div>
      <div id="ship-progress" class="hidden bg-blue-50 border border-blue-300 rounded-lg p-3 mb-3 text-xs text-blue-800">
        🚚 Te faltan <b id="ship-falta" class="price">$0</b> en módulos para tener <b>envío gratis</b>.
      </div>

      <div id="min-warning" class="hidden bg-amber-50 border border-amber-300 rounded-lg p-3 mb-3 text-xs text-amber-800">
        ⚠️ Pedido mínimo <b>$60.000</b>. Te faltan <b id="min-falta" class="price">$0</b> para poder confirmar.
      </div>

      <a id="checkout-cta" href="<?= base_url() ?>tienda/checkout" class="block w-full py-3 mt-3 text-center text-sm font-bold text-white rounded-lg transition" style="background:#10b981;">Continuar al pedido →</a>
      <a href="<?= base_url() ?>tienda" class="block text-center text-xs text-slate-500 hover:text-slate-700 mt-3">← Seguir comprando</a>

      <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-1 gap-1.5 text-[11px] text-slate-600">
        <div class="flex items-center gap-2"><span class="text-emerald-600">✓</span> Pago contra entrega</div>
        <div class="flex items-center gap-2"><span class="text-blue-600">✓</span> Envío con Interrapidísimo</div>
        <div class="flex items-center gap-2"><span class="text-amber-600">✓</span> Pedido mínimo $60.000</div>
      </div>
    </aside>
  </div>
</main>

<script>
function renderCart() {
  var c = window.LedxCart.get();
  var emptyEl = document.getElementById('cart-empty');
  var gridEl  = document.getElementById('cart-grid');
  var listEl  = document.getElementById('cart-items');
  if (c.length === 0) {
    emptyEl.classList.remove('hidden');
    gridEl.classList.add('hidden');
    return;
  }
  emptyEl.classList.add('hidden');
  gridEl.classList.remove('hidden');
  listEl.innerHTML = c.map(function(it) {
    return '<div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-3">'
      + '<div class="w-16 h-16 sm:w-20 sm:h-20 bg-slate-50 rounded-lg overflow-hidden flex-shrink-0"><img src="<?= base_url() ?>'+it.image+'" class="w-full h-full object-contain p-1"></div>'
      + '<div class="flex-1 min-w-0">'
      +   '<p class="text-[10px] font-bold text-slate-400">'+ it.id +'</p>'
      +   '<p class="text-sm font-semibold text-slate-800 line-clamp-2">'+ it.name +'</p>'
      +   '<p class="text-sm font-bold text-slate-900 mt-1 price">'+ fmtPrice(it.price) +' c/u</p>'
      + '</div>'
      + '<div class="flex flex-col items-end gap-2">'
      +   '<div class="inline-flex items-center border border-slate-300 rounded-lg overflow-hidden">'
      +     '<button onclick="upd(\''+ it.id +'\','+ (it.qty-1) +')" class="px-2 py-1 text-slate-600 hover:bg-slate-100">−</button>'
      +     '<input type="number" value="'+ it.qty +'" min="1" max="'+ (it.stock||999) +'" onchange="upd(\''+ it.id +'\',this.valueAsNumber)" class="w-12 text-center text-sm outline-none border-x border-slate-300">'
      +     '<button onclick="upd(\''+ it.id +'\','+ (it.qty+1) +')" class="px-2 py-1 text-slate-600 hover:bg-slate-100">+</button>'
      +   '</div>'
      +   '<p class="text-sm font-extrabold price">'+ fmtPrice(it.price * it.qty) +'</p>'
      +   '<button onclick="del(\''+ it.id +'\')" class="text-[11px] text-red-500 hover:text-red-700">Eliminar</button>'
      + '</div>'
      + '</div>';
  }).join('');
  var total = window.LedxCart.total();
  document.getElementById('cart-subtotal').textContent = fmtPrice(total);
  document.getElementById('cart-total').textContent = fmtPrice(total);
  // === Envío gratis (regla del negocio) ===
  var freeShip   = window.LedxCart.freeShipping();
  var freeReason = window.LedxCart.freeShippingReason();
  var modulesT   = window.LedxCart.modulesTotal();
  var shipBox    = document.getElementById('cart-shipping');
  var shipApplied = document.getElementById('ship-applied');
  var shipProgress = document.getElementById('ship-progress');
  if (freeShip) {
    shipBox.innerHTML = '<b style="color:#059669;">¡GRATIS!</b>';
    document.getElementById('ship-reason').textContent = freeReason ? '(' + freeReason + ')' : '';
    shipApplied.classList.remove('hidden');
    shipProgress.classList.add('hidden');
  } else {
    shipBox.textContent = 'Interrapidísimo · contra entrega';
    shipApplied.classList.add('hidden');
    if (modulesT > 0 && modulesT <= 60000) {
      shipProgress.classList.remove('hidden');
      document.getElementById('ship-falta').textContent = fmtPrice(60001 - modulesT);
    } else {
      shipProgress.classList.add('hidden');
    }
  }
  // Validar mínimo $60.000
  var MIN = 60000;
  var warn = document.getElementById('min-warning');
  var cta  = document.getElementById('checkout-cta');
  if (total > 0 && total < MIN) {
    warn.classList.remove('hidden');
    document.getElementById('min-falta').textContent = fmtPrice(MIN - total);
    cta.style.background = '#9ca3af';
    cta.style.pointerEvents = 'none';
    cta.textContent = 'Mínimo $60.000 →';
  } else {
    warn.classList.add('hidden');
    cta.style.background = '#10b981';
    cta.style.pointerEvents = '';
    cta.textContent = 'Continuar al pedido →';
  }
}
function upd(id, qty) { window.LedxCart.update(id, qty); renderCart(); }
function del(id) { window.LedxCart.remove(id); renderCart(); }
document.addEventListener('DOMContentLoaded', renderCart);
</script>

<?php $this->load->view('tienda/_layout_foot'); ?>
