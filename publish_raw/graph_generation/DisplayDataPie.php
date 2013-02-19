<html>
  <head>
    <!--Load the AJAX API-->

		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script type="text/javascript">
			$(function () {
				var options = {
						chart: {
							renderTo: 'pie_container',
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false
						},
						colors: [
								'#4572A7', 
								'#AA4643', 
								'#89A54E', 
								'#80699B', 
								'#3D96AE', 
								'#DB843D', 
								'#92A8CD', 
								'#A47D7C', 
								'#B5CA92'							
								],
						title: {
							text: 'Impact Breakdown'
						},
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage}%</b>',
							percentageDecimals: 2
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									color: '#000000',
									connectorColor: '#000000',
									formatter: function() {
										return '<b>'+ this.point.name +'</b>: '+ 
														Highcharts.numberFormat( this.percentage, 2 ) +' %';
									}
								}
							}
						},
						series: [{
							type: 'pie',
							name: 'Impact Breakdown',
							data: []
						}],
						credits : {
						  enabled : false
						}
					}
				var chart;
				
				$(document).ready(function() {
					$.ajax({
							  url: "GetData.php?c_id=12&date=now",
							  dataType:"json",
							  cache: false
							  }).done( 
								function( responseText ){
									options.series[0].data = eval( responseText );
									//alert( options.series.data );
									chart = new Highcharts.Chart( options );
								});
				});
				
			});
		</script>

</head>

  <body>

	<body>
	<script src="highcharts/js/highcharts.js"></script>
	<script src="highcharts/js/modules/exporting.js"></script>

	<div id="pie_container" style="min-width: 400px; height: 400px; margin: 0 auto;"></div>

  </body>
</html>