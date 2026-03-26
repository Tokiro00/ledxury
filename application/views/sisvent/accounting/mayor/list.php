<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Libro Mayor</title>
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
                            Libro Mayor
                        </h2>
                    </div>

                    <!-- FILTROS -->
                    <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/mayor"
                          class="bg-white rounded-lg shadow-sm p-4 mb-4 flex flex-wrap items-end gap-4">
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Cuenta</span>
                            <select name="account" class="form-input form-select" style="min-width:240px">
                                <option value="" <?php echo (!$accountId) ? 'selected' : ''; ?>>— Selecciona una cuenta —</option>
                                <?php foreach ($subaccounts as $sa): ?>
                                    <option value="<?php echo $sa->id; ?>" <?php echo ($accountId == $sa->id) ? 'selected' : ''; ?>>
                                        <?php echo $sa->accountName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Desde</span>
                            <input type="date" name="from" value="<?php echo $from; ?>" class="form-input"/>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Hasta</span>
                            <input type="date" name="to" value="<?php echo $to; ?>" class="form-input"/>
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                            Ver
                        </button>
                    </form>

                    <?php if ($account): ?>

                        <!-- INFO DE LA CUENTA -->
                        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                            <div class="flex items-center gap-6 text-sm">
                                <div>
                                    <p class="text-gray-500">Cuenta</p>
                                    <p class="font-semibold text-gray-700"><?php echo $account->accountName; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Naturaleza</p>
                                    <p class="font-semibold text-gray-700">
                                        <?php echo ($account->accountSide == '1') ? 'Débito' : 'Crédito'; ?>
                                    </p>
                                </div>
                                <?php if (isset($account->pucCode) && $account->pucCode): ?>
                                <div>
                                    <p class="text-gray-500">Código PUC</p>
                                    <p class="font-semibold text-gray-700"><?php echo $account->pucCode; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- RESUMEN DE SALDOS -->
                        <div class="grid grid-cols-4 gap-4 mb-6">
                            <div class="bg-white rounded-lg shadow-sm p-4">
                                <p class="text-xs text-gray-500 uppercase">Saldo Inicio</p>
                                <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($openingBalance, 2); ?></p>
                            </div>
                            <div class="bg-green-50 rounded-lg shadow-sm p-4">
                                <p class="text-xs text-green-600 uppercase">Total Débitos</p>
                                <?php
                                    $totalDeb = 0;
                                    foreach ($entries as $e) {
                                        if ($e->movType === 'debit') $totalDeb += $e->movAmount;
                                    }
                                ?>
                                <p class="text-lg font-bold text-green-700 mt-1">$<?php echo number_format($totalDeb, 2); ?></p>
                            </div>
                            <div class="bg-red-50 rounded-lg shadow-sm p-4">
                                <p class="text-xs text-red-600 uppercase">Total Créditos</p>
                                <?php
                                    $totalCred = 0;
                                    foreach ($entries as $e) {
                                        if ($e->movType === 'credit') $totalCred += $e->movAmount;
                                    }
                                ?>
                                <p class="text-lg font-bold text-red-700 mt-1">$<?php echo number_format($totalCred, 2); ?></p>
                            </div>
                            <div class="bg-white rounded-lg shadow-sm p-4">
                                <p class="text-xs text-gray-500 uppercase">Saldo Final</p>
                                <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($closingBalance, 2); ?></p>
                            </div>
                        </div>

                        <!-- TABLA DE MOVIMIENTOS -->
                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                            <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    Movimientos (<?php echo count($entries); ?>)
                                </p>
                            </div>
                            <div class="w-full overflow-x-auto">
                                <table class="w-full whitespace-no-wrap">
                                    <thead>
                                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                            <th class="px-4 py-3">Fecha</th>
                                            <th class="px-4 py-3">#</th>
                                            <th class="px-4 py-3">Descripción</th>
                                            <th class="px-4 py-3 text-right">Débito</th>
                                            <th class="px-4 py-3 text-right">Crédito</th>
                                            <th class="px-4 py-3 text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y">
                                        <!-- Fila de saldo inicial -->
                                        <tr class="bg-gray-50 text-gray-600">
                                            <td class="px-4 py-2 text-sm" colspan="3">
                                                <strong>Saldo de apertura</strong> (antes de <?php echo date('d/m/Y', strtotime($from)); ?>)
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right"></td>
                                            <td class="px-4 py-2 text-sm text-right"></td>
                                            <td class="px-4 py-2 text-sm text-right font-semibold text-gray-800">
                                                $<?php echo number_format($openingBalance, 2); ?>
                                            </td>
                                        </tr>

                                        <?php if(!empty($entries)): ?>
                                            <?php foreach($entries as $entry): ?>
                                                <tr class="text-gray-700">
                                                    <td class="px-4 py-3 text-sm">
                                                        <?php echo date('d/m/Y', strtotime($entry->entryDate)); ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm font-semibold text-gray-500">
                                                        <?php echo $entry->entryID; ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm"><?php echo $entry->entryDescription; ?></td>
                                                    <td class="px-4 py-3 text-sm text-right">
                                                        <?php if ($entry->movType === 'debit'): ?>
                                                            <span class="text-green-700 font-medium">$<?php echo number_format($entry->movAmount, 2); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right">
                                                        <?php if ($entry->movType === 'credit'): ?>
                                                            <span class="text-red-700 font-medium">$<?php echo number_format($entry->movAmount, 2); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right font-semibold
                                                        <?php echo ($entry->runningBalance < 0) ? 'text-red-700' : 'text-gray-800'; ?>">
                                                        $<?php echo number_format($entry->runningBalance, 2); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">
                                                    No hay movimientos en este período
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <!-- FILA DE TOTALES -->
                                    <?php if (!empty($entries)): ?>
                                    <tfoot>
                                        <tr class="bg-gray-50 border-t-2 border-gray-200">
                                            <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-600">Totales</td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-green-700">
                                                $<?php echo number_format($totalDeb, 2); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-red-700">
                                                $<?php echo number_format($totalCred, 2); ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-gray-800">
                                                $<?php echo number_format($closingBalance, 2); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Estado vacío cuando no se ha seleccionado cuenta -->
                        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                            <p class="text-gray-500">Selecciona una cuenta para ver sus movimientos</p>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>