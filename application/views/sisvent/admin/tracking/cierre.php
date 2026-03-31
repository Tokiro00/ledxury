<?php
    $role = $this->session->userdata('user_data')['role'];
    $months = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');

    // Helper: obtener valor del cierre o default
    function cv($cierre, $field, $default = 0) {
        if ($cierre && isset($cierre->$field)) return (int) $cierre->$field;
        return $default;
    }

    // Valores para los campos
    $vb  = $cierre ? cv($cierre, 'ventas_brutas', $ventasBrutas) : $ventasBrutas;
    $dpp = cv($cierre, 'desc_pp');
    $cc  = $cierre ? cv($cierre, 'cobros_clientes', $cobrosClientes) : $cobrosClientes;

    // Gastos
    $gastoFields = array(
        'sueldos_adm'    => 'Sueldos Administrativos',
        'sueldo_vend'    => 'Sueldo Vendedores',
        'seg_social'     => 'Seguridad Social',
        'beneficios'     => 'Beneficios',
        'comisiones'     => 'Comisiones',
        'arriendo'       => 'Arriendo',
        'reparacion'     => 'Reparacion y Mantenimiento',
        'viaticos'       => 'Viaticos',
        'equipos'        => 'Equipos',
        'fletes'         => 'Fletes',
        'legales'        => 'Legales',
        'impuestos'      => 'Impuestos',
        'intereses'      => 'Intereses',
        'castigo'        => 'Castigo Cartera',
        'otros_gastos'   => 'Otros Gastos'
    );

    // FC fields
    $fcFields = array(
        'antic_baq'           => 'Anticipo BAQ',
        'pago_china'          => 'Pago China',
        'prov_nacionales'     => 'Proveedores Nacionales',
        'prestamo_empl'       => 'Prestamo Empleados',
        'retiro_accionistas'  => 'Retiro Accionistas',
        'pago_baq'            => 'Pago BAQ',
        'mov_bancarios'       => 'Movimientos Bancarios'
    );
?>
<!DOCTYPE html>
<html lang="es">
    <title>Cierre Mensual - <?= $monthName ?> <?= $year ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 mx-auto w-full">

                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold" style="color:#1B365D">
                            Cierre Mensual - <?= $monthName ?> <?= $year ?>
                        </h2>
                    </div>

                    <?php if($this->session->flashdata('tracking_success')): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= $this->session->flashdata('tracking_success') ?>
                    </div>
                    <?php endif; ?>

                    <!-- SELECTOR MES -->
                    <div class="flex items-center gap-3 mb-6 bg-white rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-600">Anio:</label>
                            <select id="sel-year" class="form-select text-sm rounded border-gray-300">
                                <?php for($y = 2024; $y <= 2027; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-600">Mes:</label>
                            <select id="sel-month" class="form-select text-sm rounded border-gray-300">
                                <?php foreach($months as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $k == $month ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button id="btn-filter-cierre" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D">
                            Consultar
                        </button>
                    </div>

                    <form action="<?= base_url() ?>sisvent/admin/tracking/guardarCierre" method="POST">
                        <input type="hidden" name="year" value="<?= $year ?>">
                        <input type="hidden" name="month" value="<?= $month ?>">

                        <div class="grid gap-6 lg:grid-cols-2">

                            <!-- COLUMNA IZQUIERDA: P&G -->
                            <div>
                                <!-- INGRESOS -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#1B365D">
                                        <h3 class="text-sm font-bold text-white">Ingresos</h3>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Ventas Brutas</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="ventas_brutas" id="ventas_brutas" class="form-input text-sm text-right w-48 rounded border-gray-300 cierre-field" value="<?= $vb ?>">
                                                <span class="text-xs text-blue-500 ml-2">(Live: $<?= number_format($ventasBrutas, 0, ',', '.') ?>)</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Descuentos PP</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="desc_pp" id="desc_pp" class="form-input text-sm text-right w-48 rounded border-gray-300 cierre-field" value="<?= $dpp ?>">
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between border-t pt-2">
                                            <label class="text-sm font-bold text-gray-700 w-48">Ventas Netas</label>
                                            <span class="text-sm font-bold" style="color:#1B365D" id="ventas_netas">$<?= number_format($vb - $dpp, 0, ',', '.') ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-bold text-gray-700 w-48">Utilidad Bruta (52.7%)</label>
                                            <span class="text-sm font-bold" style="color:#7AB929" id="utilidad_bruta">$<?= number_format(round(($vb - $dpp) * $margenBruto), 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- GASTOS -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#1B365D">
                                        <h3 class="text-sm font-bold text-white">Gastos Operacionales</h3>
                                    </div>
                                    <div class="p-4 space-y-2">
                                        <?php foreach($gastoFields as $field => $label): ?>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48"><?= $label ?></label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="<?= $field ?>" class="form-input text-sm text-right w-48 rounded border-gray-300 cierre-field gasto-field" value="<?= cv($cierre, $field) ?>">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="flex items-center justify-between border-t pt-2">
                                            <label class="text-sm font-bold text-gray-700 w-48">Total Gastos</label>
                                            <span class="text-sm font-bold text-red-600" id="total_gastos">$0</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- RESULTADO -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#1B365D">
                                        <h3 class="text-sm font-bold text-white">Resultado</h3>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-bold w-48">Utilidad Operativa</label>
                                            <span class="text-lg font-bold" id="utilidad_operativa">$0</span>
                                        </div>
                                        <div class="flex items-center justify-between border-t pt-2">
                                            <label class="text-sm text-gray-600 w-48">Bono Ventas (2% si >= meta)</label>
                                            <span class="text-sm font-bold text-green-600" id="bono_ventas">$0</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Bono Recaudo (2% si >= meta)</label>
                                            <span class="text-sm font-bold text-green-600" id="bono_recaudo">$0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- COLUMNA DERECHA: FLUJO DE CAJA / BALANCE -->
                            <div>
                                <!-- RECAUDO -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#7AB929">
                                        <h3 class="text-sm font-bold text-white">Recaudo</h3>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Cobros Clientes</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="cobros_clientes" id="cobros_clientes" class="form-input text-sm text-right w-48 rounded border-gray-300 cierre-field" value="<?= $cc ?>">
                                                <span class="text-xs text-blue-500 ml-2">(Live: $<?= number_format($cobrosClientes, 0, ',', '.') ?>)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- FLUJO DE CAJA - EGRESOS -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#1B365D">
                                        <h3 class="text-sm font-bold text-white">Flujo de Caja - Egresos</h3>
                                    </div>
                                    <div class="p-4 space-y-2">
                                        <?php foreach($fcFields as $field => $label): ?>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48"><?= $label ?></label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="<?= $field ?>" class="form-input text-sm text-right w-48 rounded border-gray-300" value="<?= cv($cierre, $field) ?>">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- BALANCE -->
                                <div class="bg-white rounded-lg shadow-sm mb-4">
                                    <div class="p-3 rounded-t-lg" style="background:#1B365D">
                                        <h3 class="text-sm font-bold text-white">Balance</h3>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Cartera Total</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="cartera_total" class="form-input text-sm text-right w-48 rounded border-gray-300" value="<?= $cierre ? cv($cierre, 'cartera_total', $cartera) : $cartera ?>">
                                                <span class="text-xs text-blue-500 ml-2">(Live: $<?= number_format($cartera, 0, ',', '.') ?>)</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Inventario</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="inventario" class="form-input text-sm text-right w-48 rounded border-gray-300" value="<?= $cierre ? cv($cierre, 'inventario', $inventario) : $inventario ?>">
                                                <span class="text-xs text-blue-500 ml-2">(Live: $<?= number_format($inventario, 0, ',', '.') ?>)</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm text-gray-600 w-48">Caja y Bancos</label>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-400 mr-2">$</span>
                                                <input type="number" name="caja_bancos" class="form-input text-sm text-right w-48 rounded border-gray-300" value="<?= cv($cierre, 'caja_bancos') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BOTON GUARDAR -->
                                <div class="text-right">
                                    <button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-lg" style="background:#1B365D">
                                        Guardar Cierre Mensual
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    var META_VENTAS = <?= $metaVentas ?>;
    var META_RECAUDO = <?= $metaRecaudo ?>;
    var MARGEN = <?= $margenBruto ?>;

    function formatCOP(val) {
        if (val < 0) return '-$' + Math.abs(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return '$' + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function recalcular() {
        var vb = parseInt($('#ventas_brutas').val()) || 0;
        var dpp = parseInt($('#desc_pp').val()) || 0;
        var ventasNetas = vb - dpp;
        var utilidadBruta = Math.round(ventasNetas * MARGEN);

        $('#ventas_netas').text(formatCOP(ventasNetas));
        $('#utilidad_bruta').text(formatCOP(utilidadBruta));

        var totalGastos = 0;
        $('.gasto-field').each(function(){
            totalGastos += parseInt($(this).val()) || 0;
        });
        $('#total_gastos').text(formatCOP(totalGastos));

        var utilidadOp = utilidadBruta - totalGastos;
        var opColor = utilidadOp >= 0 ? '#7AB929' : '#dc2626';
        $('#utilidad_operativa').text(formatCOP(utilidadOp)).css('color', opColor);

        var cobros = parseInt($('#cobros_clientes').val()) || 0;
        var bonoV = (vb >= META_VENTAS && utilidadOp > 0) ? Math.round(vb * 0.02) : 0;
        var bonoR = (cobros >= META_RECAUDO && utilidadOp > 0) ? Math.round(cobros * 0.02) : 0;
        $('#bono_ventas').text(formatCOP(bonoV));
        $('#bono_recaudo').text(formatCOP(bonoR));
    }

    $(document).on('input', '.cierre-field, .gasto-field', function(){
        recalcular();
    });

    $(document).on('click', '#btn-filter-cierre', function(){
        var y = $('#sel-year').val();
        var m = $('#sel-month').val();
        window.location.href = '<?= base_url() ?>sisvent/admin/tracking/cierre?year=' + y + '&month=' + m;
    });

    // Inicializar calculos
    $(document).ready(function(){
        recalcular();
    });
    </script>
</body>
</html>
