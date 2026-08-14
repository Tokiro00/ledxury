<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Libro de Bancos — <?php echo $bankAccount->bankName; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <style>
        @media print {
            .no-print, aside, nav, header, .sidebar, [class*="side"] { display: none !important; }
            #bars { display: block !important; }
            main { overflow: visible !important; height: auto !important; }
            body { background: #fff !important; }
        }
    </style>
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
                            Libro de Bancos — <?php echo $bankAccount->bankName; ?> (***<?php echo substr($bankAccount->accountNumber, -4); ?>)
                        </h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <!-- FILTRO DE FECHAS + TIPO -->
                    <form method="get" action="<?php echo base_url(); ?>sisvent/admin/bankaccounts/libro/<?php echo $bankAccount->idBankAccount; ?>"
                          class="bg-white rounded-lg shadow-sm p-4 mb-4 flex flex-wrap items-end gap-4 no-print">
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Desde</span>
                            <input type="date" name="from" value="<?php echo $from; ?>" class="form-input"/>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Hasta</span>
                            <input type="date" name="to" value="<?php echo $to; ?>" class="form-input"/>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Tipo</span>
                            <?php $type = isset($type) ? $type : ''; ?>
                            <select name="mt" class="form-input">
                                <option value="">Todos</option>
                                <?php foreach (array('ingreso','egreso','transferencia','ajuste','apertura','cierre') as $mt): ?>
                                    <option value="<?php echo $mt; ?>" <?php echo ($type === $mt) ? 'selected' : ''; ?>><?php echo ucfirst($mt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                            Filtrar
                        </button>
                        <button type="button" onclick="window.print()" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100">
                            Imprimir / PDF
                        </button>
                    </form>

                    <!-- RESUMEN -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Inicio</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($openingBalance, 2); ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-green-600 uppercase">Ingresos</p>
                            <p class="text-lg font-bold text-green-700 mt-1">+$<?php echo number_format($totalIngress, 2); ?></p>
                        </div>
                        <div class="bg-red-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-red-600 uppercase">Egresos</p>
                            <p class="text-lg font-bold text-red-700 mt-1">-$<?php echo number_format($totalEgress, 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Final</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?php echo number_format($closingBalance, 2); ?></p>
                        </div>
                    </div>

                    <!-- TABLA DE MOVIMIENTOS CON SALDO CORRIDO -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                Movimientos (<?php echo count($movements); ?>)
                            </p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Comprobante</th>
                                        <th class="px-4 py-3">Categoría</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <!-- Fila de saldo inicial -->
                                    <tr class="bg-gray-50 text-gray-600">
                                        <td class="px-4 py-2 text-sm" colspan="5">
                                            <strong>Saldo de apertura</strong> (antes de <?php echo date('d/m/Y', strtotime($from)); ?>)
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right"></td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold text-gray-800">
                                            $<?php echo number_format($openingBalance, 2); ?>
                                        </td>
                                    </tr>

                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $mov): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm whitespace-no-wrap">
                                                    <?php echo date('d/m/Y H:i', strtotime($mov->movementDate)); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($mov->movementType) {
                                                            case 'ingreso':   $tc = 'text-green-700 bg-green-100'; break;
                                                            case 'egreso':    $tc = 'text-red-700 bg-red-100'; break;
                                                            case 'transferencia': $tc = 'text-blue-700 bg-blue-100'; break;
                                                            case 'ajuste':    $tc = 'text-yellow-700 bg-yellow-100'; break;
                                                            default:          $tc = 'text-gray-600 bg-gray-100'; break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-no-wrap <?php echo $tc; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?><?php echo (!empty($mov->isIncoming)) ? ' ↓' : ''; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-xs" style="max-width:26rem; overflow-wrap:break-word;"><?php echo htmlspecialchars((string)$mov->concept); ?></td>
                                                <td class="px-4 py-3 text-sm font-mono text-gray-600 whitespace-no-wrap"><?php echo htmlspecialchars((string)($mov->documentNumber ?? '')); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo movement_category_label($mov->category); ?></td>
                                                <td class="px-4 py-3 text-sm text-right whitespace-no-wrap">
                                                    <span class="<?php echo ($mov->sign === -1) ? 'text-red-600' : 'text-green-600'; ?>">
                                                        <?php echo ($mov->sign === -1) ? '-' : '+'; ?>$<?php echo number_format(abs((float)$mov->amount), 2); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800 whitespace-no-wrap">
                                                    $<?php echo number_format($mov->runningBalance, 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay movimientos en este período
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
