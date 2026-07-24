<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$statusLabels = ['pendiente'=>['Pendiente','bg-yellow-100 text-yellow-700'],'aprobada'=>['Aprobada','bg-green-100 text-green-700'],'rechazada'=>['Rechazada','bg-red-100 text-red-700']];
$typeLabels = ['devolucion'=>'Devolución','garantia'=>'Garantía'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Notas Credito</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <?php if($this->session->flashdata('success_cn')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg"><?= $this->session->flashdata('success_cn') ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('error_cn')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg"><?= $this->session->flashdata('error_cn') ?></div>
                    <?php endif; ?>

                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Notas Credito</h2>
                            <p class="text-sm text-gray-500">Devoluciones y garantias</p>
                        </div>
                        <div class="flex gap-2 mt-2 lg:mt-0">
                            <a href="<?= base_url() ?>sisvent/commercial/creditnotes/create" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">+ Nueva Nota Credito</a>
                        </div>
                    </div>

                    <!-- Filtros por estado -->
                    <?php $sQs = !empty($storeId) ? '&store='.$storeId : ''; ?>
                    <div class="flex gap-2 mb-2 flex-wrap">
                        <a href="?status=all<?= $sQs ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= $status=='all'?'text-white':'text-gray-500 bg-gray-100' ?>" <?= $status=='all'?'style="background:#1B365D;"':'' ?>>Todas</a>
                        <a href="?status=pendiente<?= $sQs ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= $status=='pendiente'?'text-white bg-yellow-500':'text-yellow-600 bg-yellow-100' ?>">Pendientes <?= $pendingCount > 0 ? '('.$pendingCount.')' : '' ?></a>
                        <a href="?status=aprobada<?= $sQs ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= $status=='aprobada'?'text-white bg-green-500':'text-green-600 bg-green-100' ?>">Aprobadas</a>
                        <a href="?status=rechazada<?= $sQs ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= $status=='rechazada'?'text-white bg-red-500':'text-red-600 bg-red-100' ?>">Rechazadas</a>
                    </div>

                    <!-- Filtro por sucursal -->
                    <?php $statusQs = '?status='.$status; ?>
                    <div class="flex gap-2 mb-4 flex-wrap items-center">
                        <span class="text-xs font-semibold text-gray-500 uppercase mr-1">Sucursal:</span>
                        <a href="<?= $statusQs ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= empty($storeId)?'text-white':'text-gray-500 bg-gray-100' ?>" <?= empty($storeId)?'style="background:#4487A0;"':'' ?>>Todas</a>
                        <?php foreach ($stores as $st):
                            $active = $storeId == $st->idStore;
                        ?>
                            <a href="<?= $statusQs ?>&store=<?= $st->idStore ?>" class="px-3 py-1 text-xs font-bold rounded-lg <?= $active?'text-white':'text-gray-600 bg-gray-100' ?>" <?= $active?'style="background:#4487A0;"':'' ?>><?= htmlspecialchars($st->name) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Bodega</th>
                                        <th class="px-3 py-2.5 font-semibold">Tipo</th>
                                        <th class="px-3 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Factura</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Estado</th>
                                        <th class="px-3 py-2.5 font-semibold">Fecha</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($notes)): ?>
                                    <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No hay notas credito</td></tr>
                                    <?php else: $i=0; foreach($notes as $n): $i++;
                                        $st = isset($statusLabels[$n->status]) ? $statusLabels[$n->status] : ['?','bg-gray-100'];
                                        $tp = isset($typeLabels[$n->type]) ? $typeLabels[$n->type] : $n->type;
                                    ?>
                                    <tr class="border-t <?= $i%2==0?'bg-gray-50':'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-mono font-bold"><?= $n->id ?></td>
                                        <td class="px-3 py-1.5">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded" style="background:#dbeafe; color:#1e40af;">
                                                <?= htmlspecialchars($n->store_name ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-1.5"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $n->type=='garantia'?'bg-yellow-100 text-yellow-700':'bg-purple-100 text-purple-700' ?>"><?= $tp ?></span></td>
                                        <td class="px-3 py-1.5 font-medium"><?= $n->client_name ?></td>
                                        <td class="px-3 py-1.5 font-mono"><?= $n->invoiceId ? '#'.$n->invoiceId : '-' ?></td>
                                        <td class="px-3 py-1.5"><?= $n->vendor_name ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold">$<?= number_format($n->total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $st[1] ?>"><?= $st[0] ?></span></td>
                                        <td class="px-3 py-1.5"><?= date('d/m/Y H:i', strtotime($n->created_at)) ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <a href="<?= base_url() ?>sisvent/commercial/creditnotes/view/<?= $n->id ?>" class="text-xs font-bold underline" style="color:#1B365D;">Ver</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
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
