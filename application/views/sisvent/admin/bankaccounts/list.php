<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas Bancarias</title>
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

                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Cuentas Bancarias</h2>
                        <div class="flex items-center space-x-3">
                            <div class="flex">
                                <input class="form-input-lg inline w-56 rounded-l-lg rounded-r-none"
                                       type="text" id="banks-search"
                                       value="<?php echo isset($search_term) ? $search_term : ''; ?>"
                                       placeholder="Buscar Banco..."/>
                                <button id="btn-search-banks" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-r-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if(in_array($role, [1])): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/add"
                                   class="flex items-center px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Agregar Banco</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($search_term)): ?>
                        <div class="mb-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts"
                               class="text-sm text-mam-blue-dark hover:underline">← Volver al listado</a>
                        </div>
                    <?php endif; ?>

                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Banco</th>
                                        <th class="px-4 py-3">Cuenta</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Saldo</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($bankAccounts)): ?>
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold"><?php echo $ba->bankName; ?></p>
                                                    <?php if($ba->ownerName): ?>
                                                        <p class="text-xs text-gray-500"><?php echo $ba->ownerName; ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    ***<?php echo substr($ba->accountNumber, -4); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm capitalize"><?php echo $ba->accountType; ?></td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($ba->status) {
                                                            case 'activa':
                                                                $statusClass = 'text-green-700 bg-green-100';
                                                                break;
                                                            case 'inactiva':
                                                                $statusClass = 'text-gray-600 bg-gray-100';
                                                                break;
                                                            case 'bloqueada':
                                                                $statusClass = 'text-red-700 bg-red-100';
                                                                break;
                                                            default:
                                                                $statusClass = 'text-gray-600 bg-gray-100';
                                                                break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($ba->status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm font-semibold">
                                                    $<?php echo number_format($ba->currentBalance, 2); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-3">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $ba->idBankAccount; ?>"
                                                           class="text-mam-blue-dark hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                                            </svg>
                                                        </a>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/edit/<?php echo $ba->idBankAccount; ?>"
                                                           class="text-mam-blue-dark hover:text-mam-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                        </a>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/delete/<?php echo $ba->idBankAccount; ?>"
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
                                        <tr>
                                            <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">No hay cuentas bancarias</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
        $('#btn-search-banks').on('click', function() {
            var term = $('#banks-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/bankaccounts/search/' + encodeURIComponent(term);
            }
        });
        $('#banks-search').on('keypress', function(e) {
            if (e.which == 13) $('#btn-search-banks').click();
        });
    </script>
</body>
</html>
