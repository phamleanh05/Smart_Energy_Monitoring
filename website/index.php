<?php
	$dataPoints1 = array();
	$dataPoints2 = array();
	$updateInterval = 2000; //in millisecond
	$initialNumberOfDataPoints = 100;
	$x = time() * 1000 - $updateInterval * $initialNumberOfDataPoints;
	$y1 = 1500;
	$y2 = 1550;
	// generates first set of dataPoints 
	for($i = 0; $i < $initialNumberOfDataPoints; $i++){
		$y1 += round(rand(-2, 2));
		$y2 += round(rand(-2, 2));	
		array_push($dataPoints1, array("x" => $x, "y" => $y1));
		array_push($dataPoints2, array("x" => $x, "y" => $y2));
		$x += $updateInterval;
	}

?>
<!DOCTYPE HTML>
<html>
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZEM Sensor Data</title>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .sensor-box { border: 1px solid #ddd; padding: 10px; border-radius: 10px; width: 150px; }
        .chart-container { width: 45%; }
    </style>
	<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</head>
<body>
	<h2>PZEM Sensor Data</h2>
    <div class="container">
        <div class="sensor-box"><h3>Voltage</h3><p id="voltage">Loading...</p></div>
        <div class="sensor-box"><h3>Current</h3><p id="current">Loading...</p></div>
        <div class="sensor-box"><h3>Power</h3><p id="power">Loading...</p></div>
        <div class="sensor-box"><h3>Energy</h3><p id="energy">Loading...</p></div>
    </div>
	<div id="chartContainer" style="height: 300px; width: 100%;"></div>	
	<script>
		window.onload = function() {
		 
		var updateInterval = <?php echo $updateInterval ?>;
		var dataPoints1 = <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>;
		var dataPoints2 = <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>;
		var yValue1 = <?php echo $y1 ?>;
		var yValue2 = <?php echo $y2 ?>;
		var xValue = <?php echo $x ?>;
		 
		var chart = new CanvasJS.Chart("chartContainer", {
			zoomEnabled: true,
			title: {
				text: "Voltage and Current"
			},
			axisX: {
				title: "chart updates every " + updateInterval / 1000 + " secs"
			},
			axisY:{
				suffix: " watts"
			}, 
			toolTip: {
				shared: true
			},
			legend: {
				cursor:"pointer",
				verticalAlign: "top",
				fontSize: 22,
				fontColor: "dimGrey",
				itemclick : toggleDataSeries
			},
			data: [{ 
					type: "line",
					name: "Building A",
					xValueType: "dateTime",
					yValueFormatString: "#,### watts",
					xValueFormatString: "hh:mm:ss TT",
					showInLegend: true,
					legendText: "{name} " + yValue1 + " watts",
					dataPoints: dataPoints1
				},
				{				
					type: "line",
					name: "Building B" ,
					xValueType: "dateTime",
					yValueFormatString: "#,### watts",
					showInLegend: true,
					legendText: "{name} " + yValue2 + " watts",
					dataPoints: dataPoints2
			}]
		});
		 
		chart.render();
		setInterval(function(){updateChart()}, updateInterval);
		 
		function toggleDataSeries(e) {
			if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
				e.dataSeries.visible = false;
			}
			else {
				e.dataSeries.visible = true;
			}
			chart.render();
		}
		 
		function updateChart() {
			var deltaY1, deltaY2;
			xValue += updateInterval;
			// adding random value
			yValue1 += Math.round(2 + Math.random() *(-2-2));
			yValue2 += Math.round(2 + Math.random() *(-2-2));
		 
			// pushing the new values
			dataPoints1.push({
				x: xValue,
				y: yValue1
			});
			dataPoints2.push({
				x: xValue,
				y: yValue2
			});
		 
			// updating legend text with  updated with y Value 
			chart.options.data[0].legendText = "Building A " + yValue1 + " watts";
			chart.options.data[1].legendText = " Building B " + yValue2+ " watts"; 
			chart.render();
		}
		 
		}
	</script>
</body>
</html>                              