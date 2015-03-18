<style>
    .axis path,
    .axis line {
        fill: none;
        stroke: #000;
        shape-rendering: crispEdges;
    }
</style>
<div class="page-header">
    <h3>
        Marriage ages
    </h3>
</div>
<div id="chart">
    <!-- Chart will be rendered here -->
</div>
<script>
    // Modified from http://bl.ocks.org/mbostock/3887118

    // Set margins
    var margin = {top: 20, right: 20, bottom: 30, left: 40};
    var width = 960 - margin.left - margin.right;
    var height = 500 - margin.top - margin.bottom;

    // Set scales
    var x = d3.time.scale().range([0, width]);
    var y = d3.scale.linear().range([height, 0]);

    var color = d3.scale.category10();

    // Set axes
    var xAxis = d3.svg.axis()
            .scale(x)
            .orient("bottom");

    var yAxis = d3.svg.axis()
            .scale(y)
            .orient("left");

    // Create chart svg
    var svg = d3.select("#chart").append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    // Load data
    d3.json("{{ URL::to('families/marriageages/' . $gedcom->id) }}", function (error, data) {
        // Alter domains
        x.domain(d3.extent(data, function (d) {
            return toDate(d.birth_date);
        })).nice();
        y.domain(d3.extent(data, function (d) {
            return d.marriage_age;
        })).nice();

        // Set axes
        svg.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(0," + height + ")")
                .call(xAxis)
                .append("text")
                .attr("class", "label")
                .attr("x", width)
                .attr("y", -6)
                .style("text-anchor", "end")
                .text("Birth date");

        svg.append("g")
                .attr("class", "y axis")
                .call(yAxis)
                .append("text")
                .attr("class", "label")
                .attr("transform", "rotate(-90)")
                .attr("y", 6)
                .attr("dy", ".71em")
                .style("text-anchor", "end")
                .text("Marriage age")

        // Add points
        svg.selectAll(".dot")
                .data(data)
                .enter()
                .append("a")
                .attr("xlink:href", function (d) {
                    return "{{ URL::to('individuals/show/') }}" + "/" + d.id;
                })
                .append("circle")
                .attr("class", "dot")
                .attr("r", 3.5)
                .attr("cx", function (d) {
                    return x(toDate(d.birth_date));
                })
                .attr("cy", function (d) {
                    return y(d.marriage_age);
                })
                .style("fill", function (d) {
                    return color(d.sex);
                });

        // Add legend
        var legend = svg.selectAll(".legend")
                .data(color.domain())
                .enter().append("g")
                .attr("class", "legend")
                .attr("transform", function (d, i) {
                    return "translate(0," + i * 20 + ")";
                });

        legend.append("rect")
                .attr("x", width - 18)
                .attr("width", 18)
                .attr("height", 18)
                .style("fill", color);

        legend.append("text")
                .attr("x", width - 24)
                .attr("y", 9)
                .attr("dy", ".35em")
                .style("text-anchor", "end")
                .text(function (d) {
                    return d;
                });
    });

    // Create a date from a string. For incomplete dates, use only the year. 
    function toDate(d) {
        if (endsWith(d, '-00')) {
            d = d.substring(0, 4);
        }
        return new Date(d);
    }

    // Checks whether a given string ends with a given suffix.
    function endsWith(str, suffix) {
        return str.indexOf(suffix, str.length - suffix.length) !== -1;
    }
</script>