<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Gastos Publicidad</title>
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
                        Agregar Gastos
                    </h2>
                    
                    <form id="adv-expenses-form" action="<?php echo base_url();?>sisvent/admin/advertising/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <?php if($this->session->flashdata("success_msg")){ ?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("success_msg"); ?></p>
                           </div>
                      <?php } ?>



                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select id="expenses-vendor" name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>"><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
                          <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2 text-center mx-auto">
                              Agregar Gasto en publicidad
                          </h2>
                          <div class="w-full overflow-x-auto overflow-y-hidden">
                            <table id="myTable" class="w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3">Gasto</th>
                                  <th class="px-4 py-3">Fecha</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <!--tr class="text-gray-700">
                                  <td class="px-4 py-3 text-sm">
                                    <input class="expenseamount form-input" type="number" min="0" name="expense[]" value="0"/>
                                  </td>
                                  <td class="px-4 py-3 text-xs whitespace-normal">
                                      <input class="expensedate form-input font-bold" type="text" name="date[]" required/>
                                  </td>
                                </tr-->
                              </tbody>
                            </table>
                          </div>
                          <div class="block text-sm my-4 w-full text-center">
                            <button id="add-adv-expense" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50 mx-auto">Agregar</button>
                          </div>
                        </div>

                        
                      </div>
                      <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                    </form>
    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
  <script type="text/javascript">  

  $(window).load(function () { 
     console.log("-----LOADED-----");
      $(document).on("click","#add-adv-expense", function(){
        addAdvExpense();
      });

      $('.expensedate').datepicker({ dateFormat: 'dd-mm-yy' });


      $('body').on('focus',".expensedate", function(){
          $('.expensedate').datepicker({ dateFormat: 'dd-mm-yy' });
      });  


      $(document).on('submit','#adv-expenses-form',function(){
    //$("#adv-expenses-form").on('submit', function(e){
           //e.preventDefault();
           //console.log($("#tborders").find('tr').length);
           //console.log("----------");
           //console.log(isValidDate($(this).closest("tr").find(".expensedate").val()));
  
           var good = true;
           var isEmpty = false;
          $("#tborders > tr").each(function () {
            //console.log($(this).closest("tr").find(".expensedate").val());
            if($(this).closest("tr").find(".expensedate").val() == null || $(this).closest("tr").find(".expensedate").val() == ''){
                isEmpty = true;
                //return false;
              }else if(!isValidDate($(this).closest("tr").find(".expensedate").val())){
                good = false;

              }
          });

          if(isEmpty)
          {
            showModal("El campo de la fecha no puede estar vacío");
            return false;
          }else if(!good)
          {
            showModal("Al menos un campo de la fecha no es válido");
            return false;
          }else{
            return true;
          }
           
           return true;
      });

      
    });

    
    function addAdvExpense()
    {
       var html = "<tr class='text-gray-700'><td class='px-4 py-3 text-sm'><input class='expenseamount form-input' type='number' min='0' name='expense[]' value='0'/></td><td class='px-4 py-3 text-xs whitespace-normal'><input class='expensedate form-input font-bold' type='text' name='date[]' required/></td></tr>";
        $("#tborders").append(html);
    }

    function isValidDate(s) {
      var separators = ['\\-'];
      var bits = s.split(new RegExp(separators.join('|'), 'g'));
      var d = new Date(bits[2], bits[1] - 1, bits[0]);
      return d.getFullYear() == bits[2] && d.getMonth() + 1 == bits[1];
    } 

  </script>

</html>