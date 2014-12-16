<script type="text/javascript">
    google.load("visualization", "1", {packages: ["corechart"]});
    google.setOnLoadCallback(drawChart);
    function drawChart() {
        var jsonData = $.ajax({
            url: "{{ URL::to('gedcoms/histodata') }}",
            dataType: "json",
            async: false
        }).responseText;
        
        // Create our data table out of JSON data loaded from server.
        var data = new google.visualization.DataTable(jsonData);

        var options = {
            title: 'Number of children',
            legend: {position: 'none'},
            width: 900, 
            height: 500
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    }
</script>

<div class="page-header">
    <h3>
        Histogram
    </h3>
</div>

<div id="chart_div"></div>