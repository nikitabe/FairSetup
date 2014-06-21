<html>
  <head>
<?php
include_once "get_hs_color_palette.php";

 define("TYPE_LINEAR",     "1");
 define("TYPE_STACKED",    "2");
 define("TYPE_IMPACT",     "3");
 define("TYPE_IMPACT_HISTORY",     "4");

 $lc = array_change_key_case($_REQUEST);
 
 $company_id = 12;
 $date_start = '1/1/1900';  // not used yet
 $cur_utc_stamp = time();
 $graph_type = TYPE_LINEAR;
 $is_impact_history = false;
 
 $titles = array( TYPE_LINEAR => "Impact over Time",
				  TYPE_STACKED => "Proportional Impact Over Time",	
				  TYPE_IMPACT => "Accumulated Impact over Time",
				  TYPE_IMPACT_HISTORY => "How Impact was Calculated",
				  );
 
 if( isset( $lc['type' ] ) ) $graph_type = (int)$lc['type'];
 if( isset( $lc['id' ] ) ) $company_id = (int)$lc['id'];
 if( isset( $lc['user_id' ] ) ) $user_id = (int)$lc['user_id'];
 if( isset( $lc['group_id' ] ) ) $group_id = (int)$lc['group_id'];
 if( isset( $lc['impact_history' ] ) && $lc['impact_history' ] == "1" ) $is_impact_history = true;
 
 $is_group = !isset( $user_id );
?>
  <!--Load the AJAX API-->

		<script type="text/javascript" src="jquery-1.9.0.min.js"></script>
		<script type="text/javascript">
			colorizeSeries = function( series, color )
			{
				series.color = color;
				series.graph.attr({ 
					stroke: color,						
				});
				<?php if( $graph_type == TYPE_STACKED || $graph_type == TYPE_IMPACT ){ ?>
				/*
				// This isn't working yet
				series.update({
					color: color
				});*/
				<?php } ?>
				
				series.chart.legend.colorizeItem(series, series.visible);
			}			

			var chart;

			function init_chart( chart, options ){
				$( "#waiting" ).slideUp("fast", function(){
					chart = new Highcharts.Chart( options );
					var c = chart.series.length;
					<?php if( $is_group ){ ?>
					if( c > 0 ){
						c = Math.floor(Math.random()*c)
						chart.series[c].onMouseOver();
					}
					<?php } ?>						
				});
			}

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
						maxZoom: 7 * 24 * 3600000,
						plotLines: [{
							value: <?php echo $cur_utc_stamp * 1000 ?>,
							color: '#ff0000',
							width: 1,
							id: 'plot-line-now',
							zIndex:100,
							label: {
								text: 'Today',
								textAlign: 'left'
								
							}
						}]
												// add line of today
							/*					chart.xAxis[0].addPlotLine({
													value: <?php echo $cur_utc_stamp * 1000 ?>,
													color: '#ff0000',
													width: 1,
													id: 'plot-line-now'
												});*/
						
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
								return Highcharts.numberFormat(this.value, 2);
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
						borderWidth: 0,
						itemStyle: {
							fontWeight:"normal"
						}
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

							var sortedPoints = this.points.sort(function(a, b){
								return ((a.y < b.y) ? 1 : ((a.y > b.y) ? -1 : 0));
							});
							
							$.each( sortedPoints, function( i, point ){
								<?php if( $is_group ){ ?>
								if( point.y > 0 ){
								<?php } ?>
									s += '<br/>';
									
									<?php if( $is_group ){ ?>
									if( point.percentage < 10 ) s += ' ';
									s += '<b>' + Highcharts.numberFormat( tot ? 100 * point.y / tot : 0, 2 ) + '%</b>';
									s += " - ";
									<?php } ?>
									s += '<span style="color:' + point.series.color + '">';
									s += point.series.name + ": ";
									s += ' (' + Highcharts.numberFormat( point.y, 2 ) + ')';
									s += '</span>';
								<?php if( $is_group ){ ?>
								}
								<?php } ?>
							});
							return s;
						}

						//,pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>'
					},
					
			
					plotOptions: {
						<?php if( !$is_group ){ ?>
						line: {
							states: {
								hover: {
									lineWidth: 2,
								}
							}
						},
						<?php } ?>
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
									<?php if( $is_group ){ ?>
										radius: 2
									<?php }else{ ?>
										radius: 4
									<?php } ?>
									}
								},
								symbol: "circle"
								
								
							},
							shadow: false, 
							events: {
								<?php if( $is_group ){ ?>
								mouseOver: function() {
									this.old_color = this.color;
									colorizeSeries( this, "orange" );
								},
								mouseOut: function() {
									colorizeSeries( this, this.old_color );
								}
								<?php } ?>
							}
							<?php if( $graph_type == TYPE_STACKED || $graph_type == TYPE_IMPACT ){ ?>
								,trackByArea: 'true'
							<?php } ?>
							
						}
					},
			
					series: []
				};				
				
				$(document).ready(function() {
					var data_url = "GetData.php?c_id=<?php echo $company_id; ?>";
					<?php if( !$is_group ){ ?>
							data_url = data_url + "&u_id=<?php echo $user_id;?>";
						<?php if( $is_impact_history ){ ?>
							data_url = data_url + "&is_impact_history=1";
						<?php }?>
					<?php } ?>
					<?php if( isset( $group_id)  ){ ?>
							data_url = data_url + "&group_id=<?php echo $group_id ?>";
					<?php } ?>

										// Testing Code
					if( false ){

						/*options.series = [{ 	name: 'Impact', 
												pointStart: Date.UTC(2010, 0, 1),
												type: 'area',
												pointInterval: 3600 * 1000 * 24, // one hour
												data: [2, 1, 3, 4, 5, 6, 9, 1, 2, 4] },
										   { 	name: 'Potential Impact', 
												pointStart: Date.UTC(2010, 0, 1),
												pointInterval: 3600 * 1000 * 24, // one hour
												data: [1, 2, 1, 3, 4, 5, 6, 9, 1, 2, 4,
													   6, 9, 1, 2, 4, 1, 2, 1, 3, 4, 
													   5] }] ;*/
						responseText = "[{'name':'Nikita Bernstein','pointStart':1341100800000,'pointInterval':86400000,'data':[0,0.004,0.014,0.03,0.055,0.089,0.134,0.194,0.272,0.371,0.496,0.652,0.846,1.085,1.38,1.742,2.183,2.719,3.368,4.15,5.088,6.209,7.539,9.11,10.951,13.093,15.566,18.395,21.602,25.201,29.201,33.602,38.395,43.566,49.093,54.951,61.11,67.539,74.209,81.088,88.15,95.368,102.719,110.183,117.742,125.38,133.085,140.846,148.652,156.496,164.371,172.272,180.194,188.134,196.089,204.055,212.03,220.014,228.004,236,244,252,260,268,276,284,292,300,11420.701,11433.201,11445.701,11458.201,11470.701,11483.201,11495.701,11508.201,11520.701,11533.201,11545.701,11558.201,11570.701,11583.201,11595.701,11608.201,11620.701,11633.201,11645.701,11658.201,11670.701,11683.201,11695.701,11708.201,11720.701,11733.201,11745.701,11758.201,11770.701,11783.201,11795.701,11808.201,11820.701,11833.201,11845.701,11858.201,11870.701,11883.201,11898.546],'zIndex':1}]";
						options.series = eval( responseText );
						
						init_chart( chart, options );

					}
					else{
						$.ajax({
								  url: data_url,
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
										options.series = eval( responseText );
										init_chart( chart, options );
									});
								
						}
				});
				
			});
		</script>

</head>

  <body>

	<body>
	<script src="http://code.highcharts.com/highcharts.js"></script>

	<!--
	<script src="libs/highcharts_v2/js/highcharts.js"></script>
	<script src="libs/highcharts_v2/js/modules/exporting.js"></script>
	-->
	<!--
	<script src="libs/highcharts_v3/js/highcharts.js"></script>
	<script src="libs/highcharts_v3/js/modules/exporting.js"></script>

	<script src="http://github.highcharts.com/v3.0Beta/highcharts.js"></script>
	<script src="http://github.highcharts.com/v3.0Beta/modules/exporting.js"></script>
	-->

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