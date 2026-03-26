<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title><?php echo $bankAccount->bankName; ?></title>
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
                            <?php echo $bankAccount->bankName; ?>
                        </h2>
                        <div class="flex items-center space-x-3">
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/libro/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                                Libro
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/reconciliation/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm font-medium text-mam-blue-petroleo border border-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo hover:text-white">
                                Conciliacion
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/uploadStatement/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Cargar Extracto
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/edit/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm font-medium text-mam-blue-petroleo border border-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo hover:text-white">
                                Editar
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts"
                               class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                        </div>
                    </div>

                    <!-- TARJETAS DE RESUMEN -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Actual</p>
                            <p class="text-xl font-bold text-gray-800 mt-1">$<?php echo number_format($bankAccount->currentBalance, 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Estado</p>
                            <?php
                                switch ($bankAccount->status) {
                                    case 'activa':
                                        $statusClass = 'text-green-700 bg-green-100';
                                        break;
                                    case 'inactiva':
                                        $statusClass = 'text-gray-600 bg-gray-100';
                                        break;
                                    case 'bloqueada':
                                        $statusClass = 'text-red-700 bg-red-100';
                                        break;
                                    default:
                                        $statusClass = 'text-gray-600 bg-gray-100';
                                        break;
                                }
                            ?>
                            <span class="inline-block mt-1 px-2 py-1 text-sm font-semibold rounded-full <?php echo $statusClass; ?>">
                                <?php echo ucfirst($bankAccount->status); ?>
                            </span>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Tipo</p>
                            <p class="text-sm font-semibold text-gray-700 mt-1 capitalize"><?php echo $bankAccount->accountType; ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Cuenta</p>
                            <p class="text-sm font-semibold text-gray-700 mt-1">***<?php echo substr($bankAccount->accountNumber, -4); ?></p>
                        </div>
                    </div>

                    <!-- INFO DEL TITULAR -->
                    <?php if($bankAccount->ownerName || $bankAccount->branchOffice): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <?php if($bankAccount->ownerName): ?>
                                    <div>
                                        <p class="text-gray-500">Titular</p>
                                        <p class="font-semibold text-gray-700"><?php echo $bankAccount->ownerName; ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if($bankAccount->ownerIdNumber): ?>
                                    <div>
                                        <p class="text-gray-500">NIT/CC</p>
                                        <p class="font-semibold text-gray-700"><?php echo $bankAccount->ownerIdNumber; ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if($bankAccount->branchOffice): ?>
                                    <div>
                                        <p class="text-gray-500">Sucursal</p>
                                        <p class="font-semibold text-gray-700"><?php echo $bankAccount->branchOffice; ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if($bankAccount->contactPhone): ?>
                                    <div>
                                        <p class="text-gray-500">Teléfono</p>
                                        <p class="font-semibold text-gray-700"><?php echo $bankAccount->contactPhone; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ÚLTIMA CONCILIACIÓN -->
                    <?php if($lastReconciliation): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-xs font-semibold text-blue-600 uppercase mb-2">Última Conciliación</p>
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Fecha</p>
                                    <p class="font-semibold text-gray-700"><?php echo date('d/m/Y', strtotime($lastReconciliation->reconciliationDate)); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Libros</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastReconciliation->bookBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Banco</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastReconciliation->bankBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Diferencia</p>
                                    <p class="font-semibold <?php echo ($lastReconciliation->difference == 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                        $<?php echo number_format($lastReconciliation->difference, 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- MOVIMIENTOS -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Movimientos</p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Categoría</th>
                                        <th class="px-4 py-3">Monto</th>
                                        <th class="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $mov): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm"><?php echo date('d/m/Y H:i', strtotime($mov->movementDate)); ?></td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($mov->movementType) {
                                                            case 'ingreso':
                                                                $typeClass = 'text-green-700 bg-green-100';
                                                                break;
                                                            case 'egreso':
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
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $typeClass; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo $mov->concept; ?></td>
                                                <td class="px-4 py-3 text-sm capitalize"><?php echo str_replace('_', ' ', $mov->category); ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php $isNeg = in_array($mov->movementType, ['egreso']); ?>
                                                    <span class="<?php echo $isNeg ? 'text-red-600' : 'text-green-600'; ?>">
                                                        <?php echo $isNeg ? '-' : '+'; ?>$<?php echo number_format($mov->amount, 2); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        <?php echo ($mov->status=='anulado') ? 'text-red-700 bg-red-100' : 'text-gray-600 bg-gray-100'; ?>">
                                                        <?php echo ucfirst($mov->status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">No hay movimientos</td>
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
