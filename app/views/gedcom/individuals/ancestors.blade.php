<style>
#ancestors .name {
font-weight: bold;
font-size: smaller;
}
#ancestors .about {
fill: #777;
font-size: smaller;
}
#ancestors .link {
fill: none;
stroke: #000;
shape-rendering: crispEdges;
}
</style>
<script>
$(document).ready(function() {
// Adapted from the example on http://bl.ocks.org/mbostock/2966094 by Mike Bostock
var margin = {top: 0, right: 320, bottom: 0, left: 0};
var width = 960 - margin.left - margin.right;
var height = 500 - margin.top - margin.bottom;
var tree = d3.layout.tree()
.separation(function(a, b) {
return a.parent === b.parent ? 1 : .5;
})
.children(function(d) {
return d.parents;
})
.size([height, width]);
var svg = d3.select("#ancestors").append("svg")
.attr("width", width + margin.left + margin.right)
.attr("height", height + margin.top + margin.bottom)
.append("g")
.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
d3.json("{{ URL::to($tree) }}", function(json) {
var nodes = tree.nodes(json);
var link = svg.selectAll(".link")
.data(tree.links(nodes))
.enter().append("path")
.attr("class", "link")
.attr("d", elbow);
var node = svg.selectAll(".node")
.data(nodes)
.enter().append("g")
.attr("class", "node")
.attr("transform", function(d) {
return "translate(" + d.y + "," + d.x + ")";
})
.on("click", function(d) {
window.location.href = d.url;
});
node.append("text")
.attr("class", "name")
.attr("x", 8)
.attr("y", -6)
.text(function(d) {
return d.name;
});
node.append("text")
.attr("x", 8)
.attr("y", 8)
.attr("dy", ".71em")
.attr("class", "about lifespan")
.text(function(d) {
return d.born + "–" + d.died;
});
node.append("text")
.attr("x", 8)
.attr("y", 8)
.attr("dy", "1.86em")
.attr("class", "about location")
.text(function(d) {
return d.location;
});
node.append("rect")
.attr("x", 0)
.attr("y", -25)
.attr("width", function(d) {
return d.children ? d.children[0].y - d.y : margin.right;
})
.attr("height", 60)
.attr("fill", function(d) {
return d.color;
})
.attr("fill-opacity", .25);
});
function elbow(d) {
return "M" + d.source.y + "," + d.source.x
+ "H" + d.target.y + "V" + d.target.x
+ (d.target.children ? "" : "h" + margin.right);
}
});
</script>