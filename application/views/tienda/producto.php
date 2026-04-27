<?php $this->load->view('tienda/_layout_head', array('pageTitle' => $product['name'])); ?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
  <div class="text-xs text-slate-500 mb-3">
    <a href="<?= base_url() ?>tienda" class="hover:underline">Catálogo</a>
    <?php if (!empty($product['family_name'])): ?>
      <span class="mx-1">›</span>
      <span><?= htmlspecialchars($product['family_name']) ?></span>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 p-6 grid md:grid-cols-2 gap-8">
    <div class="bg-slate-50 rounded-xl flex items-center justify-center aspect-square overflow-hidden">
      <img src="<?= base_url() . $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="max-w-full max-h-full object-contain p-4">
    </div>
    <div class="flex flex-col">
      <p class="text-xs font-bold text-slate-400"><?= htmlspecialchars($product['id']) ?></p>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-2"><?= htmlspecialchars($product['name']) ?></h1>
      <p class="text-sm text-slate-500 mb-4"><?= htmlspecialchars($product['family_name']) ?></p>
      <div class="text-3xl font-extrabold text-slate-900 mb-2 price">$<?= number_format($product['price'],0,',','.') ?></div>
      <p class="text-sm text-emerald-600 font-semibold mb-4">✓ Disponible · Stock: <?= number_format($product['stock'],0,',','.') ?></p>

      <div class="flex items-center gap-3 mb-4">
        <label class="text-sm font-semibold text-slate-700">Cantidad:</label>
        <div class="inline-flex items-center border border-slate-300 rounded-lg overflow-hidden">
          <button id="dec" class="px-3 py-2 text-slate-600 hover:bg-slate-100">−</button>
          <input id="qty" type="number" value="1" min="1" max="<?= $product['stock'] ?>" class="w-16 text-center border-x border-slate-300 outline-none">
          <button id="inc" class="px-3 py-2 text-slate-600 hover:bg-slate-100">+</button>
        </div>
      </div>

      <div class="flex gap-3 mt-2">
        <button id="addBtn" class="flex-1 py-3 text-sm font-bold text-white rounded-lg transition" style="background:#E63946;">
          🛒 Agregar al carrito
        </button>
        <a href="<?= base_url() ?>tienda" class="px-4 py-3 text-sm font-semibold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">Volver</a>
      </div>

      <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs text-slate-600">
        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Stock real</div>
        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Envío 24h hábiles</div>
        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/></svg>Atención WhatsApp</div>
        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Calidad premium</div>
      </div>
    </div>
  </div>
</main>

<script>
var qtyInput = document.getElementById('qty');
document.getElementById('inc').addEventListener('click', function() { qtyInput.value = Math.min((parseInt(qtyInput.value,10)||1) + 1, <?= $product['stock'] ?>); });
document.getElementById('dec').addEventListener('click', function() { qtyInput.value = Math.max((parseInt(qtyInput.value,10)||1) - 1, 1); });
document.getElementById('addBtn').addEventListener('click', function() {
  var qty = parseInt(qtyInput.value,10) || 1;
  window.LedxCart.add({
    id: '<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>',
    name: '<?= htmlspecialchars(addslashes($product['name'])) ?>',
    price: <?= $product['price'] ?>,
    stock: <?= $product['stock'] ?>,
    image: '<?= htmlspecialchars($product['image'], ENT_QUOTES) ?>',
    qty: qty,
  });
  toast('✓ ' + qty + ' agregado(s) al carrito');
});
</script>

<?php $this->load->view('tienda/_layout_foot'); ?>
