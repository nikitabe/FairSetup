<html>
  <head>
    <!--Load the AJAX API-->

		<script type="text/javascript" src="jquery-1.9.0.min.js"></script>
		<script type="text/javascript">
			$(function () {
			  var options = {
					credits: false, 
					chart: {
						renderTo: 'graph_container'
						,zoomType: 'x'
						,spacingRight:20
						,type:'area'
					},

					title: {
						text: 'Impact over time'
					},
			
					subtitle: {
						text: 'This is a subtitle'
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
							stacking: 'percent',
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
				var chart;
				
				options.series = [
					{"name":"A","pointStart":1320120000000,"pointInterval":86400000,"data":[1, 2, 3, 4, 5]},
					{"name":"B","pointStart":1320120000000,"pointInterval":86400000,"data":[1, 2, 3, 4, 5]}
					] ;
									
				chart = new Highcharts.Chart( options );
								
				
			});
		</script>

</head>

  <body>

	<body>
	<script src="highcharts/js/highcharts.js"></script>
	<script src="highcharts/js/modules/exporting.js"></script>

	<div id="graph_container" style="min-width: 400px; height: 400px; margin: 0 auto;"></div>

  </body>
</html>