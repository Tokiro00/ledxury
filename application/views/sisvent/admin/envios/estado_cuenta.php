<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$s = $stats;
$mamPaga = isset($s->flete_mam_paga) ? (float)$s->flete_mam_paga : 0;
$contrapagoCobrado = isset($s->contrapago_cobrado) ? (float)$s->contrapago_cobrado : 0;
$fleteContrapago = isset($s->flete_contrapago) ? (float)$s->flete_contrapago : 0;
$interPagaMAM = $contrapagoCobrado - $fleteContrapago; // Neto que Inter devuelve a MAM
$totalFletes = isset($s->total_fletes) ? (float)$s->total_fletes : 0;
$balance = $interPagaMAM - $mamPaga; // Positivo = Inter debe a MAM, Negativo = MAM debe a Inter
?>
<!DOCTYPE html>
<html lang="en">
    <title>Estado de Cuenta - Interrapidisimo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Estado de Cuenta - Interrapidisimo</h2>
                            <p class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($from)) ?> al <?= date('d/m/Y', strtotime($to)) ?></p>
                        </div>
                        <div class="flex items-center gap-3 mt-2 lg:mt-0">
                            <button onclick="syncEstados()" id="btnSync" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Sincronizar
                            </button>
                            <button onclick="$('#importModal').toggleClass('hidden')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#FF6B00;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Importar Guias
                            </button>
                            <a href="<?= base_url() ?>sisvent/admin/envios" class="text-sm text-blue-600 hover:underline">&larr; Dashboard</a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="get" action="<?= base_url() ?>sisvent/admin/envios/estadoCuenta" class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Bodega</label>
                                <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="">Todas</option>
                                    <?php if(!empty($stores)): foreach($stores as $st): ?>
                                    <option value="<?= $st->idStore ?>" <?= ($selectedStore == $st->idStore) ? 'selected' : '' ?>><?= $st->name ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Desde</label>
                                <input type="date" name="from" value="<?= $from ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Hasta</label>
                                <input type="date" name="to" value="<?= $to ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Tipo</label>
                                <select name="tipo" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="all" <?= $selectedTipo == 'all' ? 'selected' : '' ?>>Todos</option>
                                    <option value="mam" <?= $selectedTipo == 'mam' ? 'selected' : '' ?>>MAM paga</option>
                                    <option value="contrapago" <?= $selectedTipo == 'contrapago' ? 'selected' : '' ?>>Contrapago</option>
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Consultar</button>
                        </div>
                    </form>

                    <!-- Balance Principal -->
                    <div class="bg-white rounded-lg shadow-sm border p-5 mb-4">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- MAM paga a Inter -->
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">MAM paga a Inter</p>
                                <p class="text-xs text-gray-400">Fletes de envios gratis</p>
                                <p class="text-2xl font-bold text-red-600 mt-2">$<?= number_format($mamPaga, 0, ',', '.') ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= isset($s->guias_mam_paga) ? $s->guias_mam_paga : 0 ?> guias</p>
                            </div>
                            <!-- Inter paga a MAM -->
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Inter paga a MAM</p>
                                <p class="text-xs text-gray-400">Contrapagos cobrados - flete</p>
                                <p class="text-2xl font-bold text-green-600 mt-2">$<?= number_format($interPagaMAM, 0, ',', '.') ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= isset($s->guias_contrapago) ? $s->guias_contrapago : 0 ?> guias | Cobrado: $<?= number_format($contrapagoCobrado, 0, ',', '.') ?> - Flete: $<?= number_format($fleteContrapago, 0, ',', '.') ?></p>
                            </div>
                            <!-- Balance Neto -->
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Balance Neto</p>
                                <p class="text-xs text-gray-400"><?= $balance >= 0 ? 'Inter debe a MAM' : 'MAM debe a Inter' ?></p>
                                <p class="text-2xl font-bold mt-2 <?= $balance >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    $<?= number_format(abs($balance), 0, ',', '.') ?>
                                </p>
                                <p class="text-xs mt-1 <?= $balance >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                                    <?= $balance >= 0 ? 'A favor de MAM' : 'A favor de Inter' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose por estado -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Fletes</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($totalFletes, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= isset($s->total_guias) ? $s->total_guias : 0 ?> guias</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Fletes Entregados</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format(isset($s->flete_entregados) ? $s->flete_entregados : 0, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Fletes En Curso</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format(isset($s->flete_en_curso) ? $s->flete_en_curso : 0, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Fletes Anulados</p>
                            <p class="text-lg font-bold text-gray-400 mt-1">$<?= number_format(isset($s->flete_anulados) ? $s->flete_anulados : 0, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Detail Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">Guia</th>
                                        <th class="px-3 py-2.5 font-semibold">Factura</th>
                                        <th class="px-3 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Destino</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Cajas</th>
                                        <th class="px-3 py-2.5 font-semibold">Tipo</th>
                                        <th class="px-3 py-2.5 font-semibold">Estado Inter</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Flete</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Contrapago</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Neto MAM</th>
                                        <th class="px-3 py-2.5 font-semibold">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($guias)): ?>
                                        <?php $i=0; foreach($guias as $g): $i++;
                                            $esCp = (int)$g->isContrapago;
                                            $flete = (float)$g->valorTotal;
                                            $cp = (float)$g->contrapagoCost;
                                            // Neto: si MAM paga = -flete. Si contrapago = contrapago - flete
                                            $neto = $esCp ? ($cp - $flete) : -$flete;
                                            $statusLabel = str_replace('_', ' ', ucfirst($g->status));
                                            switch($g->status) {
                                                case 'creado':               $badgeClass = 'bg-gray-100 text-gray-700'; break;
                                                case 'en_transito':          $badgeClass = 'bg-blue-100 text-blue-800'; break;
                                                case 'entregado':            $badgeClass = 'bg-green-100 text-green-800'; break;
                                                case 'novedad':              $badgeClass = 'bg-red-100 text-red-800'; break;
                                                case 'anulado':              $badgeClass = 'bg-gray-200 text-gray-500'; break;
                                                default:                     $badgeClass = 'bg-gray-100 text-gray-600';
                                            }
                                        ?>
                                        <?php $piezas = isset($g->numeroPiezas) ? (int)$g->numeroPiezas : 1; ?>
                                        <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 cursor-pointer edit-row" data-id="<?= $g->id ?>" data-flete="<?= (float)$g->valorTotal ?>" data-cp="<?= (int)$g->isContrapago ?>" data-cpcost="<?= (float)$g->contrapagoCost ?>" data-obs="<?= htmlspecialchars($g->observations ?: '', ENT_QUOTES) ?>" data-guia="<?= $g->numeroPreenvio ?>">
                                            <td class="px-3 py-1.5 font-mono font-medium">
                                                <?= $g->numeroPreenvio ?>
                                                <?php if($piezas > 1): ?><span class="text-gray-400">(+<?= $piezas - 1 ?>)</span><?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $g->invoiceId ?>" class="text-blue-700 hover:underline">#<?= $g->invoiceId ?></a>
                                            </td>
                                            <td class="px-3 py-1.5"><?= isset($g->client_name) ? $g->client_name : ($g->recipientName ?: '-') ?></td>
                                            <td class="px-3 py-1.5"><?= $g->ciudadDestinoNombre ?: '-' ?></td>
                                            <td class="px-3 py-1.5 text-center font-bold"><?= $piezas ?></td>
                                            <td class="px-3 py-1.5">
                                                <?php if($esCp): ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 font-bold">Contrapago</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-bold">MAM</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <span class="px-2 py-0.5 rounded-full <?= $badgeClass ?> font-bold"><?= isset($g->estadoNombre) && $g->estadoNombre ? $g->estadoNombre : $statusLabel ?></span>
                                                <?php if(isset($g->fechaEstado) && $g->fechaEstado): ?>
                                                    <span class="text-gray-400 text-xs ml-1"><?= date('d/m', strtotime($g->fechaEstado)) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5 text-right text-red-600 font-medium">$<?= number_format($flete, 0, ',', '.') ?></td>
                                            <td class="px-3 py-1.5 text-right <?= $esCp ? 'text-green-600 font-medium' : 'text-gray-300' ?>">
                                                <?= $esCp ? '$' . number_format($cp, 0, ',', '.') : '-' ?>
                                            </td>
                                            <td class="px-3 py-1.5 text-right font-bold <?= $neto >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                                <?= $neto >= 0 ? '+' : '-' ?>$<?= number_format(abs($neto), 0, ',', '.') ?>
                                            </td>
                                            <td class="px-3 py-1.5 text-gray-500"><?= date('d/m/Y', strtotime($g->created_at)) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="11" class="px-3 py-8 text-center text-gray-400">No hay guias en este periodo</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if(!empty($guias)): ?>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300 font-bold text-xs">
                                        <td colspan="7" class="px-3 py-2 text-right uppercase text-gray-500">Totales:</td>
                                        <td class="px-3 py-2 text-right text-red-700">$<?= number_format($totalFletes, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-green-700">$<?= number_format($contrapagoCobrado, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= $balance >= 0 ? 'text-green-700' : 'text-red-700' ?>">
                                            <?= $balance >= 0 ? '+' : '-' ?>$<?= number_format(abs($balance), 0, ',', '.') ?>
                                        </td>
                                        <td class="px-3 py-2"></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <!-- Modal Subir Excel -->
    <div id="excelModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="$('#excelModal').addClass('hidden')"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 z-10">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Subir Excel de Interrapidisimo</h3>
                <p class="text-xs text-gray-500 mb-4">Suba el archivo "Detallado de Envios por Cliente" que envia Interrapidisimo. Se actualizaran las guias existentes y se crearan las nuevas.</p>
                <form id="excelForm" enctype="multipart/form-data">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-green-400 transition-colors" id="dropZone">
                        <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <p class="text-sm text-gray-600 mb-1">Arrastre el archivo Excel aqui o</p>
                        <label class="inline-block px-4 py-1.5 text-sm font-medium text-white rounded-lg cursor-pointer" style="background:#1B7A2F;">
                            Seleccionar archivo
                            <input type="file" name="excel" id="excelFile" accept=".xlsx,.xls" class="hidden">
                        </label>
                        <p id="excelFileName" class="text-xs text-gray-400 mt-2"></p>
                    </div>
                </form>
                <div id="excelResult" class="hidden mt-3 p-3 rounded-lg text-sm max-h-48 overflow-y-auto"></div>
                <div class="flex gap-2 mt-4">
                    <button onclick="subirExcel()" id="btnExcel" class="flex-1 px-4 py-2 text-sm font-bold text-white rounded-lg" style="background:#1B7A2F;">Procesar Excel</button>
                    <button onclick="$('#excelModal').addClass('hidden')" class="px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 bg-white rounded-lg">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Guía -->
    <div id="editModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="$('#editModal').addClass('hidden')"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Editar Guia <span id="editGuiaNum" class="font-mono text-blue-700"></span></h3>
                <p class="text-xs text-gray-500 mb-4">Modifique los valores financieros de esta guia</p>
                <input type="hidden" id="editId">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase mb-1">Valor Flete ($)</label>
                        <input type="number" id="editFlete" step="1" min="0" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase mb-1">Tipo de Pago</label>
                        <select id="editTipo" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2" onchange="$('#editCpWrap').toggleClass('hidden', this.value == '0')">
                            <option value="0">MAM paga flete</option>
                            <option value="1">Contrapago (cliente paga)</option>
                        </select>
                    </div>
                    <div id="editCpWrap" class="hidden">
                        <label class="block text-xs text-gray-500 uppercase mb-1">Valor Contrapago ($)</label>
                        <input type="number" id="editContrapago" step="1" min="0" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase mb-1">Observaciones</label>
                        <textarea id="editObs" rows="2" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2"></textarea>
                    </div>
                </div>
                <div id="editResult" class="hidden mt-3 p-3 rounded-lg text-sm"></div>
                <div class="flex gap-2 mt-4">
                    <button onclick="guardarGuia()" id="btnEditSave" class="flex-1 px-4 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">Guardar</button>
                    <button onclick="$('#editModal').addClass('hidden')" class="px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 bg-white rounded-lg">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Guías -->
    <div id="importModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="$('#importModal').addClass('hidden')"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Importar Guias de Interrapidisimo</h3>
                <p class="text-xs text-gray-500 mb-3">Pegue los numeros de guia que desea importar. Se consultara su estado directamente con Interrapidisimo.</p>
                <textarea id="importGuias" rows="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="240049085107&#10;240049087909&#10;240049109003&#10;&#10;Un numero por linea o separados por coma"></textarea>
                <div id="importResult" class="hidden mt-2 p-3 rounded-lg text-sm"></div>
                <div class="flex gap-2 mt-3">
                    <button onclick="importarGuias()" id="btnImport" class="flex-1 px-4 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">Importar y Consultar</button>
                    <button onclick="$('#importModal').addClass('hidden')" class="px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 bg-white rounded-lg">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
    function syncEstados() {
        var btn = $('#btnSync');
        btn.prop('disabled', true).html('<svg class="w-4 h-4 mr-2 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Sincronizando...');
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        $.post('<?= base_url() ?>sisvent/admin/envios/syncEstados', {
            '<?= $this->security->get_csrf_token_name() ?>': csrf
        }, function(r) {
            btn.prop('disabled', false).html('<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Sincronizar con Inter');
            if (r.success) {
                alert('Sincronizado: ' + r.updated + ' de ' + r.total + ' guías actualizadas');
                location.reload();
            } else {
                alert(r.message || 'Error al sincronizar');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Sincronizar con Inter');
            alert('Error de conexión');
        });
    }
    // Excel upload
    $(document).on('change', '#excelFile', function() {
        var name = this.files[0] ? this.files[0].name : '';
        $('#excelFileName').text(name);
    });
    // Drag & drop
    $(document).on('dragover', '#dropZone', function(e) { e.preventDefault(); $(this).addClass('border-green-400'); });
    $(document).on('dragleave', '#dropZone', function(e) { e.preventDefault(); $(this).removeClass('border-green-400'); });
    $(document).on('drop', '#dropZone', function(e) {
        e.preventDefault(); $(this).removeClass('border-green-400');
        if (e.originalEvent.dataTransfer.files.length) {
            $('#excelFile')[0].files = e.originalEvent.dataTransfer.files;
            $('#excelFileName').text(e.originalEvent.dataTransfer.files[0].name);
        }
    });
    function subirExcel() {
        var fileInput = $('#excelFile')[0];
        if (!fileInput.files.length) { alert('Seleccione un archivo Excel'); return; }
        var formData = new FormData($('#excelForm')[0]);
        var btn = $('#btnExcel');
        btn.prop('disabled', true).text('Procesando...');
        $('#excelResult').addClass('hidden');
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/envios/importExcel',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(r) {
                btn.prop('disabled', false).text('Procesar Excel');
                if (r.error) {
                    $('#excelResult').removeClass('hidden bg-green-50 text-green-700').addClass('bg-red-50 text-red-700').html(r.error);
                    return;
                }
                var html = '<strong>' + r.updated + ' guia(s) actualizadas, ' + r.created + ' nuevas</strong>';
                if (r.skipped) html += ', ' + r.skipped + ' sin cambios';
                html += '. Total procesadas: ' + r.total;
                $('#excelResult').removeClass('hidden bg-red-50 text-red-700').addClass('bg-green-50 text-green-700').html(html);
                if (r.updated > 0 || r.created > 0) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Procesar Excel');
                $('#excelResult').removeClass('hidden').addClass('bg-red-50 text-red-700').text('Error de conexion');
            }
        });
    }
    $(document).on('click', '.edit-row', function(e) {
        if ($(e.target).closest('a').length) return;
        var r = $(this);
        $('#editId').val(r.data('id'));
        $('#editFlete').val(r.data('flete'));
        $('#editTipo').val(r.data('cp'));
        $('#editContrapago').val(r.data('cpcost'));
        $('#editObs').val(r.data('obs'));
        $('#editGuiaNum').text('#' + r.data('guia'));
        $('#editCpWrap').toggleClass('hidden', !r.data('cp'));
        $('#editResult').addClass('hidden');
        $('#editModal').removeClass('hidden');
    });
    function guardarGuia() {
        var btn = $('#btnEditSave');
        btn.prop('disabled', true).text('Guardando...');
        $('#editResult').addClass('hidden');
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        $.post('<?= base_url() ?>sisvent/admin/envios/updateFinancial', {
            '<?= $this->security->get_csrf_token_name() ?>': csrf,
            id: $('#editId').val(),
            valorTotal: $('#editFlete').val(),
            isContrapago: $('#editTipo').val(),
            contrapagoCost: $('#editContrapago').val(),
            observations: $('#editObs').val()
        }, function(r) {
            btn.prop('disabled', false).text('Guardar');
            if (r.success) {
                $('#editResult').removeClass('hidden bg-red-50 text-red-700').addClass('bg-green-50 text-green-700').text('Guardado correctamente');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                $('#editResult').removeClass('hidden bg-green-50 text-green-700').addClass('bg-red-50 text-red-700').text(r.error || r.message || 'Error al guardar');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Guardar');
            $('#editResult').removeClass('hidden').addClass('bg-red-50 text-red-700').text('Error de conexion');
        });
    }
    function importarGuias() {
        var guias = $('#importGuias').val().trim();
        if (!guias) { alert('Ingrese números de guía'); return; }
        var btn = $('#btnImport');
        btn.prop('disabled', true).text('Consultando Inter...');
        $('#importResult').addClass('hidden');
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        $.post('<?= base_url() ?>sisvent/admin/envios/agregarGuias', {
            '<?= $this->security->get_csrf_token_name() ?>': csrf,
            guias: guias
        }, function(r) {
            btn.prop('disabled', false).text('Importar y Consultar');
            if (r.error) {
                $('#importResult').removeClass('hidden bg-green-50 text-green-700').addClass('bg-red-50 text-red-700').text(r.error);
                return;
            }
            $('#importResult').removeClass('hidden bg-red-50 text-red-700').addClass('bg-green-50 text-green-700')
                .html('<strong>' + r.added + ' guía(s) importada(s)</strong>' + (r.skipped ? ', ' + r.skipped + ' ya existían' : '') + '. Total consultadas: ' + r.total);
            if (r.added > 0) {
                setTimeout(function() { location.reload(); }, 1500);
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Importar y Consultar');
            alert('Error de conexión');
        });
    }
    </script>
</body>
</html>
