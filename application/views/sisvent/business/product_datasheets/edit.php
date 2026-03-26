<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Ficha Técnica</title>
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
                        Editar Ficha Técnica
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/products/updatedatasheet" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <input class="form-input" type="hidden" name="datasheet_id" value="<?php echo $datasheet->idDatasheet;?>" readonly/>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre de Ficha Técnica</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $datasheet->name;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div id="labels-elemets" class="block text-sm mt-4">
                            <?php foreach($labels as $key => $label):?>
                             <label id="label-element-<?php echo $key; ?>-o" class="block text-sm mt-4">
                                <div class="flex flex-row gap-4">
                                  <input class="form-input" type="hidden" name="label_id[]" value="<?php echo $label->idLabel;?>" readonly/>
                                  <div class="flex-1">
                                    <span class="text-gray-700">Etiqueta <?php echo $key+1;?></span>
                                    <input class="form-input" type="text" name="label[]" value="<?php echo $label->label;?>" required/>
                                  </div>
                                  <div class="flex-1">
                                    <span class="text-gray-700">Valor por defecto <?php echo $key+1;?></span>
                                    <input class="form-input" type="text" name="defval[]" value="<?php echo $label->default_value;?>"/>
                                  </div>
                                </div>
                                <?php  if($key != 0): ?>
                                 <div class="col col-lg-1">
                                     <button type="button" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue" name="button" onclick="remove_element('labels-elemets','label-element-<?php echo $key; ?>-o');"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                      </button>
                                  </div>
                                <?php endif; ?>
                              </label>
                         <?php endforeach; ?>
                          <a id="add-label-button" href="#" class="flex items-center text-center justify-between px-4 py-2 mt-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue" onclick="add_element('labels-elemets')">Agregar Etiqueta</a>
                          
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
    <script type="text/javascript">
        var el = <?php echo sizeof($labels); ?>;
        function add_element(parent_el)
        {
          el++;
          var html = '<label id="label-element-'+el+'" class="block text-sm mt-4">'
           + '  <div class="flex flex-row gap-4">'
            + '  <div class="flex-1">'
            + '  <input class="form-input" type="hidden" name="label_id[]" value="new_'+(el+1)+'" readonly/>'
            + '  <span class="text-gray-700">Etiqueta '+(el+1)+'</span>'
            + '  <input class="form-input" type="text" name="label[]" value="" required/>'
            + '  </div>'
            + '  <div class="flex-1">'
            + '  <span class="text-gray-700">Valor por defecto '+(el+1)+'</span>'
            + '  <input class="form-input" type="text" name="defval[]" value=""/>'
            + '  </div>'
            + '  </div>'
            +'   <div class="col col-lg-1">'
            +'       <button type="button" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue" name="button" onclick="remove_element(\''+parent_el+'\',\'label-element-'+el+'\');"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>'
            +'        </button>'
            +'    </div>'
            + '</label>';
          //$("#"+parent_el).append(html);
          $(html).insertBefore("#add-label-button");

        }
        function remove_element(parent_el,el)
        {
            console.log(parent_el+"  "+el);
            $("#"+el).remove();
        }
    </script>
  </body>
</html>