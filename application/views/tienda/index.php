<?php $pageTitle = 'Catálogo'; $this->load->view('tienda/_layout_head', array('pageTitle' => $pageTitle)); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
  <!-- Hero strip -->
  <div class="rounded-2xl p-6 sm:p-8 mb-3 text-white" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 60%,#2E7D91 100%);">
    <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">Catálogo Ledxury</h1>
    <p class="text-sm sm:text-base text-slate-200">Productos LED. Despacho desde Medellín a toda Colombia.</p>
  </div>

  <!-- Promo: envío gratis -->
  <div class="rounded-xl px-4 py-3 mb-4 text-white text-sm font-semibold flex items-start sm:items-center gap-3 shadow-sm" style="background:linear-gradient(90deg,#10b981 0%,#059669 100%);">
    <span class="text-xl flex-shrink-0">🚚</span>
    <div class="flex-1">
      <div class="font-extrabold text-base">¡Envío GRATIS!</div>
      <div class="text-xs opacity-95">En compras de módulos por más de <b>$60.000</b>.</div>
    </div>
  </div>

  <!-- Shipping/COD/Min order banner -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-5">
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
      <div><div class="text-[11px] font-bold text-emerald-800">Pago contra entrega</div><div class="text-[10px] text-emerald-700">Pagas al recibir</div></div>
    </div>
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/></svg>
      <div><div class="text-[11px] font-bold text-blue-800">Envío Interrapidísimo</div><div class="text-[10px] text-blue-700">Toda Colombia</div></div>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-center gap-2">
      <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div><div class="text-[11px] font-bold text-amber-800">Pedido mínimo $60.000</div><div class="text-[10px] text-amber-700">Antes de envío</div></div>
    </div>
  </div>

  <!-- Categorías nav (sticky) -->
  <?php if (!empty($catalog['families'])): ?>
  <nav class="bg-white rounded-xl border border-slate-200 p-2 mb-6 sticky top-16 z-30 overflow-x-auto">
    <div class="flex gap-2 whitespace-nowrap">
      <?php foreach ($catalog['families'] as $fam): ?>
      <a href="#fam-<?= $fam['id'] ?>" class="px-3 py-1.5 text-sm font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-lg flex-shrink-0">
        <?= htmlspecialchars($fam['name']) ?>
        <span class="text-[11px] text-slate-400 ml-1">(<?= count($fam['products']) ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>
  </nav>
  <?php endif; ?>

  <!-- Búsqueda -->
  <div class="mb-5">
    <input id="searchBox" type="search" placeholder="Buscar producto por código o nombre..."
           class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
  </div>

  <?php if (empty($catalog['families'])): ?>
  <div class="text-center py-20">
    <p class="text-slate-500">No hay productos disponibles en este momento.</p>
  </div>
  <?php endif; ?>

  <?php foreach ($catalog['families'] as $fam): ?>
  <section id="fam-<?= $fam['id'] ?>" class="family-section mb-10" data-family-name="<?= htmlspecialchars(strtolower($fam['name'])) ?>">
    <div class="flex items-baseline justify-between mb-3">
      <h2 class="text-lg sm:text-xl font-extrabold text-slate-900"><?= htmlspecialchars($fam['name']) ?></h2>
      <span class="text-xs text-slate-400"><?= count($fam['products']) ?> productos</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
      <?php foreach ($fam['products'] as $p): ?>
      <article class="product-card bg-white rounded-xl overflow-hidden card-shadow border border-slate-100 <?= !empty($p['is_blocked']) ? 'opacity-75' : 'hover:border-red-300' ?> transition flex flex-col relative"
               data-name="<?= htmlspecialchars(strtolower($p['name'] . ' ' . $p['id'])) ?>">
        <?php if(!empty($p['is_blocked'])): ?>
          <span class="absolute top-2 right-2 z-10 px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-slate-700 text-white shadow">AGOTADO</span>
        <?php endif; ?>
        <a href="<?= base_url() ?>tienda/producto/<?= rawurlencode($p['id']) ?>" class="block aspect-square bg-slate-50 overflow-hidden">
          <img src="<?= base_url() . $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-contain p-2 <?= !empty($p['is_blocked']) ? 'grayscale' : 'hover:scale-105' ?> transition" loading="lazy" onerror="var c=this.closest('article'); if(c) c.remove();">
        </a>
        <div class="p-3 flex-1 flex flex-col">
          <p class="text-[10px] font-bold text-slate-400 truncate"><?= htmlspecialchars($p['id']) ?></p>
          <p class="text-xs sm:text-sm font-semibold text-slate-800 line-clamp-2 mb-2 min-h-[34px]"><?= htmlspecialchars($p['name']) ?></p>
          <div class="mt-auto">
            <p class="text-base sm:text-lg font-extrabold text-slate-900 price">$<?= number_format($p['price'],0,',','.') ?></p>
            <?php if(!empty($p['is_blocked'])): ?>
              <p class="text-[10px] text-slate-500 font-semibold mb-2">Sin existencias por ahora</p>
              <button class="w-full py-2 text-xs font-bold text-slate-500 bg-slate-200 rounded-lg cursor-not-allowed" disabled>
                Agotado
              </button>
            <?php else: ?>
              <p class="text-[10px] text-emerald-600 font-semibold mb-2">✓ Disponible · entrega en 2–3 días</p>
              <button class="btn-add w-full py-2 text-xs font-bold text-white rounded-lg transition" style="background:#E63946;"
                data-id="<?= htmlspecialchars($p['id']) ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>"
                data-price="<?= $p['price'] ?>"
                data-stock="<?= $p['stock'] ?>"
                data-image="<?= htmlspecialchars($p['image']) ?>">
                + Agregar
              </button>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
</main>

<script>
// Add to cart
document.querySelectorAll('.btn-add').forEach(function(btn) {
  btn.addEventListener('click', function() {
    window.LedxCart.add({
      id: btn.dataset.id,
      name: btn.dataset.name,
      price: parseInt(btn.dataset.price, 10),
      stock: parseInt(btn.dataset.stock, 10),
      image: btn.dataset.image,
      qty: 1,
    });
    toast('✓ Agregado al carrito');
  });
});

// Filtro de búsqueda
var searchBox = document.getElementById('searchBox');
if (searchBox) {
  searchBox.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.product-card').forEach(function(card) {
      card.style.display = !q || card.dataset.name.indexOf(q) >= 0 ? '' : 'none';
    });
    // Esconder secciones vacías
    document.querySelectorAll('.family-section').forEach(function(sec) {
      var visible = Array.from(sec.querySelectorAll('.product-card')).some(function(c) { return c.style.display !== 'none'; });
      sec.style.display = visible ? '' : 'none';
    });
  });
}
</script>

<?php $this->load->view('tienda/_layout_foot'); ?>
