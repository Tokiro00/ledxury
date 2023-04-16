<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $isSuperAdmin = $this->session->userdata('user_data')['uname'] == "00000" 
    || $this->session->userdata('user_data')['uname'] == '6542543'//Alex
    || $this->session->userdata('user_data')['uname'] == '71339095';//Alex
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Editar Vendedor
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/vendors/update" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('user_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Identificación</span>
                          <input class="form-input" type="text" name="user_id" value="<?php echo $user->idUser;?>" readonly/>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('f_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id Factusol</span>
                          <input class="form-input" type="number" name="f_id" value="<?php echo !empty(form_error('f_id')) ? set_value('f_id') : $user->f_id;?>"/>
                          <?php echo form_error("f_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $user->name;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Dirección</span>
                          <input class="form-input" type="text" name="address" value="<?php echo !empty(form_error('address')) ? set_value('address') : $user->address;?>" required/>
                          <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Teléfono</span>
                          <input class="form-input" type="text" name="phone" value="<?php echo !empty(form_error('phone')) ? set_value('phone') : $user->phone;?>" required/>
                          <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Email</span>
                          <input class="form-input" type="email" value="<?php echo !empty(form_error('email')) ? set_value('email') : $user->email;?>" name="email"/>
                          <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="e_commerce" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $user->e_commerce ? 'checked':''; ?> />
                          <span class="ml-2">Venta por E-commerce</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="by_commission" type="checkbox" name="by_commission" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $user->by_commission ? 'checked':''; ?> />
                          <span class="ml-2">Solo por comisión</span>
                        </label>

                        <label id="commission_perc" class="block text-sm mt-4 <?php echo $user->by_commission ? '':'hidden';?>">
                          <span class="text-gray-700">Porcentaje de comisión</span>
                          <input class="form-input" type="number" name="commission_perc"  min="1" max="100" value="<?php echo !empty(form_error('commission_perc')) ? set_value('commission_perc') : $user->commission_perc;?>"/>
                          <?php echo form_error("commission_perc","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php if(form_error("store")!=false || set_value("store") != false): ?>
                                <?php foreach ($stores as $store) : ?>
                                    <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore,$store->idStore==2);?> ><?php echo $store->name;?></option>
                                <?php endforeach;?>
                            <?php else: ?>
                                <?php foreach ($stores as $store) :?>
                                    <option value="<?php echo $store->idStore;?>" <?php echo $store->idStore == $user->store ? 'selected':'';?>><?php echo $store->name; ?></option>
                                <?php endforeach;?>
                            <?php endif;?>    
                          </select>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('password')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Contraseña</span>
                          <input class="form-input" type="password" name="password"value="<?php echo set_value('password');?>"/>
                          <?php echo form_error("password","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('passconf')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Confirmar Contraseña</span>
                          <input class="form-input" type="password" name="passconf" value="<?php echo set_value('passconf');?>"/>
                          <?php echo form_error("passconf","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">Foto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="hidden" type="file" onchange="readURLAvatar(this);" name="imageAvatar" id="imageAvatar" accept="image/jpeg, image/png"/>
                            <input class="form-input" type="text" name="image_name" id="image_name" readonly/>
                            <input class="absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Buscar..." onclick="document.getElementById('imageAvatar').click();"/>
                          </div>
                          <span class="post-error text-xs text-red-600"></span>
                          <div class="avatar-image-preview" <?php if(empty($user->picture_url)): ?>style="display: none" <?php endif; ?>><img id="preview-avatar" src="<?php echo get_images_path($user->picture_url) ?>"></div>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>


                      </div>
                    </form>

                    <?php if(in_array($role, [1]) /*&& $isSuperAdmin*/): ?>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2 text-center mx-auto">
                          Metas de ventas
                      </h2>
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table id="myTable" class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3">Año</th>
                              <th class="px-4 py-3">Enero</th>
                              <th class="px-4 py-3">Febrero</th>
                              <th class="px-4 py-3">Marzo</th>
                              <th class="px-4 py-3">Abril</th>
                              <th class="px-4 py-3">Mayo</th>
                              <th class="px-4 py-3">Junio</th>
                              <th class="px-4 py-3">Julio</th>
                              <th class="px-4 py-3">Agosto</th>
                              <th class="px-4 py-3">Septiembre</th>
                              <th class="px-4 py-3">Octubre</th>
                              <th class="px-4 py-3">Noviembre</th>
                              <th class="px-4 py-3">Diciembre</th>
                            </tr>
                          </thead>
                          <tbody id="goals-table" class="bg-white divide-y">
                            <?php if(!empty($goals)):?>
                                <?php foreach($goals as $goal):?>
                                    <tr class="text-gray-700">
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo $goal["year"];?>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m1"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m2"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m3"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m4"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m5"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m6"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m7"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m8"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m9"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m10"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m11"])), 2);?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal["m12"])), 2);?>
                                      </td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                          </tbody>
                        </table>
                      </div>
                      
                    </div>

                    <div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
                      <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2 text-center mx-auto">
                          Agregar Meta
                      </h2>
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table id="myTable" class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3">Año</th>
                              <th class="px-4 py-3">Enero</th>
                              <th class="px-4 py-3">Febrero</th>
                              <th class="px-4 py-3">Marzo</th>
                              <th class="px-4 py-3">Abril</th>
                              <th class="px-4 py-3">Mayo</th>
                              <th class="px-4 py-3">Junio</th>
                              <th class="px-4 py-3">Julio</th>
                              <th class="px-4 py-3">Agosto</th>
                              <th class="px-4 py-3">Septiembre</th>
                              <th class="px-4 py-3">Octubre</th>
                              <th class="px-4 py-3">Noviembre</th>
                              <th class="px-4 py-3">Diciembre</th>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y">
                            <tr class="text-gray-700">
                              <input id="user_id" class="form-input" type="hidden" name="user_id" value="<?php echo $user->idUser;?>" readonly/>
                              <td class="px-4 py-3 text-sm">
                                <input id="year_goal" class="form-input" type="number" minlength="4" maxlength="4" name="year" value="<?php echo  date("Y");?>"/>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <input id="m1_goal" class="form-input" type="number" min="0" name="m1" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m2_goal" class="form-input" type="number" min="0" name="m2" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m3_goal" class="form-input" type="number" min="0" name="m3" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m4_goal" class="form-input" type="number" min="0" name="m4" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m5_goal" class="form-input" type="number" min="0" name="m5" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m6_goal" class="form-input" type="number" min="0" name="m6" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m7_goal" class="form-input" type="number" min="0" name="m7" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m8_goal" class="form-input" type="number" min="0" name="m8" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m9_goal" class="form-input" type="number" min="0" name="m9" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m10_goal" class="form-input" type="number" min="0" name="m10" value="30000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m11_goal" class="form-input" type="number" min="0" name="m11" value="60000000"/>
                              </td>
                              <td class="px-4 py-3 text-xs">
                                <input id="m12_goal" class="form-input" type="number" min="0" name="m12" value="60000000"/>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="block text-sm my-4 w-full text-center">
                        <button id="add-vendor-goal" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50 mx-auto">Agregar/Actualizar</button>
                      </div>
                    </div>
                    <?php endif; ?>
    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>

  <?php if(in_array($role, [1]) /*&& $isSuperAdmin*/): ?>
  <script type="text/javascript">    


    $(function () { 
      $(document).on("click","#add-vendor-goal", function(){
        addVendorGoal();
      });
    });

    function addVendorGoal()
    {
      var user = $('#user_id').val();
      var year = $('#year_goal').val();
      var m1 = $('#m1_goal').val();
      var m2 = $('#m2_goal').val();
      var m3 = $('#m3_goal').val();
      var m4 = $('#m4_goal').val();
      var m5 = $('#m5_goal').val();
      var m6 = $('#m6_goal').val();
      var m7 = $('#m7_goal').val();
      var m8 = $('#m8_goal').val();
      var m9 = $('#m9_goal').val();
      var m10 = $('#m10_goal').val();
      var m11 = $('#m11_goal').val();
      var m12 = $('#m12_goal').val();

        $.ajax({
                url: base_url+"sisvent/business/vendors/saveGoal",
                type:"POST",
                dataType:"html",
                data:{user: user, year: year, m1: m1, m2: m2, m3: m3, m4: m4, m5: m5, m6: m6, m7: m7, m8: m8, m9: m9, m10: m10, m11: m11, m12: m12},
                success:function(data){
                  let json = JSON.parse(data);
                    $('#goals-table').html(json.table);
                }
            }); 
    }


  </script>
  <?php endif; ?>
</html>