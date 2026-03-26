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
              <label class="block mt-4 text-sm">
                <span class="text-gray-700">
                  Vendedor
                </span>
                <select id="vendor-report" class="form-input form-select">
                      <option value="-1" <?php echo set_select("vendor-report",-1);?>>Selecione Vendedor</option>
                  <?php foreach($vendors as $vendor):?>
                      <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor-report",$vendor->idUser);?>><?php echo $vendor->name;?></option>
                  <?php endforeach;?>
                </select>
              </label>
              <label class="block mt-4 text-sm mx-4">
                <span class="text-gray-700">
                  Año
                </span>
                <select id="year-report" class="form-input form-select">
                  <?php 
                      $from = 2021;
                      $current = date("Y");
                      for($i = $from; $i <= $current; $i++):?>
                      <option value="<?php echo $i?>" <?php echo set_select("year-report",$i,$i==$current);?>><?php echo $i;?></option>
                  <?php endfor;?>
                </select>
              </label>
              <button id="update-user-report" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo disabled:opacity-50">Actualizar</button>
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

          <div class="px-6 mx-auto grid mt-4">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Ventas por Vendedor y Ciudad
            </h2>
            <div id="sales-vendors-reports-charts">
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
    google.charts.setOnLoadCallback(drawInitPieChart);      

      $(document).on("change","#vendor-report", function(){
          showGraphData();
      });

      $(document).on("change","#year-report", function(){
          showGraphData();
      });

      $(document).on("click","#update-user-report", function(){
        showGraphData();
      });
    });

    function showGraphData()
    {
      var user = $('#vendor-report').children("option:selected").val();
      var year = $('#year-report').children("option:selected").val();
      if(user==-1) return;
        $.ajax({
                url: base_url+"sisvent/admin/reports/getUSerData",
                type:"POST",
                dataType:"html",
                data:{user: user, year: year},
                success:function(data){
                  let json = JSON.parse(data);
                    drawChart(json.chart);
                    $('#sales-table').html(json.table);
                    drawPieChart(json.salesbystore);
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

      function drawInitPieChart() {
        var json = <?php echo json_encode($salesbystore); ?>

        drawPieChart(json);
      }

       function drawPieChart(jsongraphdata) {
        
          var graphdata = [];
          var chartdata = [];
          var graphopts = [];
          var charts = [];
          $('#sales-vendors-reports-charts').empty();
          for(let i = 0; i < jsongraphdata.length; i++) {

            $('#sales-vendors-reports-charts').append('<div id="sales-vendors-report-piechart-'+jsongraphdata[i].store+'" style="min-height: 500px;"></div>');

              graphdata[i] = [];
              let arr = [];
              arr.push('Vendedor');
              arr.push('Ventas');
              graphdata[i].push(arr);
              let totalvent = 0;
              for (let j = 0; j < jsongraphdata[i].salesbyvendor.length; j++) {
                let arr = [];
                arr.push(jsongraphdata[i].salesbyvendor[j].vendor_name);
                arr.push(parseInt(jsongraphdata[i].salesbyvendor[j].total));
                totalvent += parseInt(jsongraphdata[i].salesbyvendor[j].total);
                graphdata[i].push(arr);
              }

          
            chartdata[i] = google.visualization.arrayToDataTable(graphdata[i]);

            graphopts[i] = {
              title: 'Reporte Ventas por Vendedor '+jsongraphdata[i].storename +' - Total de ventas: $'+totalvent.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
            };

            charts[i] = new google.visualization.PieChart(document.getElementById('sales-vendors-report-piechart-'+jsongraphdata[i].store));

            charts[i].draw(chartdata[i], graphopts[i]);

          }
          
      }

      /*function drawPieChart(data) {
        <?php 

          foreach ($salesbystore as $key => $report){

              $graph_data_g = array();
              $arr = array();
                array_push($arr, 'Vendedor');
                array_push($arr, 'Ventas');
                array_push($graph_data_g,$arr);
              foreach ($report['salesbyvendor'] as $key => $value) {
                $arr = array();
                array_push($arr, $value->vendor_name);
                array_push($arr, (int)$value->total);
                array_push($graph_data_g,$arr);
              }

          
            echo 'var data_'.$report['store'].' = google.visualization.arrayToDataTable('.json_encode($graph_data_g).');';

            echo "var options_".$report['store']." = {
              title: 'Reporte Ventas por Vendedor ".$report['storename']."'};";

            echo "var chart_".$report['store']." = new google.visualization.PieChart(document.getElementById('sales-vendors-report-piechart-".$report['store']."'));";

            echo "chart_".$report['store'].".draw(data_".$report['store'].", options_".$report['store'].");";
          }
          ?>
      }*/

  </script>
</html>