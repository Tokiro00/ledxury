<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Gastos</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Gastos</h2>

                        <div class="flex items-center space-x-3">
                            <div class="flex">
                                <input class="form-input-lg inline w-56 rounded-l-lg rounded-r-none"
                                       type="text" id="expenses-search"
                                       value="<?php echo isset($search_term) ? $search_term : ''; ?>"
                                       placeholder="Buscar gasto"/>
                                <button id="btn-search-expenses" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-r-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <?php if(in_array($role, [1])): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expenses/add"
                                   class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white
                                          bg-mam-blue-petroleo border border-transparent rounded-lg
                                          hover:bg-mam-blue-petroleo focus:outline-none">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Nuevo Gasto</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="px-4 py-3 mb-4 bg-white rounded-lg shadow-xs">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/admin/expenses" class="flex flex-wrap items-end gap-3">
                            <label class="block text-sm">
                                <span class="text-gray-700 text-xs">Categoria</span>
                                <select class="form-input form-select text-sm py-1" name="category_id">
                                    <option value="">Todas</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat->id; ?>" <?php echo (isset($filters['category_id']) && $filters['category_id'] == $cat->id) ? 'selected' : ''; ?>><?php echo $cat->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block text-sm">
                                <span class="text-gray-700 text-xs">Estado</span>
                                <select class="form-input form-select text-sm py-1" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendiente" <?php echo (isset($filters['status']) && $filters['status'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="aprobado" <?php echo (isset($filters['status']) && $filters['status'] == 'aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                                    <option value="pagado" <?php echo (isset($filters['status']) && $filters['status'] == 'pagado') ? 'selected' : ''; ?>>Pagado</option>
                                    <option value="anulado" <?php echo (isset($filters['status']) && $filters['status'] == 'anulado') ? 'selected' : ''; ?>>Anulado</option>
                                </select>
                            </label>
                            <label class="block text-sm">
                                <span class="text-gray-700 text-xs">Desde</span>
                                <input class="form-input text-sm py-1" type="date" name="from"
                                       value="<?php echo isset($filters['from']) ? $filters['from'] : ''; ?>"/>
                            </label>
                            <label class="block text-sm">
                                <span class="text-gray-700 text-xs">Hasta</span>
                                <input class="form-input text-sm py-1" type="date" name="to"
                                       value="<?php echo isset($filters['to']) ? $filters['to'] : ''; ?>"/>
                            </label>
                            <button type="submit" class="px-3 py-1 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg">Filtrar</button>
                            <a href="<?php echo base_url(); ?>sisvent/admin/expenses" class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
                        </form>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($search_term)): ?>
                        <div class="mb-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
                               class="text-sm text-mam-blue-petroleo hover:underline">← Volver al listado</a>
                        </div>
                    <?php endif; ?>

                    <!-- TABLA -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="w-full overflow-x-auto overflow-y-hidden">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Codigo</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Descripcion</th>
                                        <th class="px-4 py-3">Proveedor</th>
                                        <th class="px-4 py-3">Categoria</th>
                                        <th class="px-4 py-3">Monto</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($expenses)): ?>
                                        <?php foreach($expenses as $exp): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm font-semibold">
                                                    <?php echo $exp->code; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('d/m/Y', strtotime($exp->expense_date)); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="text-sm"><?php echo mb_strimwidth($exp->description, 0, 40, '...'); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $exp->store_name; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo $exp->provider_name; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo $exp->category_name; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm font-semibold">
                                                    $<?php echo number_format($exp->amount, 2); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($exp->status) {
                                                            case 'pagado':
                                                                $statusClass = 'bg-green-100 text-green-800';
                                                                break;
                                                            case 'aprobado':
                                                                $statusClass = 'bg-blue-100 text-blue-800';
                                                                break;
                                                            case 'pendiente':
                                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                                break;
                                                            case 'anulado':
                                                                $statusClass = 'bg-red-100 text-red-800';
                                                                break;
                                                            default:
                                                                $statusClass = 'bg-gray-100 text-gray-600';
                                                                break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($exp->status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3 text-sm">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/expenses/view/<?php echo $exp->id; ?>"
                                                           class="text-mam-blue-petroleo hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                                            </svg>
                                                        </a>
                                                        <?php if($exp->status == 'pendiente'): ?>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/expenses/edit/<?php echo $exp->id; ?>"
                                                               class="text-mam-blue-petroleo hover:text-mam-blue">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                </svg>
                                                            </a>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/expenses/delete/<?php echo $exp->id; ?>"
                                                               class="text-red-500 hover:text-red-700"
                                                               onclick="showSureModal(event,this)">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="text-gray-700">
                                            <td colspan="8" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay gastos registrados
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACION -->
                        <div class="grid grid-cols-7 px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50">
                            <span class="flex items-center col-span-3">
                                <?php if($total > 0): ?>
                                    Mostrando <?php echo ((($page-1) * $limit)+1) . '-' .
                                        (($last == $page) ? $total : ((($page-1) * $limit) + $limit)) . ' de ' . $total; ?>
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
        $(document).on('click', '#btn-search-expenses', function() {
            var term = $('#expenses-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/expenses/search/' + encodeURIComponent(term);
            }
        });

        $(document).on('keypress', '#expenses-search', function(e) {
            if (e.which == 13) {
                $('#btn-search-expenses').click();
            }
        });
    </script>
</body>
</html>
