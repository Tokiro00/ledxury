<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Reporte diario</title>
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
                Ventas diario
            </h2>
            
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
              <!--label class="block mt-4 text-sm">
                <span class="text-gray-700">
                  Vendedor
                </span>
                <select id="vendor-report" class="form-input form-select">
                      <option value="-1" <?php echo set_select("vendor-report",-1);?>>Selecione Vendedor</option>
                  <?php foreach($vendors as $vendor):?>
                      <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor-report",$vendor->idUser);?>><?php echo $vendor->name;?></option>
                  <?php endforeach;?>
                </select>
              </label-->
                
              <label class="block text-sm mt-4">
                <span class="text-gray-700">Desde:</span>
                <input id="datepicker" class="form-input" type="text" name="from" value="" required />
              </label>
              <label class="block text-sm mt-4">
                <span class="text-gray-700">Hasta:</span>
                <input id="datepicker2" class="form-input" type="text" name="until" value="" />
              </label>

             

              <button id="update-daily-report" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50">Actualizar</button>
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

          <div class="px-6 mx-auto grid mt-4">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Presupuestos por Vendedor y Ciudad
            </h2>
            <div id="budgets-vendors-reports-charts">
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
    google.charts.setOnLoadCallback(drawInitCharts);      

      /*$(document).on("change","#vendor-report", function(){
          showGraphData();
      });

      $(document).on("change","#year-report", function(){
          showGraphData();
      });*/

      $(document).on("click","#update-daily-report", function(){
        showGraphData();
      });
    });

    function showGraphData()
    {
      var since = $('#datepicker').val();
      var until = $('#datepicker2').val();

        $.ajax({
                url: base_url+"sisvent/admin/reports/getDailyData",
                type:"POST",
                dataType:"html",
                data:{since: since, until: until},
                success:function(data){
                  let json = JSON.parse(data);
                  //console.log(json.lastq);
                  //console.log(json.salesByDay);
                    drawSalesChart(json.salesbystore);
                    //$('#sales-table').html(json.table);
                  //console.log(json);
                    drawBudgetsChart(json.budgetsbystore);
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

      function drawInitCharts() {
        var json = <?php echo json_encode($salesbystore); ?>;
        var json2 = <?php echo json_encode($budgetsbystore); ?>;
        //console.log(JSON.stringify(json));
        drawSalesChart(json);
        //console.log(json2);
        drawBudgetsChart(json2);
      }

       function drawSalesChart(jsongraphdata) {
        
          var graphdata = [];
          var chartdata = [];
          var graphopts = [];
          var charts = [];
          var m = 0;
          $('#sales-vendors-reports-charts').empty();
          
              //console.log(jsongraphdata);
              //console.log(jsongraphdata.length);
          for (const i in jsongraphdata) {
          ////for(let i = 0; i < jsongraphdata.length; i++) {

            $('#sales-vendors-reports-charts').append('<div id="sales-vendors-report-piechart-'+i+'" style="min-height: 500px;"></div>');

              graphdata[m] = [];
              
              //console.log(" -=> data "+i);
              //console.log(jsongraphdata[i]);
              //console.log(jsongraphdata[i].sales);

               let arrHeader = [];
              arrHeader.push('Día');
              let totalvent = 0;
              for (const day in jsongraphdata[i].sales) {
               
                //console.log(jsongraphdata[i].sales[day]);
                  let arr = [];
              //console.log(" -=> day "+day);
                  arr.push(day);
                  for (const vendor in jsongraphdata[i].vendor_ids){
                  ////for (var vendor = 0; vendor < jsongraphdata[i].vendor_ids.length; vendor++) {
              //console.log(" -=> vendor "+vendor);
                    //console.log(jsongraphdata[i].vendor_ids[vendor]);
                    if(arrHeader.indexOf(jsongraphdata[i].vendor_ids[vendor]) == -1) arrHeader.push(jsongraphdata[i].vendor_ids[vendor]);
                ////arrHeader.push(jsongraphdata[i].vendor_ids[vendor]);
                //console.log(jsongraphdata[i].sales[day][vendor])
                      let total = 0;
              if(jsongraphdata[i].sales[day][vendor] !== undefined)
              {
                ////arrHeader.push(jsongraphdata[i].sales[day][vendor].vendor_name/*jsongraphdata[i].vendor_ids[vendor]*/);
                    ////for (let j = 0; j < jsongraphdata[i].sales[day].length; j++) {
                      //console.log(jsongraphdata[i].sales[day][vendor]);
                      //console.log("total "+jsongraphdata[i].sales[day][vendor].total);
                      //console.log(vendor +"  "+jsongraphdata[i].sales[day][j].vendorId);
                      ////if(vendor == jsongraphdata[i].sales[day][vendor].vendorId)
                      {
                        ////arr.push(key(jsongraphdata[i][j]));
                        ////arr.push(parseInt(jsongraphdata[i].sales[day][j].total));
                        total = parseInt(jsongraphdata[i].sales[day][vendor].total);
                        totalvent += total;
                        ////break;
                      }
                    ////}
                    arr.push(total);
                  } else{
                    //console.log("total 0");
                    arr.push(total);
                  }

                }
                /*for (let j = 0; j < jsongraphdata[i].sales[day].length; j++) {
                  arrHeader.push(jsongraphdata[i].sales[day][j].vendorId);
                  //arr.push(key(jsongraphdata[i][j]));
                  arr.push(parseInt(jsongraphdata[i].sales[day][j].total));
                  totalvent += parseInt(jsongraphdata[i].sales[day][j].total);
                }*/
                  graphdata[m].push(arr);
              }

          
              graphdata[m].unshift(arrHeader);
              //console.log(graphdata[m]);
            chartdata[m] = google.visualization.arrayToDataTable(graphdata[m]);

            graphopts[m] = {
              title: 'Reporte Ventas por Día '+jsongraphdata[i].storename +' - Total de ventas: $'+totalvent.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
            };

            charts[m] = new google.visualization.ColumnChart(document.getElementById('sales-vendors-report-piechart-'+i));

            charts[m].draw(chartdata[m], graphopts[m]);
            m++;
          }
          
      }

      function drawBudgetsChart(jsongraphdata) {
        
          var graphdata = [];
          var chartdata = [];
          var graphopts = [];
          var charts = [];
          var m = 0;
          $('#budgets-vendors-reports-charts').empty();
          
          //console.log("full data");
          //console.log(jsongraphdata);
          //for(let i = 0; i < jsongraphdata.length; i++) {
          /*for (const i in jsongraphdata) {

            $('#budgets-vendors-reports-charts').append('<div id="budgets-vendors-report-piechart-'+jsongraphdata[i].store+'" style="min-height: 500px;"></div>');

              graphdata[i] = [];
              
              //console.log(" -=> data "+i);
              //console.log(jsongraphdata[i]);
              //console.log(jsongraphdata[i].budgetsbyday);

               let arrHeader = [];
              arrHeader.push('Día');
              let totalvent = 0;
              for (const property in jsongraphdata[i].budgetsbyday["budgets"]) {
               
                //console.log(jsongraphdata[i].budgetsbyday["budgets"][property].length);
                  let arr = [];
                  arr.push(property);
                  for (const vendor in jsongraphdata[i].vendor_ids)
                    / *for (var k = 0; k < jsongraphdata[i].vendor_ids.length; k++)* / {
                    //console.log(jsongraphdata[i].vendor_ids[vendor]);
                      if(arrHeader.indexOf(jsongraphdata[i].vendor_ids[vendor]) == -1) arrHeader.push(jsongraphdata[i].vendor_ids[vendor]);
                      let total = 0;
                    for (let j = 0; j < jsongraphdata[i].budgetsbyday["budgets"][property].length; j++) {
                      //console.log(jsongraphdata[i].budgetsbyday["budgets"][property][j]);
                      //console.log(vendor +"  "+jsongraphdata[i].budgetsbyday["budgets"][property][j].vendorId);
                      if(vendor == jsongraphdata[i].budgetsbyday["budgets"][property][j].vendorId)
                      {
                        total = parseInt(jsongraphdata[i].budgetsbyday["budgets"][property][j].total);
                        totalvent += total;
                        break;
                      }
                    }
                    arr.push(total);
                  }
                  graphdata[i].push(arr);
              }

          
              graphdata[i].unshift(arrHeader);
              //console.log("  --->  graphdata["+i+"]");
              //console.log(graphdata[i]);
              chartdata[i] = google.visualization.arrayToDataTable(graphdata[i]);

              graphopts[i] = {
                title: 'Reporte Presupuestos por Día '+jsongraphdata[i].storename +' - Total: $'+totalvent.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
              };

              charts[i] = new google.visualization.ColumnChart(document.getElementById('budgets-vendors-report-piechart-'+jsongraphdata[i].store));

              charts[i].draw(chartdata[i], graphopts[i]);

          }*/

          for (const i in jsongraphdata) {
          //for(let i = 0; i < jsongraphdata.length; i++) {

            $('#budgets-vendors-reports-charts').append('<div id="budgets-vendors-report-piechart-'+i+'" style="min-height: 500px;"></div>');

              graphdata[m] = [];
              
              //console.log(" -=> data "+i);
              //console.log(jsongraphdata[i]);
              //console.log(jsongraphdata[i].budgets);

               let arrHeader = [];
              arrHeader.push('Día');
              let totalvent = 0;
              for (const day in jsongraphdata[i].budgets) {
               
                //console.log(jsongraphdata[i].budgets[day]);
                  let arr = [];
              //console.log(" -=> day "+day);
                  arr.push(day);
                  for (const vendor in jsongraphdata[i].vendor_ids){
              //console.log(" -=> vendor "+vendor);
                    //console.log(jsongraphdata[i].vendor_ids[vendor]);
                    if(arrHeader.indexOf(jsongraphdata[i].vendor_ids[vendor]) == -1) arrHeader.push(jsongraphdata[i].vendor_ids[vendor]);
                //console.log(jsongraphdata[i].budgets[day][vendor])
                      let total = 0;
              if(jsongraphdata[i].budgets[day][vendor] !== undefined)
              {
                      //console.log("total "+jsongraphdata[i].budgets[day][vendor].total);
                      total = parseInt(jsongraphdata[i].budgets[day][vendor].total);
                        totalvent += total;
                        
                    arr.push(total);
                  } else{
                    //console.log("total 0");
                    arr.push(total);
                  }

                }
                  graphdata[m].push(arr);
              }

          
              graphdata[m].unshift(arrHeader);
              //console.log(graphdata[m]);
            chartdata[m] = google.visualization.arrayToDataTable(graphdata[m]);

            graphopts[m] = {
                title: 'Reporte Presupuestos por Día '+jsongraphdata[i].storename +' - Total: $'+totalvent.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
            };

            charts[m] = new google.visualization.ColumnChart(document.getElementById('budgets-vendors-report-piechart-'+i));

            charts[m].draw(chartdata[m], graphopts[m]);
            m++;
          }
          
      }

  </script>
</html>