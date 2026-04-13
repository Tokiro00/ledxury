<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Categorias de Gastos</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Categorias de Gastos</h2>

                        <div class="flex items-center space-x-3">
                            <div class="flex">
                                <input class="form-input-lg inline w-56 rounded-l-lg rounded-r-none"
                                       type="text" id="expcategories-search"
                                       value="<?php echo isset($search_term) ? $search_term : ''; ?>"
                                       placeholder="Buscar categoria"/>
                                <button id="btn-search-expcategories" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-r-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <?php if(in_array($role, [1])): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories/add"
                                   class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white
                                          bg-mam-blue-petroleo border border-transparent rounded-lg
                                          hover:bg-mam-blue-petroleo focus:outline-none">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Agregar</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($search_term)): ?>
                        <div class="mb-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories"
                               class="text-sm text-mam-blue-petroleo hover:underline">
                                ← Volver al listado
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- TABLA -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="w-full overflow-x-auto overflow-y-hidden">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Codigo</th>
                                        <th class="px-4 py-3">Nombre</th>
                                        <th class="px-4 py-3">Subcuenta Contable</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($categories)): ?>
                                        <?php foreach($categories as $cat): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm font-semibold">
                                                    <?php echo $cat->code; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold"><?php echo $cat->name; ?></p>
                                                    <?php if($cat->description): ?>
                                                        <p class="text-xs text-gray-500"><?php echo $cat->description; ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php if($cat->subaccount_name): ?>
                                                        <p><?php echo $cat->subaccount_puc; ?> - <?php echo $cat->subaccount_name; ?></p>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Sin asignar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php if($cat->is_active): ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Inactiva</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3 text-sm">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories/edit/<?php echo $cat->id; ?>"
                                                           class="text-mam-blue-petroleo hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                        </a>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories/delete/<?php echo $cat->id; ?>"
                                                           class="text-red-500 hover:text-red-700"
                                                           onclick="showSureModal(event,this)">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="text-gray-700">
                                            <td colspan="5" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay categorias registradas
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
        $(document).on('click', '#btn-search-expcategories', function() {
            var term = $('#expcategories-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/expensecategories/search/' + encodeURIComponent(term);
            }
        });

        $(document).on('keypress', '#expcategories-search', function(e) {
            if (e.which == 13) {
                $('#btn-search-expcategories').click();
            }
        });
    </script>
</body>
</html>
