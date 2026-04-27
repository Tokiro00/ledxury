<?php $this->load->view('tienda/_layout_head', array('pageTitle' => 'Finalizar pedido')); ?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
  <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Finalizar pedido</h1>
  <p class="text-sm text-slate-500 mb-4">Completa tus datos. Te contactaremos por WhatsApp para confirmar el envío.</p>

  <!-- Info envío + COD -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-5">
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <span class="text-emerald-600 text-lg">💵</span>
      <div><div class="text-[11px] font-bold text-emerald-800">Pago contra entrega</div><div class="text-[10px] text-emerald-700">No pagas nada ahora</div></div>
    </div>
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <span class="text-blue-600 text-lg">🚚</span>
      <div><div class="text-[11px] font-bold text-blue-800">Envío Interrapidísimo</div><div class="text-[10px] text-blue-700">Toda Colombia</div></div>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <span class="text-amber-600 text-lg">📦</span>
      <div><div class="text-[11px] font-bold text-amber-800">Pedido mínimo $60.000</div><div class="text-[10px] text-amber-700">Antes de envío</div></div>
    </div>
  </div>

  <div class="grid md:grid-cols-3 gap-5">
    <form id="checkout-form" class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre completo *</label>
        <input name="name" required minlength="3" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Cédula / Documento</label>
          <input name="doc" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Teléfono / WhatsApp *</label>
          <input name="phone" required minlength="7" inputmode="tel" placeholder="3001234567" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Email (opcional)</label>
        <input name="email" type="email" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Dirección de entrega *</label>
        <input name="address" required minlength="5" placeholder="Calle / Carrera, número, barrio" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ciudad *</label>
          <input name="city" required minlength="2" class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Departamento</label>
          <input name="dept" placeholder="Antioquia, Cundinamarca..." class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        </div>
      </div>
      <div class="pt-3 border-t border-slate-100 text-xs text-slate-500">
        Al confirmar, recibirás un mensaje de WhatsApp para coordinar el envío y el pago.
      </div>
      <button type="submit" id="submitBtn" class="w-full py-3 text-base font-bold text-white rounded-lg transition disabled:opacity-50" style="background:#10b981;">
        Confirmar pedido
      </button>
    </form>

    <aside class="bg-white rounded-2xl border border-slate-200 p-5 self-start md:sticky md:top-20">
      <h3 class="font-bold text-slate-900 mb-3">Tu pedido</h3>
      <div id="order-items" class="space-y-2 text-sm max-h-64 overflow-y-auto"></div>
      <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between font-extrabold">
        <span>Total</span>
        <span id="order-total" class="price">$0</span>
      </div>
      <a href="<?= base_url() ?>tienda/carrito" class="block text-center text-xs text-slate-500 hover:text-slate-700 mt-3">← Editar carrito</a>
    </aside>
  </div>
</main>

<script>
function renderSummary() {
  var c = window.LedxCart.get();
  if (c.length === 0) { window.location.href = '<?= base_url() ?>tienda/carrito'; return; }
  // Si no llega al mínimo, devolver al carrito
  var total = window.LedxCart.total();
  if (total < 60000) {
    alert('El pedido mínimo es $60.000. Te faltan ' + fmtPrice(60000 - total) + ' para poder continuar.');
    window.location.href = '<?= base_url() ?>tienda/carrito';
    return;
  }
  document.getElementById('order-items').innerHTML = c.map(function(it) {
    return '<div class="flex justify-between gap-2"><span class="truncate text-slate-700">'+ it.qty +'× '+ it.name +'</span><span class="font-semibold flex-shrink-0">'+ fmtPrice(it.price * it.qty) +'</span></div>';
  }).join('');
  document.getElementById('order-total').textContent = fmtPrice(total);
}
document.addEventListener('DOMContentLoaded', renderSummary);

document.getElementById('checkout-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = 'Enviando...';
  var fd = new FormData(this);
  var client = {};
  fd.forEach(function(v, k) { client[k] = (v||'').trim(); });
  var items = window.LedxCart.get().map(function(i) { return { id: i.id, qty: i.qty }; });
  fetch('<?= base_url() ?>tienda/placeOrder', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ client: client, items: items })
  })
  .then(function(r) { return r.json(); })
  .then(function(r) {
    if (r.ok) {
      window.LedxCart.clear();
      window.location.href = r.redirect;
    } else {
      btn.disabled = false; btn.textContent = 'Confirmar pedido';
      toast('Error: ' + (r.error || 'no se pudo procesar'), 'error');
    }
  })
  .catch(function() {
    btn.disabled = false; btn.textContent = 'Confirmar pedido';
    toast('Error de conexión', 'error');
  });
});
</script>

<?php $this->load->view('tienda/_layout_foot'); ?>
