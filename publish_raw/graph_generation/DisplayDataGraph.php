<html>
  <head>
<?php
include_once "get_hs_color_palette.php";

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
			colorizeSeries = function( series, color )
			{
				series.color = color;
				series.graph.attr({ 
					stroke: color,
					<?php if( $graph_type == TYPE_STACKED || $graph_type == TYPE_IMPACT ){ ?>
					//	fill: color
					<?php } ?>
						
				});
				series.chart.legend.colorizeItem(series, series.visible);
			}			
		
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
					<?php echo get_hs_color_palette(); ?>,

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
						,formatter: function()
						{
							var d = new Date( this.x );
							var s = '<b>' + (d.getMonth() + 1 ) + '/' + d.getDate() + '/' + d.getFullYear() + '</b>';

							var tot = 0;
							$.each( this.points, function( i, point ){
								tot += point.y;
							});
							
							$.each( this.points, function( i, point ){
								if( point.y > 0 ){
									s += '<br/>';
									
									if( point.percentage < 10 ) s += ' ';
									s += '<b>' + Highcharts.numberFormat( tot ? 100 * point.y / tot : 0, 2 ) + '%</b>';
									s += " - ";
									s += '<span style="color:' + point.series.color + '">';
									s += point.series.name + ": ";
									s += ' (' + Highcharts.numberFormat( point.y, 2 ) + ')';
									s += '</span>';
								}
							});
							return s;
						}
						//,pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>'
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
							marker: {
								enabled: false,
								states: {
									hover:{
										radius: 2
									}
								},
								symbol: "circle"
								
							},
							shadow: false, 
							events: {
								mouseOver: function() {
									this.old_color = this.color;
									colorizeSeries( this, "orange" );
								},
								mouseOut: function() {
									colorizeSeries( this, this.old_color );
								}
							}
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