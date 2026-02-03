<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;
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
                               class="px-4 py-2 text-sm font-medium text-mam-blue-dark border border-mam-blue-dark rounded-lg hover:bg-mam-blue-dark hover:text-white">
                                Transferencia
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/add"
                               class="flex items-center px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark">
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
                                    class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-r-lg">
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
                                                <?php echo $cb->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Bancos">
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <option value="banco|<?php echo $ba->idBankAccount; ?>"
                                                    <?php echo (isset($filters['sourceType']) && $filters['sourceType']=='banco' && $filters['sourceId']==$ba->idBankAccount) ? 'selected' : ''; ?>>
                                                <?php echo $ba->bankName . ' - ' . substr($ba->accountNumber, -4); ?>
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
                                       value="<?php echo isset($filters['from']) ? substr($filters['from'], 0, 10) : ''; ?>"/>
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-500">Hasta</span>
                                <input class="form-input" type="date" id="filter-to"
                                       value="<?php echo isset($filters['to']) ? substr($filters['to'], 0, 10) : ''; ?>"/>
                            </label>
                        </div>
                        <div class="mt-3 flex items-center space-x-3">
                            <button id="btn-apply-filters" type="button"
                                    class="px-4 py-1 text-xs font-medium text-white bg-mam-blue-dark rounded-lg">
                                Aplicar Filtros
                            </button>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                               class="text-xs text-gray-500 hover:text-gray-700">Limpiar filtros</a>
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
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Acciones</th>
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
                                                <td class="px-4 py-3 text-sm capitalize"><?php echo $mov->sourceType; ?> #<?php echo $mov->sourceId; ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php $isNeg = in_array($mov->movementType, ['egreso', 'cierre']); ?>
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
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/view/<?php echo $mov->idMovement; ?>"
                                                           class="text-mam-blue-dark hover:text-mam-blue">
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
                                            <td colspan="7" class="px-4 py-3 text-sm text-center text-gray-500">No hay movimientos</td>
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
        // Búsqueda
        $('#btn-search-movements').on('click', function() {
            var term = $('#movements-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/cashmovements/search/' + encodeURIComponent(term);
            }
        });
        $('#movements-search').on('keypress', function(e) {
            if (e.which == 13) $('#btn-search-movements').click();
        });

        // Filtros
        $('#btn-apply-filters').on('click', function() {
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
