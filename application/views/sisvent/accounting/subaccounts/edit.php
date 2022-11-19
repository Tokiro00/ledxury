<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Subcuentas</title>
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
                        Editar Subcuenta
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/accounting/subaccounts/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <input class="form-input" type="hidden" name="subaccount_id" value="<?php echo $subaccount->id;?>" readonly/>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $subaccount->accountName;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                         <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Cuenta
                          </span>
                          <select name="account_id" class="form-input form-select">
                            <?php foreach($accounts as $account): ?>
                                <option value="<?php echo $account->id?>" <?php echo set_select("class",$account->id,$account->id==$subaccount->accountAccount);?>><?php echo $account->accountID." - ".$account->accountName;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>


                        <label class="block text-sm mt-4 <?php echo !empty(form_error('account_balance')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Balance Inicial</span>
                          <input class="form-input"  type="number" name="account_balance" min="0" step="0.01" name="account_balance" value="<?php echo !empty(form_error('account_balance')) ? set_value('account_balance') : $subaccount->accountBalance;?>" required/>
                          <?php echo form_error("account_balance","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          
                          <select name="account_side" class="form-input form-select">
                            <?php foreach($accountside as $side): ?>
                                <option value="<?php echo $side->id?>" <?php echo set_select("class",$side->id,$side->id==$subaccount->accountSide);?>><?php echo $side->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <select name="account_statement" class="form-input form-select">
                            <?php foreach($accountstatement as $statement): ?>
                                <option value="<?php echo $statement->id?>" <?php echo set_select("class",$statement->id,$statement->id==$subaccount->accountStatement);?>><?php echo $statement->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>