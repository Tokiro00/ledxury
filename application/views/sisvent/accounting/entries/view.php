<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Detalle de Asiento #<?php echo $entry->entryID; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => 'sisvent/accounting/entries/list.php','role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid max-w-4xl">
            <!-- Header -->
            <div class="flex items-center justify-between mt-4 mb-6">
                <div>
                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver al Libro Diario
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Asiento Contable #<?php echo $entry->entryID; ?>
                    </h2>
                </div>
                <?php if(!empty($entry->entryTransactionType)): ?>
                <span class="inline-flex px-3 py-1 text-sm font-medium leading-tight rounded-full
                    <?php
                    switch($entry->entryTransactionType) {
                        case 'purchase': echo 'bg-purple-100 text-purple-700'; break;
                        case 'invoice': echo 'bg-green-100 text-green-700'; break;
                        case 'payment': echo 'bg-blue-100 text-blue-700'; break;
                        case 'refund': echo 'bg-red-100 text-red-700'; break;
                        case 'settlement': echo 'bg-yellow-100 text-yellow-700'; break;
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
                        default: echo ucfirst($entry->entryTransactionType);
                    }
                    ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Información General</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Fecha:</dt>
                                <dd class="font-medium"><?php echo date("d/m/Y", strtotime($entry->entryDate ?: $entry->entryCreateDate)); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Descripción:</dt>
                                <dd class="font-medium text-right max-w-xs"><?php echo $entry->entryDescription; ?></dd>
                            </div>
                            <?php if(!empty($entry->storeName)): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Bodega:</dt>
                                <dd class="font-medium"><?php echo $entry->storeName; ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($entry->entryType)): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Tipo:</dt>
                                <dd class="font-medium"><?php echo $entry->entryType; ?></dd>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Estado:</dt>
                                <dd>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        <?php echo ($entry->entryStatus == 'activo' || $entry->entryStatus == '1') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo ($entry->entryStatus == 'activo' || $entry->entryStatus == '1') ? 'Activo' : $entry->entryStatus; ?>
                                    </span>
                                </dd>
                            </div>
                            <?php if(!empty($entry->entryStatusComment)): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Comentario:</dt>
                                <dd class="font-medium text-right max-w-xs"><?php echo $entry->entryStatusComment; ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Fecha de Registro</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Creado:</dt>
                                <dd class="font-medium"><?php echo date("d/m/Y H:i", strtotime($entry->entryCreateDate)); ?></dd>
                            </div>
                            <?php if(!empty($entry->created_by)): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Creado por:</dt>
                                <dd class="font-medium"><?php echo $entry->created_by; ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Entry Details Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-100 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Detalle del Asiento</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-6 py-3">Cuenta</th>
                            <th class="px-6 py-3 text-right">Débito</th>
                            <th class="px-6 py-3 text-right">Crédito</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <!-- Debit Row -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php if(!empty($entry->debitaccCode)): ?>
                                <p class="text-xs text-gray-500"><?php echo $entry->debitaccCode; ?></p>
                                <?php endif; ?>
                                <p class="font-medium text-gray-800"><?php echo $entry->debitaccName; ?></p>
                                <?php if(!empty($entry->debitauxaccName)): ?>
                                <p class="text-sm text-gray-500 italic"><?php echo $entry->debitauxaccName; ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-semibold text-green-600">$ <?php echo number_format($entry->entryDebitBalance, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-400">-</td>
                        </tr>
                        <!-- Credit Row -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php if(!empty($entry->creditaccCode)): ?>
                                <p class="text-xs text-gray-500"><?php echo $entry->creditaccCode; ?></p>
                                <?php endif; ?>
                                <p class="font-medium text-gray-800"><?php echo $entry->creditaccName; ?></p>
                                <?php if(!empty($entry->creditauxaccName)): ?>
                                <p class="text-sm text-gray-500 italic"><?php echo $entry->creditauxaccName; ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-400">-</td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-semibold text-red-600">$ <?php echo number_format($entry->entryCreditBalance, 2); ?></span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr class="font-bold text-gray-800">
                            <td class="px-6 py-4 text-right">TOTALES:</td>
                            <td class="px-6 py-4 text-right text-green-600">$ <?php echo number_format($entry->entryDebitBalance, 2); ?></td>
                            <td class="px-6 py-4 text-right text-red-600">$ <?php echo number_format($entry->entryCreditBalance, 2); ?></td>
                        </tr>
                        <?php
                        $diff = $entry->entryDebitBalance - $entry->entryCreditBalance;
                        $isBalanced = abs($diff) < 0.01;
                        ?>
                        <tr class="text-sm">
                            <td colspan="3" class="px-6 py-3 text-center">
                                <?php if($isBalanced): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Asiento Cuadrado
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Diferencia: $ <?php echo number_format(abs($diff), 2); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>
