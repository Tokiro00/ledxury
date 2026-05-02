<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Detalle de Gasto - <?php echo $expense->code; ?></title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Detalle de Gasto</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver al listado</a>
                    </div>

                    <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                        <!-- Estado badge -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-700"><?php echo $expense->code; ?></h3>
                            <?php
                                switch ($expense->status) {
                                    case 'pagado': $sc = 'bg-green-100 text-green-800'; break;
                                    case 'pendiente': $sc = 'bg-yellow-100 text-yellow-800'; break;
                                    case 'anulado': $sc = 'bg-red-100 text-red-800'; break;
                                    default: $sc = 'bg-gray-100 text-gray-600';
                                }
                            ?>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $sc; ?>">
                                <?php echo ucfirst($expense->status); ?>
                            </span>
                        </div>

                        <!-- Datos principales -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Descripcion</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo $expense->description; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Fecha</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo date('d/m/Y', strtotime($expense->expense_date)); ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Proveedor</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo $expense->provider_name; ?></p>
                                <?php if(isset($expense->provider_idnum) && $expense->provider_idnum): ?>
                                    <p class="text-xs text-gray-500">NIT: <?php echo $expense->provider_idnum; ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Categoria</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo $expense->category_name; ?> (<?php echo $expense->category_code; ?>)</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Bodega</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo $expense->store_name; ?></p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Monto</p>
                                    <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($expense->amount, 2); ?></p>
                                </div>
                                <?php if($expense->payment_method): ?>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Metodo de Pago</p>
                                    <p class="text-sm font-medium text-gray-700 capitalize"><?php echo $expense->payment_method; ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if($expense->voucher_reference): ?>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Referencia</p>
                                    <p class="text-sm font-medium text-gray-700"><?php echo $expense->voucher_reference; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($expense->source_type): ?>
                        <div class="mt-4 pt-4 border-t">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Pagado desde</p>
                                    <p class="text-sm font-medium text-gray-700 capitalize"><?php echo $expense->source_type; ?> #<?php echo $expense->source_id; ?></p>
                                </div>
                                <?php if($expense->cash_movement_id): ?>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Movimiento de Caja</p>
                                    <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/view/<?php echo $expense->cash_movement_id; ?>"
                                       class="text-sm text-mam-blue-petroleo hover:underline">
                                        Ver Movimiento #<?php echo $expense->cash_movement_id; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($expense->entry_id || (isset($expense->payment_entry_id) && $expense->payment_entry_id) || (isset($expense->reversal_entry_id) && $expense->reversal_entry_id)): ?>
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-500 uppercase mb-1">Asientos contables</p>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <?php if($expense->entry_id): ?>
                                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries/view/<?php echo $expense->entry_id; ?>"
                                       class="text-mam-blue-petroleo hover:underline">
                                        Causación #<?php echo $expense->entry_id; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if(isset($expense->payment_entry_id) && $expense->payment_entry_id): ?>
                                    <span class="text-gray-300">·</span>
                                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries/view/<?php echo $expense->payment_entry_id; ?>"
                                       class="text-green-700 hover:underline">
                                        Pago #<?php echo $expense->payment_entry_id; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if(isset($expense->reversal_entry_id) && $expense->reversal_entry_id): ?>
                                    <span class="text-gray-300">·</span>
                                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries/view/<?php echo $expense->reversal_entry_id; ?>"
                                       class="text-red-600 hover:underline">
                                        Reversa #<?php echo $expense->reversal_entry_id; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($expense->observations): ?>
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-500 uppercase">Observaciones</p>
                            <p class="text-sm text-gray-700"><?php echo $expense->observations; ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- E.2 — Comprobantes (factura PDF / foto) -->
                        <div class="mt-4 pt-4 border-t">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-500 uppercase">Comprobantes <?php echo !empty($attachments) ? '(' . count($attachments) . ')' : ''; ?></p>
                            </div>

                            <?php if(!empty($attachments)): ?>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                <?php foreach ($attachments as $att):
                                    $isImage = strpos($att->mime_type, 'image/') === 0;
                                    $url = base_url() . 'sisvent/admin/expenses/downloadAttachment/' . $att->id;
                                ?>
                                <div class="border rounded-lg p-2 bg-gray-50 hover:bg-gray-100 transition-colors relative group">
                                    <a href="<?php echo $url; ?>" target="_blank" class="block">
                                        <?php if ($isImage): ?>
                                            <img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars($att->original_name); ?>" class="w-full h-24 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-full h-24 flex items-center justify-center bg-red-50 rounded">
                                                <svg class="w-10 h-10 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                            </div>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-700 mt-1 truncate" title="<?php echo htmlspecialchars($att->original_name); ?>"><?php echo htmlspecialchars($att->original_name); ?></p>
                                        <p class="text-xxs text-gray-400"><?php echo round($att->size_bytes / 1024); ?> KB · <?php echo date('d/m/Y', strtotime($att->uploaded_at)); ?></p>
                                    </a>
                                    <button type="button"
                                            data-att-id="<?php echo $att->id; ?>"
                                            class="btn-delete-att absolute top-1 right-1 hidden group-hover:flex items-center justify-center w-6 h-6 bg-red-500 text-white rounded-full hover:bg-red-600 text-xs"
                                            title="Eliminar">×</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <form action="<?php echo base_url(); ?>sisvent/admin/expenses/uploadAttachment/<?php echo $expense->id; ?>"
                                  method="POST" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                <input type="file" name="attachment" accept=".pdf,image/jpeg,image/png,image/gif" required class="text-xs">
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-mam-blue-petroleo rounded">Subir</button>
                                <span class="text-xxs text-gray-400">PDF / JPG / PNG / GIF · max 5MB</span>
                            </form>
                        </div>

                        <!-- Meta info -->
                        <div class="mt-4 pt-4 border-t text-xs text-gray-400">
                            <p>Creado por: <?php echo $expense->created_by; ?> | Fecha: <?php echo $expense->created_at; ?></p>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center space-x-3 mt-4 pt-4 border-t">
                            <?php if($expense->status == 'pendiente'): ?>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expenses/edit/<?php echo $expense->id; ?>"
                                   class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg">
                                    Editar
                                </a>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expenses/delete/<?php echo $expense->id; ?>"
                                   class="px-4 py-2 text-sm font-medium text-red-600 border border-red-600 rounded-lg hover:bg-red-50"
                                   onclick="showSureModal(event,this)">
                                    Anular
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
                               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Volver</a>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
    // E.2 — Eliminar adjunto via AJAX (CSRF + soft delete)
    $(document).on('click', '.btn-delete-att', function(e){
        e.preventDefault();
        if (!confirm('¿Eliminar este comprobante? El archivo se conservará para auditoría pero no se mostrará.')) return;
        var attId = $(this).data('att-id');
        var $card = $(this).closest('.border.rounded-lg');
        $.post(
            '<?php echo base_url(); ?>sisvent/admin/expenses/deleteAttachment/' + attId,
            { '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>' },
            function(response){
                if (response && response.indexOf('error:') === 0) {
                    alert(response.substring(6));
                } else {
                    $card.fadeOut(200, function(){ $(this).remove(); });
                }
            }
        );
    });
    </script>
</body>
</html>
