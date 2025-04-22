<?php
    $updateInterval = 2000; // in milliseconds
    $initialNumberOfDataPoints = 100;
    $x = time() * 1000 - $updateInterval * $initialNumberOfDataPoints;
    
    $dataPoints = array();
    
    $json = file_get_contents("get_data.php");
    $sensorData = json_decode($json, true);
    
    // Check if we got valid data
    if ($sensorData === null) {
        $yValue = 0; // Default value if no data
        error_log("Failed to decode JSON from get_data.php");
    } else {
        $yValue = isset($sensorData['voltage']) ? $sensorData['voltage'] : 0;
    }
    
    for ($i = 0; $i < $initialNumberOfDataPoints; $i++) {
        array_push($dataPoints, array("x" => $x, "y" => $yValue));
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .sensor-box { border: 1px solid #ddd; padding: 10px; border-radius: 10px; width: 150px; }
    </style>
</head>
<body>
    <h2>PZEM Sensor Data</h2>
    <div class="container">
        <div class="sensor-box"><h3>Voltage</h3><p id="voltage">Loading...</p></div>
    </div>
    <div id="chartContainer" style="height: 300px; width: 100%;"></div>
    <script>
        window.onload = function() {
            var updateInterval = <?php echo $updateInterval ?>;
            var dataPoints = <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>;
            var xValue = <?php echo $x ?>;
            var chart = new CanvasJS.Chart("chartContainer", {
                zoomEnabled: true,
                title: { text: "Voltage Data" },
                axisX: { 
                    title: "Time",
                    valueFormatString: "HH:mm:ss"
                },
                axisY: { 
                    suffix: " V",
                    interval: 50,
                    minimum: 0,
                    maximum: 300,
                    labelFormatter: function(e) {
                        return e.value;
                    }
                },
                data: [{
                    type: "line",
                    xValueType: "dateTime",
                    yValueFormatString: "#,### V",
                    showInLegend: false,
                    dataPoints: dataPoints
                }]
            });
            chart.render();

            function updateChart() {
                $.getJSON("get_data.php", function(data) {
                    console.log("Received data:", data); // Debug JSON response
                    
                    if (data && data.voltage !== undefined) {
                        var voltage = parseFloat(data.voltage);
                        xValue += updateInterval;
                        
                        if (!isNaN(voltage)) {
                            dataPoints.push({ x: xValue, y: voltage });
                            if (dataPoints.length > 60) {
                                dataPoints.shift();
                            }
                            
                            $("#voltage").text(voltage + " V");
                            chart.render();
                        } else {
                            console.error("Invalid voltage value:", data.voltage);
                        }
                    } else {
                        console.error("Invalid response from get_data.php:", data);
                    }
                }).fail(function(jqxhr, textStatus, error) {
                    console.error("Request failed:", textStatus, error);
                });
            }
            setInterval(updateChart, updateInterval);
        }
    </script>
</body>
</html>
