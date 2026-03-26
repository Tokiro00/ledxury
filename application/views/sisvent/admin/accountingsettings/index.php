<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Configuracion Contable</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Configuracion Contable</h2>
                            <p class="text-xs text-gray-400">Mapeo de cuentas PUC para automatizacion contable</p>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('success')): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $this->session->flashdata('success'); ?></div>
                    <?php endif; ?>

                    <!-- Configuraciones contables -->
                    <form action="<?php echo base_url(); ?>sisvent/admin/accountingsettings/save" method="POST">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">

                        <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs mb-6">
                            <h4 class="mb-4 font-semibold text-gray-600">Cuentas por Defecto</h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <?php if(isset($settings) && count($settings) > 0): ?>
                                <?php foreach($settings as $setting): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo isset($setting->label) ? $setting->label : (isset($setting->description) ? $setting->description : $setting->setting_key); ?></label>
                                    <select name="settings[<?php echo $setting->setting_key; ?>]" class="block w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option value="">-- Seleccione --</option>
                                        <?php if(isset($subaccounts)): ?>
                                        <?php foreach($subaccounts as $sub): ?>
                                        <option value="<?php echo $sub->id; ?>" <?php echo ($setting->subaccount_id == $sub->id) ? 'selected' : ''; ?>>
                                            <?php echo $sub->pucCode . ' - ' . $sub->accountName; ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <p class="text-sm text-gray-500 col-span-2">No hay configuraciones contables definidas. Ejecute la migracion correspondiente.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if(isset($settings) && count($settings) > 0): ?>
                        <div class="flex justify-end mb-8">
                            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-mam-green rounded-lg hover:bg-mam-green-dark">Guardar Configuracion</button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>
</body>
</html>
