<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Centros de Costo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Centros de Costo
            </h2>

            <!-- Flash Messages -->
            <?php if($this->session->flashdata('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('success'); ?>
            </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
            <?php endif; ?>

            <!-- Add Button -->
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                <?php if(in_array($role, [1, 4])): ?>
                    <button onclick="document.getElementById('addForm').classList.toggle('hidden')"
                            class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                      <span>Agregar Centro de Costo</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Add Form (hidden by default) -->
            <div id="addForm" class="hidden bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Nuevo Centro de Costo</h3>
                <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/costcenters/save">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codigo *</label>
                            <input type="text" name="code" required placeholder="Ej: CC001"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="name" required placeholder="Nombre del centro de costo"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                            <select name="type" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccione tipo</option>
                                <option value="tienda">Tienda</option>
                                <option value="departamento">Departamento</option>
                                <option value="proyecto">Proyecto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bodega</label>
                            <select name="store_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin bodega</option>
                                <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>"><?php echo $store->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo Padre</label>
                            <select name="parent_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Ninguno (nivel raiz)</option>
                                <?php if(!empty($costCenters)): ?>
                                    <?php foreach($costCenters as $cc): ?>
                                    <option value="<?php echo $cc->id; ?>"><?php echo $cc->code . ' - ' . $cc->name; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                            <input type="text" name="description" placeholder="Descripcion opcional"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end mt-4 gap-2">
                        <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-md hover:opacity-90 focus:outline-none">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cost Centers Table -->
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Codigo</th>
                      <th class="px-4 py-3">Nombre</th>
                      <th class="px-4 py-3">Tipo</th>
                      <th class="px-4 py-3">Bodega</th>
                      <th class="px-4 py-3">Centro Padre</th>
                      <th class="px-4 py-3">Estado</th>
                      <th class="px-4 py-3"></th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($costCenters)):?>
                        <?php foreach($costCenters as $cc):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3 text-sm font-mono font-semibold">
                                <?php echo $cc->code; ?>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $cc->name; ?></p>
                                <?php if(!empty($cc->description)): ?>
                                <p class="text-xs text-gray-500"><?php echo $cc->description; ?></p>
                                <?php endif; ?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php
                                    $typeColors = array(
                                        'tienda' => 'bg-blue-100 text-blue-800',
                                        'departamento' => 'bg-purple-100 text-purple-800',
                                        'proyecto' => 'bg-orange-100 text-orange-800'
                                    );
                                    $typeColor = isset($typeColors[$cc->type]) ? $typeColors[$cc->type] : 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $typeColor; ?>">
                                    <?php echo ucfirst($cc->type); ?>
                                </span>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $cc->store_name ?: '-'; ?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $cc->parent_name ?: '-'; ?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php if(isset($cc->is_active) ? $cc->is_active : (isset($cc->status) ? $cc->status == 'activo' : false)): ?>
                                <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Activo</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">Inactivo</span>
                                <?php endif; ?>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                  <button onclick="editCostCenter(<?php echo htmlspecialchars(json_encode($cc)); ?>)" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                  </button>
                                  <a href="<?php echo base_url()?>sisvent/accounting/costcenters/delete/<?php echo $cc->id;?>" onclick="showSureModal(event,this)" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Delete">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Eliminar</span></p>
                                  </a>
                                </div>
                              </td>
                            </tr>
                        <?php endforeach;?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No hay centros de costo registrados.
                            </td>
                        </tr>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </div>

    	 		</div>
        </main>
      </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Editar Centro de Costo</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/costcenters/update" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Codigo *</label>
                        <input type="text" name="code" id="edit_code" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="type" id="edit_type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="tienda">Tienda</option>
                            <option value="departamento">Departamento</option>
                            <option value="proyecto">Proyecto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bodega</label>
                        <select name="store_id" id="edit_store_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Sin bodega</option>
                            <?php foreach($stores as $store): ?>
                            <option value="<?php echo $store->idStore; ?>"><?php echo $store->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Centro Padre</label>
                        <select name="parent_id" id="edit_parent_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Ninguno (nivel raiz)</option>
                            <?php if(!empty($costCenters)): ?>
                                <?php foreach($costCenters as $cc): ?>
                                <option value="<?php echo $cc->id; ?>"><?php echo $cc->code . ' - ' . $cc->name; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" id="edit_status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                        <input type="text" name="description" id="edit_description"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-end mt-4 gap-2">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-md hover:opacity-90 focus:outline-none">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        function editCostCenter(cc) {
            document.getElementById('edit_id').value = cc.id;
            document.getElementById('edit_code').value = cc.code;
            document.getElementById('edit_name').value = cc.name;
            document.getElementById('edit_type').value = cc.type || 'tienda';
            document.getElementById('edit_store_id').value = cc.store_id || '';
            document.getElementById('edit_parent_id').value = cc.parent_id || '';
            document.getElementById('edit_status').value = cc.status || 'activo';
            document.getElementById('edit_description').value = cc.description || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
  </body>
</html>
