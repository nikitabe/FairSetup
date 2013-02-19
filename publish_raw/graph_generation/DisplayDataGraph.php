<html>
  <head>
<?php
 define("TYPE_LINEAR",     "1");
 define("TYPE_STACKED",    "2");
 define("TYPE_IMPACT",     "3");

 $company_id = 12;
 $date_start = '1/1/1900';  // not used yet
 $graph_type = TYPE_LINEAR;
 
 $titles = array( TYPE_LINEAR => "Impact over Time",
				  TYPE_STACKED => "Proportional Impact Over Time",	
				  TYPE_IMPACT => "Accumulated Impact over Time" );
 
 if( isset( $_REQUEST['type' ] ) ) $graph_type = (int)$_REQUEST['type'];
 if( isset( $_REQUEST['id' ] ) ) $company_id = (int)$_REQUEST['id'];
?>
  <!--Load the AJAX API-->

		<script type="text/javascript" src="jquery-1.9.0.min.js"></script>
		<script type="text/javascript">
			var chart;
			$(function () {
			  var options = {
					credits: false, 
					chart: {
						renderTo: 'graph_container'
						,zoomType: 'x'
						,spacingRight:20
						<?php 
							if( $graph_type == TYPE_STACKED || $graph_type == TYPE_IMPACT ){ 
								echo ",type:'area'";
							}
							
						?>
					},

					title: {
						text: "<?php echo $titles[$graph_type]; ?>"
					},
			
					subtitle: {
						//text: 'This is a subtitle'
					},
			
					xAxis: {
						type: 'datetime',
						labels: {
							align: 'left',
							x: 3,
							y: -3
						},
						maxZoom: 7 * 24 * 3600000
					},
			
					yAxis: [{ // left y axis
						title: {
							text: null
						},
						labels: {
							align: 'left',
							x: 3,
							y: 16,
							formatter: function() {
								return Highcharts.numberFormat(this.value, 0);
							}
						},
						showFirstLabel: false
					}, { // right y axis
						linkedTo: 0,
						gridLineWidth: 0,
						opposite: true,
						title: {
							text: null
						},
						labels: {
							align: 'right',
							x: -3,
							y: 16,
							formatter: function() {
								return Highcharts.numberFormat(this.value, 0);
							}
						},
						showFirstLabel: false
					}],
			
					legend: {
						verticalAlign: 'bottom',
						borderWidth: 0
					},
			
					tooltip: {
						  shared: true
						, crosshairs: true
					},
					
					loading: {
						style: { background: 'url(img/kitty_loader.gif) no-repeat center' }
					},
			
					plotOptions: {
						series: {
							<?php
							if( $graph_type == TYPE_STACKED ){ 
								echo "stacking: 'percent',";
							} 
							else if ( $graph_type == TYPE_IMPACT ){ 
								echo "stacking: 'normal',";
							}
							?>
							cursor: 'pointer',
							point: {
								events: {
									click: function() {
									}
								}
							},
							marker: {
								enabled: false,
								states: {
									hover: {
										enabled: true,
										radius: 5
									}
								}
							},
							shadow: false
						}
					},
			
					series: []
				};				
				
				$(document).ready(function() {
					$.ajax({
							  url: "GetData.php?c_id=<?php echo $company_id; ?>&date_start=<?php echo $date_start; ?>",
							  //url: "UpToLauren_test.json",
							  dataType:"json",
							  cache: false
							  })
							  .fail(function() { 
								 $( "#waiting" ).slideUp("fast", function(){
									$( "#error" ).slideDown( "slow" );
									});
								 
							  })
							  .done( 
								function( responseText ){
									$( "#waiting" ).slideUp("fast", function(){
	/*									
										var response = eval( responseText );
										for( s in response ){
											chart.showLoading( response[s].name );
											chart.addSeries( response[s] );
										}
										chart.hideLoading();
	*/
										options.series = eval( responseText );
										
											if( false )
												options.series = [{ 	name: 'Nikita', 
																		pointStart: Date.UTC(2010, 0, 1),
																		pointInterval: 3600 * 1000 * 24, // one hour
																		data: [1, 2, 1, 3, 4, 5, 6, 9, 1, 2, 4] },
																   { 	name: 'Rob', 
																		pointStart: Date.UTC(2010, 2, 1),
																		pointInterval: 3600 * 1000 * 24, // one hour
																		data: [6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
																			   6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
																			   6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
																			   6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
																			   6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
																			   5] }] ;
										
									chart = new Highcharts.Chart( options );
									});								
								});
				});
				
			});
		</script>

</head>

  <body>

	<body>
	<script src="highcharts/js/highcharts.js"></script>
	<script src="highcharts/js/modules/exporting.js"></script>
	<div id="waiting" >
		<p align = "center">
		<img src="img/kitty_loader.gif"/>
		</p>
	</div>

	<div id="error" style="display:none;">
		<p align = "center">
		An error occured.
		</p>
	</div>
	<div id="graph_container" style="min-width: 400px; height: 400px; margin: 0 auto;"></div>
  </body>
</html>