<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Nueva Nota Credito</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full max-w-4xl mx-auto">

                    <?php if($this->session->flashdata('error_cn')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg"><?= $this->session->flashdata('error_cn') ?></div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Nueva Nota Credito</h2>
                        <a href="<?= base_url() ?>sisvent/commercial/creditnotes" class="text-sm hover:underline" style="color:#1B365D;">← Volver</a>
                    </div>

                    <form method="POST" action="<?= base_url() ?>sisvent/commercial/creditnotes/store" id="form-cn">

                    <!-- Paso 1: Tipo y Cliente -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Tipo</label>
                                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="devolucion">Devolución</option>
                                    <option value="garantia">Garantía</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Motivo</label>
                                <select name="reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="defecto">Producto defectuoso</option>
                                    <option value="dano">Producto dañado</option>
                                    <option value="inconformidad">Inconformidad del cliente</option>
                                    <option value="garantia">Garantía del fabricante</option>
                                    <option value="error_facturacion">Error de facturación</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Bodega</label>
                                <select name="storeId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <?php foreach($stores as $s): ?>
                                    <option value="<?= $s->idStore ?>"><?= $s->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="text-xs text-gray-500 uppercase">Cliente</label>
                            <input type="text" id="client-search" placeholder="Buscar cliente por nombre o NIT..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <input type="hidden" name="clientId" id="clientId">
                            <div id="client-results" class="border rounded-lg mt-1 max-h-40 overflow-y-auto" style="display:none;"></div>
                            <p id="client-selected" class="text-sm font-bold mt-1" style="color:#1B365D;"></p>
                        </div>
                    </div>

                    <!-- Paso 2: Factura de origen -->
                    <div id="invoice-section" style="display:none;" class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <label class="text-xs text-gray-500 uppercase">Factura de origen (opcional)</label>
                        <div id="invoice-list" class="mt-2 space-y-2"></div>
                        <input type="hidden" name="invoiceId" id="invoiceId">
                    </div>

                    <!-- Paso 3: Productos -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-gray-600">Productos a devolver</h3>
                            <button type="button" onclick="addEmptyRow()" class="text-xs font-bold px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600">+ Agregar producto manual</button>
                        </div>
                        <div id="products-container">
                            <table class="w-full text-xs" id="products-table">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="px-2 py-1.5">Codigo</th>
                                        <th class="px-2 py-1.5">Descripcion</th>
                                        <th class="px-2 py-1.5 text-center">Cant.</th>
                                        <th class="px-2 py-1.5 text-right">Precio</th>
                                        <th class="px-2 py-1.5 text-right">Subtotal</th>
                                        <th class="px-2 py-1.5 text-center">Estado</th>
                                        <th class="px-2 py-1.5"></th>
                                    </tr>
                                </thead>
                                <tbody id="products-body"></tbody>
                                <tfoot>
                                    <tr class="border-t-2 font-bold">
                                        <td colspan="4" class="px-2 py-2">TOTAL</td>
                                        <td class="px-2 py-2 text-right" id="total-display">$0</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <label class="text-xs text-gray-500 uppercase">Observaciones</label>
                        <textarea name="observations" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-1" rows="3" placeholder="Detalle del motivo de la devolución..."></textarea>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 text-sm font-bold text-white rounded-lg" style="background:#1B365D;">Crear Nota Credito</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    var rowIndex = 0;

    // Buscar cliente
    var clientTimer = null;
    $(document).on('input', '#client-search', function(){
        var q = $(this).val().trim();
        if (q.length < 2) { $('#client-results').hide(); return; }
        clearTimeout(clientTimer);
        clientTimer = setTimeout(function(){
            $.getJSON('<?= base_url() ?>sisvent/business/clients/search/' + encodeURIComponent(q), function(data){
                var html = '';
                (data || []).forEach(function(c){
                    html += '<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b text-sm" onclick="selectClient('+c.idClient+',\''+c.name.replace(/'/g,"\\'")+'\')">'+c.name+' <span class="text-gray-400">'+( c.idNum || '')+'</span></div>';
                });
                $('#client-results').html(html || '<div class="px-3 py-2 text-gray-400">Sin resultados</div>').show();
            });
        }, 400);
    });

    function selectClient(id, name) {
        $('#clientId').val(id);
        $('#client-selected').text(name);
        $('#client-search').val(name);
        $('#client-results').hide();
        // Cargar facturas
        $.getJSON('<?= base_url() ?>sisvent/commercial/creditnotes/clientInvoices?clientId=' + id, function(invoices){
            if (invoices.length > 0) {
                var html = '';
                invoices.forEach(function(inv){
                    html += '<div class="border rounded-lg p-3 cursor-pointer hover:bg-blue-50 invoice-option" data-id="'+inv.idInvoice+'">';
                    html += '<div class="flex justify-between"><span class="font-bold">#'+inv.idInvoice+'</span><span class="text-gray-500">$'+Number(inv.total).toLocaleString('es-CO')+'</span></div>';
                    html += '<div class="text-xs text-gray-400">'+inv.date+'</div>';
                    html += '<div class="mt-1 text-xs text-gray-500">';
                    (inv.details || []).forEach(function(d){
                        html += d.idProduct + ' x' + d.quantity + ' &middot; ';
                    });
                    html += '</div></div>';
                });
                $('#invoice-list').html(html);
                $('#invoice-section').show();
            }
        });
    }

    // Seleccionar factura
    $(document).on('click', '.invoice-option', function(){
        var id = $(this).data('id');
        $('.invoice-option').removeClass('border-blue-500 bg-blue-50').addClass('border-gray-200');
        $(this).addClass('border-blue-500 bg-blue-50').removeClass('border-gray-200');
        $('#invoiceId').val(id);
        // Cargar productos de la factura
        $.getJSON('<?= base_url() ?>sisvent/commercial/creditnotes/clientInvoices?clientId=' + $('#clientId').val(), function(invoices){
            var inv = invoices.find(function(i){ return i.idInvoice == id; });
            if (inv && inv.details) {
                $('#products-body').html('');
                inv.details.forEach(function(d){
                    addProductRow(d.idProduct, d.description || d.idProduct, d.quantity, d.unit || d.base || 0);
                });
                calcTotal();
            }
        });
    });

    function addProductRow(code, desc, qty, price) {
        rowIndex++;
        var html = '<tr class="border-t" id="row-'+rowIndex+'">';
        html += '<td class="px-2 py-1"><input type="text" name="productId[]" value="'+code+'" class="w-full border rounded px-1 py-0.5 text-xs" readonly></td>';
        html += '<td class="px-2 py-1 text-xs">'+desc+'</td>';
        html += '<td class="px-2 py-1"><input type="number" name="quantity[]" value="'+qty+'" min="1" class="w-16 border rounded px-1 py-0.5 text-xs text-center qty-input" onchange="calcTotal()"></td>';
        html += '<td class="px-2 py-1"><input type="number" name="price[]" value="'+price+'" class="w-20 border rounded px-1 py-0.5 text-xs text-right price-input" onchange="calcTotal()"></td>';
        html += '<td class="px-2 py-1 text-right subtotal-cell text-xs font-bold">$'+Number(qty*price).toLocaleString('es-CO')+'</td>';
        html += '<td class="px-2 py-1"><select name="condition[]" class="text-xs border rounded px-1 py-0.5"><option value="bueno">Bueno</option><option value="danado">Dañado</option><option value="defectuoso">Defectuoso</option></select></td>';
        html += '<td class="px-2 py-1"><button type="button" onclick="$(\'#row-'+rowIndex+'\').remove();calcTotal();" class="text-red-500 text-xs">X</button></td>';
        html += '</tr>';
        $('#products-body').append(html);
    }

    function addEmptyRow() {
        rowIndex++;
        var html = '<tr class="border-t" id="row-'+rowIndex+'">';
        html += '<td class="px-2 py-1"><input type="text" name="productId[]" class="w-full border rounded px-1 py-0.5 text-xs" placeholder="Codigo"></td>';
        html += '<td class="px-2 py-1 text-xs">Manual</td>';
        html += '<td class="px-2 py-1"><input type="number" name="quantity[]" value="1" min="1" class="w-16 border rounded px-1 py-0.5 text-xs text-center qty-input" onchange="calcTotal()"></td>';
        html += '<td class="px-2 py-1"><input type="number" name="price[]" value="0" class="w-20 border rounded px-1 py-0.5 text-xs text-right price-input" onchange="calcTotal()"></td>';
        html += '<td class="px-2 py-1 text-right subtotal-cell text-xs font-bold">$0</td>';
        html += '<td class="px-2 py-1"><select name="condition[]" class="text-xs border rounded px-1 py-0.5"><option value="bueno">Bueno</option><option value="danado">Dañado</option><option value="defectuoso">Defectuoso</option></select></td>';
        html += '<td class="px-2 py-1"><button type="button" onclick="$(\'#row-'+rowIndex+'\').remove();calcTotal();" class="text-red-500 text-xs">X</button></td>';
        html += '</tr>';
        $('#products-body').append(html);
    }

    function calcTotal() {
        var total = 0;
        $('#products-body tr').each(function(){
            var qty = parseFloat($(this).find('.qty-input').val()) || 0;
            var price = parseFloat($(this).find('.price-input').val()) || 0;
            var sub = qty * price;
            total += sub;
            $(this).find('.subtotal-cell').text('$' + sub.toLocaleString('es-CO'));
        });
        $('#total-display').text('$' + total.toLocaleString('es-CO'));
    }
    </script>
</body>
</html>
