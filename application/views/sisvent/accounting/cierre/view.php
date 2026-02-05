<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$months = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
$periodLabel = $period->periodType === 'yearly' ? "Año " . $period->periodYear : $months[$period->periodMonth] . ' ' . $period->periodYear;

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
<!DOCTYPE html>
<html lang="en">
    <title>Cierre Contable - <?php echo $periodLabel; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => 'sisvent/accounting/cierre/list.php','role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid max-w-4xl">
            <!-- Header -->
            <div class="flex items-center justify-between mt-4 mb-6">
                <div>
                    <a href="<?php echo base_url(); ?>sisvent/accounting/cierre" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver a Períodos
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">Cierre Contable: <?php echo $periodLabel; ?></h2>
                </div>
                <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full <?php echo $statusColors[$period->status]; ?>">
                    <?php echo $statusLabels[$period->status]; ?>
                </span>
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-green-100">Ingresos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($period->totalIncome, 2); ?></p>
                </div>
                <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-yellow-100">Costos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($period->totalCosts, 2); ?></p>
                </div>
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-red-100">Gastos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($period->totalExpenses, 2); ?></p>
                </div>
                <div class="<?php echo $period->netIncome >= 0 ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'; ?> rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-white text-opacity-80"><?php echo $period->netIncome >= 0 ? 'Utilidad Neta' : 'Pérdida Neta'; ?></p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format(abs($period->netIncome), 2); ?></p>
                </div>
            </div>

            <!-- Period Details -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Detalles del Período</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Período:</dt>
                            <dd class="font-medium"><?php echo $periodLabel; ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Tipo:</dt>
                            <dd class="font-medium"><?php echo $period->periodType === 'yearly' ? 'Anual' : 'Mensual'; ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Fecha Inicio:</dt>
                            <dd class="font-medium"><?php echo date('d/m/Y', strtotime($period->startDate)); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Fecha Fin:</dt>
                            <dd class="font-medium"><?php echo date('d/m/Y', strtotime($period->endDate)); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Bodega:</dt>
                            <dd class="font-medium"><?php echo $period->storeName ?: 'Todas (Consolidado)'; ?></dd>
                        </div>
                    </dl>
                    <dl class="space-y-3">
                        <?php if($period->closedBy): ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Cerrado por:</dt>
                            <dd class="font-medium"><?php echo $period->closedBy; ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Fecha de Cierre:</dt>
                            <dd class="font-medium"><?php echo date('d/m/Y H:i', strtotime($period->closedAt)); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if($period->reopenedBy): ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Reabierto por:</dt>
                            <dd class="font-medium text-orange-600"><?php echo $period->reopenedBy; ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Fecha Reapertura:</dt>
                            <dd class="font-medium text-orange-600"><?php echo date('d/m/Y H:i', strtotime($period->reopenedAt)); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if($period->closingEntryId): ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Asiento de Cierre:</dt>
                            <dd>
                                <a href="<?php echo base_url(); ?>sisvent/accounting/entries/view/<?php echo $period->closingEntryId; ?>"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    #<?php echo $period->closingEntryId; ?>
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
                <?php if($period->notes): ?>
                <div class="mt-4 pt-4 border-t">
                    <dt class="text-gray-600 mb-1">Notas:</dt>
                    <dd class="text-gray-800"><?php echo nl2br(htmlspecialchars($period->notes)); ?></dd>
                </div>
                <?php endif; ?>
            </div>

            <!-- Closing Entry Details -->
            <?php if($closingEntry): ?>
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-4 py-3 bg-blue-50 border-b">
                    <h3 class="font-semibold text-blue-700">Asiento de Cierre #<?php echo $closingEntry->entryID; ?></h3>
                </div>
                <div class="p-4">
                    <p class="text-gray-600 mb-4"><?php echo $closingEntry->entryDescription; ?></p>
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs font-semibold text-gray-500 uppercase border-b">
                                <th class="px-4 py-2 text-left">Cuenta</th>
                                <th class="px-4 py-2 text-right">Débito</th>
                                <th class="px-4 py-2 text-right">Crédito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr>
                                <td class="px-4 py-3">
                                    <?php if(!empty($closingEntry->debitaccCode)): ?>
                                    <p class="text-xs text-gray-500"><?php echo $closingEntry->debitaccCode; ?></p>
                                    <?php endif; ?>
                                    <p class="font-medium"><?php echo $closingEntry->debitaccName; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">$ <?php echo number_format($closingEntry->entryDebitBalance, 2); ?></td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <?php if(!empty($closingEntry->creditaccCode)): ?>
                                    <p class="text-xs text-gray-500"><?php echo $closingEntry->creditaccCode; ?></p>
                                    <?php endif; ?>
                                    <p class="font-medium"><?php echo $closingEntry->creditaccName; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-600">$ <?php echo number_format($closingEntry->entryCreditBalance, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <?php if($role == 1 && $period->status === 'closed'): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Acciones</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Precaución</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Reabrir un período cerrado anulará el asiento de cierre y permitirá modificar transacciones del período.
                                Esta acción quedará registrada en el sistema.
                            </p>
                        </div>
                    </div>
                </div>
                <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/cierre/reopen/<?php echo $period->id; ?>">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
                            onclick="return confirm('¿Está seguro de reabrir este período? El asiento de cierre será anulado.');">
                        Reabrir Período
                    </button>
                </form>
            </div>
            <?php endif; ?>

    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>
