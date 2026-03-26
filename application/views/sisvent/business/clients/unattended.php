<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
     $url_params = createFullParamsLinks($page);
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
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Clientes
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/business/clients/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                              <span>Agregar Cliente</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                        <?php endif; ?>
                        <div class="flex-1"></div>
                        <?php if(strpos(uri_string(), 'search') !== false): ?>
                        <a href="<?php echo base_url();?>sisvent/business/clients<?php echo $url_params ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Volver</span>
                        </a>
                        <?php endif; ?>
                        <label class="block my-4 text-sm">
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params ?>" type="text" id="clients-search" placeholder="Buscar cliente"/>
                            <button id="btn-search-client" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                              </svg>
                              <span class="inline pr-4">Buscar</span>
                            </button>
                          </div>
                        </label>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
                      <div class="grid mb-6">
  <div class="text-center">
    <b>Clientes sin atender</b><br>
  </div>
</div> 
<hr class="my-6">
   <div class="w-full overflow-x-auto">

     <table class="w-full whitespace-no-wrap">
      <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
          <th class="px-4 py-3">Id</th>
          <th class="px-4 py-3">Cliente</th>
          <th class="px-4 py-3">Tipo</th>
          <th class="px-4 py-3">Dirección</th>
          <th class="px-4 py-3">Teléfono</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Vendedor</th>
          <th class="px-4 py-3">Acciones</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y">
        <?php if(!empty($clients)):?>
            <?php foreach($clients as $client):?>
                <tr class="text-gray-700">
                  <td class="px-4 py-3 text-sm">
                    <?php echo $client->idClient;?>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center text-sm whitespace-normal">
                        <div>
                          <p class="font-semibold whitespace-normal"><?php echo $client->name;?></p>
                          <p class="text-xs text-gray-600">
                            <?php echo $client->idNum;?>
                          </p>
                        </div>
                    </div>
                  </td>
                  
                  <td class="flex items-center text-xs">
                      <?php echo $client->type;?>
                  </td>
                  <td class="px-4 py-3 text-xs whitespace-normal">
                    <p><?php echo $client->address;?></p>
                    <?php if(isset($client->zone)): ?>
                    <p><?php echo $client->zone;?></p>
                    <?php endif; ?> 
                    <p><?php echo $client->city." - ".$client->state;?></p>
                  </td>
                  <td class="flex items-center text-xs">
                    <div>
                      <p><?php echo $client->phone;?></p>
                      <p><?php echo $client->cellphone;?></p>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-xs">
                    <?php echo $client->email;?>
                  </td>
                  <td class="px-4 py-3 text-sm whitespace-normal">
                    <?php echo $client->vendor_name;?>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center space-x-4 text-sm">
                      <a href="<?php echo base_url()?>sisvent/business/clients/blacklistedunatt/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'¿Está seguro que desea poner este cliente en lista negra?')" aria-label="Blacklistedlist">
                        <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Lista Negra</span></p>
                      </a>
                    </div>
                  </td>
                </tr>
            <?php endforeach;?>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>
<hr class="my-6">
<div class="text-center">
    <b>Clientes nunca atendidos</b><br>
  </div>
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
  
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
      <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
          <th class="px-4 py-3">Id</th>
          <th class="px-4 py-3">Cliente</th>
          <th class="px-4 py-3">Dirección</th>
          <th class="px-4 py-3">Teléfono</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Acciones</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y">
        <?php if(!empty($neverclients)):?>
            <?php foreach($neverclients as $client):?>
                <tr class="text-gray-700">
                  <td class="px-4 py-3 text-sm">
                    <?php echo $client->idClient;?>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center text-sm whitespace-normal">
                        <div>
                          <p class="font-semibold whitespace-normal"><?php echo $client->name;?></p>
                          <p class="text-xs text-gray-600">
                            <?php echo $client->idNum;?>
                          </p>
                        </div>
                    </div>
                  </td>
                  
                  
                  <td class="px-4 py-3 text-xs whitespace-normal">
                    <p><?php echo $client->address;?></p>
                    <p><?php echo $client->city." - ".$client->state;?></p>
                  </td>
                  <td class="flex items-center text-xs">
                    <div>
                      <p><?php echo $client->phone;?></p>
                      <p><?php echo $client->cellphone;?></p>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-xs">
                    <?php echo $client->email;?>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex items-center space-x-4 text-sm">
                      <a href="<?php echo base_url()?>sisvent/business/clients/blacklistedunatt/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'¿Está seguro que desea poner este cliente en lista negra?')" aria-label="Blacklistedlist">
                        <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Lista Negra</span></p>
                      </a>
                    </div>
                  </td>
                </tr>
            <?php endforeach;?>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>