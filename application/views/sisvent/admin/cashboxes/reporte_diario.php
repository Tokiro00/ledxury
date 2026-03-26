<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Reporte Diario — <?php echo $cashbox->name; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Reporte Diario — <?php echo $cashbox->name; ?>
                        </h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/view/<?php echo $cashbox->idCashbox; ?>"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <!-- SELECTOR DE FECHA -->
                    <form method="get" action="<?php echo base_url(); ?>sisvent/admin/cashboxes/reporte_diario/<?php echo $cashbox->idCashbox; ?>"
                          class="bg-white rounded-lg shadow-sm p-4 mb-4 flex flex-wrap items-end gap-4">
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Fecha</span>
                            <input type="date" name="date" value="<?php echo $date; ?>" class="form-input"/>
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                            Ver Reporte
                        </button>
                        <!-- Navegar día anterior / siguiente -->
                        <?php
                            $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
                            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                        ?>
                        <a href="?date=<?php echo $prevDate; ?>"
                           class="px-3 py-2 text-sm text-gray-600 border rounded-lg hover:bg-gray-100">← Anterior</a>
                        <a href="?date=<?php echo $nextDate; ?>"
                           class="px-3 py-2 text-sm text-gray-600 border rounded-lg hover:bg-gray-100">Siguiente →</a>
                    </form>

                    <!-- RESUMEN DEL DÍA -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Inicio Día</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($openingBalance, 2); ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-green-600 uppercase">Ingresos</p>
                            <?php
                                $totalIngress = 0;
                                foreach ($movements as $m) {
                                    if ($m->sign === 1) $totalIngress += (float)$m->amount;
                                }
                            ?>
                            <p class="text-lg font-bold text-green-700 mt-1">+$<?php echo number_format($totalIngress, 2); ?></p>
                        </div>
                        <div class="bg-red-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-red-600 uppercase">Egresos</p>
                            <?php
                                $totalEgress = 0;
                                foreach ($movements as $m) {
                                    if ($m->sign === -1) $totalEgress += (float)$m->amount;
                                }
                            ?>
                            <p class="text-lg font-bold text-red-700 mt-1">-$<?php echo number_format($totalEgress, 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Cierre Día</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($closingBalance, 2); ?></p>
                        </div>
                    </div>

                    <!-- TABLA DE MOVIMIENTOS -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                Movimientos del <?php echo date('d/m/Y', strtotime($date)); ?> (<?php echo count($movements); ?>)
                            </p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Hora</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Categoría</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <!-- Saldo de apertura del día -->
                                    <tr class="bg-gray-50 text-gray-600">
                                        <td class="px-4 py-2 text-sm" colspan="4">
                                            <strong>Saldo de apertura del día</strong>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right"></td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold text-gray-800">
                                            $<?php echo number_format($openingBalance, 2); ?>
                                        </td>
                                    </tr>

                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $mov): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('H:i', strtotime($mov->movementDate)); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($mov->movementType) {
                                                            case 'ingreso':   case 'apertura':  $tc = 'text-green-700 bg-green-100'; break;
                                                            case 'egreso':    case 'cierre':    $tc = 'text-red-700 bg-red-100'; break;
                                                            case 'transferencia': $tc = 'text-blue-700 bg-blue-100'; break;
                                                            case 'ajuste':    $tc = 'text-yellow-700 bg-yellow-100'; break;
                                                            default:          $tc = 'text-gray-600 bg-gray-100'; break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $tc; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo $mov->concept; ?></td>
                                                <td class="px-4 py-3 text-sm capitalize"><?php echo str_replace('_', ' ', $mov->category); ?></td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <span class="<?php echo ($mov->sign === -1) ? 'text-red-600' : 'text-green-600'; ?>">
                                                        <?php echo ($mov->sign === -1) ? '-' : '+'; ?>$<?php echo number_format($mov->amount, 2); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800">
                                                    $<?php echo number_format($mov->runningBalance, 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay movimientos en este día
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
