<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Libro Diario - Asientos Contables</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid">
            <div class="flex items-center justify-between mt-2 mb-4">
                <h2 class="text-lg font-semibold text-gray-600">
                    Libro Diario <span class="text-sm font-normal text-gray-400">(<?php echo number_format($total); ?> asientos)</span>
                </h2>
                <div class="flex gap-2">
                    <button id="exportDiario" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Excel
                    </button>
                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries/add"
                       class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Nuevo Asiento
                    </a>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if($this->session->flashdata('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('success'); ?>
            </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/entries" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                        <input type="date" name="from" value="<?php echo $filter_from; ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                        <input type="date" name="to" value="<?php echo $filter_to; ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bodega</label>
                        <select name="store" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las bodegas</option>
                            <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>" <?php echo ($filter_store == $store->idStore) ? 'selected' : ''; ?>>
                                    <?php echo $store->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los tipos</option>
                            <option value="purchase" <?php echo ($filter_type == 'purchase') ? 'selected' : ''; ?>>Compra</option>
                            <option value="invoice" <?php echo ($filter_type == 'invoice') ? 'selected' : ''; ?>>Factura</option>
                            <option value="payment" <?php echo ($filter_type == 'payment') ? 'selected' : ''; ?>>Pago</option>
                            <option value="refund" <?php echo ($filter_type == 'refund') ? 'selected' : ''; ?>>Devolución</option>
                            <option value="settlement" <?php echo ($filter_type == 'settlement') ? 'selected' : ''; ?>>Liquidación</option>
                            <option value="supplier_bill" <?php echo ($filter_type == 'supplier_bill') ? 'selected' : ''; ?>>Factura Proveedor</option>
                            <option value="supplier_payment" <?php echo ($filter_type == 'supplier_payment') ? 'selected' : ''; ?>>Pago Proveedor</option>
                            <option value="cash_movement" <?php echo ($filter_type == 'cash_movement') ? 'selected' : ''; ?>>Mov. Caja/Banco</option>
                            <option value="closing" <?php echo ($filter_type == 'closing') ? 'selected' : ''; ?>>Cierre Contable</option>
                            <option value="manual" <?php echo ($filter_type == 'manual') ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Filtrar
                        </button>
                        <a href="<?php echo base_url(); ?>sisvent/accounting/entries" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 mr-4 bg-white bg-opacity-20 rounded-full">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-100">Total Débitos</p>
                            <p class="text-xl font-bold text-white">$ <?php echo number_format($totalDebit, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 mr-4 bg-white bg-opacity-20 rounded-full">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-100">Total Créditos</p>
                            <p class="text-xl font-bold text-white">$ <?php echo number_format($totalCredit, 2); ?></p>
                        </div>
                    </div>
                </div>
                <?php
                $difference = $totalDebit - $totalCredit;
                $isBalanced = abs($difference) < 0.01;
                $bgColor = $isBalanced ? 'from-blue-500 to-blue-600' : 'from-yellow-500 to-yellow-600';
                ?>
                <div class="bg-gradient-to-r <?php echo $bgColor; ?> rounded-lg shadow-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 mr-4 bg-white bg-opacity-20 rounded-full">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white text-opacity-80">Diferencia</p>
                            <p class="text-xl font-bold text-white">
                                $ <?php echo number_format(abs($difference), 2); ?>
                                <?php if($isBalanced): ?>
                                    <span class="text-xs ml-1">(Cuadrado)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Asientos -->
            <div class="w-full overflow-hidden rounded-lg shadow-md bg-white">
              <div class="w-full overflow-x-auto">
                <table id="tableDiario" class="w-full whitespace-no-wrap table2excel" data-tableName="LibroDiario">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-100">
                      <th class="px-4 py-3">Fecha</th>
                      <th class="px-4 py-3">Asiento #</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Cuenta Débito</th>
                      <th class="px-4 py-3 text-right">Débito</th>
                      <th class="px-4 py-3">Cuenta Crédito</th>
                      <th class="px-4 py-3 text-right">Crédito</th>
                      <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($entries)):?>
                        <?php foreach($entries as $entry):?>
                            <tr class="text-gray-700 hover:bg-gray-50">
                              <td class="px-4 py-3 text-sm">
                                <?php echo date("d/m/Y", strtotime($entry->entryDate ?: $entry->entryCreateDate));?>
                              </td>
                              <td class="px-4 py-3 text-sm font-medium text-blue-600">
                                #<?php echo $entry->entryID;?>
                              </td>
                              <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800"><?php echo $entry->entryDescription;?></p>
                                <?php if(!empty($entry->entryTransactionType)): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium leading-tight rounded-full
                                    <?php
                                    switch($entry->entryTransactionType) {
                                        case 'purchase': echo 'bg-purple-100 text-purple-700'; break;
                                        case 'invoice': echo 'bg-green-100 text-green-700'; break;
                                        case 'payment': echo 'bg-blue-100 text-blue-700'; break;
                                        case 'refund': echo 'bg-red-100 text-red-700'; break;
                                        case 'settlement': echo 'bg-yellow-100 text-yellow-700'; break;
                                        case 'supplier_bill': echo 'bg-orange-100 text-orange-700'; break;
                                        case 'supplier_payment': echo 'bg-teal-100 text-teal-700'; break;
                                        case 'cash_movement': echo 'bg-indigo-100 text-indigo-700'; break;
                                        case 'closing': echo 'bg-pink-100 text-pink-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700';
                                    }
                                    ?>">
                                    <?php
                                    switch($entry->entryTransactionType) {
                                        case 'purchase': echo 'Compra'; break;
                                        case 'invoice': echo 'Factura'; break;
                                        case 'payment': echo 'Pago'; break;
                                        case 'refund': echo 'Devolución'; break;
                                        case 'settlement': echo 'Liquidación'; break;
                                        case 'supplier_bill': echo 'Fact. Proveedor'; break;
                                        case 'supplier_payment': echo 'Pago Proveedor'; break;
                                        case 'cash_movement': echo 'Mov. Caja'; break;
                                        case 'closing': echo 'Cierre'; break;
                                        default: echo ucfirst($entry->entryTransactionType);
                                    }
                                    ?>
                                </span>
                                <?php endif; ?>
                              </td>
                              <td class="px-4 py-3">
                                <?php if(!empty($entry->debitaccCode)): ?>
                                <p class="text-xs text-gray-500"><?php echo $entry->debitaccCode; ?></p>
                                <?php endif; ?>
                                <p class="text-sm font-medium"><?php echo $entry->debitaccName;?></p>
                                <?php if (!empty($entry->debitauxaccName)): ?>
                                <p class="text-xs text-gray-500 italic"><?php echo $entry->debitauxaccName;?></p>
                                <?php endif ?>
                              </td>
                              <td class="px-4 py-3 text-right">
                                <p class="text-sm font-semibold text-green-600">$ <?php echo number_format($entry->entryDebitBalance, 2);?></p>
                              </td>
                              <td class="px-4 py-3">
                                <?php if(!empty($entry->creditaccCode)): ?>
                                <p class="text-xs text-gray-500"><?php echo $entry->creditaccCode; ?></p>
                                <?php endif; ?>
                                <p class="text-sm font-medium"><?php echo $entry->creditaccName;?></p>
                                <?php if (!empty($entry->creditauxaccName)): ?>
                                <p class="text-xs text-gray-500 italic"><?php echo $entry->creditauxaccName;?></p>
                                <?php endif ?>
                              </td>
                              <td class="px-4 py-3 text-right">
                                <p class="text-sm font-semibold text-red-600">$ <?php echo number_format($entry->entryCreditBalance, 2);?></p>
                              </td>
                              <td class="px-4 py-3 text-center">
                                <a href="<?php echo base_url(); ?>sisvent/accounting/entries/view/<?php echo $entry->entryID; ?>"
                                   class="text-blue-600 hover:text-blue-800" title="Ver detalle">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                              </td>
                            </tr>
                        <?php endforeach;?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No se encontraron asientos contables para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif;?>
                  </tbody>
                  <?php if(!empty($entries)): ?>
                  <tfoot class="bg-gray-100">
                    <tr class="font-semibold text-gray-700">
                        <td colspan="4" class="px-4 py-3 text-right">Totales de página:</td>
                        <td class="px-4 py-3 text-right text-green-600">
                            $ <?php
                            $pageDebit = 0;
                            foreach($entries as $e) $pageDebit += $e->entryDebitBalance;
                            echo number_format($pageDebit, 2);
                            ?>
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-right text-red-600">
                            $ <?php
                            $pageCredit = 0;
                            foreach($entries as $e) $pageCredit += $e->entryCreditBalance;
                            echo number_format($pageCredit, 2);
                            ?>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                  </tfoot>
                  <?php endif; ?>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50 sm:grid-cols-9">
                <span class="flex items-center col-span-3">
                  <?php $last = ceil($total / $limit); ?>
                  Mostrando <?php echo ((($page-1) * $limit)+1).'-'.((($last == $page) || $last == 0) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total); ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php
                    // Build filter query string for pagination
                    $filterParams = array();
                    if(!empty($filter_from)) $filterParams[] = 'from=' . $filter_from;
                    if(!empty($filter_to)) $filterParams[] = 'to=' . $filter_to;
                    if(!empty($filter_store)) $filterParams[] = 'store=' . $filter_store;
                    if(!empty($filter_type)) $filterParams[] = 'type=' . $filter_type;
                    $filterString = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';

                    echo createLinks($page, $total, $filterString, $limit);
                    ?>
                  </nav>
                </span>
              </div>
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
        $(document).ready(function(){
            $(document).on("click","#exportDiario",function(){
                var table = document.getElementById('tableDiario');
                var wb = XLSX.utils.table_to_book(table, {sheet: "LibroDiario"});
                var fileName = 'LibroDiario_<?php echo $filter_from; ?>_<?php echo $filter_to; ?>.xlsx';
                XLSX.writeFile(wb, fileName);
            });
        });
    </script>
  </body>
</html>
