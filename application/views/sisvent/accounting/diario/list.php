<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Libro Diario</title>
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
                            Libro Diario
                        </h2>
                    </div>

                    <!-- FILTRO DE FECHAS -->
                    <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/diario"
                          class="bg-white rounded-lg shadow-sm p-4 mb-4 flex flex-wrap items-end gap-4">
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Desde</span>
                            <input type="date" name="from" value="<?php echo $from; ?>" class="form-input"/>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Hasta</span>
                            <input type="date" name="to" value="<?php echo $to; ?>" class="form-input"/>
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue">
                            Filtrar
                        </button>
                    </form>

                    <!-- RESUMEN -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Asientos</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?php echo $total; ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-green-600 uppercase">Total Débitos</p>
                            <p class="text-lg font-bold text-green-700 mt-1">$<?php echo number_format($totalDebit, 2); ?></p>
                        </div>
                        <div class="bg-blue-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-blue-600 uppercase">Total Créditos</p>
                            <p class="text-lg font-bold text-blue-700 mt-1">$<?php echo number_format($totalCredit, 2); ?></p>
                        </div>
                    </div>

                    <!-- TABLA DE ASIENTOS -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                Asientos (<?php echo $total; ?>)
                            </p>
                            <?php if ($total > 0): ?>
                                <p class="text-xs text-gray-400">
                                    <?php echo (($page-1)*$limit+1); ?> – <?php echo min($page*$limit, $total); ?> de <?php echo $total; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">#</th>
                                        <th class="px-4 py-3">Descripción</th>
                                        <th class="px-4 py-3">Cuenta Débito</th>
                                        <th class="px-4 py-3">Cuenta Crédito</th>
                                        <th class="px-4 py-3 text-right">Débito</th>
                                        <th class="px-4 py-3 text-right">Crédito</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
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
                                                <td class="px-4 py-3 text-sm">
                                                    <p class="font-medium"><?php echo $entry->debitaccName; ?></p>
                                                    <?php if ($entry->debitauxaccName): ?>
                                                        <p class="text-xs text-gray-400"><?php echo $entry->debitauxaccName; ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <p class="font-medium"><?php echo $entry->creditaccName; ?></p>
                                                    <?php if ($entry->creditauxaccName): ?>
                                                        <p class="text-xs text-gray-400"><?php echo $entry->creditauxaccName; ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-green-700 font-medium">
                                                    $<?php echo number_format((float)$entry->entryDebitBalance, 2); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-blue-700 font-medium">
                                                    $<?php echo number_format((float)$entry->entryCreditBalance, 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay asientos en este período
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <!-- FILA DE TOTALES -->
                                <?php if ($total > 0): ?>
                                <tfoot>
                                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                                        <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-600">Totales</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold text-green-700">
                                            $<?php echo number_format($totalDebit, 2); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-bold text-blue-700">
                                            $<?php echo number_format($totalCredit, 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- PAGINACIÓN -->
                    <?php if ($total > $limit): ?>
                        <div class="flex items-center justify-between mt-4 mb-6">
                            <p class="text-xs text-gray-500">
                                Página <?php echo $page; ?> de <?php echo ceil($total/$limit); ?>
                            </p>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?from=<?php echo $from; ?>&to=<?php echo $to; ?>&p=<?php echo $page-1; ?>"
                                       class="px-3 py-1 text-sm text-gray-600 border rounded-lg hover:bg-gray-100">← Anterior</a>
                                <?php endif; ?>
                                <?php if ($page * $limit < $total): ?>
                                    <a href="?from=<?php echo $from; ?>&to=<?php echo $to; ?>&p=<?php echo $page+1; ?>"
                                       class="px-3 py-1 text-sm text-gray-600 border rounded-lg hover:bg-gray-100">Siguiente →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>