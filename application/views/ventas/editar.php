<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Editar #<?= $budget->idBudget ?></title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>favicon.ico"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root { --petrol:#2E7D91; --navy:#1B365D; --bg:#f4f6f8; --card:#fff; --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0; --success:#10b981; --radius:12px; --radius-sm:8px; --shadow:0 1px 3px rgba(0,0,0,.08); --safe-bottom:env(safe-area-inset-bottom,0px); }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; height:100%; max-width:480px; margin:0 auto; background:var(--bg); }
        .header { height:56px; background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); justify-content:space-between; }
        .header h1 { font-size:16px; font-weight:700; }
        .header a { color:rgba(255,255,255,.8); font-size:12px; text-decoration:none; }
        .screen-container { flex:1; overflow-y:auto; padding:12px; padding-bottom:calc(80px + var(--safe-bottom) + 12px); }
        .card { background:var(--card); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow); margin-bottom:12px; }
        .card-title { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-bottom:12px; }
        .form-group { margin-bottom:12px; }
        .form-label { display:block; font-size:11px; color:var(--text-secondary); margin-bottom:4px; font-weight:600; text-transform:uppercase; }
        .form-input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; color:var(--text); outline:none; background:#fff; }
        .form-input:focus { border-color:var(--petrol); box-shadow:0 0 0 2px rgba(46,125,145,.15); }
        .product-row { display:flex; gap:8px; align-items:flex-end; margin-bottom:8px; padding:10px; background:#f8fafc; border-radius:var(--radius-sm); border:1px solid var(--border); }
        .product-row .col-code { flex:2; }
        .product-row .col-qty { flex:.8; }
        .product-row .col-unit { flex:1; }
        .product-row .col-del { flex:.3; display:flex; align-items:center; justify-content:center; }
        .product-input { width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:13px; }
        .product-input:focus { border-color:var(--petrol); outline:none; }
        .btn-add-product { width:100%; padding:10px; border:2px dashed var(--border); border-radius:var(--radius-sm); background:none; color:var(--petrol); font-size:13px; font-weight:600; cursor:pointer; }
        .btn-add-product:active { background:#f0f9ff; }
        .btn-del { background:none; border:none; color:#ef4444; cursor:pointer; padding:4px; }
        .total-display { text-align:center; font-size:24px; font-weight:800; color:var(--petrol); padding:8px 0; }
        .fixed-bottom { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); padding:12px 16px; padding-bottom:calc(12px + var(--safe-bottom)); display:flex; gap:10px; z-index:10; }
        .btn { flex:1; padding:14px; border:none; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; display:block; }
        .btn:active { transform:scale(.97); }
        .btn-save { background:var(--petrol); color:#fff; }
        .btn-back { background:#F3F4F6; color:#374151; }
        .autocomplete-list { position:absolute; left:0; right:0; top:100%; background:#fff; border:1px solid var(--border); border-radius:0 0 var(--radius-sm) var(--radius-sm); max-height:150px; overflow-y:auto; z-index:20; box-shadow:var(--shadow); }
        .autocomplete-item { padding:8px 12px; font-size:13px; cursor:pointer; border-bottom:1px solid #f3f4f6; }
        .autocomplete-item:active { background:#f0f9ff; }
        .autocomplete-item small { color:var(--text-secondary); }
    </style>
</head>
<body>
<div id="app">
    <div class="header">
        <a href="<?= base_url() ?>ventas/ver/<?= $budget->idBudget ?>">← Volver</a>
        <h1>Editar #<?= $budget->idBudget ?></h1>
        <span></span>
    </div>

    <div class="screen-container">
        <!-- Cliente -->
        <div class="card">
            <div class="card-title">Datos del cliente</div>
            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-input" id="client_name" value="<?= $client ? htmlspecialchars($client->name) : '' ?>">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <div class="form-group">
                    <label class="form-label">Documento</label>
                    <input type="text" class="form-input" id="client_doc" value="<?= $client ? $client->idNum : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Celular</label>
                    <input type="tel" class="form-input" id="client_phone" value="<?= $client ? $client->cellphone : '' ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Direccion</label>
                <input type="text" class="form-input" id="client_address" value="<?= $client ? htmlspecialchars($client->address) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ciudad</label>
                <input type="text" class="form-input" id="client_city" value="<?= $client ? htmlspecialchars($client->city) : '' ?>">
            </div>
        </div>

        <!-- Productos -->
        <div class="card">
            <div class="card-title">Productos</div>
            <div id="productList">
                <?php if (!empty($details)): foreach ($details as $i => $d): ?>
                <div class="product-row" data-idx="<?= $i ?>">
                    <div class="col-code" style="position:relative;">
                        <label class="form-label">Codigo</label>
                        <input type="text" class="product-input prod-code" value="<?= htmlspecialchars($d->productId) ?>" oninput="searchProduct(this)">
                    </div>
                    <div class="col-qty">
                        <label class="form-label">Cant</label>
                        <input type="number" class="product-input prod-qty" value="<?= $d->quantity ?>" min="1" onchange="calcTotal()">
                    </div>
                    <div class="col-unit">
                        <label class="form-label">Unit $</label>
                        <input type="number" class="product-input prod-unit" value="<?= (int)$d->unit ?>" min="0" onchange="calcTotal()">
                    </div>
                    <div class="col-del">
                        <button class="btn-del" onclick="removeProduct(this)">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <button class="btn-add-product" onclick="addProduct()">+ Agregar producto</button>
            <div class="total-display" id="totalDisplay">$<?= number_format($budget->total, 0, ',', '.') ?></div>
        </div>

        <!-- Observaciones -->
        <div class="card">
            <div class="card-title">Observaciones</div>
            <textarea class="form-input" id="comments" rows="3" style="resize:none;"><?= htmlspecialchars($budget->comments ?: '') ?></textarea>
        </div>
    </div>

    <div class="fixed-bottom">
        <a href="<?= base_url() ?>ventas/ver/<?= $budget->idBudget ?>" class="btn btn-back">Cancelar</a>
        <button class="btn btn-save" onclick="guardar()">Guardar</button>
    </div>
</div>

<script>
var BASE = '<?= base_url() ?>';
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
var productIdx = <?= !empty($details) ? count($details) : 0 ?>;

function addProduct() {
    var html = '<div class="product-row" data-idx="' + productIdx + '">' +
        '<div class="col-code" style="position:relative;"><label class="form-label">Codigo</label><input type="text" class="product-input prod-code" oninput="searchProduct(this)" placeholder="Buscar..."></div>' +
        '<div class="col-qty"><label class="form-label">Cant</label><input type="number" class="product-input prod-qty" value="1" min="1" onchange="calcTotal()"></div>' +
        '<div class="col-unit"><label class="form-label">Unit $</label><input type="number" class="product-input prod-unit" value="0" min="0" onchange="calcTotal()"></div>' +
        '<div class="col-del"><button class="btn-del" onclick="removeProduct(this)"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>';
    $('#productList').append(html);
    productIdx++;
}

function removeProduct(btn) {
    $(btn).closest('.product-row').remove();
    calcTotal();
}

function calcTotal() {
    var total = 0;
    $('.product-row').each(function() {
        var qty = parseInt($(this).find('.prod-qty').val()) || 0;
        var unit = parseInt($(this).find('.prod-unit').val()) || 0;
        total += qty * unit;
    });
    $('#totalDisplay').text('$' + total.toLocaleString('es-CO'));
}

var searchTimer;
function searchProduct(input) {
    clearTimeout(searchTimer);
    var $input = $(input);
    var q = $input.val().trim();
    $input.parent().find('.autocomplete-list').remove();
    if (q.length < 2) return;
    searchTimer = setTimeout(function() {
        $.getJSON(BASE + 'ventas/buscarProducto?q=' + encodeURIComponent(q), function(results) {
            if (!results.length) return;
            var html = '<div class="autocomplete-list">';
            results.forEach(function(p) {
                html += '<div class="autocomplete-item" onclick="selectProduct(this,\'' + p.idProduct + '\',' + (p.price||0) + ')">' +
                    '<strong>' + p.idProduct + '</strong> <small>' + (p.description||'') + '</small></div>';
            });
            html += '</div>';
            $input.parent().append(html);
        });
    }, 300);
}

function selectProduct(el, code, price) {
    var $row = $(el).closest('.product-row');
    $row.find('.prod-code').val(code);
    $row.find('.prod-unit').val(Math.round(price));
    $(el).closest('.autocomplete-list').remove();
    calcTotal();
}

$(document).on('click', function(e) { if (!$(e.target).closest('.autocomplete-list, .prod-code').length) $('.autocomplete-list').remove(); });

function guardar() {
    var products = [], quantities = [], units = [];
    $('.product-row').each(function() {
        products.push($(this).find('.prod-code').val());
        quantities.push($(this).find('.prod-qty').val());
        units.push($(this).find('.prod-unit').val());
    });

    var data = {
        id: <?= $budget->idBudget ?>,
        client_name: $('#client_name').val(),
        client_doc: $('#client_doc').val(),
        client_phone: $('#client_phone').val(),
        client_address: $('#client_address').val(),
        client_city: $('#client_city').val(),
        comments: $('#comments').val(),
        'product_ids[]': products,
        'quantities[]': quantities,
        'units[]': units,
    };
    data[CSRF_NAME] = CSRF_HASH;

    $.ajax({
        url: BASE + 'ventas/guardar',
        type: 'POST',
        data: $.param(data, true),
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                alert('Guardado');
                location.href = BASE + 'ventas/ver/' + <?= $budget->idBudget ?>;
            } else { alert(r.error || 'Error'); }
        },
        error: function() { alert('Error de conexion'); }
    });
}
</script>
</body>
</html>
