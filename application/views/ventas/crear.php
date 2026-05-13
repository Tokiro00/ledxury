<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Nuevo Presupuesto - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
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
        .form-input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; color:var(--text); outline:none; }
        .form-input:focus { border-color:var(--petrol); box-shadow:0 0 0 2px rgba(46,125,145,.15); }
        .form-select { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; background:#fff; }
        .client-search { position:relative; }
        .client-results { position:absolute; left:0; right:0; top:100%; background:#fff; border:1px solid var(--border); border-radius:0 0 var(--radius-sm) var(--radius-sm); max-height:220px; overflow-y:auto; z-index:20; box-shadow:var(--shadow); display:none; }
        .client-result-item { padding:10px 12px; border-bottom:1px solid #f3f4f6; cursor:pointer; }
        .client-result-item:active { background:#f0f9ff; }
        .client-result-item strong { font-size:13px; }
        .client-result-item small { color:var(--text-secondary); font-size:11px; display:block; margin-top:2px; }
        .client-result-new { padding:12px; background:#F0FDF4; color:#065F46; font-weight:700; font-size:13px; cursor:pointer; text-align:center; border-top:1px dashed #86EFAC; }
        .client-result-new:active { background:#D1FAE5; }
        .selected-client { background:#D1FAE5; border:1px solid #10b981; border-radius:var(--radius-sm); padding:10px 12px; font-size:13px; display:flex; justify-content:space-between; align-items:center; }
        .selected-client button { background:none; border:none; color:#ef4444; font-size:12px; cursor:pointer; font-weight:700; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:100; display:none; align-items:center; justify-content:center; padding:16px; }
        .modal-overlay.show { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius); width:100%; max-width:440px; max-height:90vh; overflow-y:auto; padding:18px; }
        .modal-title { font-size:16px; font-weight:700; margin-bottom:4px; }
        .modal-sub { font-size:12px; color:var(--text-secondary); margin-bottom:14px; }
        .modal-actions { display:flex; gap:8px; margin-top:14px; }
        .modal-actions .btn { flex:1; padding:12px; font-size:14px; font-weight:700; border:none; border-radius:var(--radius-sm); cursor:pointer; }
        .modal-actions .btn-save { background:var(--success); color:#fff; }
        .modal-actions .btn-cancel { background:#F3F4F6; color:#374151; }
        .product-row { display:flex; gap:8px; align-items:flex-end; margin-bottom:8px; padding:10px; background:#f8fafc; border-radius:var(--radius-sm); border:1px solid var(--border); }
        .product-row .col-code { flex:2; position:relative; }
        .product-row .col-qty { flex:.8; }
        .product-row .col-unit { flex:1; }
        .product-row .col-del { flex:.3; display:flex; align-items:center; justify-content:center; }
        .product-input { width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:13px; }
        .product-input:focus { border-color:var(--petrol); outline:none; }
        .btn-add { width:100%; padding:10px; border:2px dashed var(--border); border-radius:var(--radius-sm); background:none; color:var(--petrol); font-size:13px; font-weight:600; cursor:pointer; }
        .btn-del { background:none; border:none; color:#ef4444; cursor:pointer; }
        .total-display { text-align:center; font-size:24px; font-weight:800; color:var(--petrol); padding:8px 0; }
        .autocomplete-list { position:absolute; left:0; right:0; top:100%; background:#fff; border:1px solid var(--border); border-radius:0 0 6px 6px; max-height:150px; overflow-y:auto; z-index:20; box-shadow:var(--shadow); }
        .autocomplete-item { padding:8px 10px; font-size:12px; cursor:pointer; border-bottom:1px solid #f3f4f6; }
        .autocomplete-item:active { background:#f0f9ff; }
        .fixed-bottom { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); padding:12px 16px; padding-bottom:calc(12px + var(--safe-bottom)); display:flex; gap:10px; z-index:10; }
        .btn { flex:1; padding:14px; border:none; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; display:block; }
        .btn:active { transform:scale(.97); }
        .btn-create { background:var(--success); color:#fff; }
        .btn-back { background:#F3F4F6; color:#374151; }
    </style>
</head>
<body>
<div id="app">
    <div class="header">
        <a href="<?= base_url() ?>ventas/dashboard">← Inicio</a>
        <h1>Nuevo Presupuesto</h1>
        <div style="display:flex;align-items:center;gap:8px;">
          <a href="<?= base_url() ?>sisvent/dashboard/profile" style="color:rgba(255,255,255,.85);font-size:14px;text-decoration:none;" title="Editar perfil">👤</a>
          <a href="<?= base_url() ?>ventas/logout" style="color:rgba(255,255,255,.85);font-size:11px;text-decoration:none;">Salir</a>
        </div>
    </div>

    <div class="screen-container">
        <!-- Cliente -->
        <div class="card">
            <div class="card-title">Cliente</div>
            <div class="client-search" id="clientSearch">
                <input type="text" class="form-input" id="clientInput" placeholder="Buscar por nombre, documento o celular..." oninput="searchClient(this.value)">
                <div class="client-results" id="clientResults"></div>
            </div>
            <button type="button" class="btn-add" id="btnNewClient" style="margin-top:8px;" onclick="openNewClientModal('')">+ Nuevo cliente</button>
            <div id="selectedClient" style="display:none; margin-top:10px;"></div>
            <input type="hidden" id="clientId" value="">
        </div>

        <!-- Productos -->
        <div class="card">
            <div class="card-title">Productos</div>
            <div id="productList"></div>
            <button class="btn-add" onclick="addProduct()">+ Agregar producto</button>
            <div class="total-display" id="totalDisplay">$0</div>
        </div>

        <!-- Observaciones -->
        <div class="card">
            <div class="card-title">Observaciones</div>
            <textarea class="form-input" id="comments" rows="2" style="resize:none;" placeholder="Comentarios opcionales..."></textarea>
        </div>
    </div>

    <div class="fixed-bottom">
        <a href="<?= base_url() ?>ventas/dashboard" class="btn btn-back">Cancelar</a>
        <button class="btn btn-create" onclick="crearPresupuesto()">Crear Presupuesto</button>
    </div>
</div>

<!-- Modal: Nuevo Cliente -->
<div class="modal-overlay" id="newClientModal">
    <div class="modal-box">
        <div class="modal-title">Nuevo cliente</div>
        <div class="modal-sub">Solo necesitas: nombres, celular y dirección. El documento es opcional.</div>
        <div class="form-group">
            <label class="form-label">Nombres *</label>
            <input type="text" class="form-input" id="nc_nombres" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Apellidos</label>
            <input type="text" class="form-input" id="nc_apellidos" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Celular *</label>
            <input type="text" class="form-input" id="nc_cellphone" inputmode="tel" autocomplete="off" placeholder="3001234567">
        </div>
        <div class="form-group">
            <label class="form-label">Dirección *</label>
            <input type="text" class="form-input" id="nc_address" autocomplete="off" placeholder="Calle / Carrera, número, barrio">
        </div>
        <div class="form-group">
            <label class="form-label">Documento <span style="color:#94a3b8;font-weight:normal;">(opcional)</span></label>
            <input type="text" class="form-input" id="nc_idNum" inputmode="numeric" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Ciudad</label>
            <input type="text" class="form-input" id="nc_city" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Departamento</label>
            <input type="text" class="form-input" id="nc_state" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" class="form-input" id="nc_email" autocomplete="off">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-cancel" onclick="closeNewClientModal()">Cancelar</button>
            <button type="button" class="btn btn-save" id="ncSaveBtn" onclick="saveNewClient()">Guardar</button>
        </div>
    </div>
</div>

<script>
var BASE = '<?= base_url() ?>';
var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
var vendorId = '<?= $vendor->idUser ?>';
var storeId = <?= $vendor->store ?: 1 ?>;

// Client search
var clientTimer;
var lastClientQuery = '';
function escAttr(s) { return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function searchClient(q) {
    clearTimeout(clientTimer);
    lastClientQuery = q;
    if (q.length < 2) { $('#clientResults').hide(); return; }
    clientTimer = setTimeout(function() {
        $.getJSON(BASE + 'ventas/buscarCliente?q=' + encodeURIComponent(q), function(results) {
            var html = '';
            (results || []).forEach(function(c) {
                html += '<div class="client-result-item" onclick="selectClient(' + c.idClient + ',\'' + c.name.replace(/'/g,"\\'") + '\',\'' + (c.cellphone||'') + '\',\'' + (c.idNum||'') + '\')">' +
                    '<strong>' + c.name + '</strong><small>' + (c.idNum||'') + ' - ' + (c.cellphone||'') + ' - ' + (c.city||'') + '</small></div>';
            });
            html += '<div class="client-result-new" onclick="openNewClientModal(\'' + escAttr(q).replace(/'/g,"\\'") + '\')">+ Nuevo cliente: "' + escAttr(q) + '"</div>';
            $('#clientResults').html(html).show();
        });
    }, 300);
}

function openNewClientModal(query) {
    $('#nc_name').val('');
    $('#nc_idNum').val('');
    $('#nc_cellphone').val('');
    $('#nc_address').val('');
    $('#nc_city').val('');
    $('#nc_state').val('');
    $('#nc_email').val('');
    // Heurística: si el query es numérico → documento o celular; sino → nombre
    var q = (query || lastClientQuery || '').trim();
    if (q) {
        if (/^\d+$/.test(q)) {
            if (q.length >= 10) $('#nc_cellphone').val(q);
            else $('#nc_idNum').val(q);
        } else {
            $('#nc_name').val(q);
        }
    }
    $('#clientResults').hide();
    $('#newClientModal').addClass('show');
    setTimeout(function(){ $('#nc_name').focus(); }, 50);
}

function closeNewClientModal() {
    $('#newClientModal').removeClass('show');
}

function saveNewClient() {
    var nombres = $.trim($('#nc_nombres').val());
    var apellidos = $.trim($('#nc_apellidos').val());
    var idNum = $.trim($('#nc_idNum').val());
    var cellphone = $.trim($('#nc_cellphone').val());
    var address = $.trim($('#nc_address').val());
    if (!nombres) { alert('Los nombres son obligatorios'); $('#nc_nombres').focus(); return; }
    if (!cellphone) { alert('El celular es obligatorio'); $('#nc_cellphone').focus(); return; }
    if (!address) { alert('La dirección es obligatoria'); $('#nc_address').focus(); return; }

    var data = {
        nombres: nombres,
        apellidos: apellidos,
        idNum: idNum,
        cellphone: cellphone,
        address: address,
        city: $.trim($('#nc_city').val()),
        state: $.trim($('#nc_state').val()),
        email: $.trim($('#nc_email').val())
    };
    data[CSRF_NAME] = CSRF_HASH;

    $('#ncSaveBtn').prop('disabled', true).text('Guardando...');
    $.ajax({
        url: BASE + 'ventas/crearCliente',
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(resp) {
            $('#ncSaveBtn').prop('disabled', false).text('Guardar');
            if (!resp || !resp.success) { alert((resp && resp.error) || 'Error al crear cliente'); return; }
            if (resp.duplicate) alert(resp.message || 'Cliente ya existía, se seleccionó');
            selectClient(resp.idClient, resp.name, resp.cellphone || '', resp.idNum || '');
            closeNewClientModal();
        },
        error: function() {
            $('#ncSaveBtn').prop('disabled', false).text('Guardar');
            alert('Error de red al crear cliente');
        }
    });
}

function selectClient(id, name, phone, doc) {
    $('#clientId').val(id);
    $('#clientInput').hide();
    $('#clientResults').hide();
    $('#btnNewClient').hide();
    $('#selectedClient').html('<div class="selected-client"><span><strong>' + name + '</strong> ' + doc + ' - ' + phone + '</span><button onclick="clearClient()">X</button></div>').show();
}

function clearClient() {
    $('#clientId').val('');
    $('#clientInput').val('').show();
    $('#btnNewClient').show();
    $('#selectedClient').hide();
}

// Products
var pidx = 0;
function addProduct() {
    var html = '<div class="product-row" data-idx="' + pidx + '">' +
        '<div class="col-code"><label class="form-label">Codigo</label><input type="text" class="product-input prod-code" oninput="searchProd(this)" onblur="this.value=this.value.toUpperCase().trim()" autocapitalize="characters" placeholder="Buscar..."></div>' +
        '<div class="col-qty"><label class="form-label">Cant</label><input type="number" class="product-input prod-qty" value="1" min="1" onchange="calcTotal()"></div>' +
        '<div class="col-unit"><label class="form-label">Unit $</label><input type="number" class="product-input prod-unit" value="0" min="0" onchange="calcTotal()"></div>' +
        '<div class="col-del"><button class="btn-del" onclick="$(this).closest(\'.product-row\').remove();calcTotal();"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div></div>';
    $('#productList').append(html);
    pidx++;
}

var prodTimer;
function searchProd(input) {
    clearTimeout(prodTimer);
    var $input = $(input), q = $input.val().trim();
    $input.parent().find('.autocomplete-list').remove();
    if (q.length < 2) return;
    prodTimer = setTimeout(function() {
        $.getJSON(BASE + 'ventas/buscarProducto?q=' + encodeURIComponent(q), function(r) {
            if (!r.length) return;
            var html = '<div class="autocomplete-list">';
            r.forEach(function(p) {
                var fam = p.family_name ? ' <em style="color:#888;">' + p.family_name + '</em>' : '';
                html += '<div class="autocomplete-item" onclick="pickProd(this,\'' + p.idProduct + '\',' + (p.price||0) + ')"><strong>' + p.idProduct + '</strong>' + fam + '<br><small>' + (p.description||'') + '</small></div>';
            });
            html += '</div>';
            $input.parent().append(html);
        });
    }, 300);
}

function pickProd(el, code, price) {
    var $row = $(el).closest('.product-row');
    $row.find('.prod-code').val(code);
    $row.find('.prod-unit').val(Math.round(price));
    $(el).closest('.autocomplete-list').remove();
    calcTotal();
}

function calcTotal() {
    var t = 0;
    $('.product-row').each(function() { t += (parseInt($(this).find('.prod-qty').val())||0) * (parseInt($(this).find('.prod-unit').val())||0); });
    $('#totalDisplay').text('$' + t.toLocaleString('es-CO'));
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('.autocomplete-list,.prod-code').length) $('.autocomplete-list').remove();
    if (!$(e.target).closest('#clientSearch').length) $('#clientResults').hide();
});

function crearPresupuesto() {
    var clientId = $('#clientId').val();
    if (!clientId) { alert('Selecciona un cliente'); return; }

    var products = [], quantities = [], units = [];
    $('.product-row').each(function() {
        var c = $(this).find('.prod-code').val();
        if (c) {
            c = String(c).toUpperCase().trim();
            $(this).find('.prod-code').val(c); // refleja el uppercase visualmente
            products.push(c);
            quantities.push($(this).find('.prod-qty').val());
            units.push($(this).find('.prod-unit').val());
        }
    });
    if (!products.length) { alert('Agrega al menos un producto'); return; }

    var total = 0;
    for (var i = 0; i < products.length; i++) total += (parseInt(quantities[i])||0) * (parseInt(units[i])||0);

    var data = {
        client: clientId, vendor: vendorId, store: storeId, total: total,
        hasIva: 'off', e_commerce: 'off', list_price: 0, comments: $('#comments').val(),
        'refs[]': products, 'budget-quantities[]': quantities, 'budget-subtotal[]': units.map(function(u,i){ return (parseInt(u)||0) * (parseInt(quantities[i])||0); }),
        'price_base[]': units, 'budget-rates[]': units,
    };
    data[CSRF_NAME] = CSRF_HASH;

    $.ajax({
        url: BASE + 'sisvent/commercial/budgets/store',
        type: 'POST',
        data: $.param(data, true),
        success: function() {
            alert('Presupuesto creado');
            location.href = BASE + 'ventas/pendientes';
        },
        error: function() { alert('Error al crear'); }
    });
}

// Start with one product row
addProduct();
</script>
</body>
</html>
