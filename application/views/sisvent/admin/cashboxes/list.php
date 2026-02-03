<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cajas</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Cajas</h2>

                        <!-- BÚSQUEDA -->
                        <div class="flex items-center space-x-3">
                            <div class="flex">
                                <input class="form-input-lg inline w-56 rounded-l-lg rounded-r-none"
                                       type="text" id="cashboxes-search"
                                       value="<?php echo isset($search_term) ? $search_term : ''; ?>"
                                       placeholder="Buscar Caja"/>
                                <button id="btn-search-cashboxes" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-r-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- BOTÓN AGREGAR -->
                            <?php if(in_array($role, [1])): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/add"
                                   class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white
                                          bg-mam-blue-dark border border-transparent rounded-lg
                                          hover:bg-mam-blue-dark focus:outline-none">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Agregar Caja</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- MENSAJE FLASH -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- VOLVER (si estamos en búsqueda) -->
                    <?php if(isset($search_term)): ?>
                        <div class="mb-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes"
                               class="text-sm text-mam-blue-dark hover:underline">
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
                                        <th class="px-4 py-3">Código</th>
                                        <th class="px-4 py-3">Nombre</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Saldo Actual</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($cashboxes)): ?>
                                        <?php foreach($cashboxes as $cashbox): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo $cashbox->code; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold"><?php echo $cashbox->name; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm capitalize">
                                                    <?php echo $cashbox->type; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($cashbox->status) {
                                                            case 'abierta':
                                                                $statusClass = 'bg-green-100 text-green-800';
                                                                break;
                                                            case 'cerrada':
                                                                $statusClass = 'bg-gray-100 text-gray-600';
                                                                break;
                                                            case 'arqueo':
                                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                                break;
                                                            case 'bloqueada':
                                                                $statusClass = 'bg-red-100 text-red-800';
                                                                break;
                                                            default:
                                                                $statusClass = 'bg-gray-100 text-gray-600';
                                                                break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($cashbox->status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    $<?php echo number_format($cashbox->currentBalance, 2); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3 text-sm">
                                                        <!-- Ver detalle -->
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/view/<?php echo $cashbox->idCashbox; ?>"
                                                           class="text-mam-blue-dark hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                                            </svg>
                                                        </a>
                                                        <!-- Editar -->
                                                        <?php if($cashbox->status == 'cerrada'): ?>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/edit/<?php echo $cashbox->idCashbox; ?>"
                                                               class="text-mam-blue-dark hover:text-mam-blue">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                </svg>
                                                            </a>
                                                        <?php endif; ?>
                                                        <!-- Eliminar -->
                                                        <?php if($cashbox->status == 'cerrada'): ?>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/delete/<?php echo $cashbox->idCashbox; ?>"
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
                                            <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay cajas registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
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
        // Búsqueda
        $('#btn-search-cashboxes').on('click', function() {
            var term = $('#cashboxes-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/cashboxes/search/' + encodeURIComponent(term);
            }
        });

        $('#cashboxes-search').on('keypress', function(e) {
            if (e.which == 13) {
                $('#btn-search-cashboxes').click();
            }
        });
    </script>
</body>
</html>
