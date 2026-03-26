<?php
    $role = $this->session->userdata('user_data')['role'];
    $monthNames = array(1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',
                        7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic');

    // Filas del P&G
    $rows = array(
        array('key' => 'ventas_brutas',    'label' => 'Ventas Brutas',         'type' => 'ingreso'),
        array('key' => 'desc_pp',          'label' => 'Descuentos PP',         'type' => 'gasto'),
        array('key' => '_ventas_netas',    'label' => 'Ventas Netas',          'type' => 'subtotal'),
        array('key' => '_utilidad_bruta',  'label' => 'Utilidad Bruta (52.7%)', 'type' => 'subtotal'),
        array('key' => '_sep1',            'label' => '',                       'type' => 'separator'),
        array('key' => 'sueldos_adm',      'label' => 'Sueldos Adm.',         'type' => 'gasto'),
        array('key' => 'sueldo_vend',      'label' => 'Sueldo Vend.',         'type' => 'gasto'),
        array('key' => 'seg_social',       'label' => 'Seg. Social',          'type' => 'gasto'),
        array('key' => 'beneficios',       'label' => 'Beneficios',           'type' => 'gasto'),
        array('key' => 'comisiones',       'label' => 'Comisiones',           'type' => 'gasto'),
        array('key' => 'arriendo',         'label' => 'Arriendo',             'type' => 'gasto'),
        array('key' => 'reparacion',       'label' => 'Reparacion',           'type' => 'gasto'),
        array('key' => 'viaticos',         'label' => 'Viaticos',             'type' => 'gasto'),
        array('key' => 'equipos',          'label' => 'Equipos',              'type' => 'gasto'),
        array('key' => 'fletes',           'label' => 'Fletes',               'type' => 'gasto'),
        array('key' => 'legales',          'label' => 'Legales',              'type' => 'gasto'),
        array('key' => 'impuestos',        'label' => 'Impuestos',            'type' => 'gasto'),
        array('key' => 'intereses',        'label' => 'Intereses',            'type' => 'gasto'),
        array('key' => 'castigo',          'label' => 'Castigo Cartera',      'type' => 'gasto'),
        array('key' => 'otros_gastos',      'label' => 'Otros Gastos',        'type' => 'gasto'),
        array('key' => '_total_gastos',    'label' => 'Total Gastos',          'type' => 'subtotal_red'),
        array('key' => 'utilidad_operativa','label' => 'Utilidad Operativa',   'type' => 'resultado'),
        array('key' => '_sep2',            'label' => '',                       'type' => 'separator'),
        array('key' => 'cobros_clientes',  'label' => 'Cobros Clientes',      'type' => 'ingreso'),
        array('key' => 'antic_baq',        'label' => 'Anticipo BAQ',         'type' => 'gasto'),
        array('key' => 'pago_china',       'label' => 'Pago China',           'type' => 'gasto'),
        array('key' => 'prov_nacionales',  'label' => 'Prov. Nacionales',     'type' => 'gasto'),
        array('key' => 'prestamo_empl',    'label' => 'Prestamo Empl.',       'type' => 'gasto'),
        array('key' => 'retiro_accionistas','label' => 'Retiro Accionistas',  'type' => 'gasto'),
        array('key' => 'pago_baq',         'label' => 'Pago BAQ',             'type' => 'gasto'),
        array('key' => 'mov_bancarios',    'label' => 'Mov. Bancarios',       'type' => 'gasto'),
        array('key' => '_sep3',            'label' => '',                       'type' => 'separator'),
        array('key' => 'cartera_total',    'label' => 'Cartera Total',        'type' => 'balance'),
        array('key' => 'inventario',       'label' => 'Inventario',           'type' => 'balance'),
        array('key' => 'caja_bancos',      'label' => 'Caja y Bancos',        'type' => 'balance'),
        array('key' => '_sep4',            'label' => '',                       'type' => 'separator'),
        array('key' => 'bono_ventas',      'label' => 'Bono Ventas',          'type' => 'bono'),
        array('key' => 'bono_recaudo',     'label' => 'Bono Recaudo',         'type' => 'bono'),
    );

    // Helper para obtener valor
    function getVal($cierresPorMes, $m, $key, $margenBruto) {
        $c = isset($cierresPorMes[$m]) ? $cierresPorMes[$m] : null;
        if (!$c) return 0;

        // Campos calculados
        if ($key == '_ventas_netas') return (int)$c->ventas_brutas - (int)$c->desc_pp;
        if ($key == '_utilidad_bruta') return round(((int)$c->ventas_brutas - (int)$c->desc_pp) * $margenBruto);
        if ($key == '_total_gastos') {
            return (int)$c->sueldos_adm + (int)$c->sueldo_vend + (int)$c->seg_social
                 + (int)$c->beneficios + (int)$c->comisiones + (int)$c->arriendo
                 + (int)$c->reparacion + (int)$c->viaticos + (int)$c->equipos
                 + (int)$c->fletes + (int)$c->legales + (int)$c->impuestos
                 + (int)$c->intereses + (int)$c->castigo + (int)$c->otros_gastos;
        }

        if (isset($c->$key)) return (int)$c->$key;
        return 0;
    }
?>
<!DOCTYPE html>
<html lang="es">
    <title>Acumulado Anual <?= $year ?></title>
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
                            Acumulado Anual <?= $year ?>
                        </h2>
                    </div>

                    <!-- SELECTOR -->
                    <div class="flex items-center gap-3 mb-6 bg-white rounded-lg shadow-sm p-4">
                        <label class="text-sm font-medium text-gray-600">Anio:</label>
                        <select id="sel-year" class="form-select text-sm rounded border-gray-300">
                            <?php for($y = 2024; $y <= 2027; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button id="btn-filter-acum" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D">
                            Consultar
                        </button>
                    </div>

                    <!-- TABLA ACUMULADO -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full" style="font-size:12px">
                                <thead>
                                    <tr style="background:#1B365D; color:white">
                                        <th class="px-3 py-2 text-left sticky left-0" style="background:#1B365D; min-width:180px">Concepto</th>
                                        <?php foreach($monthNames as $k => $mn): ?>
                                        <th class="px-2 py-2 text-right" style="min-width:100px"><?= $mn ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2 text-right" style="min-width:120px; background:#0f2544">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rows as $row):
                                        $key = $row['key'];
                                        $type = $row['type'];

                                        if ($type == 'separator'):
                                    ?>
                                    <tr><td colspan="14" class="py-1" style="background:#e5e7eb"></td></tr>
                                    <?php continue; endif;

                                        // Estilos por tipo
                                        $rowClass = '';
                                        $cellClass = 'text-gray-700';
                                        if ($type == 'subtotal') { $rowClass = 'bg-blue-50 font-bold'; $cellClass = 'text-blue-800'; }
                                        if ($type == 'subtotal_red') { $rowClass = 'bg-red-50 font-bold'; $cellClass = 'text-red-700'; }
                                        if ($type == 'resultado') { $rowClass = 'bg-green-50 font-bold'; $cellClass = ''; }
                                        if ($type == 'bono') { $rowClass = 'bg-green-50'; $cellClass = 'text-green-700'; }
                                        if ($type == 'ingreso') { $cellClass = 'text-blue-700'; }
                                        if ($type == 'balance') { $rowClass = 'bg-gray-50'; $cellClass = 'text-gray-600'; }

                                        $total = 0;
                                    ?>
                                    <tr class="<?= $rowClass ?>" style="border-bottom:1px solid #e5e7eb">
                                        <td class="px-3 py-1 sticky left-0 bg-inherit font-medium"><?= $row['label'] ?></td>
                                        <?php for($m = 1; $m <= 12; $m++):
                                            $val = getVal($cierresPorMes, $m, $key, $margenBruto);
                                            $total += $val;
                                            $colorStyle = '';
                                            if ($type == 'resultado') {
                                                $colorStyle = $val >= 0 ? 'color:#7AB929' : 'color:#dc2626';
                                            }
                                        ?>
                                        <td class="px-2 py-1 text-right <?= $cellClass ?>" style="<?= $colorStyle ?>">
                                            <?= $val != 0 ? '$' . number_format($val, 0, ',', '.') : '-' ?>
                                        </td>
                                        <?php endfor; ?>
                                        <td class="px-3 py-1 text-right font-bold <?= $cellClass ?>" style="background:#f1f5f9; <?= $type == 'resultado' ? ($total >= 0 ? 'color:#7AB929' : 'color:#dc2626') : '' ?>">
                                            <?= $total != 0 ? '$' . number_format($total, 0, ',', '.') : '-' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    $(document).on('click', '#btn-filter-acum', function(){
        var y = $('#sel-year').val();
        window.location.href = '<?= base_url() ?>sisvent/admin/tracking/acumulado?year=' + y;
    });
    </script>
</body>
</html>
