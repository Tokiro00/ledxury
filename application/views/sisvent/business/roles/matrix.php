<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$userRole = $this->session->userdata('user_data')['role'];

// Group colors for section headers
$groupColors = array(
    'VENTAS' => '#2E7D91',
    'COMPRAS' => '#E67E22',
    'INVENTARIO' => '#27AE60',
    'TESORERIA' => '#8E44AD',
    'CARTERA' => '#C0392B',
    'CONTABILIDAD' => '#2980B9',
    'REPORTES' => '#16A085',
    'CONFIGURACION' => '#7F8C8D',
    'HERRAMIENTAS' => '#D35400',
);
?>
<!DOCTYPE html>
<html lang="en">
    <title>Matriz de Permisos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $userRole)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <?php if($this->session->flashdata('success')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white rounded-lg" style="background:#2E7D91;"><?= $this->session->flashdata('success') ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg"><?= $this->session->flashdata('error') ?></div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Matriz de Permisos</h2>
                            <p class="text-sm text-gray-500">Configure los permisos de todos los roles en una sola vista</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/business/roles" class="px-4 py-2 text-sm text-gray-700 bg-white border rounded-lg hover:bg-gray-50">Volver a Roles</a>
                    </div>

                    <form action="<?= base_url() ?>sisvent/business/roles/matrix" method="POST">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full" style="font-size:13px;">
                                    <thead>
                                        <tr style="background:#1B365D; color:white;">
                                            <th class="px-4 py-3 text-left font-semibold sticky left-0 z-10" style="min-width:250px; background:#1B365D;">Modulo / Permiso</th>
                                            <?php foreach($roles as $role): ?>
                                            <th class="px-2 py-3 text-center font-semibold" style="min-width:110px;">
                                                <div><?= strtoupper($role->description ?: $role->name) ?></div>
                                                <?php if($role->idRoles == 1): ?>
                                                <div style="font-size:10px; opacity:0.7;">(todos)</div>
                                                <?php else: ?>
                                                <div style="font-size:10px; opacity:0.7;">ID: <?= $role->idRoles ?></div>
                                                <?php endif; ?>
                                            </th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <!-- Select all row -->
                                        <tr style="background:#E8EDF2;">
                                            <td class="px-4 py-2 font-semibold text-gray-600 sticky left-0 z-10" style="background:#E8EDF2; font-size:12px;">
                                                Seleccionar todo
                                            </td>
                                            <?php foreach($roles as $role): ?>
                                            <td class="px-2 py-2 text-center">
                                                <?php if($role->idRoles == 1): ?>
                                                <input type="checkbox" checked disabled class="w-4 h-4 rounded" style="accent-color:#2E7D91;">
                                                <?php else: ?>
                                                <input type="checkbox"
                                                       class="w-4 h-4 rounded select-all-role"
                                                       style="accent-color:#2E7D91;"
                                                       data-role="<?= $role->idRoles ?>"
                                                       title="Seleccionar/deseleccionar todo para <?= htmlspecialchars($role->description ?: $role->name) ?>">
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rowIndex = 0; ?>
                                        <?php foreach($allModuleKeys as $section => $modules): ?>
                                        <!-- Section header -->
                                        <tr>
                                            <td class="px-4 py-2 font-bold text-white uppercase sticky left-0 z-10"
                                                style="font-size:12px; letter-spacing:0.08em; background:<?= isset($groupColors[$section]) ? $groupColors[$section] : '#555' ?>;"
                                                colspan="<?= count($roles) + 1 ?>">
                                                <?= $section ?>
                                                <span style="font-size:10px; opacity:0.8; font-weight:normal; margin-left:8px;">(<?= count($modules) ?> permisos)</span>
                                            </td>
                                        </tr>
                                        <?php foreach($modules as $moduleKey => $moduleLabel): ?>
                                        <tr class="border-t hover:bg-blue-50 <?= ($rowIndex % 2 == 0) ? 'bg-white' : 'bg-gray-50' ?>">
                                            <td class="px-4 py-2 sticky left-0 z-10 <?= ($rowIndex % 2 == 0) ? 'bg-white' : 'bg-gray-50' ?>">
                                                <span class="text-gray-800"><?= $moduleLabel ?></span>
                                                <span class="text-gray-400 ml-1" style="font-size:10px;">(<?= $moduleKey ?>)</span>
                                            </td>
                                            <?php foreach($roles as $role): ?>
                                            <td class="px-2 py-2 text-center">
                                                <?php if($role->idRoles == 1): ?>
                                                <!-- Superadmin always checked -->
                                                <input type="checkbox" checked disabled class="w-4 h-4 rounded" style="accent-color:#2E7D91;">
                                                <?php else: ?>
                                                <input type="checkbox"
                                                       name="perms_<?= $role->idRoles ?>[]"
                                                       value="<?= $moduleKey ?>"
                                                       class="w-4 h-4 rounded perm-checkbox"
                                                       style="accent-color:#2E7D91;"
                                                       data-role="<?= $role->idRoles ?>"
                                                       <?= in_array($moduleKey, $permissionsByRole[$role->idRoles]) ? 'checked' : '' ?>>
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php $rowIndex++; ?>
                                        <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Summary counts -->
                        <div class="flex flex-wrap gap-3 mt-4 mb-2">
                            <?php foreach($roles as $role): ?>
                            <?php if($role->idRoles == 1) continue; ?>
                            <div class="px-3 py-1.5 bg-white border rounded-lg text-sm">
                                <span class="font-semibold text-gray-700"><?= $role->description ?: $role->name ?>:</span>
                                <span class="perm-count text-gray-500" data-role="<?= $role->idRoles ?>">
                                    <?= count($permissionsByRole[$role->idRoles]) ?>
                                </span>
                                <span class="text-gray-400">permisos</span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex items-center gap-4 mt-3">
                            <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg" style="background:#2E7D91;">
                                Guardar Permisos
                            </button>
                            <span class="text-sm text-gray-400">Los cambios aplican cuando el usuario cierre e inicie sesion nuevamente.</span>
                        </div>
                    </form>

                </div>
            </main>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Select all checkbox per role column
        $(document).on('change', '.select-all-role', function() {
            var roleId = $(this).data('role');
            var isChecked = $(this).is(':checked');
            $('.perm-checkbox[data-role="' + roleId + '"]').prop('checked', isChecked);
            updateCount(roleId);
        });

        // Update select-all state when individual checkboxes change
        $(document).on('change', '.perm-checkbox', function() {
            var roleId = $(this).data('role');
            var total = $('.perm-checkbox[data-role="' + roleId + '"]').length;
            var checked = $('.perm-checkbox[data-role="' + roleId + '"]:checked').length;
            $('.select-all-role[data-role="' + roleId + '"]').prop('checked', checked === total);
            $('.select-all-role[data-role="' + roleId + '"]').prop('indeterminate', checked > 0 && checked < total);
            updateCount(roleId);
        });

        // Initialize select-all state on page load
        $('.select-all-role').each(function() {
            var roleId = $(this).data('role');
            var total = $('.perm-checkbox[data-role="' + roleId + '"]').length;
            var checked = $('.perm-checkbox[data-role="' + roleId + '"]:checked').length;
            $(this).prop('checked', checked === total);
            $(this).prop('indeterminate', checked > 0 && checked < total);
        });

        function updateCount(roleId) {
            var checked = $('.perm-checkbox[data-role="' + roleId + '"]:checked').length;
            $('.perm-count[data-role="' + roleId + '"]').text(checked);
        }
    });
    </script>
</body>
</html>
