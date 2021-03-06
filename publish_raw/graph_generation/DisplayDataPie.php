<?php
include_once "get_hs_color_palette.php";
 if( isset( $_REQUEST['id' ] ) ) $company_id = (int)$_REQUEST['id'];
 if( isset( $_REQUEST['group_id' ] ) ) $group_id = (int)$_REQUEST['group_id'];
 if( isset( $_REQUEST['pie_contents' ] ) ) $pie_contents = $_REQUEST['pie_contents'];
 ?>
<html>
  <head>
    <!--Load the AJAX API-->

		<script type="text/javascript" src="jquery-1.9.0.min.js"></script>
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
							  url: "GetData.php?c_id=<?php echo $company_id;?><?php 
								if( isset( $group_id ) ) 
									echo "&group_id=".$group_id;
								if( isset( $pie_contents ) )
									echo "&pie_contents=".$pie_contents ?>&date=",
							  dataType:"json",
							  cache: false
							  }).done( 
								function( responseText ){
									options.series[0].data = eval( responseText );
		
									chart = new Highcharts.Chart( options );

									var c = chart.series[0].points.length;
									if( c > 0 ){
										c = Math.floor(Math.random()*c)
										chart.series[0].points[c].select();
									}
									
								});
				});
				
			});
		</script>

</head>

  <body>

	<body>
	<script src="libs/highcharts_v2/js/highcharts.js"></script>
	<script src="libs/highcharts_v2/js/modules/exporting.js"></script>
<!--	
	<script src="http://code.highcharts.com/highcharts.js"></script>
-->
	<div id="pie_container" style="min-width: 400px; height: 400px; margin: 0 auto;"></div>

  </body>
</html>