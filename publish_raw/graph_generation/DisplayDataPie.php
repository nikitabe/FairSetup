<?php
include_once "get_hs_color_palette.php";
 if( isset( $_REQUEST['id' ] ) ) $company_id = (int)$_REQUEST['id'];
 ?>
<html>
  <head>
    <!--Load the AJAX API-->

		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script type="text/javascript">
				var chart;
		
			colorizeSeries = function( item, color )
			{
				item.color = color;
			}			
		
			$(function () {
				var options = {
						chart: {
							renderTo: 'pie_container',
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false
						},
						<?php echo get_hs_color_palette(); ?>,
						title: {
							text: 'Impact Breakdown'
						},
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage}%</b> ({point.y})',
							percentageDecimals: 2,
							valueDecimals: 2
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
										return '<b>' + this.point.name +'</b>: ' +
														Highcharts.numberFormat( this.percentage, 2 ) +' %' +
														' (' + Highcharts.numberFormat( this.y, 2 ) + ')' ;
									}
								}
								,point: {
									events: {
										click: function() {
										/*
											location.href = "http://www.google.com";//this.options.url;
											location.target = "_top";
											*/
										},
										mouseOver: function() {
											if( !this.selected ) this.select();
											
											this.old_color = this.color;
											colorizeSeries( this, "orange" );
											}

									}
								}
							}

						},
						series: [{
							type: 'pie',
							name: 'Impact Breakdown',
							states: {
								select: {
									color: 'orange'
								}
							},
							data: []
						}],
						credits : {
						  enabled : false
						}
					}
				
				$(document).ready(function() {
					$.ajax({
							  url: "GetData.php?c_id=<?php echo $company_id;?>&date=",
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