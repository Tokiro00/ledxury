<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAM - Catálogo Digital</title>
    <link rel="stylesheet" href="<?= base_url('public/dist/catalogo/catalogo.css') ?>">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-inner">
        <a href="<?= base_url('catalogo') ?>" class="logo">
            <span class="logo-text">MAM</span>
            <span class="logo-sub">Catálogo Digital</span>
        </a>

        <div class="search-box">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Buscar código o nombre..."
                   value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            <?php if ($search): ?>
                <button class="search-clear" onclick="clearSearch()">✕</button>
            <?php endif; ?>
        </div>

        <div class="header-actions">
            <button class="btn-share" onclick="shareCatalog()" title="Compartir por WhatsApp">
                📤 <span class="hide-mobile">Compartir</span>
            </button>
            <button class="btn-budget" id="btnBudget" onclick="toggleBudget()" style="display:none">
                📋 Presupuesto (<span id="budgetCount">0</span>)
            </button>
        </div>
    </div>
</header>

<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <p class="sidebar-title">CATEGORÍAS</p>

        <a href="<?= base_url('catalogo') ?>"
           class="family-link <?= $familyId == 0 ? 'active' : '' ?>">
            📦 Todos (<?= $stats['total'] ?>)
        </a>

        <?php
        $icons = [1=>'📦',2=>'📱',3=>'🔦',4=>'🪞',5=>'💡',6=>'🚗',7=>'💫',8=>'🔌',9=>'🦺',10=>'🔧'];
        foreach ($families as $f):
            if ($f->idFamily == 1) continue;
        ?>
            <a href="<?= base_url('catalogo?f=' . $f->idFamily . ($search ? '&q=' . urlencode($search) : '')) ?>"
               class="family-link <?= $familyId == $f->idFamily ? 'active' : '' ?>">
                <?= $icons[$f->idFamily] ?? '📦' ?> <?= $f->name ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-divider"></div>

        <!-- Filtro por tienda -->
        <label class="checkbox-label" style="font-weight:600;font-size:11px;color:#666;margin-bottom:4px;display:block;">Tienda</label>
        <select id="selStore" onchange="applyFilters()" style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:6px;font-size:12px;margin-bottom:8px;">
            <option value="0">Todas las bodegas</option>
            <?php if (!empty($stores)): foreach ($stores as $s): ?>
            <option value="<?= $s->idStore ?>" <?= $storeId == $s->idStore ? 'selected' : '' ?>><?= $s->name ?></option>
            <?php endforeach; endif; ?>
        </select>

        <label class="checkbox-label">
            <input type="checkbox" id="chkAvailable" <?= $onlyAvailable ? 'checked' : '' ?>>
            Solo con disponibilidad
        </label>

        <label class="checkbox-label">
            <input type="checkbox" id="chkOnlyImg" <?= $onlyWithImg ? 'checked' : '' ?>>
            Solo con foto
        </label>

        <button onclick="applyFilters()" style="width:100%;margin-top:8px;padding:8px;background:#FF6B00;color:white;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Aplicar Filtros</button>

        <div class="sidebar-divider"></div>

        <div class="view-toggle">
            <button class="view-btn active" id="btnGrid" onclick="setView('grid')">▦</button>
            <button class="view-btn" id="btnList" onclick="setView('list')">≡</button>
        </div>

        <?php if (isset($vendor) && $vendor): ?>
            <div class="vendor-card">
                <p class="vendor-name"><?= htmlspecialchars($vendor->name) ?></p>
                <p class="vendor-info">Tu asesor comercial</p>
                <?php if ($vendor->phone): ?>
                    <a href="https://wa.me/57<?= preg_replace('/\D/', '', $vendor->phone) ?>"
                       class="vendor-wa" target="_blank">
                        💬 WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Products -->
    <main class="main">
        <div class="results-header">
            <div>
                <h1 class="results-title"><?= htmlspecialchars($familyName) ?>
                    <?php if ($search): ?>
                        <span class="results-query"> · "<?= htmlspecialchars($search) ?>"</span>
                    <?php endif; ?>
                </h1>
                <p class="results-count"><?= count($products) ?> productos</p>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>No se encontraron productos</p>
                <a href="<?= base_url('catalogo') ?>">Limpiar filtros</a>
            </div>
        <?php else: ?>

            <!-- Grid View -->
            <div class="products-grid" id="viewGrid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card" data-code="<?= htmlspecialchars($p->code) ?>">
                        <div class="product-image <?= $p->hasImage ? 'has-img' : '' ?>">
                            <?php if ($p->hasImage): ?>
                                <img src="<?= base_url($p->image_url) ?>"
                                     alt="<?= htmlspecialchars($p->name) ?>"
                                     loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="no-img-fallback" style="display:none">
                                    <span class="product-code-big"><?= htmlspecialchars($p->code) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="no-img">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <?php if ($p->hasImage): ?>
                                <span class="badge-foto">CON FOTO</span>
                            <?php endif; ?>

                            <button class="btn-add" onclick="addToBudget('<?= htmlspecialchars($p->code) ?>', '<?= htmlspecialchars(addslashes($p->name)) ?>', <?= $p->price ?>)">+</button>
                        </div>

                        <div class="product-info">
                            <p class="product-code"><?= htmlspecialchars($p->code) ?></p>
                            <p class="product-name"><?= htmlspecialchars($p->name) ?></p>
                            <span class="product-family"><?= htmlspecialchars($p->familyName ?? '') ?></span>
                            <?php if (isset($p->stock) && $storeId > 0): ?>
                            <span style="font-size:10px;color:<?= $p->stock > 0 ? '#16a34a' : '#dc2626' ?>;font-weight:600;">Stock: <?= number_format($p->stock, 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- List View -->
            <div class="products-list" id="viewList" style="display:none">
                <?php foreach ($products as $p): ?>
                    <div class="product-row" data-code="<?= htmlspecialchars($p->code) ?>">
                        <div class="row-img">
                            <?php if ($p->hasImage): ?>
                                <img src="<?= base_url($p->image_url) ?>"
                                     alt="" loading="lazy"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <span class="row-no-img">📷</span>
                            <?php endif; ?>
                        </div>
                        <div class="row-info">
                            <div class="row-top">
                                <span class="product-code"><?= htmlspecialchars($p->code) ?></span>
                                <span class="product-family"><?= htmlspecialchars($p->familyName ?? '') ?></span>
                                <?php if ($p->hasImage): ?>
                                    <span class="dot-foto">●</span>
                                <?php endif; ?>
                            </div>
                            <p class="product-name"><?= htmlspecialchars($p->name) ?></p>
                        </div>
                        <button class="btn-add-row" onclick="addToBudget('<?= htmlspecialchars($p->code) ?>', '<?= htmlspecialchars(addslashes($p->name)) ?>', <?= $p->price ?>)">+</button>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </main>

    <!-- Budget Panel -->
    <aside class="budget-panel" id="budgetPanel" style="display:none">
        <div class="budget-header">
            <h2>📋 Presupuesto (<span id="budgetCountPanel">0</span>)</h2>
            <button onclick="toggleBudget()">✕</button>
        </div>

        <div class="budget-items" id="budgetItems">
            <!-- Se llena con JS -->
        </div>

        <div class="budget-footer">
            <button class="btn-wa" onclick="sendBudgetWhatsApp()">
                📲 Enviar por WhatsApp
            </button>
            <button class="btn-save" onclick="saveBudget()">
                💾 Guardar como presupuesto
            </button>
            <button class="btn-clear" onclick="clearBudget()">
                Vaciar
            </button>
        </div>
    </aside>
</div>

<!-- Mobile Budget FAB -->
<button class="fab-budget" id="fabBudget" onclick="toggleBudget()" style="display:none">
    📋 <span id="fabCount">0</span>
</button>

<script>
// ============================================================
// CATÁLOGO MAM - JavaScript
// ============================================================

// Estado del presupuesto
let budget = [];
const BASE_URL = '<?= base_url() ?>';
const VENDOR_ID = '<?= $vendorId ?>';
const CLIENT_ID = '<?= $clientId ?>';
const FAMILY_ID = <?= $familyId ?>;
const SEARCH = '<?= addslashes($search) ?>';

// --- BÚSQUEDA ---
const searchInput = document.getElementById('searchInput');
let searchTimeout;
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const q = this.value.trim();
        const url = new URL(window.location);
        if (q) url.searchParams.set('q', q);
        else url.searchParams.delete('q');
        window.location = url.toString();
    }, 500);
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        const url = new URL(window.location);
        if (q) url.searchParams.set('q', q);
        else url.searchParams.delete('q');
        window.location = url.toString();
    }
});

function clearSearch() {
    const url = new URL(window.location);
    url.searchParams.delete('q');
    window.location = url.toString();
}

// --- FILTRO SOLO CON FOTO ---
function toggleImgFilter() { applyFilters(); }

function applyFilters() {
    const url = new URL(window.location);

    // Tienda
    const store = document.getElementById('selStore').value;
    if (store && store !== '0') url.searchParams.set('store', store);
    else url.searchParams.delete('store');

    // Disponibilidad
    if (document.getElementById('chkAvailable').checked) url.searchParams.set('disp', '1');
    else url.searchParams.delete('disp');

    // Solo con foto
    if (document.getElementById('chkOnlyImg').checked) url.searchParams.set('img', '1');
    else url.searchParams.delete('img');

    window.location = url.toString();
}

// --- VISTAS ---
function setView(mode) {
    document.getElementById('viewGrid').style.display = mode === 'grid' ? '' : 'none';
    document.getElementById('viewList').style.display = mode === 'list' ? '' : 'none';
    document.getElementById('btnGrid').classList.toggle('active', mode === 'grid');
    document.getElementById('btnList').classList.toggle('active', mode === 'list');
    localStorage.setItem('catalogView', mode);
}

// Restaurar vista guardada
const savedView = localStorage.getItem('catalogView');
if (savedView === 'list') setView('list');

// --- PRESUPUESTO ---
function addToBudget(code, name, price) {
    const existing = budget.find(b => b.code === code);
    if (existing) {
        existing.qty++;
    } else {
        budget.push({ code, name, price, qty: 1 });
    }
    updateBudgetUI();

    // Feedback visual
    const card = document.querySelector(`[data-code="${code}"]`);
    if (card) {
        card.style.outline = '2px solid #FF6B00';
        setTimeout(() => card.style.outline = '', 300);
    }
}

function updateBudgetQty(code, delta) {
    const item = budget.find(b => b.code === code);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        budget = budget.filter(b => b.code !== code);
    }
    updateBudgetUI();
}

function removeBudgetItem(code) {
    budget = budget.filter(b => b.code !== code);
    updateBudgetUI();
}

function clearBudget() {
    budget = [];
    updateBudgetUI();
}

function updateBudgetUI() {
    const totalItems = budget.reduce((s, b) => s + b.qty, 0);

    // Counters
    document.getElementById('budgetCount').textContent = totalItems;
    document.getElementById('budgetCountPanel').textContent = totalItems;
    document.getElementById('fabCount').textContent = totalItems;

    // Show/hide buttons
    document.getElementById('btnBudget').style.display = totalItems > 0 ? '' : 'none';
    document.getElementById('fabBudget').style.display = totalItems > 0 ? '' : 'none';

    // Render items
    const container = document.getElementById('budgetItems');
    if (budget.length === 0) {
        container.innerHTML = '<div class="budget-empty">Agrega productos desde el catálogo</div>';
        return;
    }

    container.innerHTML = budget.map(b => `
        <div class="budget-item">
            <div class="budget-item-info">
                <span class="budget-item-code">${b.code}</span>
                <span class="budget-item-name">${b.name}</span>
            </div>
            <div class="budget-item-actions">
                <button class="qty-btn" onclick="updateBudgetQty('${b.code}', -1)">−</button>
                <span class="qty-val">${b.qty}</span>
                <button class="qty-btn" onclick="updateBudgetQty('${b.code}', 1)">+</button>
                <button class="remove-btn" onclick="removeBudgetItem('${b.code}')">✕</button>
            </div>
        </div>
    `).join('');
}

function toggleBudget() {
    const panel = document.getElementById('budgetPanel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
}

// --- COMPARTIR POR WHATSAPP ---
function shareCatalog() {
    const url = window.location.href;
    const familyName = '<?= addslashes($familyName) ?>';
    const count = <?= count($products) ?>;
    const text = `📋 *Catálogo MAM - ${familyName}*\n${count} productos disponibles\n\n👉 ${url}`;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

function sendBudgetWhatsApp() {
    if (budget.length === 0) return;

    let text = '📋 *Presupuesto MAM*\n\n';
    budget.forEach(b => {
        text += `▪️ ${b.code} - ${b.name} x${b.qty}\n`;
    });

    const totalItems = budget.reduce((s, b) => s + b.qty, 0);
    text += `\n📦 Total: ${totalItems} productos`;

    if (VENDOR_ID) text += `\n👤 Asesor: ${VENDOR_ID}`;

    text += '\n\n_Enviado desde Catálogo Digital MAM_';

    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

// --- GUARDAR PRESUPUESTO (en budgets de MAM) ---
function saveBudget() {
    if (budget.length === 0) {
        alert('Agrega productos al presupuesto primero');
        return;
    }

    if (!CLIENT_ID) {
        alert('Este catálogo no está vinculado a un cliente.\nComparte el presupuesto por WhatsApp o pide a tu admin que lo cree.');
        sendBudgetWhatsApp();
        return;
    }

    const payload = {
        clientId: parseInt(CLIENT_ID),
        vendorId: VENDOR_ID,
        storeId: 7, // Default Barranquilla
        items: budget.map(b => ({ productId: b.code, quantity: b.qty }))
    };

    fetch(BASE_URL + 'catalogo/crear_presupuesto', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`✅ Presupuesto #${data.budgetId} creado!\n${data.items} productos - Total registrado`);
            clearBudget();
            toggleBudget();
        } else {
            alert('Error: ' + (data.error || 'No se pudo crear'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión. Intenta de nuevo.');
    });
}

// Init
updateBudgetUI();
</script>

</body>
</html>
