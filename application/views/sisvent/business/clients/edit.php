<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page );
    $isSuperAdmin = $this->session->userdata('user_data')['uname'] == "00000";
?>
<!DOCTYPE html>
<html lang="en">
    <title>Clientes</title>
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
                        Editar Cliente
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/clients/update<?php echo $url_params; ?>" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $client->idClient;?>" readonly/>
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('client_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cédula/NIT</span>
                          <input class="form-input" type="text" name="client_id" value="<?php echo $client->idNum;?>"/>
                          <?php echo form_error("client_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('f_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id Factusol</span>
                          <input class="form-input" type="number" name="f_id" value="<?php echo !empty(form_error('f_id')) ? set_value('f_id') : $client->f_id;?>" <?php echo (in_array($role, [1])) ? '' : 'readonly' ?>/>
                          <?php echo form_error("f_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <?php if(in_array($role, [1,2])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="can_bill" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $client->can_bill ? 'checked':''; ?> />
                          <span class="ml-2">Puede facturar siendo moroso?</span>
                        </label>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="check_can_bill" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $client->check_can_bill ? 'checked':''; ?> />
                          <span class="ml-2">Dejar facturar sólo una vez</span>
                        </label>
                        <?php endif; ?>

                        <?php if(in_array($role, [1,2])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="is_new" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $client->is_new ? 'checked':''; ?> />
                          <span class="ml-2">Cliente Nuevo</span>
                        </label>
                        <?php endif; ?>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $client->name;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('commercial_name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre Comercial</span>
                          <input class="form-input" type="text" name="commercial_name" value="<?php echo !empty(form_error('commercial_name')) ? set_value('commercial_name') : $client->commercial_name;?>" required/>
                          <?php echo form_error("commercial_name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>


                        <label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Dirección</span>
                          <input class="form-input" type="text" name="address" minlength="15" value="<?php echo !empty(form_error('address')) ? set_value('address') : $client->address;?>" required/>
                          <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('zone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Zona</span>
                          <input class="form-input" type="text" name="zone" value="<?php echo !empty(form_error('zone')) ? set_value('zone') : $client->zone;?>"/>
                          <?php echo form_error("zone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>


                        <label class="block text-sm mt-4 <?php echo !empty(form_error('city')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Ciudad</span>
                          <input class="form-input" type="text" name="city" value="<?php echo !empty(form_error('city')) ? set_value('city') : $client->city;?>" required/>
                          <?php echo form_error("city","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('state')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Departamento</span>
                          <input class="form-input" type="text" name="state" value="<?php echo !empty(form_error('state')) ? set_value('state') : $client->state;?>" required/>
                          <?php echo form_error("state","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Teléfono</span>
                          <input class="form-input" type="text" name="phone" value="<?php echo !empty(form_error('phone')) ? set_value('phone') : $client->phone;?>" required/>
                          <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('cellphone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Celular</span>
                          <input class="form-input" type="text" name="cellphone" value="<?php echo !empty(form_error('cellphone')) ? set_value('cellphone') : $client->cellphone;?>"/>
                          <?php echo form_error("cellphone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Email</span>
                          <input class="form-input" type="email" value="<?php echo !empty(form_error('email')) ? set_value('email') : $client->email;?>" name="email" required/>
                          <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Tipo de Cliente
                          </span>
                          <select name="type" class="form-input form-select">
                              <option value="-" <?php echo set_select("type","-","-" == $client->type);?>>-</option>
                              <option value="A" <?php echo set_select("type","A","A" == $client->type);?>>A</option>
                              <option value="B" <?php echo set_select("type","B","B" == $client->type);?>>B</option>
                              <option value="C" <?php echo set_select("type","C","C" == $client->type);?>>C</option>
                              <option value="D" <?php echo set_select("type","D","D" == $client->type);?>>D</option>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select name="vendor" class="form-input form-select">
                            <?php if(form_error("vendor")!=false || set_value("vendor") != false): ?>
                                <?php foreach ($vendors as $vendor) : ?>
                                    <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$client->vendor);?> ><?php echo $vendor->name;?></option>
                                <?php endforeach;?>
                            <?php else: ?>
                                <?php foreach ($vendors as $vendor) : ?>
                                    <option value="<?php echo $vendor->idUser;?>" <?php echo $vendor->idUser == $client->vendor ? 'selected':'';?>><?php echo $vendor->name; ?></option>
                                <?php endforeach;?>
                            <?php endif;?>    
                          </select>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('maximum_debt')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Deuda máxima</span>
                          <input class="form-input" type="number" name="maximum_debt" value="<?php echo !empty(form_error('maximum_debt')) ? set_value('maximum_debt') : $client->maximum_debt;?>"/>
                          <?php echo form_error("maximum_debt","<span class='text-xs text-red-600'>","</span>");?>
                        </label>
                        <?php endif; ?>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="retail" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $client->retail ? 'checked':''; ?> />
                          <span class="ml-2">Cliente al Detal</span>
                        </label>

                         <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="blacklisted" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $client->blacklisted ? 'checked':''; ?> />
                          <span class="ml-2">En lista negra</span>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Tarifa
                          </span>
                          <select name="rate" class="form-input form-select">
                              <option value="1" <?php echo set_select("rate",1,1 == $client->rate);?>>Precio</option>
                              <option value="2" <?php echo set_select("rate",2,2 == $client->rate);?>>Precio Base</option>
                              <option value="3" <?php echo set_select("rate",3,3 == $client->rate);?>>Precio Escala</option>
                              <option value="4" <?php echo set_select("rate",4,4 == $client->rate);?>>Precio Distribución</option>
                          </select>
                        </label>

                        <?php 
                    if(!empty($client->docs_url)):

                      $map = directory_map('./public/dist/images/' .($client->docs_url).'/');
                    ?>

                    <hr class="my-6">
              <div class="w-full overflow-hidden rounded-lg shadow-xs">
                  <div class="w-full overflow-x-auto">
                    <table class="w-full whitespace-no-wrap">
                      <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                          <th class="px-4 py-3 hidden lg:table-cell">Archivo</th>
                            <th class="px-4 py-3 hidden lg:table-cell"></th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y">
                       <?php if(!empty($map)) foreach ($map as $k => $file): ?>
                      
                        <tr class="text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                            <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-sm">
                              <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Archivo</span>
                              <?php echo $file;?>
                            </td>
                            <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                              <a href="<?php echo get_images_path($client->docs_url).'/'.$file ?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Descargar" download>
                                <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Descargar</span></p>
                              </a>
                            </td>
                            
                        </tr>
                      <?php endforeach; ?>
                      </tbody>
                    </table>

                  </div>

                </div>

                    <?php endif; ?>

                        <div id="client-docs" class="block flex flex-col mt-4 text-sm">
                          <span class="text-gray-700">Documentos (RUT y Cédula)</span>
                            <label class="mb-6">
                              <input class="my-2" type="file" name="clientDocs[]" accept="image/jpeg, image/png,application/pdf" />
                            </label>
                            
                            <label class="mb-6">
                            <input class="my-2" type="file" name="clientDocs[]" accept="image/jpeg, image/png,application/pdf" />
                            </label>

                        </div>

                        <div class="block mt-4 text-sm">
                          <div id="btn-add-client-doc" class="flex items-center pointer justify-between w-64 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                              <span>Agregar Documento</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
  <script type="text/javascript">    


    $(function () { 

      $(document).on("click","#btn-add-client-doc", function(){
        addDocRow();
      });

      $(document).on("click",".btn-remove-client-doc", function(){
        console.log("Remove");
        console.log($(this).closest(".client-doc"));
          $(this).closest(".client-doc").remove();
      });
    });

    function addDocRow()
    {
        html = "<div class='flex flex-row my-2 items-center justify-between client-doc'><label class='mb-6'><input class='my-2' type='file' name='clientDocs[]' accept='image/jpeg, image/png,application/pdf' /></label><button type='button' class='button-main btn-remove-client-doc'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-petroleo'>Eliminar</span></p></button></div>";
       $("#client-docs").append(html);
    }
    

  </script>
</html>