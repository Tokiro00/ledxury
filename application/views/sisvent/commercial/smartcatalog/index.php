<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$url_params = createFullParamsLinks($page);
$family_icons = [1=>'📦',2=>'📱',3=>'🔦',4=>'🪞',5=>'💡',6=>'🚗',7=>'💫',8=>'🔌',9=>'🦺',10=>'🔧'];
?>
<!DOCTYPE html>
<html lang="es">
<title>Ledxury — Catálogo Inteligente</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.product-card { border-radius: 12px; overflow: hidden; transition: all 0.2s; border: 1px solid #E5E7EB; background: white; position: relative; }
.product-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-color: #3B82F6; }
.product-img { aspect-ratio: 1; background: #F3F4F6; position: relative; display: flex; align-items: center; justify-content: center; }
.product-img img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.badge-abc { position: absolute; top: 8px; right: 8px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.badge-a { background: #FEF3C7; color: #92400E; }
.badge-b { background: #DBEAFE; color: #1E40AF; }
.badge-c { background: #F3F4F6; color: #6B7280; }
.badge-foto { position: absolute; top: 8px; left: 8px; background: #10B981; color: white; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-stock { position: absolute; bottom: 8px; right: 8px; font-size: 10px; padding: 2px 6px; border-radius: 4px; }
.stock-ok { background: #D1FAE5; color: #065F46; }
.stock-low { background: #FEF3C7; color: #92400E; }
.stock-out { background: #FEE2E2; color: #991B1B; }
.family-pill { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; transition: all 0.15s; margin: 2px; }
.family-pill:hover { background: #EFF6FF; }
.family-pill.active { background: #1E3A5F; color: white; }
.stat-card { background: white; border-radius: 12px; padding: 16px; border: 1px solid #E5E7EB; }
.btn-add-cart { position: absolute; bottom: 8px; left: 8px; width: 32px; height: 32px; border-radius: 50%; background: #3B82F6; color: white; border: none; font-size: 18px; cursor: pointer; opacity: 0; transition: all 0.2s; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.product-card:hover .btn-add-cart { opacity: 1; }
.btn-add-cart:hover { background: #2563EB; transform: scale(1.1); }
.cart-fab { position: fixed; bottom: 20px; right: 20px; z-index: 40; padding: 14px 24px; border-radius: 50px; background: #FF6B00; color: white; border: none; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(255,107,0,0.4); display: none; transition: transform 0.2s; }
.cart-fab:hover { transform: scale(1.05); }
.cart-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 380px; background: white; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 50; display: none; flex-direction: column; }
.cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 45; display: none; }
.cart-item-added { animation: cartPulse 0.3s ease; }
@keyframes cartPulse { 0% { outline: 3px solid #3B82F6; } 100% { outline: 3px solid transparent; } }
@media (max-width: 768px) { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; } .cart-panel { width: 100%; } .btn-add-cart { opacity: 1; } }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="px-6 mx-auto">

                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4 mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Catálogo Inteligente</h2>
                        <p class="text-sm text-gray-500"><?= number_format($total) ?> productos · <?= $stats->withImg ?> con foto · Stock: <?= number_format($stats->totalStock) ?> uds</p>
                    </div>
                    <div class="flex gap-2 mt-2 md:mt-0">
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clients') ?>" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-blue-700 transition">
                            👥 Clientes ABC
                        </a>
                        <?php if(in_array($role, [1, 2])): ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/fidelizacion') ?>" class="px-4 py-2 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 transition">
                            🎯 Fidelización
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats rápidos -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="stat-card">
                        <p class="text-xs text-gray-500">Productos</p>
                        <p class="text-xl font-bold text-gray-800"><?= number_format($stats->total) ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-gray-500">Con foto</p>
                        <p class="text-xl font-bold text-green-600"><?= number_format($stats->withImg) ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-gray-500">Sin foto</p>
                        <p class="text-xl font-bold text-red-500"><?= number_format($stats->noImg) ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="text-xs text-gray-500">Stock total</p>
                        <p class="text-xl font-bold text-blue-600"><?= number_format($stats->totalStock) ?></p>
                    </div>
                </div>

                <!-- Filtros: Búsqueda + Familias + Tienda -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <form method="get" class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <input type="text" name="q" id="catalogSearch" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por código o nombre... (Enter para buscar)" class="w-full py-2 pl-10 pr-4 text-sm border border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none bg-gray-50" autofocus>
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </div>
                        <select name="store" class="py-2 px-3 text-sm border border-gray-200 rounded-lg bg-gray-50">
                            <option value="0">Todas las tiendas</option>
                            <?php foreach($stores as $s): ?>
                            <option value="<?= $s->idStore ?>" <?= $storeId == $s->idStore ? 'selected' : '' ?>><?= $s->name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="img" value="1" <?= $onlyImg ? 'checked' : '' ?> class="rounded"> Solo con foto
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg">Filtrar</button>
                        <?php if($search || $familyId || $onlyImg || $storeId): ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog') ?>" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50">Limpiar</a>
                        <?php endif; ?>
                        <?php if($familyId): ?><input type="hidden" name="f" value="<?= $familyId ?>"><?php endif; ?>
                    </form>

                    <!-- Familias como pills -->
                    <div class="flex flex-wrap mt-3">
                        <a href="<?= base_url('sisvent/commercial/smartcatalog') . ($search ? '?q=' . urlencode($search) : '') ?>" class="family-pill <?= !$familyId ? 'active' : 'bg-gray-100 text-gray-600' ?>">
                            📦 Todos
                        </a>
                        <?php foreach($families as $f): if($f->idFamily == 1 || $f->total_products == 0) continue; ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog?f=' . $f->idFamily . ($search ? '&q=' . urlencode($search) : '') . ($storeId ? '&store=' . $storeId : '')) ?>" class="family-pill <?= $familyId == $f->idFamily ? 'active' : 'bg-gray-100 text-gray-600' ?>">
                            <?= $family_icons[$f->idFamily] ?? '📦' ?> <?= $f->name ?> <span class="text-xs opacity-60">(<?= $f->total_products ?>)</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Grid de productos -->
                <?php if(empty($products)): ?>
                    <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-gray-400">No se encontraron productos</p>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog') ?>" class="text-sm text-blue-500 mt-2 inline-block">Limpiar filtros</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach($products as $p):
                            $imgurl = $p->picture_url;
                            if ($p->picture_url == 'products/no_image.png' && file_exists('public/dist/images/products/' . $p->idProduct . '.jpg')) {
                                $imgurl = 'products/' . $p->idProduct . '.jpg';
                                $p->hasImage = true;
                            }
                        ?>
                        <div class="product-card">
                            <div class="product-img">
                                <?php if($p->hasImage): ?>
                                    <a href="<?= get_images_path($imgurl) ?>" data-fancybox>
                                        <img src="<?= get_images_path($imgurl) ?>" alt="<?= htmlspecialchars($p->description) ?>" loading="lazy" onerror="this.style.display='none'">
                                    </a>
                                    <span class="badge-foto">FOTO</span>
                                <?php else: ?>
                                    <span class="text-2xl font-bold text-gray-200"><?= htmlspecialchars($p->idProduct) ?></span>
                                <?php endif; ?>

                                <!-- ABC badge -->
                                <?php if($p->abc_type): ?>
                                    <span class="badge-abc badge-<?= strtolower($p->abc_type) ?>"><?= $p->abc_type ?></span>
                                <?php endif; ?>

                                <!-- Stock badge -->
                                <?php if($p->total_stock > 10): ?>
                                    <span class="badge-stock stock-ok"><?= number_format($p->total_stock) ?> uds</span>
                                <?php elseif($p->total_stock > 0): ?>
                                    <span class="badge-stock stock-low"><?= $p->total_stock ?> uds ⚠️</span>
                                <?php else: ?>
                                    <span class="badge-stock stock-out">Agotado</span>
                                <?php endif; ?>

                                <!-- Add to cart -->
                                <?php if($p->total_stock > 0): ?>
                                <button class="btn-add-cart" onclick="addToCart('<?= htmlspecialchars($p->idProduct) ?>', '<?= htmlspecialchars(addslashes($p->description)) ?>', <?= $p->price ?>, <?= $p->total_stock ?>)" title="Agregar al carrito">+</button>
                                <?php endif; ?>
                            </div>

                            <div class="p-3">
                                <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($p->idProduct) ?></p>
                                <p class="text-xs text-gray-700 mt-0.5 leading-snug" style="min-height:32px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    <?= htmlspecialchars($p->description) ?>
                                </p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5"><?= htmlspecialchars($p->family_name ?? '') ?></span>
                                    <?php if($p->total_vendido > 0): ?>
                                    <span class="text-xs text-gray-400" title="<?= number_format($p->total_vendido) ?> unidades vendidas en <?= $p->total_facturas ?> facturas">
                                        🔥 <?= number_format($p->total_vendido) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm font-bold text-gray-800 mt-2">$<?= number_format($p->price, 0, ',', '.') ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginación -->
                    <?php $lastPage = ceil($total / $limit); if($lastPage > 1): ?>
                    <div class="flex justify-center gap-2 mt-6 mb-8">
                        <?php if($page > 1): ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog?p=' . ($page-1) . ($familyId ? '&f='.$familyId : '') . ($search ? '&q='.urlencode($search) : '') . ($storeId ? '&store='.$storeId : '')) ?>" class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50">← Anterior</a>
                        <?php endif; ?>
                        <span class="px-3 py-2 text-sm text-gray-500">Página <?= $page ?> de <?= $lastPage ?></span>
                        <?php if($page < $lastPage): ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog?p=' . ($page+1) . ($familyId ? '&f='.$familyId : '') . ($search ? '&q='.urlencode($search) : '') . ($storeId ? '&store='.$storeId : '')) ?>" class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50">Siguiente →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>
<!-- Carrito FAB -->
<button class="cart-fab" id="cartFab" onclick="toggleCart()">🛒 <span id="cartCount">0</span> productos · $<span id="cartTotal">0</span></button>

<!-- Cart overlay -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

<!-- Cart panel -->
<div class="cart-panel" id="cartPanel">
    <div class="p-4 border-b flex items-center justify-between" style="background:#FF6B00; color:white">
        <h3 class="font-bold text-lg">🛒 Pedido (<span id="cartCountPanel">0</span>)</h3>
        <button onclick="toggleCart()" class="text-white opacity-70 hover:opacity-100 text-xl">✕</button>
    </div>
    <div class="flex-1 overflow-y-auto p-4" id="cartItems">
        <p class="text-gray-400 text-sm text-center py-8">Agrega productos con el boton +</p>
    </div>
    <div class="border-t p-4 bg-gray-50">
        <div class="flex justify-between items-center mb-3">
            <span class="text-sm text-gray-500">Total estimado:</span>
            <span class="text-xl font-bold text-gray-800">$<span id="cartTotalPanel">0</span></span>
        </div>
        <button onclick="sendCartWhatsApp()" class="w-full py-3 rounded-lg bg-green-500 text-white font-bold text-sm hover:bg-green-600 transition mb-2">📲 Enviar pedido por WhatsApp</button>
        <button onclick="copyCartText()" class="w-full py-2 rounded-lg bg-gray-200 text-gray-700 font-medium text-sm hover:bg-gray-300 transition mb-2">📋 Copiar pedido</button>
        <button onclick="clearCart()" class="w-full py-2 text-xs text-gray-400 hover:text-red-500 transition">🗑 Vaciar pedido</button>
    </div>
</div>

<script>
// ============================================================
// CARRITO — Smart Catalog (localStorage, funciona offline)
// ============================================================
var CART_KEY = 'mam_smart_cart';
var cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');

function addToCart(code, name, price, stock) {
    var item = cart.find(function(i) { return i.code === code; });
    if (item) {
        if (item.qty >= stock) { showToast('Stock maximo: ' + stock + ' uds'); return; }
        item.qty++;
    } else {
        cart.push({ code: code, name: name, price: price, qty: 1, stock: stock });
    }
    saveCart();
    showToast(name.substring(0, 30) + ' agregado');
    // Visual feedback
    var cards = document.querySelectorAll('.product-card');
    cards.forEach(function(card) {
        var btn = card.querySelector('.btn-add-cart');
        if (btn && btn.getAttribute('onclick').indexOf("'" + code + "'") > -1) {
            card.classList.add('cart-item-added');
            setTimeout(function() { card.classList.remove('cart-item-added'); }, 300);
        }
    });
}

function updateQty(code, delta) {
    var item = cart.find(function(i) { return i.code === code; });
    if (!item) return;
    if (delta > 0 && item.qty >= item.stock) { showToast('Stock maximo: ' + item.stock); return; }
    item.qty += delta;
    if (item.qty <= 0) cart = cart.filter(function(i) { return i.code !== code; });
    saveCart();
}

function removeFromCart(code) {
    cart = cart.filter(function(i) { return i.code !== code; });
    saveCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (!confirm('Vaciar todo el pedido?')) return;
    cart = [];
    saveCart();
}

function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    renderCart();
}

function renderCart() {
    var totalItems = cart.reduce(function(s, i) { return s + i.qty; }, 0);
    var totalMoney = cart.reduce(function(s, i) { return s + (i.price * i.qty); }, 0);
    var fmt = function(n) { return n.toLocaleString('es-CO'); };

    document.getElementById('cartCount').textContent = totalItems;
    document.getElementById('cartCountPanel').textContent = totalItems;
    document.getElementById('cartTotal').textContent = fmt(totalMoney);
    document.getElementById('cartTotalPanel').textContent = fmt(totalMoney);
    document.getElementById('cartFab').style.display = totalItems > 0 ? 'block' : 'none';

    var html = '';
    if (cart.length === 0) {
        html = '<p class="text-gray-400 text-sm text-center py-8">Agrega productos con el boton +</p>';
    } else {
        cart.forEach(function(item) {
            var subtotal = item.price * item.qty;
            html += '<div style="padding:10px 0; border-bottom:1px solid #F3F4F6">' +
                '<div style="display:flex; justify-content:space-between; align-items:start">' +
                '<div style="flex:1">' +
                '<span style="font-size:12px; font-family:monospace; color:#3B82F6; font-weight:600">' + item.code + '</span>' +
                '<p style="font-size:11px; color:#6B7280; margin-top:2px; line-height:1.3">' + item.name + '</p>' +
                '</div>' +
                '<button onclick="removeFromCart(\'' + item.code + '\')" style="color:#EF4444; background:none; border:none; cursor:pointer; font-size:16px; padding:0 4px" title="Eliminar">✕</button>' +
                '</div>' +
                '<div style="display:flex; align-items:center; justify-content:space-between; margin-top:8px">' +
                '<div style="display:flex; align-items:center; gap:8px">' +
                '<button onclick="updateQty(\'' + item.code + '\', -1)" style="width:28px; height:28px; border:1px solid #E5E7EB; border-radius:6px; background:white; cursor:pointer; font-size:16px">−</button>' +
                '<span style="width:28px; text-align:center; font-weight:700; font-size:14px">' + item.qty + '</span>' +
                '<button onclick="updateQty(\'' + item.code + '\', 1)" style="width:28px; height:28px; border:1px solid #E5E7EB; border-radius:6px; background:white; cursor:pointer; font-size:16px">+</button>' +
                '</div>' +
                '<div style="text-align:right">' +
                '<p style="font-size:11px; color:#9CA3AF">$' + fmt(item.price) + ' x ' + item.qty + '</p>' +
                '<p style="font-size:14px; font-weight:700; color:#1F2937">$' + fmt(subtotal) + '</p>' +
                '</div>' +
                '</div></div>';
        });
    }
    document.getElementById('cartItems').innerHTML = html;
}

function toggleCart() {
    var panel = document.getElementById('cartPanel');
    var overlay = document.getElementById('cartOverlay');
    var isOpen = panel.style.display === 'flex';
    panel.style.display = isOpen ? 'none' : 'flex';
    overlay.style.display = isOpen ? 'none' : 'block';
}

function buildCartText() {
    if (cart.length === 0) return '';
    var totalItems = cart.reduce(function(s, i) { return s + i.qty; }, 0);
    var totalMoney = cart.reduce(function(s, i) { return s + (i.price * i.qty); }, 0);
    var text = '📋 *Pedido Smart Catalog Ledxury*\n';
    text += '📅 ' + new Date().toLocaleDateString('es-CO') + '\n\n';
    cart.forEach(function(item, idx) {
        text += (idx + 1) + '. ' + item.code + ' - ' + item.name + '\n';
        text += '   Cant: ' + item.qty + ' x $' + item.price.toLocaleString('es-CO') + ' = $' + (item.price * item.qty).toLocaleString('es-CO') + '\n';
    });
    text += '\n📦 Total: ' + totalItems + ' productos';
    text += '\n💰 Valor: $' + totalMoney.toLocaleString('es-CO');
    text += '\n\n_Enviado desde Smart Catalog MAM_';
    return text;
}

function sendCartWhatsApp() {
    var text = buildCartText();
    if (!text) return;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

function copyCartText() {
    var text = buildCartText();
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        showToast('Pedido copiado al portapapeles');
    });
}

function showToast(msg) {
    var existing = document.getElementById('cartToast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.id = 'cartToast';
    toast.textContent = msg;
    toast.style.cssText = 'position:fixed; bottom:80px; right:20px; z-index:60; background:#1F2937; color:white; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:500; animation:fadeIn 0.2s ease; box-shadow:0 4px 12px rgba(0,0,0,0.15)';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 2000);
}

// Init
renderCart();
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
