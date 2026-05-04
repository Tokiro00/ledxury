<?php
$role = $this->session->userdata('user_data')['role'];
$fmt = function($n){ return number_format((float)$n, 0, ',', '.'); };
$statusBadge = function($s){
    switch($s){
        case 'pendiente':    return ['bg-yellow-100 text-yellow-800', 'Pendiente'];
        case 'aprobado':     return ['bg-blue-100 text-blue-800', 'Aprobado'];
        case 'desembolsado': return ['bg-green-100 text-green-800', 'Desembolsado'];
        case 'pagado':       return ['bg-gray-200 text-gray-700', 'Pagado'];
        case 'anulado':      return ['bg-red-100 text-red-800', 'Anulado'];
    }
    return ['bg-gray-100 text-gray-600', ucfirst($s)];
};
?>
<!DOCTYPE html>
<html lang="es">
<title>Anticipos a vendedores - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Anticipos a vendedores</h2>
                        <p class="text-xs text-gray-400">Plata entregada por adelantado que se cruza FIFO con futuras liquidaciones.</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/advances/add" class="px-4 py-2 text-xs font-bold text-white bg-mam-blue-petroleo rounded">+ Nuevo anticipo</a>
                </div>

                <!-- Saldos por vendedor (con anticipos activos) -->
                <?php if (!empty($balances)): ?>
                <div class="mb-5 p-4 bg-white rounded-lg shadow-xs">
                    <p class="text-xs text-gray-500 uppercase mb-3 font-semibold">Saldos pendientes por vendedor</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ($balances as $b): ?>
                        <a href="?employee_id=<?= urlencode($b->employee_id) ?>"
                           class="block p-3 border border-gray-200 rounded hover:bg-yellow-50 hover:border-yellow-300 transition-colors">
                            <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($b->employee_name ?: $b->employee_id) ?></p>
                            <p class="text-lg font-bold text-yellow-700">$<?= $fmt($b->total_balance) ?></p>
                            <p class="text-xxs text-gray-400"><?= (int)$b->active_count ?> anticipo<?= $b->active_count > 1 ? 's' : '' ?> activo<?= $b->active_count > 1 ? 's' : '' ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filtros -->
                <form method="GET" class="flex flex-wrap items-end gap-3 mb-4 p-3 bg-white rounded-lg shadow-xs">
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Vendedor</label>
                        <select name="employee_id" class="px-2 py-1 border rounded text-sm">
                            <option value="">Todos</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?= htmlspecialchars($v->idUser) ?>" <?= (isset($filters['employee_id']) && $filters['employee_id'] == $v->idUser) ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Estado</label>
                        <select name="status" class="px-2 py-1 border rounded text-sm">
                            <option value="">Todos</option>
                            <?php foreach (array('pendiente','aprobado','desembolsado','pagado','anulado') as $st): ?>
                                <option value="<?= $st ?>" <?= (isset($filters['status']) && $filters['status'] == $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Desde</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '') ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Hasta</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '') ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-mam-blue-petroleo rounded">Filtrar</button>
                    <a href="<?= base_url() ?>sisvent/admin/advances" class="text-xs text-gray-400 hover:text-gray-700">Limpiar</a>
                </form>

                <?php if($this->session->flashdata('error')): ?>
                <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                    <p class="text-sm text-red-700"><?= $this->session->flashdata('error') ?></p>
                </div>
                <?php endif; ?>

                <!-- Tabla -->
                <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-sm whitespace-no-wrap">
                            <thead>
                                <tr class="text-xxs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-2">Código</th>
                                    <th class="px-3 py-2">Fecha</th>
                                    <th class="px-3 py-2">Vendedor</th>
                                    <th class="px-3 py-2">Concepto</th>
                                    <th class="px-3 py-2 text-right">Monto</th>
                                    <th class="px-3 py-2 text-right">Saldo</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($advances)): ?>
                                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay anticipos.</td></tr>
                                <?php else: foreach ($advances as $a): list($cls,$lbl) = $statusBadge($a->status); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-mono"><?= $a->code ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?= date('d/m/Y', strtotime($a->created_at)) ?></td>
                                    <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($a->employee_name ?: $a->employee_id) ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($a->purpose) ?></td>
                                    <td class="px-3 py-2 text-right text-gray-700">$<?= $fmt($a->amount) ?></td>
                                    <td class="px-3 py-2 text-right font-semibold <?= $a->outstanding_balance > 0 ? 'text-yellow-700' : 'text-gray-400' ?>">$<?= $fmt($a->outstanding_balance) ?></td>
                                    <td class="px-3 py-2"><span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $cls ?>"><?= $lbl ?></span></td>
                                    <td class="px-3 py-2 text-right">
                                        <a href="<?= base_url() ?>sisvent/admin/advances/view/<?= $a->id ?>" class="text-mam-blue-petroleo hover:underline text-xs">Ver →</a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Paginación -->
                <?php if ($last > 1): ?>
                <div class="mt-4 flex items-center justify-center gap-2 text-sm">
                    <?php for ($p = 1; $p <= $last; $p++): ?>
                        <a href="?p=<?= $p ?><?= !empty($filters['employee_id']) ? '&employee_id=' . urlencode($filters['employee_id']) : '' ?><?= !empty($filters['status']) ? '&status=' . $filters['status'] : '' ?>" class="px-3 py-1 rounded <?= $p == $page ? 'bg-mam-blue-petroleo text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
