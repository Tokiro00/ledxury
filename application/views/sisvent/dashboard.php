<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $thisFile = pathinfo(__FILE__, PATHINFO_FILENAME);
    $thisViewName = trim($thisFile, '.php');
    $url_params = createFullParamsLinks($page);
    $url_params2 = createFullParamsLinks($page2);
    $goal_sales = [30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 80000000, 80000000];
    $month_names = ['Enero','Febrero','Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    $graph_data_g = array();
    $arr = array();
      //array_push($arr, ["type" => 'string', "label" => 'Mes']);
      array_push($arr, 'Mes');
      //array_push($arr, ["type" => 'number', "label" => 'Ventas Objetivo']);
      array_push($arr, 'Ventas Objetivo');
      array_push($arr, 'Ventas Reales');
      array_push($graph_data_g,$arr);
    foreach ($salesByMonth as $key => $value) {
      $arr = array();
      array_push($arr, $month_names[$value->month-1]);
      array_push($arr, $goal_sales[$value->month-1]);
      array_push($arr, (int)$value->total);
      array_push($graph_data_g,$arr);
    }

    $graph_data = array();
    foreach ($salesByMonth as $key => $value) {
      array_push($graph_data, ["y" => $month_names[$value->month-1], "a" => $goal_sales[$value->month-1], "b" => (int)$value->total]);
    }
    $data = [];
      foreach($salesByMonth as $row) {
    $data[] = ['date' => $month_names[$row->month-1], 'count' =>(int)$row->total];
      }
      $chart_data = json_encode($data);

      $dataPoints = array(
  array("x"=> 10, "y"=> 41),
  array("x"=> 20, "y"=> 35, "indexLabel"=> "Lowest"),
  array("x"=> 30, "y"=> 50),
  array("x"=> 40, "y"=> 45),
  array("x"=> 50, "y"=> 52),
  array("x"=> 60, "y"=> 68),
  array("x"=> 70, "y"=> 38),
  array("x"=> 80, "y"=> 71, "indexLabel"=> "Highest"),
  array("x"=> 90, "y"=> 52),
  array("x"=> 100, "y"=> 60),
  array("x"=> 110, "y"=> 36),
  array("x"=> 120, "y"=> 49),
  array("x"=> 130, "y"=> 41)
);
?>
<!DOCTYPE html>
<html lang="en">
    <title>M.A.M. Dashboard</title>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
   <!--script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.js"></script-->
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <!--script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
   <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

   <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
    <script src="https://www.amcharts.com/lib/3/serial.js"></script>
    <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>
    <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script-->
   <style>
     .highcharts-figure,
.highcharts-data-table table {
  min-width: 310px;
  max-width: 800px;
  margin: 1em auto;
}

#sales-report-chart {
  height: 400px;
}

.highcharts-data-table table {
  font-family: Verdana, sans-serif;
  border-collapse: collapse;
  border: 1px solid #ebebeb;
  margin: 10px auto;
  text-align: center;
  width: 100%;
  max-width: 500px;
}

.highcharts-data-table caption {
  padding: 1em 0;
  font-size: 1.2em;
  color: #555;
}

.highcharts-data-table th {
  font-weight: 600;
  padding: 0.5em;
}

.highcharts-data-table td,
.highcharts-data-table th,
.highcharts-data-table caption {
  padding: 0.5em;
}

.highcharts-data-table thead tr,
.highcharts-data-table tr:nth-child(even) {
  background: #f8f8f8;
}

.highcharts-data-table tr:hover {
  background: #f1f7ff;
}
   </style>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50 dark:bg-gray-900" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Dashboard
                    </h2>
    	 		</div>
                <!-- Cards -->
                <div class="grid gap-6 px-8 mb-8 md:grid-cols-2 xl:grid-cols-4">
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <button value="<?php echo $this->session->userdata('user_data')['uname'];?>"  class="btn-view-settlement p-3 mr-4 text-green-500 bg-green-100 rounded-full">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    </button>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Tu Balance 
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        Total <?php echo ($settlement >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlement)), 2); ?>
                      </p>
                      <p class="text-xs text-gray-700">
                        IVA <?php echo ($settlementiva >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlementiva)), 2); ?>  -  Rem. <?php echo ($settlementnoiva >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlementnoiva)), 2); ?>
                      </p>
                    </div>
                  </div>
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <button value="<?php echo $this->session->userdata('user_data')['uname'];?>"  class="btn-view-unattenclients p-3 mr-4 text-orange-500 bg-orange-100 rounded-full">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
                    </button>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Total Clientes
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $numClients; ?>
                      </p>
                    </div>
                  </div>
                  
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Facturas Pagadas
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $paidInvoices; ?>
                      </p>
                    </div>
                  </div>
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <div class="p-3 mr-4 text-teal-500 bg-teal-100 rounded-full dark:text-teal-100 dark:bg-teal-500">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Facturas Pendientes
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $nonPaidInvoices; ?>
                      </p>
                    </div>
                  </div>

                  <?php if($lostInvoices > 0): ?>
                   <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <button value="<?php echo $this->session->userdata('user_data')['uname'];?>"  class="btn-view-lostinvoices p-3 mr-4 text-orange-500 bg-orange-100 rounded-full">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                    </button>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Facturas Perdidas
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $lostInvoices; ?>
                      </p>
                    </div>
                  </div>
                <?php endif; ?>
                </div>
	        </main>

          <!--div class="flex flex-wrap mb-6">
          <div class="w-full max-w-full px-3 mt-0">
          <div class="border-black/12.5 dark:bg-slate-850 dark:shadow-dark-xl shadow-xl relative z-20 flex min-w-0 flex-col break-words rounded-2xl border-0 border-solid bg-white bg-clip-border">
            <div class="flex-auto p-4">
              <div id="sales-report-chart" style="height: 500px;">
              </div>
                <!--canvas id="myChart"></canvas- ->
            </div>
          </div>
          </div>
          </div-->

          <div id="sales-report-chart">
              </div>
          <div id="chart_div"></div>

          <div id="myfirstchart" style="height: 250px;"></div>

          <div id="hero-bar" class="graph"></div>

          <div id="chartdiv" style="width: 900px; height: 800px;"></div>

          <div id="chartContainer" style="height: 370px; width: 100%;"></div>

          <div class="">
          <p class="mb-2 text-xl font-medium text-gray-600">
            Productos Agotados <?php if(!empty($noInventory)): echo $noInventory[0]->store_name; endif; ?>
          </p>
          <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Stock</th>
                      
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($noInventory)):?>
                        <?php foreach($noInventory as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                                    <img class="object-cover w-full h-full" src="<?php echo get_images_path($product->picture_url) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                    <div>
                                      <p class="font-semibold"><?php echo $product->idProduct;?></p>
                                    </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->stock;?>
                              </td>
                              
                              
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                <span class="flex items-center col-span-3">
                  <?php  $last2       = ceil( $total2 / $limit ); ?>
                  Mostrando <?php echo ((($page2-1) * $limit)+1).'-'.(($last2 == $page2) ? ($total2) : ((($page2-1) * $limit)+$limit)).' de '.($total2) ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php echo createLinks($page2, $total2, "", $limit, 2, "2") ?>
                  </nav>
                </span>
              </div>
            </div>
          </div>

          <hr class="my-8">

        <div class="">
          <p class="mb-2 text-xl font-medium text-gray-600">
            Productos con bajo inventario <?php if(!empty($lowInventory)): echo $lowInventory[0]->store_name; endif; ?>
          </p>
          <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Stock</th>
                      
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($lowInventory)):?>
                        <?php foreach($lowInventory as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                                    <img class="object-cover w-full h-full" src="<?php echo get_images_path($product->picture_url) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                    <div>
                                      <p class="font-semibold"><?php echo $product->idProduct;?></p>
                                    </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->stock;?>
                              </td>
                              
                              
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                <span class="flex items-center col-span-3">
                  <?php  $last       = ceil( $total / $limit ); ?>
                  Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total) ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php echo createLinks($page, $total, "", $limit) ?>
                  </nav>
                </span>
              </div>
            </div>
          </div>


	      </div>

    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
  <script type="text/javascript">    


    /*$('#sales-report-chart').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Yearly Website Ratio'
        },
        xAxis: {
            categories: ['2013','2014','2015','2016']
        },
        yAxis: {
            title: {
                text: 'Rate'
            }
        },
        series: [{
            name: 'Click',
            data: [20,21,15,26]
        }, {
            name: 'View',
            data: [13,14,25,21]
        }]
    });*/

    /*new Morris.Line({
  // ID of the element in which to draw the chart.
  element: 'myfirstchart',
  // Chart data records -- each entry in this array corresponds to a point on
  // the chart.
  data: [
    { year: '2008', value: 20 },
    { year: '2009', value: 10 },
    { year: '2010', value: 5 },
    { year: '2011', value: 5 },
    { year: '2012', value: 20 }
  ],
  // The name of the data record attribute that contains x-values.
  xkey: 'year',
  // A list of names of data record attributes that contain y-values.
  ykeys: ['value'],
  // Labels for the ykeys -- will be displayed when you hover over the
  // chart.
  labels: ['Value']
});*/

  /* Morris.Bar({
  element: 'myfirstchart',
  data: <?php echo json_encode($graph_data); ?>,
  xkey: 'y',
  ykeys: ['a', 'b'],
  hideHover: false,
  axes: true,
  labels: ['Ventas Objetivo', 'Ventas Reales'],
  pointSize: 0
});*/

/*  Morris.Bar({
    element: 'hero-bar',
    data: [
      {device: 'iPhone', geekbench: 136},
      {device: 'iPhone 3G', geekbench: 137},
      {device: 'iPhone 3GS', geekbench: 275},
      {device: 'iPhone 4', geekbench: 380},
      {device: 'iPhone 4S', geekbench: 655},
      {device: 'iPhone 5', geekbench: 1571}
    ],
    xkey: 'device',
    ykeys: ['geekbench'],
    labels: ['Geekbench'],
    barRatio: 0.4,
    xLabelAngle: 35,
    hideHover: 'auto'
  });
*/

 /*$(function () { 
      var chart = new CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        exportEnabled: true,
        theme: "light1", // "light1", "light2", "dark1", "dark2"
        title:{
          text: "Simple Column Chart with Index Labels"
        },
        axisY:{
          includeZero: true
        },
        data: [{
          type: "column", //change type to bar, line, area, pie, etc
          //indexLabel: "{y}", //Shows y value on all Data Points
          indexLabelFontColor: "#5A5757",
          indexLabelPlacement: "outside",   
          dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
        }]
      });
      chart.render();
    });*/

    $(function () { 
            
      /*google.load("visualization", "1.1", {packages: ['bar', 'timeline']});
            google.setOnLoadCallback(drawChart);
      });*/

    google.charts.load('current', {packages: ['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
          var data2 = google.visualization.arrayToDataTable(<?php echo json_encode($graph_data_g); ?>);

          var options2 = {
              chart: {
                  title: 'Reporte Ventas del Vendedor',
                  subtitle: 'Desempeño mensual'
              }
          };

          var chart2 = new google.visualization.ColumnChart(document.getElementById('sales-report-chart'));

          chart2.draw(data2, options2);
      }
    });
  </script>
  <script>

    /*google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(drawBarColors);

function drawBarColors() {
      var data = google.visualization.arrayToDataTable([
        ['City', '2010 Population', '2000 Population'],
        ['New York City, NY', 8175000, 8008000],
        ['Los Angeles, CA', 3792000, 3694000],
        ['Chicago, IL', 2695000, 2896000],
        ['Houston, TX', 2099000, 1953000],
        ['Philadelphia, PA', 1526000, 1517000]
      ]);

      var options = {
        title: 'Population of Largest U.S. Cities',
        chartArea: {width: '50%'},
        colors: ['#b0120a', '#ffab91'],
        hAxis: {
          title: 'Total Population',
          minValue: 0
        },
        vAxis: {
          title: 'City'
        }
      };
      var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
      chart.draw(data, options);
    }*/

          /*  try {
          var chart = AmCharts.makeChart( "chartdiv", {
          "type": "serial",
          "theme":"light",
          "dataProvider": <?php echo $chart_data; ?>,
          "valueAxes": [ {
          "gridColor": "#FFFFFF",
          "gridAlpha": 0.2,
          "dashLength": 0
          } ],
          "gridAboveGraphs": true,
          "startDuration": 1,
          "graphs": [ {
          "balloonText": "[[category]]: <b>[[value]]</b>",
          "fillAlphas": 0.8,
          "lineAlpha": 0.2,
          "type": "column",
          "valueField": "count"
          } ],
          "chartScrollbar": {
          "graph": "g1",
          "scrollbarHeight": 60,
          "backgroundAlpha": 0,
          "selectedBackgroundAlpha": 0.1,
          "selectedBackgroundColor": "#888888",
          "graphFillAlpha": 0,
          "graphLineAlpha": 0.5,
          "selectedGraphFillAlpha": 0,
          "selectedGraphLineAlpha": 1,
          "autoGridCount": true,
          "color": "#AAAAAA",
          "oppositeAxis": false
                    },
          "chartCursor": {
          "categoryBalloonEnabled": false,
          "cursorAlpha": 0,
          "zoomable": false
          },
          "categoryField": "date",
          "categoryAxis": {
          "gridPosition": "start",
          "gridAlpha": 0,
          "tickPosition": "start",
          "tickLength": 20
          },
          "export": {
          "enabled": true
          }
        } );           
            }
            catch( e ) {
              console.log( e );
            }*/
    </script>
</html>












