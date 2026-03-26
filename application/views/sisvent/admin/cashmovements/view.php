<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Movimiento #<?php echo $movement->idMovement; ?></title>
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

                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Movimiento #<?php echo $movement->idMovement; ?>
                        </h2>
                        <div class="flex items-center space-x-3">
                            <?php if($movement->status != 'anulado' && !in_array($movement->movementType, ['apertura','cierre'])): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/cancel/<?php echo $movement->idMovement; ?>"
                                   class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                                   onclick="showSureModal(event,this)">
                                    Anular
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                               class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                        </div>
                    </div>

                    <!-- DETALLE DEL MOVIMIENTO -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Tipo</p>
                                <?php
                                    switch ($movement->movementType) {
                                        case 'ingreso':
                                        case 'apertura':
                                            $typeClass = 'text-green-700 bg-green-100';
                                            break;
                                        case 'egreso':
                                        case 'cierre':
                                            $typeClass = 'text-red-700 bg-red-100';
                                            break;
                                        case 'transferencia':
                                            $typeClass = 'text-blue-700 bg-blue-100';
                                            break;
                                        case 'ajuste':
                                            $typeClass = 'text-yellow-700 bg-yellow-100';
                                            break;
                                        default:
                                            $typeClass = 'text-gray-600 bg-gray-100';
                                            break;
                                    }
                                ?>
                                <span class="inline-block mt-1 px-2 py-1 text-sm font-semibold rounded-full <?php echo $typeClass; ?>">
                                    <?php echo ucfirst($movement->movementType); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Estado</p>
                                <span class="inline-block mt-1 px-2 py-1 text-sm font-semibold rounded-full
                                    <?php echo ($movement->status=='anulado') ? 'text-red-700 bg-red-100' : 'text-gray-600 bg-gray-100'; ?>">
                                    <?php echo ucfirst($movement->status); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Monto</p>
                                <?php $isNeg = in_array($movement->movementType, ['egreso', 'cierre']); ?>
                                <p class="text-xl font-bold mt-1 <?php echo $isNeg ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php echo $isNeg ? '-' : '+'; ?>$<?php echo number_format($movement->amount, 2); ?>
                                </p>
                            </div>
                        </div>

                        <hr class="my-4"/>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Concepto</p>
                                <p class="text-sm text-gray-700 mt-1"><?php echo $movement->concept; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Categoría</p>
                                <p class="text-sm text-gray-700 mt-1 capitalize"><?php echo str_replace('_', ' ', $movement->category); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Origen</p>
                                <p class="text-sm text-gray-700 mt-1 capitalize">
                                    <?php echo $movement->sourceType; ?> #<?php echo $movement->sourceId; ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Fecha</p>
                                <p class="text-sm text-gray-700 mt-1"><?php echo date('d/m/Y H:i:s', strtotime($movement->movementDate)); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Ejecutado por</p>
                                <p class="text-sm text-gray-700 mt-1"><?php echo $movement->executedBy; ?></p>
                            </div>
                            <?php if($movement->movementType == 'transferencia'): ?>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Destino</p>
                                    <p class="text-sm text-gray-700 mt-1 capitalize">
                                        <?php echo $movement->destinationType; ?> #<?php echo $movement->destinationId; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <?php if($movement->notes): ?>
                                <div class="col-span-2">
                                    <p class="text-xs text-gray-500 uppercase">Notas</p>
                                    <p class="text-sm text-gray-700 mt-1"><?php echo $movement->notes; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
