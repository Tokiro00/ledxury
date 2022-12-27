<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Ventas por mes</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
                Ventas por mes
            </h2>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                
                <a href="<?php echo base_url();?>sisvent/admin/vouchers"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                  <span>Volver</span>
                </a>
                <div class="flex-1"></div>
                
            </div>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
              <label class="block mt-4 text-sm">
                <span class="text-gray-700">
                  Vendedor
                </span>
                <select id="vendor-report" class="form-input form-select">
                      <option value="-1" <?php echo set_select("vendor",-1);?>>Selecione Vendedor</option>
                  <?php foreach($vendors as $vendor):?>
                      <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser);?>><?php echo $vendor->name;?></option>
                  <?php endforeach;?>
                </select>
              </label>
              <button id="update-user-report" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50">Actualizar</button>
            </div>

            <div id="sales-report-chart">
              </div>

    	 		</div>

          <div class="w-full overflow-hidden rounded-lg shadow-xs mt-8">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <tbody id="sales-table" class="bg-white divide-y">
                    
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

    <script type="text/javascript">    


    $(function () { 

    google.charts.load('current', {packages: ['corechart', 'bar']});
    //google.charts.setOnLoadCallback(drawChart);      

      $(document).on("change","#vendor-report", function(){
          showGraphData();
      });

      $(document).on("click","#update-user-report", function(){
        showGraphData();
      });
    });

    function showGraphData()
    {
      var user = $('#vendor-report').children("option:selected").val();

        $.ajax({
                url: base_url+"sisvent/admin/reports/getUSerData",
                type:"POST",
                dataType:"html",
                data:{user: user},
                success:function(data){
                  let json = JSON.parse(data);
                    drawChart(json.chart);
                    $('#sales-table').html(json.table);
                }
            }); 
    }

    function drawChart(data) {
          var data2 = google.visualization.arrayToDataTable(data);

          var options2 = {
              chart: {
                  title: 'Reporte Ventas del Vendedor',
                  subtitle: 'Desempeño mensual'
              }
          };

          var chart2 = new google.visualization.ColumnChart(document.getElementById('sales-report-chart'));

          chart2.draw(data2, options2);
      }

  </script>
</html>