<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cierre Contable - Períodos</title>
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
            <div class="flex items-center justify-between mt-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-600">
                    Cierre Contable <span class="text-sm font-normal text-gray-400">(<?php echo number_format($total); ?> períodos)</span>
                </h2>
                <a href="<?php echo base_url(); ?>sisvent/accounting/cierre/nuevo"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Nuevo Cierre
                </a>
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

            <!-- Filtro por bodega -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/cierre" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Filtrar por Bodega</label>
                        <select name="store" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las bodegas</option>
                            <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>" <?php echo ($filter_store == $store->idStore) ? 'selected' : ''; ?>>
                                    <?php echo $store->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Filtrar
                    </button>
                </form>
            </div>

            <!-- Tabla de Períodos -->
            <div class="w-full overflow-hidden rounded-lg shadow-md bg-white">
              <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-100">
                      <th class="px-4 py-3">Período</th>
                      <th class="px-4 py-3">Bodega</th>
                      <th class="px-4 py-3">Tipo</th>
                      <th class="px-4 py-3">Estado</th>
                      <th class="px-4 py-3 text-right">Ingresos</th>
                      <th class="px-4 py-3 text-right">Gastos + Costos</th>
                      <th class="px-4 py-3 text-right">Resultado</th>
                      <th class="px-4 py-3">Cerrado por</th>
                      <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($periods)):?>
                        <?php
                        $months = array(1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
                                       7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic');
                        ?>
                        <?php foreach($periods as $period):?>
                            <tr class="text-gray-700 hover:bg-gray-50">
                              <td class="px-4 py-3 font-medium">
                                <?php echo $months[$period->periodMonth] . ' ' . $period->periodYear; ?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $period->storeName ?: 'Todas'; ?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $period->periodType === 'yearly' ? 'Anual' : 'Mensual'; ?>
                              </td>
                              <td class="px-4 py-3">
                                <?php
                                $statusColors = array(
                                    'open' => 'bg-yellow-100 text-yellow-700',
                                    'closed' => 'bg-green-100 text-green-700',
                                    'reopened' => 'bg-orange-100 text-orange-700'
                                );
                                $statusLabels = array(
                                    'open' => 'Abierto',
                                    'closed' => 'Cerrado',
                                    'reopened' => 'Reabierto'
                                );
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$period->status]; ?>">
                                    <?php echo $statusLabels[$period->status]; ?>
                                </span>
                              </td>
                              <td class="px-4 py-3 text-right text-sm">
                                $ <?php echo number_format($period->totalIncome, 2); ?>
                              </td>
                              <td class="px-4 py-3 text-right text-sm">
                                $ <?php echo number_format($period->totalExpenses + $period->totalCosts, 2); ?>
                              </td>
                              <td class="px-4 py-3 text-right">
                                <span class="font-semibold <?php echo $period->netIncome >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    $ <?php echo number_format($period->netIncome, 2); ?>
                                </span>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php if($period->closedBy): ?>
                                    <?php echo $period->closedBy; ?><br>
                                    <span class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($period->closedAt)); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                              </td>
                              <td class="px-4 py-3 text-center">
                                <a href="<?php echo base_url(); ?>sisvent/accounting/cierre/view/<?php echo $period->id; ?>"
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
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                No hay períodos contables registrados.
                            </td>
                        </tr>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <?php if($total > $limit): ?>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50 sm:grid-cols-9">
                <span class="flex items-center col-span-3">
                  <?php $last = ceil($total / $limit); ?>
                  Mostrando <?php echo ((($page-1) * $limit)+1).'-'.((($last == $page) || $last == 0) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total); ?>
                </span>
                <span class="col-span-2"></span>
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php
                    $filterString = $filter_store ? '&store=' . $filter_store : '';
                    echo createLinks($page, $total, $filterString, $limit);
                    ?>
                  </nav>
                </span>
              </div>
              <?php endif; ?>
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>
