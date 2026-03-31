<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Reporte Conciliacion #<?php echo $reconciliation->idReconciliation; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 mx-auto w-full" id="report-content">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Reporte de Conciliacion — <?php echo $bankAccount->bankName; ?> (***<?php echo substr($bankAccount->accountNumber, -4); ?>)
                        </h2>
                        <div class="flex items-center gap-3 no-print">
                            <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Imprimir / Exportar PDF
                            </button>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/reconciliationDetail/<?php echo $reconciliation->idReconciliation; ?>"
                               class="text-sm text-mam-blue-petroleo hover:underline">&larr; Volver a Detalle</a>
                        </div>
                    </div>

                    <!-- Bank Info -->
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <div class="grid grid-cols-6 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Banco</p>
                                <p class="font-semibold text-gray-700"><?php echo $bankAccount->bankName; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Cuenta</p>
                                <p class="font-semibold text-gray-700"><?php echo $bankAccount->accountNumber; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Periodo</p>
                                <p class="font-semibold text-gray-700">
                                    <?php
                                    $months = array(1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic');
                                    echo ($reconciliation->periodMonth ? $months[$reconciliation->periodMonth] . ' ' . $reconciliation->periodYear : date('d/m/Y', strtotime($reconciliation->statementDate)));
                                    ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Realizada por</p>
                                <p class="font-semibold text-gray-700"><?php echo $reconciliation->reconciledBy; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Fecha</p>
                                <p class="font-semibold text-gray-700"><?php echo date('d/m/Y H:i', strtotime($reconciliation->reconciliationDate)); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Estado</p>
                                <p class="font-semibold capitalize <?php echo ($reconciliation->status === 'conciliada') ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo $reconciliation->status; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 border-mam-blue-petroleo">
                            <p class="text-xs text-gray-500 uppercase mb-1">Saldo en Libros</p>
                            <p class="text-xl font-bold text-gray-800">$<?php echo number_format($reconciliation->bookBalance, 0, ',', '.'); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 border-green-500">
                            <p class="text-xs text-gray-500 uppercase mb-1">Saldo segun Banco</p>
                            <p class="text-xl font-bold text-gray-800">$<?php echo number_format($reconciliation->bankBalance, 0, ',', '.'); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 <?php echo ($reconciliation->difference == 0) ? 'border-green-500' : 'border-red-500'; ?>">
                            <p class="text-xs text-gray-500 uppercase mb-1">Diferencia</p>
                            <p class="text-xl font-bold <?php echo ($reconciliation->difference == 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                $<?php echo number_format($reconciliation->difference, 0, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-3 mb-6">
                        <div class="bg-blue-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-blue-600">Total Lineas</p>
                            <p class="text-2xl font-bold text-blue-700"><?php echo $stats ? $stats->total_lines : 0; ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-green-600">Conciliadas</p>
                            <p class="text-2xl font-bold text-green-700"><?php echo count($matchedLines); ?></p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-yellow-600">No Conc. Extracto</p>
                            <p class="text-2xl font-bold text-yellow-700"><?php echo count($unmatchedLines); ?></p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-orange-600">No Conc. Sistema</p>
                            <p class="text-2xl font-bold text-orange-700"><?php echo count($unreconciledMovements); ?></p>
                        </div>
                    </div>

                    <!-- Matched Items -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="px-4 py-3 border-b" style="background:#1B365D;">
                            <h3 class="text-sm font-semibold text-white">Partidas Conciliadas (<?php echo count($matchedLines); ?>)</h3>
                        </div>
                        <?php if(!empty($matchedLines)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2 text-left">#</th>
                                        <th class="px-3 py-2 text-left">Fecha Banco</th>
                                        <th class="px-3 py-2 text-left">Descripcion</th>
                                        <th class="px-3 py-2 text-right">Debito</th>
                                        <th class="px-3 py-2 text-right">Credito</th>
                                        <th class="px-3 py-2 text-center">Mov. Sistema</th>
                                        <th class="px-3 py-2 text-center">Tipo Match</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach($matchedLines as $line): ?>
                                    <tr class="bg-green-50">
                                        <td class="px-3 py-2"><?php echo $line->rowNumber; ?></td>
                                        <td class="px-3 py-2"><?php echo date('d/m/Y', strtotime($line->transactionDate)); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars(mb_substr($line->description, 0, 50)); ?></td>
                                        <td class="px-3 py-2 text-right text-red-600"><?php echo (float)$line->debit > 0 ? '$' . number_format($line->debit, 0, ',', '.') : ''; ?></td>
                                        <td class="px-3 py-2 text-right text-green-600"><?php echo (float)$line->credit > 0 ? '$' . number_format($line->credit, 0, ',', '.') : ''; ?></td>
                                        <td class="px-3 py-2 text-center font-mono">#<?php echo $line->matchedMovementId; ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 rounded text-xs <?php echo $line->matchStatus === 'matched' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo $line->matchStatus === 'matched' ? 'Auto' : 'Manual'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="p-4 text-center text-gray-500 text-sm">No hay partidas conciliadas</p>
                        <?php endif; ?>
                    </div>

                    <!-- Unmatched Bank Lines -->
                    <?php if(!empty($unmatchedLines)): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="px-4 py-3 border-b bg-yellow-600">
                            <h3 class="text-sm font-semibold text-white">Partidas en Extracto sin Conciliar (<?php echo count($unmatchedLines); ?>)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2 text-left">#</th>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Descripcion</th>
                                        <th class="px-3 py-2 text-left">Referencia</th>
                                        <th class="px-3 py-2 text-right">Debito</th>
                                        <th class="px-3 py-2 text-right">Credito</th>
                                        <th class="px-3 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach($unmatchedLines as $line): ?>
                                    <tr class="bg-yellow-50">
                                        <td class="px-3 py-2"><?php echo $line->rowNumber; ?></td>
                                        <td class="px-3 py-2"><?php echo date('d/m/Y', strtotime($line->transactionDate)); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($line->description); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($line->reference); ?></td>
                                        <td class="px-3 py-2 text-right text-red-600"><?php echo (float)$line->debit > 0 ? '$' . number_format($line->debit, 0, ',', '.') : ''; ?></td>
                                        <td class="px-3 py-2 text-right text-green-600"><?php echo (float)$line->credit > 0 ? '$' . number_format($line->credit, 0, ',', '.') : ''; ?></td>
                                        <td class="px-3 py-2 text-right">$<?php echo number_format($line->balance, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Unreconciled System Movements -->
                    <?php if(!empty($unreconciledMovements)): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="px-4 py-3 border-b bg-orange-600">
                            <h3 class="text-sm font-semibold text-white">Movimientos del Sistema sin Conciliar (<?php echo count($unreconciledMovements); ?>)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2 text-left">ID</th>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Concepto</th>
                                        <th class="px-3 py-2 text-left">Tipo</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                        <th class="px-3 py-2 text-left">Categoria</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach($unreconciledMovements as $mov): ?>
                                    <tr class="bg-orange-50">
                                        <td class="px-3 py-2 font-mono">#<?php echo $mov->idMovement; ?></td>
                                        <td class="px-3 py-2"><?php echo date('d/m/Y', strtotime($mov->movementDate)); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($mov->concept); ?></td>
                                        <td class="px-3 py-2">
                                            <span class="px-1 py-0.5 rounded <?php echo ($mov->movementType === 'ingreso') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($mov->movementType); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold">$<?php echo number_format($mov->amount, 0, ',', '.'); ?></td>
                                        <td class="px-3 py-2"><?php echo $mov->category ?? ''; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($reconciliation->notes)): ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Observaciones</p>
                        <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($reconciliation->notes)); ?></p>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <style>
    @media print {
        .no-print { display: none !important; }
        #bars > div:first-child { display: none !important; }
        main { overflow: visible !important; }
    }
    </style>
</body>
</html>
