<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$nombre = $client->commercial_name ?: $client->name;
$baseUrl = base_url('sisvent/commercial/smartcatalog/clientcatalog/' . $client->idClient);
$family_icons = [1=>'📦',2=>'📱',3=>'🔦',4=>'🪞',5=>'💡',6=>'🚗',7=>'💫',8=>'🔌',9=>'🦺',10=>'🔧'];
?>
<!DOCTYPE html>
<html lang="es">
<title>MAM — Catálogo de <?= htmlspecialchars($nombre) ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.product-card { border-radius: 12px; overflow: hidden; transition: all 0.2s; border: 1px solid #E5E7EB; background: white; position: relative; }
.product-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-color: #3B82F6; }
.product-card.favorite { border-color: #F59E0B; }
.product-img { aspect-ratio: 1; background: #F3F4F6; position: relative; display: flex; align-items: center; justify-content: center; }
.product-img img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.badge-foto { position: absolute; top: 8px; left: 8px; background: #10B981; color: white; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-fav { position: absolute; top: 8px; right: 8px; background: #F59E0B; color: white; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-rec { position: absolute; top: 8px; right: 8px; background: #8B5CF6; color: white; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.badge-times { position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; font-size: 10px; padding: 2px 8px; border-radius: 4px; }
.hero-banner { background: linear-gradient(135deg, #1E3A5F 0%, #0F2A45 100%); color: white; border-radius: 12px; padding: 24px 32px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.tab { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s; text-decoration: none; }
.tab.active { background: #1E3A5F; color: white; }
.tab:not(.active) { background: #F3F4F6; color: #6B7280; }
.tab:not(.active):hover { background: #E5E7EB; }
.btn-add-cart { position: absolute; bottom: 8px; left: 8px; width: 32px; height: 32px; border-radius: 50%; background: #3B82F6; color: white; border: none; font-size: 18px; cursor: pointer; opacity: 0; transition: all 0.2s; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.product-card:hover .btn-add-cart { opacity: 1; }
.btn-add-cart:hover { background: #2563EB; transform: scale(1.1); }
.cart-fab { position: fixed; bottom: 20px; right: 20px; z-index: 40; padding: 14px 24px; border-radius: 50px; background: #3B82F6; color: white; border: none; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(59,130,246,0.4); display: none; }
.cart-panel { position: fixed; top: 0; right: 0; bottom: 0; width: 360px; background: white; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 50; display: none; flex-direction: column; }
.cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 45; display: none; }
.family-pill { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; transition: all 0.15s; margin: 2px; text-decoration: none; }
.family-pill.active { background: #1E3A5F; color: white; }
.family-pill:not(.active) { background: #F3F4F6; color: #6B7280; }
@media (max-width: 768px) { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; } .cart-panel { width: 100%; } .hero-banner { flex-direction: column; text-align: center; } .btn-add-cart { opacity: 1; } }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="px-6 mx-auto">

                <!-- Banner -->
                <div class="hero-banner mt-4">
                    <div>
                        <p class="text-sm text-blue-200 mb-1">Catálogo personalizado para</p>
                        <h2 class="text-2xl font-bold"><?= htmlspecialchars($nombre) ?></h2>
                        <p class="text-sm text-blue-200 mt-1">📍 <?= htmlspecialchars($client->city ?? '') ?>
                            <?php if($vendor): ?> · 👤 <?= htmlspecialchars($vendor->name) ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <?php if($vendor && $vendor->phone):
                            $waPhone = preg_replace('/\D/', '', $vendor->phone);
                            if(strlen($waPhone) === 10) $waPhone = '57' . $waPhone;
                        ?>
                        <a href="https://wa.me/<?= $waPhone ?>" target="_blank" class="px-4 py-2 text-sm font-medium bg-green-500 text-white rounded-lg hover:bg-green-600">💬 WhatsApp</a>
                        <?php endif; ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clientview/' . $client->idClient) ?>" class="px-4 py-2 text-sm font-medium bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30">📊 Dashboard</a>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <a href="<?= $baseUrl ?>?tab=favoritos" class="tab <?= $tab === 'favoritos' ? 'active' : '' ?>">⭐ Favoritos (<?= count($products) ?>)</a>
                    <a href="<?= $baseUrl ?>?tab=recomendados" class="tab <?= $tab === 'recomendados' ? 'active' : '' ?>">💡 Recomendados (<?= count($recommendations) ?>)</a>
                    <a href="<?= $baseUrl ?>?tab=completo" class="tab <?= $tab === 'completo' ? 'active' : '' ?>">📦 Catálogo completo</a>
                </div>

                <!-- TAB: Favoritos -->
                <?php if($tab === 'favoritos'): ?>
                <h3 class="text-lg font-bold text-gray-800 mb-1">🔥 Productos que más compra <?= htmlspecialchars(explode(' ', $nombre)[0]) ?></h3>
                <p class="text-sm text-gray-500 mb-4">Basado en historial. Con su precio real.</p>

                <div class="product-grid">
                    <?php foreach($products as $p):
                        $imgurl = $p->picture_url;
                        if ($p->picture_url == 'products/no_image.png' && file_exists('public/dist/images/products/' . $p->idProduct . '.jpg')) {
                            $imgurl = 'products/' . $p->idProduct . '.jpg';
                            $p->hasImage = true;
                        }
                    ?>
                    <div class="product-card favorite" data-code="<?= htmlspecialchars($p->idProduct) ?>" data-name="<?= htmlspecialchars($p->description) ?>" data-price="<?= $p->precio_promedio ?>">
                        <div class="product-img">
                            <?php if($p->hasImage): ?>
                                <img src="<?= get_images_path($imgurl) ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                                <span class="badge-foto">FOTO</span>
                            <?php else: ?>
                                <span class="text-2xl font-bold text-gray-200"><?= htmlspecialchars($p->idProduct) ?></span>
                            <?php endif; ?>
                            <span class="badge-fav">⭐ FAVORITO</span>
                            <span class="badge-times">🔄 <?= $p->veces ?>x · 📦 <?= number_format($p->total_comprado) ?></span>
                            <button class="btn-add-cart" onclick="addToCart('<?= htmlspecialchars($p->idProduct) ?>', '<?= htmlspecialchars(addslashes($p->description)) ?>', <?= $p->precio_promedio ?>)">+</button>
                        </div>
                        <div class="p-3">
                            <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($p->idProduct) ?></p>
                            <p class="text-xs text-gray-700 mt-0.5 leading-snug" style="min-height:32px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($p->description) ?></p>
                            <?php if($p->precio_promedio > 0 && $p->precio_promedio != $p->price): ?>
                                <p class="text-xs text-gray-400 line-through mt-1">Lista: $<?= number_format($p->price, 0, ',', '.') ?></p>
                                <p class="text-lg font-bold text-green-600">$<?= number_format($p->precio_promedio, 0, ',', '.') ?></p>
                                <?php $dcto = round((1 - $p->precio_promedio / $p->price) * 100); ?>
                                <p class="text-xs text-green-500 font-medium"><?= $dcto > 0 ? "-{$dcto}% tu precio" : 'Tu precio' ?></p>
                            <?php else: ?>
                                <p class="text-sm font-bold text-gray-800 mt-2">$<?= number_format($p->price, 0, ',', '.') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- TAB: Recomendados -->
                <?php elseif($tab === 'recomendados'): ?>
                <h3 class="text-lg font-bold text-gray-800 mb-1">💡 Productos recomendados</h3>
                <p class="text-sm text-gray-500 mb-4">Clientes similares compran estos productos que <?= htmlspecialchars(explode(' ', $nombre)[0]) ?> aún no tiene.</p>

                <?php if(empty($recommendations)): ?>
                    <div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-400">No hay recomendaciones disponibles</p></div>
                <?php else: ?>
                <div class="product-grid">
                    <?php foreach($recommendations as $p):
                        $imgurl = $p->picture_url ?: 'products/no_image.png';
                        if ($imgurl == 'products/no_image.png' && file_exists('public/dist/images/products/' . $p->productId . '.jpg')) {
                            $imgurl = 'products/' . $p->productId . '.jpg';
                            $p->hasImage = true;
                        }
                    ?>
                    <div class="product-card" data-code="<?= htmlspecialchars($p->productId) ?>" data-name="<?= htmlspecialchars($p->description) ?>" data-price="<?= $p->price ?>">
                        <div class="product-img">
                            <?php if($p->hasImage): ?>
                                <img src="<?= get_images_path($imgurl) ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                                <span class="badge-foto">FOTO</span>
                            <?php else: ?>
                                <span class="text-2xl font-bold text-gray-200"><?= htmlspecialchars($p->productId) ?></span>
                            <?php endif; ?>
                            <span class="badge-rec">💡 SUGERIDO</span>
                            <button class="btn-add-cart" onclick="addToCart('<?= htmlspecialchars($p->productId) ?>', '<?= htmlspecialchars(addslashes($p->description)) ?>', <?= $p->price ?>)">+</button>
                        </div>
                        <div class="p-3">
                            <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($p->productId) ?></p>
                            <p class="text-xs text-gray-700 mt-0.5 leading-snug" style="min-height:32px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($p->description) ?></p>
                            <p class="text-xs text-purple-500 mt-1"><?= $p->clientes_que_compran ?> clientes similares lo compran</p>
                            <p class="text-sm font-bold text-gray-800 mt-1">$<?= number_format($p->price, 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- TAB: Catálogo completo -->
                <?php elseif($tab === 'completo'): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <form method="get" class="flex flex-col md:flex-row gap-3">
                        <input type="hidden" name="tab" value="completo">
                        <div class="relative flex-1">
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar producto..." class="w-full py-2 pl-10 pr-4 text-sm border border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none bg-gray-50" autofocus>
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg">Buscar</button>
                        <?php if($search): ?>
                        <a href="<?= $baseUrl ?>?tab=completo" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <div class="flex flex-wrap mt-3">
                        <a href="<?= $baseUrl ?>?tab=completo" class="family-pill <?= !$familyId ? 'active' : '' ?>">📦 Todos</a>
                        <?php foreach($families as $f): if($f->idFamily == 1 || $f->total_products == 0) continue; ?>
                        <a href="<?= $baseUrl ?>?tab=completo&f=<?= $f->idFamily ?>" class="family-pill <?= $familyId == $f->idFamily ? 'active' : '' ?>">
                            <?= $family_icons[$f->idFamily] ?? '📦' ?> <?= $f->name ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="product-grid">
                    <?php foreach($allProducts as $p):
                        $imgurl = $p->picture_url;
                        if ($p->picture_url == 'products/no_image.png' && file_exists('public/dist/images/products/' . $p->idProduct . '.jpg')) {
                            $imgurl = 'products/' . $p->idProduct . '.jpg';
                            $p->hasImage = true;
                        }
                    ?>
                    <div class="product-card" data-code="<?= htmlspecialchars($p->idProduct) ?>" data-name="<?= htmlspecialchars($p->description) ?>" data-price="<?= $p->price ?>">
                        <div class="product-img">
                            <?php if($p->hasImage): ?>
                                <img src="<?= get_images_path($imgurl) ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                                <span class="badge-foto">FOTO</span>
                            <?php else: ?>
                                <span class="text-2xl font-bold text-gray-200"><?= htmlspecialchars($p->idProduct) ?></span>
                            <?php endif; ?>
                            <?php if($p->total_stock > 0): ?>
                            <span class="badge-times"><?= number_format($p->total_stock) ?> uds</span>
                            <?php endif; ?>
                            <button class="btn-add-cart" onclick="addToCart('<?= htmlspecialchars($p->idProduct) ?>', '<?= htmlspecialchars(addslashes($p->description)) ?>', <?= $p->price ?>)">+</button>
                        </div>
                        <div class="p-3">
                            <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($p->idProduct) ?></p>
                            <p class="text-xs text-gray-700 mt-0.5 leading-snug" style="min-height:32px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($p->description) ?></p>
                            <span class="text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5"><?= htmlspecialchars($p->family_name ?? '') ?></span>
                            <p class="text-sm font-bold text-gray-800 mt-2">$<?= number_format($p->price, 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<!-- Carrito FAB -->
<button class="cart-fab" id="cartFab" onclick="toggleCart()">🛒 <span id="cartCount">0</span> productos</button>

<!-- Cart overlay -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

<!-- Cart panel -->
<div class="cart-panel" id="cartPanel">
    <div class="p-4 border-b flex items-center justify-between bg-blue-600 text-white">
        <h3 class="font-bold">🛒 Pedido (<span id="cartCountPanel">0</span>)</h3>
        <button onclick="toggleCart()" class="text-white opacity-70 hover:opacity-100 text-lg">✕</button>
    </div>
    <div class="flex-1 overflow-y-auto p-4" id="cartItems">
        <p class="text-gray-400 text-sm text-center py-8">Agrega productos con el botón +</p>
    </div>
    <div class="border-t p-4 space-y-2">
        <button onclick="sendCartWhatsApp()" class="w-full py-3 rounded-lg bg-green-500 text-white font-bold text-sm hover:bg-green-600">📲 Enviar pedido por WhatsApp</button>
        <button onclick="clearCart()" class="w-full py-2 text-xs text-gray-400 hover:text-red-500">Vaciar pedido</button>
    </div>
</div>

<script>
// Carrito con localStorage (funciona offline)
var CART_KEY = 'mam_cart_<?= $client->idClient ?>';
var cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
var CLIENT_NAME = '<?= addslashes($nombre) ?>';
var VENDOR_NAME = '<?= addslashes($vendor->name ?? '') ?>';
var VENDOR_PHONE = '<?= $vendor ? preg_replace('/\D/', '', $vendor->phone ?: '') : '' ?>';
if (VENDOR_PHONE.length === 10) VENDOR_PHONE = '57' + VENDOR_PHONE;

function addToCart(code, name, price) {
    var item = cart.find(function(i) { return i.code === code; });
    if (item) {
        item.qty++;
    } else {
        cart.push({ code: code, name: name, price: price, qty: 1 });
    }
    saveCart();
    // Feedback visual
    var card = document.querySelector('[data-code="' + code + '"]');
    if (card) { card.style.outline = '3px solid #3B82F6'; setTimeout(function() { card.style.outline = ''; }, 300); }
}

function updateQty(code, delta) {
    var item = cart.find(function(i) { return i.code === code; });
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) cart = cart.filter(function(i) { return i.code !== code; });
    saveCart();
}

function clearCart() { cart = []; saveCart(); }

function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    renderCart();
}

function renderCart() {
    var total = cart.reduce(function(s, i) { return s + i.qty; }, 0);
    document.getElementById('cartCount').textContent = total;
    document.getElementById('cartCountPanel').textContent = total;
    document.getElementById('cartFab').style.display = total > 0 ? 'block' : 'none';

    var html = '';
    if (cart.length === 0) {
        html = '<p class="text-gray-400 text-sm text-center py-8">Agrega productos con el botón +</p>';
    } else {
        cart.forEach(function(item) {
            html += '<div style="padding:8px 0; border-bottom:1px solid #F3F4F6">' +
                '<div style="display:flex; justify-content:space-between">' +
                '<span style="font-size:12px; font-family:monospace; color:#3B82F6; font-weight:600">' + item.code + '</span>' +
                '<span style="font-size:13px; font-weight:600">$' + item.price.toLocaleString('es-CO') + '</span>' +
                '</div>' +
                '<p style="font-size:11px; color:#6B7280; margin-top:2px">' + item.name + '</p>' +
                '<div style="display:flex; align-items:center; gap:6px; margin-top:6px">' +
                '<button onclick="updateQty(\'' + item.code + '\', -1)" style="width:24px; height:24px; border:1px solid #E5E7EB; border-radius:4px; background:white; cursor:pointer">−</button>' +
                '<span style="width:24px; text-align:center; font-weight:600">' + item.qty + '</span>' +
                '<button onclick="updateQty(\'' + item.code + '\', 1)" style="width:24px; height:24px; border:1px solid #E5E7EB; border-radius:4px; background:white; cursor:pointer">+</button>' +
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

function sendCartWhatsApp() {
    if (cart.length === 0) return;
    var text = '📋 *Pedido para ' + CLIENT_NAME + '*\n\n';
    var totalItems = 0;
    cart.forEach(function(item) {
        text += '▪️ ' + item.code + ' - ' + item.name + ' x' + item.qty + '\n';
        totalItems += item.qty;
    });
    text += '\n📦 Total: ' + totalItems + ' productos';
    text += '\n\n_Enviado desde Catálogo Digital MAM_';

    var url = VENDOR_PHONE
        ? 'https://wa.me/' + VENDOR_PHONE + '?text=' + encodeURIComponent(text)
        : 'https://wa.me/?text=' + encodeURIComponent(text);
    window.open(url, '_blank');
}

// Init
renderCart();
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
