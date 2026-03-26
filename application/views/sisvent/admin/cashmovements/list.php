<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;

    // Lookup arrays para nombres legibles
    $cashboxNames = array();
    $cashboxBalances = array();
    foreach($cashboxes as $cb) {
        $cashboxNames[$cb->idCashbox] = $cb->name;
        $cashboxBalances[$cb->idCashbox] = $cb->currentBalance;
    }
    $bankNames = array();
    $bankBalances = array();
    foreach($bankAccounts as $ba) {
        $bankNames[$ba->idBankAccount] = $ba->bankName . ' - ****' . substr($ba->accountNumber, -4);
        $bankBalances[$ba->idBankAccount] = $ba->currentBalance;
    }

    // Calcular saldo corrido cuando se filtra por un origen específico
    $showSaldo = (!empty($filters['sourceType']) && !empty($filters['sourceId']));
    $runningBalances = array();
    if ($showSaldo && !empty($movements)) {
        // Balance antes del filtro + net de páginas anteriores (orden ASC: antiguo arriba)
        $bal = (isset($balanceBeforeFilter) ? (float)$balanceBeforeFilter : 0)
             + (isset($netPreviousPages) ? (float)$netPreviousPages : 0);

        // Movimientos ordenados ASC (más antiguo primero), acumular hacia adelante
        foreach ($movements as $i => $m) {
            if (in_array($m->movementType, ['ingreso', 'apertura'])) {
                $bal += (float)$m->amount;
            } elseif (in_array($m->movementType, ['egreso', 'cierre', 'transferencia'])) {
                $bal -= (float)$m->amount;
            }
            $runningBalances[$i] = $bal;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <title>Movimientos</title>
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
                    <div class="flex items-center justify-between mb-2 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Movimientos</h2>
                        <div class="flex items-center space-x-3">
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/transfer"
                               class="px-4 py-2 text-sm font-medium text-mam-blue-petroleo border border-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo hover:text-white">
                                Transferencia
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/add"
                               class="flex items-center px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Nuevo Movimiento</span>
                            </a>
                        </div>
                    </div>

                    <!-- BÚSQUEDA -->
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="flex flex-1">
                            <input class="form-input-lg inline w-full rounded-l-lg rounded-r-none"
                                   type="text" id="movements-search"
                                   value="<?php echo isset($search_term) ? $search_term : ''; ?>"
                                   placeholder="Buscar por concepto, documento..."/>
                            <button id="btn-search-movements" type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-r-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if(isset($search_term)): ?>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                               class="text-sm text-red-500 hover:text-red-700">✕ Limpiar</a>
                        <?php endif; ?>
                    </div>

                    <!-- FILTROS -->
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <div class="grid grid-cols-4 gap-4">
                            <label class="block text-xs">
                                <span class="text-gray-500">Origen</span>
                                <select class="form-input form-select" id="filter-source">
                                    <option value="">Todos</option>
                                    <optgroup label="Cajas">
                                        <?php foreach($cashboxes as $cb): ?>
                                            <option value="caja|<?php echo $cb->idCashbox; ?>"
                                                    <?php echo (isset($filters['sourceType']) && $filters['sourceType']=='caja' && $filters['sourceId']==$cb->idCashbox) ? 'selected' : ''; ?>>
                                                <?php echo $cb->name; ?> (Saldo: $<?php echo number_format($cb->currentBalance, 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Bancos">
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <option value="banco|<?php echo $ba->idBankAccount; ?>"
                                                    <?php echo (isset($filters['sourceType']) && $filters['sourceType']=='banco' && $filters['sourceId']==$ba->idBankAccount) ? 'selected' : ''; ?>>
                                                <?php echo $ba->bankName . ' - ****' . substr($ba->accountNumber, -4); ?> (Saldo: $<?php echo number_format($ba->currentBalance, 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-500">Tipo</span>
                                <select class="form-input form-select" id="filter-type">
                                    <option value="">Todos</option>
                                    <option value="ingreso" <?php echo isset($filters['movementType']) && $filters['movementType']=='ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                                    <option value="egreso" <?php echo isset($filters['movementType']) && $filters['movementType']=='egreso' ? 'selected' : ''; ?>>Egreso</option>
                                    <option value="transferencia" <?php echo isset($filters['movementType']) && $filters['movementType']=='transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="ajuste" <?php echo isset($filters['movementType']) && $filters['movementType']=='ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                                </select>
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-500">Desde</span>
                                <input class="form-input" type="date" id="filter-from"
                                       value="<?php echo isset($filters['from']) ? substr($filters['from'], 0, 10) : date('Y') . '-01-01'; ?>"/>
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-500">Hasta</span>
                                <input class="form-input" type="date" id="filter-to"
                                       value="<?php echo isset($filters['to']) ? substr($filters['to'], 0, 10) : date('Y-m-d'); ?>"/>
                            </label>
                        </div>
                        <div class="mt-3 flex items-center space-x-3">
                            <button id="btn-apply-filters" type="button"
                                    class="px-4 py-1 text-xs font-medium text-white bg-mam-blue-petroleo rounded-lg">
                                Aplicar Filtros
                            </button>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                               class="text-xs text-gray-500 hover:text-gray-700">Limpiar filtros</a>
                        </div>
                    </div>

                    <!-- SALDOS ACTUALES (sticky) -->
                    <div class="sticky top-0 z-10 bg-gray-50 pt-1 pb-2 -mx-6 px-6 mb-4 border-b border-gray-200">
                        <div class="flex flex-wrap gap-3">
                            <?php foreach($cashboxes as $cb): ?>
                            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg shadow-sm border-l-4 border-green-500">
                                <div>
                                    <p class="text-xs font-medium text-gray-500"><?php echo $cb->name; ?></p>
                                    <p class="text-base font-bold text-green-600">$<?php echo number_format($cb->currentBalance, 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php foreach($bankAccounts as $ba): ?>
                            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg shadow-sm border-l-4 border-blue-500">
                                <div>
                                    <p class="text-xs font-medium text-gray-500"><?php echo $ba->bankName; ?> ****<?php echo substr($ba->accountNumber, -4); ?></p>
                                    <p class="text-base font-bold text-blue-600">$<?php echo number_format($ba->currentBalance, 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- MENSAJE FLASH -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- TABLA -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Origen</th>
                                        <th class="px-4 py-3">Monto</th>
                                        <?php if($showSaldo): ?>
                                        <th class="px-4 py-3">Saldo</th>
                                        <?php endif; ?>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $idx => $mov): ?>
                                            <?php
                                                // Nombre legible del origen
                                                if ($mov->sourceType == 'caja' && !empty($mov->cashboxName)) {
                                                    $sourceName = $mov->cashboxName;
                                                } elseif ($mov->sourceType == 'banco' && !empty($mov->bankName)) {
                                                    $sourceName = $mov->bankName . ' - ****' . substr($mov->accountNumber, -4);
                                                } else {
                                                    $sourceName = ucfirst($mov->sourceType) . ' #' . $mov->sourceId;
                                                }
                                            ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm"><?php echo date('d/m/Y H:i', strtotime($mov->movementDate)); ?></td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($mov->movementType) {
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
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $typeClass; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo $mov->concept; ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo $sourceName; ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php $isNeg = in_array($mov->movementType, ['egreso', 'cierre']); ?>
                                                    <span class="<?php echo $isNeg ? 'text-red-600' : 'text-green-600'; ?>">
                                                        <?php echo $isNeg ? '-' : '+'; ?>$<?php echo number_format($mov->amount, 2); ?>
                                                    </span>
                                                </td>
                                                <?php if($showSaldo): ?>
                                                <td class="px-4 py-3 text-sm font-semibold">
                                                    <?php
                                                        $saldo = isset($runningBalances[$idx]) ? $runningBalances[$idx] : 0;
                                                    ?>
                                                    <span class="<?php echo $saldo >= 0 ? 'text-gray-700' : 'text-red-600'; ?>">
                                                        $<?php echo number_format($saldo, 2); ?>
                                                    </span>
                                                </td>
                                                <?php endif; ?>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        <?php echo ($mov->status=='anulado') ? 'text-red-700 bg-red-100' : 'text-gray-600 bg-gray-100'; ?>">
                                                        <?php echo ucfirst($mov->status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/view/<?php echo $mov->idMovement; ?>"
                                                           class="text-mam-blue-petroleo hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                                            </svg>
                                                        </a>
                                                        <?php if($mov->status != 'anulado' && !in_array($mov->movementType, ['apertura','cierre'])): ?>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/cancel/<?php echo $mov->idMovement; ?>"
                                                               class="text-red-500 hover:text-red-700"
                                                               onclick="showSureModal(event,this)">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $showSaldo ? 8 : 7; ?>" class="px-4 py-3 text-sm text-center text-gray-500">No hay movimientos</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
                        <div class="grid grid-cols-7 px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50">
                            <span class="flex items-center col-span-3">
                                <?php if($total > 0): ?>
                                    Mostrando <?php echo ((($page-1)*$limit)+1).'-'.(($last==$page)?$total:((($page-1)*$limit)+$limit)).' de '.$total; ?>
                                <?php else: ?>
                                    No se encontraron resultados
                                <?php endif; ?>
                            </span>
                            <span class="flex col-span-4 justify-end">
                                <?php echo createLinks($page, $total, "", $limit); ?>
                            </span>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        // Búsqueda - delegated events
        $(document).on('click', '#btn-search-movements', function() {
            var term = $('#movements-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/cashmovements/search/' + encodeURIComponent(term);
            }
        });
        $(document).on('keypress', '#movements-search', function(e) {
            if (e.which == 13) $('#btn-search-movements').click();
        });

        // Filtros - delegated events
        $(document).on('click', '#btn-apply-filters', function() {
            var source = $('#filter-source').val();
            var type = $('#filter-type').val();
            var from = $('#filter-from').val();
            var to = $('#filter-to').val();

            var params = [];
            if (source) {
                var parts = source.split('|');
                params.push('st=' + parts[0]);
                params.push('si=' + parts[1]);
            }
            if (type) params.push('mt=' + type);
            if (from) params.push('from=' + from);
            if (to) params.push('to=' + to);

            var url = '<?php echo base_url(); ?>sisvent/admin/cashmovements';
            if (params.length > 0) url += '?' + params.join('&');
            window.location.href = url;
        });
    </script>
</body>
</html>
