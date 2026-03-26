<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas</title>
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
                        Editar Cuenta
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/accounting/accounts/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <input class="form-input" type="hidden" name="account_id" value="<?php echo $account->id;?>" readonly/>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Grupo
                          </span>
                          <select name="group_id" class="form-input form-select">
                            <?php foreach($groups as $group): ?>
                                <option value="<?php echo $group->id?>" <?php echo set_select("group_id",$group->id,$group->id==$account->groupID);?>><?php echo $group->groupID." - ".$group->groupName;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $account->accountName;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('description')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descripción</span>
                          <input class="form-input" type="text" name="description" value="<?php echo !empty(form_error('description')) ? set_value('description') : $account->accountDescription;?>"/>
                          <?php echo form_error("description","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

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
</html>