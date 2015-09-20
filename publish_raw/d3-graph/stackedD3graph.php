<!DOCTYPE html>
<meta charset="utf-8">
<?php
 $lc = array_change_key_case($_REQUEST);
$company_id = "";
 if( isset ($lc['c_id'])) $company_id 	= (int)$lc['c_id'];	

 if( !is_numeric( $company_id ) || $company_id < 0 ){
	echo "No company with that id";
	exit;
}

?>
<style>

</style>
<body>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" charset="utf-8"></script>
<script>

screenWidth = screen.width;
screenHeight = 500;

var margin = {top: 20, right: 40, bottom: 30, left: 40},
         width  = screenWidth - margin.left - margin.right,
         height = screenHeight - margin.top  - margin.bottom;
var svg = d3.select(".chart").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

var x = d3.scale.ordinal()
  .rangeBands([0, width], .1);
var y = d3.scale.linear()
  .range([height, 0]);

//////////////////////////////////////////
var xAxis = d3.svg.axis()
  .scale(x)
  .ticks(d3.time.year)
  .orient("bottom");

var yAxis = d3.svg.axis()
  .scale(y)
  .orient("left");

//////////////////////////////////////////
var stack = d3.layout.stack()
  .offset("zero")
  .values(function (d) { return d.values; })
  .x(function (d) { return x(d.label) + x.rangeBand() / 2; })
  .y(function (d) { return d.value; })
  .order("reverse");


var area = d3.svg.area()
  .interpolate("cardinal")
  .x(function (d) { return x(d.label) + x.rangeBand() / 2; })
  .y0(function (d) { return y(d.y0); })
  .y1(function (d) { return y(d.y0 + d.y); });

var color = d3.scale.category20c();

var svg = d3.select("body").append("svg")
  .attr("width",  width  + margin.left + margin.right)
  .attr("height", height + margin.top  + margin.bottom)
.append("g")
  .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

d3.json("../graph_generation/GetData.php?c_id=<?php echo $company_id?>", function(error, data) {
  if (error) throw error;
  var dLength = data.length;
  var names = [];

  var format = d3.time.format("%x");
  var parseDate = d3.time.format("%x").parse;

  var seriesArr = [], series = {};
  dates = [];

dataLengths = [];
data.forEach(function(person){
  dataLengths.push(person.data.length);
});

leastDataIndex = dataLengths.indexOf(d3.min(dataLengths));

  data.forEach(function(person){
     var name = person.name;
     names.push( name );
     series[name] = {name: person.name, values:[]};
     
     if (person.data.length > data[leastDataIndex].data.length){
      rem = data[leastDataIndex].data.length - person.data.length;
      person.data = person.data.slice(0, rem);
     }
     person.data.forEach( function( v, i ){
      
      d_v = format( new Date( person.pointStart + (i * person.pointInterval) ) );
      series[name].values.push({
          name:name, 
          label:d_v , 
          value: v});
      
      if( seriesArr.length == leastDataIndex )
        dates.push( d_v );
     });
     seriesArr.push( series[name] );
  });

cumTot = [];
for (i = 0; i < data[leastDataIndex].data.length; i++) {
  dayTot = 0;
  for (j = 0; j < dLength; j++) {
    dayTot = dayTot + data[j].data[i]
  }
  cumTot[i] = dayTot;
}

for (i = 0; i < seriesArr.length; i++){
  for (j=0; j<seriesArr[i].values.length; j++){
    seriesArr[i].values[j].value = seriesArr[i].values[j].value / cumTot[j] * 100;
    if (isNaN(seriesArr[i].values[j].value)){
          seriesArr[i].values[j].value = 0;
        };
  };
};

  var layers = stack(seriesArr);

  color.domain( names );

    x.domain( dates );
        y.domain([0, d3.max(seriesArr, function (c) { 
            return d3.max(c.values, function (d) { return d.y0 + d.y; });
          })]);
        

    // before drawing

    // svg.append("g")
    //     .attr("class", "x axis")
    //     .attr("transform", "translate(0," + height + ")")
    //     .call(xAxis);
        
        svg.append("g")
            .attr("class", "y axis")
            .call(yAxis)
          .append("text")
            .attr("transform", "rotate(-90)")
            .attr("y", 6)
            .attr("dy", ".71em")
            .style("text-anchor", "end")
            .text("Proportional Impact Over Time");


        var selection = svg.selectAll(".layer")
          .data(seriesArr)
          .enter().append("g")
            .attr("class", "layer");
        selection.append("path")
          .attr("d", function (d) { return area(d.values); })
          .style("fill", function (d) { return color(d.name); })
          .style("stroke", "grey");

////////////////
// Add line
 var line = d3.select(".layer").append("svg")
                                     .attr("width", screenWidth)
                                     .attr("height", screenHeight).append("line")
                                    .attr("x1", 200)
                                    .attr("y1", 0)
                                    .attr("x2", 200)
                                    .attr("y2", screenHeight)
                                    .attr("stroke-width", 2)
                                    .attr("stroke", "red");


//Make an SVG Container
 var svgContainer = d3.select(".layer").append("svg")
                                     .attr("width", 200)
                                     .attr("height", 200);
  svg.selectAll(".layer")
    .attr("opacity", 1)
    .on("mousemove", function(d, i) {
      svg.selectAll(".layer").transition()
      .duration(250)
      .attr("opacity", function(d, j) {
        return j != i ? 0.6 : 1;
      });
      var xPos = d3.mouse(this)[0];
      var leftEdges = x.range();
      var width = x.rangeBand();
      //do nothing, just increment j until case fails
      for(var j=0; xPos > (leftEdges[j] + width); j++) {}
      mouseDate = x.domain()[j]
      document.getElementById("dateLabel").innerHTML = mouseDate;
      personNameIndex = names.indexOf(d.name);
      dateIndex = dates.indexOf(mouseDate);
      personVal = data[personNameIndex].data[dateIndex];
      personPerc = (personVal/cumTot[dateIndex]) * 100;
      if (isNaN(personPerc)){
        personPerc = 0;
      };
      personName = names[personNameIndex];
      document.getElementById("personNameLabel").innerHTML = personName;
      document.getElementById("personValLabel").innerHTML = (personVal).toFixed(2);
      document.getElementById("personPercLabel").innerHTML = (personPerc).toFixed(2) + "%";
      tableValues = [];
    seriesArr.forEach(function(person,i){
      peepsAndVals = {name: person.name, percentage: person.values[dateIndex].value.toFixed(2) + "%"}
      tableValues.push(peepsAndVals);
    });
    var table = document.getElementById("fullTable");
    table.innerHTML = "";
    for (personIndex in tableValues) {
      var tablePerson = tableValues[personIndex];
      var newRow = table.insertRow(-1);
      for (value in tablePerson) {
        newRow.insertCell(-1).innerHTML = tablePerson[value];
      }
    }
    line.attr("x1", xPos).attr("x2", xPos);
    })
    .on("mouseout", function(d, i) {
    var mousex = d3.mouse(this);
     svg.selectAll(".layer")
      .transition()
      .duration(250)
      .attr("opacity", "1");
      d3.select(this)
      .classed("hover", false)
        });

});

</script>
  <center><div id="dateLabel"></div></center>
  <center><div id="personNameLabel"></div></center>
  <center><div id="personValLabel"></div></center>
  <center><div id="personPercLabel"></div></center>
  <center><table id="fullTable"></table></center>

</body>