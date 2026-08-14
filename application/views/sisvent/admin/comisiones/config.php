<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Configuración de Comisiones — Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/comisiones/config', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Configuración de comisiones</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Define quién gana qué % sobre ventas o recaudo de bots — parametrizable.</p>
                    </div>
                    <div class="flex items-center gap-3 mt-2 lg:mt-0">
                        <a href="<?= base_url() ?>sisvent/admin/comisiones" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">← Panel mensual</a>
                        <button type="button" onclick="openConfigForm()" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">+ Nueva configuración</button>
                    </div>
                </div>

                <?php if($this->session->flashdata('com_success')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg"><?= $this->session->flashdata('com_success') ?></div>
                <?php endif; ?>
                <?php if($this->session->flashdata('com_error')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg"><?= $this->session->flashdata('com_error') ?></div>
                <?php endif; ?>

                <!-- Tabla -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="background:#1B365D;color:#fff;">
                                    <th class="px-3 py-2.5 text-left font-semibold">#</th>
                                    <th class="px-3 py-2.5 text-left font-semibold">Persona</th>
                                    <th class="px-3 py-2.5 text-left font-semibold">Descripción</th>
                                    <th class="px-3 py-2.5 text-right font-semibold">%</th>
                                    <th class="px-3 py-2.5 text-center font-semibold">Base</th>
                                    <th class="px-3 py-2.5 text-center font-semibold">Aplica a</th>
                                    <th class="px-3 py-2.5 text-center font-semibold">Vigencia</th>
                                    <th class="px-3 py-2.5 text-center font-semibold">Estado</th>
                                    <th class="px-3 py-2.5 text-center font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($configs)): ?>
                                    <tr><td colspan="9" class="px-3 py-8 text-center text-gray-400">Sin configuraciones. Crea la primera con el botón verde.</td></tr>
                                <?php else: foreach ($configs as $c):
                                    $appliesLabel = $c->applies_to === 'all' ? 'Todos los bots' : 'Bot #' . $c->applies_to;
                                    $vigencia = '';
                                    if ($c->valid_from || $c->valid_to) {
                                        $vigencia = ($c->valid_from ? date('d/m/Y', strtotime($c->valid_from)) : '∞') . ' → ' . ($c->valid_to ? date('d/m/Y', strtotime($c->valid_to)) : '∞');
                                    } else {
                                        $vigencia = 'Sin límite';
                                    }
                                ?>
                                <tr class="border-t hover:bg-gray-50 <?= !$c->is_active ? 'opacity-60' : '' ?>">
                                    <td class="px-3 py-2 font-mono text-xs"><?= $c->id ?></td>
                                    <td class="px-3 py-2">
                                        <div class="font-semibold text-gray-700"><?= htmlspecialchars($c->user_name ?: $c->user_id) ?></div>
                                        <div class="text-xxs text-gray-400 font-mono"><?= htmlspecialchars($c->user_id) ?></div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($c->description ?: $c->commission_type) ?></td>
                                    <td class="px-3 py-2 text-right font-bold" style="color:#0F766E;"><?= number_format((float)$c->percentage, 2) ?>%</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $c->basis === 'recaudo' ? 'bg-yellow-100 text-yellow-800' : ($c->basis === 'ventas' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') ?>">
                                            <?= ucfirst($c->basis) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs"><?= $appliesLabel ?></td>
                                    <td class="px-3 py-2 text-center text-xs text-gray-500"><?= $vigencia ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $c->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' ?>">
                                            <?= $c->is_active ? 'Activo' : 'Pausado' ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick='editConfig(<?= json_encode($c) ?>)' class="text-xs font-bold text-blue-600 hover:underline mr-2">Editar</button>
                                        <a href="<?= base_url() ?>sisvent/admin/comisiones/configToggle/<?= $c->id ?>" class="text-xs font-bold <?= $c->is_active ? 'text-yellow-600' : 'text-green-600' ?> hover:underline" onclick="return confirm('¿<?= $c->is_active ? 'Pausar' : 'Reactivar' ?> esta configuración?')"><?= $c->is_active ? 'Pausar' : 'Activar' ?></a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal form -->
                <div id="configModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
                        <form method="POST" action="<?= base_url() ?>sisvent/admin/comisiones/configSave" class="p-5">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id" id="cfg_id" value="">
                            <h3 class="text-lg font-bold mb-4" id="cfg_title">Nueva configuración</h3>

                            <div class="space-y-3">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Usuario (idUser)</span>
                                    <input type="text" name="user_id" id="cfg_user_id" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg" placeholder="ej: 71211970">
                                    <span class="text-xxs text-gray-400">Cédula/idUser de la persona que gana esta comisión</span>
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Descripción</span>
                                    <input type="text" name="description" id="cfg_description" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg" placeholder="ej: Admin de bots">
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Tipo (legacy)</span>
                                    <select name="commission_type" id="cfg_commission_type" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg">
                                        <option value="admin_bots">Admin Bots</option>
                                        <option value="operator">Operador Bot</option>
                                        <option value="ads_manager">Coordinador Publicidad</option>
                                    </select>
                                </label>

                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block text-sm">
                                        <span class="text-gray-700 font-medium">Porcentaje</span>
                                        <input type="number" step="0.01" min="0.01" max="100" name="percentage" id="cfg_percentage" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg" placeholder="3.00">
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700 font-medium">Base</span>
                                        <select name="basis" id="cfg_basis" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg">
                                            <option value="recaudo">Recaudo (cobros)</option>
                                            <option value="ventas">Ventas (facturas)</option>
                                            <option value="margen">Margen</option>
                                        </select>
                                    </label>
                                </div>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Aplica a</span>
                                    <select name="applies_to" id="cfg_applies_to" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg">
                                        <option value="all">Todos los bots</option>
                                        <?php foreach ($bots as $b): ?>
                                            <option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?> (#<?= $b->id ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block text-sm">
                                        <span class="text-gray-700 font-medium">Válido desde</span>
                                        <input type="date" name="valid_from" id="cfg_valid_from" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg">
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700 font-medium">Válido hasta</span>
                                        <input type="date" name="valid_to" id="cfg_valid_to" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg">
                                    </label>
                                </div>

                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="is_active" id="cfg_is_active" value="1" checked class="mr-2 form-checkbox">
                                    <span class="text-gray-700 font-medium">Activo</span>
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 mt-5 pt-4 border-t">
                                <button type="button" onclick="closeConfigForm()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">Cancelar</button>
                                <button type="submit" class="px-5 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
function openConfigForm() {
    document.getElementById('cfg_title').textContent = 'Nueva configuración';
    document.getElementById('cfg_id').value = '';
    document.getElementById('cfg_user_id').value = '';
    document.getElementById('cfg_user_id').readOnly = false;
    document.getElementById('cfg_description').value = '';
    document.getElementById('cfg_commission_type').value = 'admin_bots';
    document.getElementById('cfg_percentage').value = '';
    document.getElementById('cfg_basis').value = 'recaudo';
    document.getElementById('cfg_applies_to').value = 'all';
    document.getElementById('cfg_valid_from').value = '';
    document.getElementById('cfg_valid_to').value = '';
    document.getElementById('cfg_is_active').checked = true;
    document.getElementById('configModal').classList.remove('hidden');
}
function editConfig(c) {
    document.getElementById('cfg_title').textContent = 'Editar configuración #' + c.id;
    document.getElementById('cfg_id').value = c.id;
    document.getElementById('cfg_user_id').value = c.user_id;
    document.getElementById('cfg_user_id').readOnly = true;
    document.getElementById('cfg_description').value = c.description || '';
    document.getElementById('cfg_commission_type').value = c.commission_type;
    document.getElementById('cfg_percentage').value = c.percentage;
    document.getElementById('cfg_basis').value = c.basis || 'recaudo';
    document.getElementById('cfg_applies_to').value = c.applies_to;
    document.getElementById('cfg_valid_from').value = c.valid_from || '';
    document.getElementById('cfg_valid_to').value = c.valid_to || '';
    document.getElementById('cfg_is_active').checked = !!parseInt(c.is_active);
    document.getElementById('configModal').classList.remove('hidden');
}
function closeConfigForm() { document.getElementById('configModal').classList.add('hidden'); }
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
