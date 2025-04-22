<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $updateInterval = 1000; // in milliseconds (1 second)
    $initialNumberOfDataPoints = 60; // Reduced to match get_data.php
    
    $json = file_get_contents("get_data.php");
    $response = json_decode($json, true);
    
    // Check if we got valid data
    if ($response === null) {
        $voltage = 0;
        $current = 0;
        $power = 0;
        $energy = 0;
        $dataPoints = array();
        $currentDataPoints = array();
        $lastUpdateTime = date('H:i:s');
        $deviceStatus = 'FAILED';
        error_log("Failed to decode JSON from get_data.php");
    } else {
        $latestData = $response['latest'];
        $historyData = $response['history'];
        
        $voltage = isset($latestData['voltage']) ? $latestData['voltage'] : 0;
        $current = isset($latestData['current']) ? $latestData['current'] : 0;
        $power = isset($latestData['power']) ? $latestData['power'] : 0;
        $energy = isset($latestData['energy_consumed']) ? $latestData['energy_consumed'] : 0;
        $lastUpdateTime = isset($latestData['created_at']) ? date('H:i:s', strtotime($latestData['created_at'])) : date('H:i:s');
        $deviceStatus = isset($latestData['status_read_sensor_pzem']) ? $latestData['status_read_sensor_pzem'] : 'FAILED';
        
        // Initialize data points from history
        $dataPoints = array();
        $currentDataPoints = array();
        
        foreach ($historyData as $record) {
            if (isset($record['timestamp']) && isset($record['voltage']) && isset($record['current'])) {
                $dataPoints[] = array("x" => $record['timestamp'], "y" => $record['voltage']);
                $currentDataPoints[] = array("x" => $record['timestamp'], "y" => $record['current']);
            }
        }
        
        // If no history data, create some initial points
        if (empty($dataPoints)) {
            $x = time() * 1000 - $updateInterval * $initialNumberOfDataPoints;
            for ($i = 0; $i < $initialNumberOfDataPoints; $i++) {
                array_push($dataPoints, array("x" => $x, "y" => $voltage));
                array_push($currentDataPoints, array("x" => $x, "y" => $current));
                $x += $updateInterval;
            }
        }
    }
?>
<!DOCTYPE HTML>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển</title>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: "Times New Roman", "Times", "Noto Serif", "DejaVu Serif", serif; 
            margin: 0;
            padding: 10px;
            background-color: #f5f5f5;
            height: 100vh;
            overflow: hidden;
            font-feature-settings: "kern" 1, "liga" 1, "calt" 1, "pnum" 1, "onum" 0, "lnum" 0, "tnum" 0, "salt" 0, "ss01" 0, "ss02" 0, "ss03" 0, "ss04" 0, "ss05" 0, "ss06" 0, "ss07" 0, "ss08" 0, "zero" 0, "mark" 1, "mkmk" 1, "locl" 1;
            -webkit-font-feature-settings: "kern" 1, "liga" 1, "calt" 1, "pnum" 1, "onum" 0, "lnum" 0, "tnum" 0, "salt" 0, "ss01" 0, "ss02" 0, "ss03" 0, "ss04" 0, "ss05" 0, "ss06" 0, "ss07" 0, "ss08" 0, "zero" 0, "mark" 1, "mkmk" 1, "locl" 1;
            -moz-font-feature-settings: "kern" 1, "liga" 1, "calt" 1, "pnum" 1, "onum" 0, "lnum" 0, "tnum" 0, "salt" 0, "ss01" 0, "ss02" 0, "ss03" 0, "ss04" 0, "ss05" 0, "ss06" 0, "ss07" 0, "ss08" 0, "zero" 0, "mark" 1, "mkmk" 1, "locl" 1;
            -ms-font-feature-settings: "kern" 1, "liga" 1, "calt" 1, "pnum" 1, "onum" 0, "lnum" 0, "tnum" 0, "salt" 0, "ss01" 0, "ss02" 0, "ss03" 0, "ss04" 0, "ss05" 0, "ss06" 0, "ss07" 0, "ss08" 0, "zero" 0, "mark" 1, "mkmk" 1, "locl" 1;
            -o-font-feature-settings: "kern" 1, "liga" 1, "calt" 1, "pnum" 1, "onum" 0, "lnum" 0, "tnum" 0, "salt" 0, "ss01" 0, "ss02" 0, "ss03" 0, "ss04" 0, "ss05" 0, "ss06" 0, "ss07" 0, "ss08" 0, "zero" 0, "mark" 1, "mkmk" 1, "locl" 1;
            font-kerning: normal;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .dashboard-container {
            max-width: 100%;
            height: 100%;
            margin: 0 auto;
            display: grid;
            grid-template-rows: auto 1fr;
            gap: 10px;
        }
        .header {
            background-color: #2196F3;
            color: white;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a {
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .header a:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 10px;
            height: calc(100vh - 80px);
        }
        .stats-panel {
            display: grid;
            grid-template-rows: repeat(4, auto);
            gap: 10px;
        }
        .charts-panel {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 10px;
            overflow-y: auto;
            height: 100%;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container {
            height: 90%;
            width: 100%;
            min-height: 200px;
        }
        .sensor-value {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
            margin: 5px 0;
        }
        .sensor-label {
            color: #666;
            font-size: 14px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-online {
            background-color: #4CAF50;
        }
        .status-offline {
            background-color: #f44336;
        }
        h1 {
            margin: 0;
            font-size: 24px;
        }
        h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .update-time {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }
        .header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .nav-links {
            margin-top: 10px;
        }
        .history-link {
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .history-link:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .logout-link {
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .logout-link:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            .stats-panel {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, auto);
            }
        }
        @media (max-width: 768px) {
            .stats-panel {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(4, auto);
            }
            .charts-panel {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(4, 400px);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>Bảng Điều Khiển Thiết Bị IoT</h1>
                <div>
                    <span class="status-indicator <?php echo $deviceStatus === 'SUCCEED' ? 'status-online' : 'status-offline'; ?>"></span>
                    <span>Thiết Bị <?php echo $deviceStatus === 'SUCCEED' ? 'Hoạt Động' : 'Không Hoạt Động'; ?></span>
                </div>
            </div>
            <div class="header-right">
                <div class="update-time">
                    Cập nhật lần cuối: <span id="lastUpdate"><?php echo $lastUpdateTime; ?></span>
                </div>
                <div class="nav-links">
                    <a href="historical_data.php" class="history-link">Xem Dữ Liệu Lịch Sử</a>
                    <a href="logout.php" class="logout-link">Đăng Xuất</a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="stats-panel">
                <div class="card">
                    <h3>Điện Áp</h3>
                    <div class="sensor-value" id="voltage"><?php echo number_format($voltage, 2); ?> V</div>
                    <div class="sensor-label">Đo điện áp thời gian thực</div>
                </div>
                <div class="card">
                    <h3>Dòng Điện</h3>
                    <div class="sensor-value" id="current"><?php echo number_format($current, 2); ?> A</div>
                    <div class="sensor-label">Đo dòng điện thời gian thực</div>
                </div>
                <div class="card">
                    <h3>Công Suất</h3>
                    <div class="sensor-value" id="power"><?php echo number_format($power, 2); ?> W</div>
                    <div class="sensor-label">Tiêu thụ điện năng thời gian thực</div>
                </div>
                <div class="card">
                    <h3>Điện Năng</h3>
                    <div class="sensor-value" id="energy"><?php echo number_format($energy, 2); ?> kWh</div>
                    <div class="sensor-label">Tổng điện năng tiêu thụ</div>
                </div>
            </div>

            <div class="charts-panel">
                <div class="card">
                    <h3>Biểu Đồ Điện Áp</h3>
                    <div id="voltageChart" class="chart-container"></div>
                </div>
                <div class="card">
                    <h3>Biểu Đồ Dòng Điện</h3>
                    <div id="currentChart" class="chart-container"></div>
                </div>
                <div class="card">
                    <h3>Biểu Đồ Công Suất</h3>
                    <div id="powerChart" class="chart-container"></div>
                </div>
                <div class="card">
                    <h3>Biểu Đồ Điện Năng Tiêu Thụ</h3>
                    <div id="energyChart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Configuration parameters - adjust these to control timing
            var updateInterval = <?php echo $updateInterval ?>; // How often to update the dashboard (in milliseconds)
            var statusCheckInterval = 1000; // How often to check if ESP32 is still active (in milliseconds)
            var requiredFailures = 1; // Number of consecutive failures needed to change status to FAILED
            var requiredSuccesses = 2; // Number of consecutive successes needed to change status to SUCCEED
            
            var dataPoints = <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>;
            var currentDataPoints = <?php echo json_encode($currentDataPoints, JSON_NUMERIC_CHECK); ?>;
            var powerDataPoints = [];
            var energyDataPoints = [];
            var updateCount = 0;
            var lastStatusUpdate = Date.now();
            var consecutiveFailures = 0;
            var consecutiveSuccesses = 0;
            var currentStatus = '<?php echo $deviceStatus; ?>'; // Store current status
            
            // Initialize power and energy data points
            var now = Date.now();
            for (var i = 0; i < 60; i++) {
                powerDataPoints.push({ x: now - (60 - i) * updateInterval, y: 0 });
                energyDataPoints.push({ x: now - (60 - i) * updateInterval, y: 0 });
            }
            
            // Function to update status indicator
            function updateStatusIndicator(status, isEsp32Active) {
                var statusIndicator = $(".status-indicator");
                var statusText = statusIndicator.next("span");
                
                // If ESP32 is not active, increment failure counter
                if (!isEsp32Active) {
                    consecutiveFailures++;
                    consecutiveSuccesses = 0; // Reset success counter
                    console.log("ESP32 not active, consecutive failures: " + consecutiveFailures);
                    
                    // Only change status to FAILED after multiple consecutive failures
                    if (consecutiveFailures >= requiredFailures && currentStatus !== 'FAILED') {
                        currentStatus = 'FAILED';
                        statusIndicator.removeClass('status-online').addClass('status-offline');
                        statusText.text('Thiết Bị Không Hoạt Động');
                    }
                } else {
                    // ESP32 is active, increment success counter
                    consecutiveSuccesses++;
                    console.log("ESP32 active, consecutive successes: " + consecutiveSuccesses);
                    
                    // Only change status to SUCCEED after multiple consecutive successes
                    if (consecutiveSuccesses >= requiredSuccesses && currentStatus !== 'SUCCEED') {
                        currentStatus = status; // Use the provided status
                        if (currentStatus === 'SUCCEED') {
                            statusIndicator.removeClass('status-offline').addClass('status-online');
                            statusText.text('Thiết Bị Hoạt Động');
                        } else {
                            statusIndicator.removeClass('status-online').addClass('status-offline');
                            statusText.text('Thiết Bị Không Hoạt Động');
                        }
                    }
                }
            }
            
            // Check status every statusCheckInterval milliseconds
            setInterval(function() {
                var currentTime = Date.now();
                if (currentTime - lastStatusUpdate > statusCheckInterval) {
                    // If no update received within statusCheckInterval, increment failure counter
                    consecutiveFailures++;
                    consecutiveSuccesses = 0; // Reset success counter
                    console.log("No update received, consecutive failures: " + consecutiveFailures);
                    
                    // Only change status to FAILED after multiple consecutive failures
                    if (consecutiveFailures >= requiredFailures && currentStatus !== 'FAILED') {
                        currentStatus = 'FAILED';
                        var statusIndicator = $(".status-indicator");
                        var statusText = statusIndicator.next("span");
                        statusIndicator.removeClass('status-online').addClass('status-offline');
                        statusText.text('Thiết Bị Không Hoạt Động');
                    }
                }
            }, statusCheckInterval);
            
            // CanvasJS Charts
            var voltageChart = new CanvasJS.Chart("voltageChart", {
                zoomEnabled: true,
                title: { text: "Điện Áp Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
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
                toolTip: {
                    shared: true,
                    content: function(e) {
                        var date = new Date(e.entries[0].dataPoint.x);
                        var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                        date.getMinutes().toString().padStart(2, '0') + ':' + 
                                        date.getSeconds().toString().padStart(2, '0');
                        var content = "<strong>Thời gian:</strong> " + timeString + "<br/>";
                        content += "<strong>Điện áp:</strong> " + e.entries[0].dataPoint.y.toFixed(2) + " V";
                        return content;
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
            voltageChart.render();

            var currentChart = new CanvasJS.Chart("currentChart", {
                zoomEnabled: true,
                title: { text: "Dòng Điện Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss"
                },
                axisY: { 
                    suffix: " A",
                    interval: 1,
                    minimum: 0,
                    maximum: 1,
                    labelFormatter: function(e) {
                        return e.value;
                    }
                },
                toolTip: {
                    shared: true,
                    content: function(e) {
                        var date = new Date(e.entries[0].dataPoint.x);
                        var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                        date.getMinutes().toString().padStart(2, '0') + ':' + 
                                        date.getSeconds().toString().padStart(2, '0');
                        var content = "<strong>Thời gian:</strong> " + timeString + "<br/>";
                        content += "<strong>Dòng điện:</strong> " + e.entries[0].dataPoint.y.toFixed(2) + " A";
                        return content;
                    }
                },
                data: [{
                    type: "line",
                    xValueType: "dateTime",
                    yValueFormatString: "#,### A",
                    showInLegend: false,
                    dataPoints: currentDataPoints
                }]
            });
            currentChart.render();

            var powerChart = new CanvasJS.Chart("powerChart", {
                zoomEnabled: true,
                title: { text: "Công Suất Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss"
                },
                axisY: { 
                    suffix: " W",
                    interval: 50,
                    minimum: 0,
                    maximum: 300,
                    labelFormatter: function(e) {
                        return e.value;
                    }
                },
                toolTip: {
                    shared: true,
                    content: function(e) {
                        var date = new Date(e.entries[0].dataPoint.x);
                        var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                        date.getMinutes().toString().padStart(2, '0') + ':' + 
                                        date.getSeconds().toString().padStart(2, '0');
                        var content = "<strong>Thời gian:</strong> " + timeString + "<br/>";
                        content += "<strong>Công suất:</strong> " + e.entries[0].dataPoint.y.toFixed(2) + " W";
                        return content;
                    }
                },
                data: [{
                    type: "line",
                    xValueType: "dateTime",
                    yValueFormatString: "#,### W",
                    showInLegend: false,
                    dataPoints: powerDataPoints
                }]
            });
            powerChart.render();

            var energyChart = new CanvasJS.Chart("energyChart", {
                zoomEnabled: true,
                title: { text: "Điện Năng Tiêu Thụ Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss"
                },
                axisY: { 
                    suffix: " kWh",
                    interval: 50,
                    minimum: 0,
                    maximum: 300,
                    labelFormatter: function(e) {
                        return e.value.toFixed(2);
                    }
                },
                toolTip: {
                    shared: true,
                    content: function(e) {
                        var date = new Date(e.entries[0].dataPoint.x);
                        var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                        date.getMinutes().toString().padStart(2, '0') + ':' + 
                                        date.getSeconds().toString().padStart(2, '0');
                        var content = "<strong>Thời gian:</strong> " + timeString + "<br/>";
                        content += "<strong>Điện năng tiêu thụ:</strong> " + e.entries[0].dataPoint.y.toFixed(2) + " kWh";
                        return content;
                    }
                },
                data: [{
                    type: "line",
                    xValueType: "dateTime",
                    yValueFormatString: "#.## kWh",
                    showInLegend: false,
                    dataPoints: energyDataPoints
                }]
            });
            energyChart.render();
            
            function updateCharts() {
                updateCount++;
                var now = new Date();
                var timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                                now.getMinutes().toString().padStart(2, '0') + ':' + 
                                now.getSeconds().toString().padStart(2, '0');
                
                $.getJSON("get_data.php", function(response) {
                    console.log("Update #" + updateCount + " at " + timeString + " - Received data:", response);
                    
                    if (response && response.latest) {
                        var latestData = response.latest;
                        var voltage = parseFloat(latestData.voltage);
                        var current = parseFloat(latestData.current);
                        var power = parseFloat(latestData.power);
                        var energy = parseFloat(latestData.energy_consumed || 0);
                        var timestamp = latestData.timestamp || Date.now();
                        var deviceStatus = latestData.status_read_sensor_pzem || 'FAILED';
                        var isEsp32Active = response.is_esp32_active || false;
                        var recentCount = response.recent_count || 0;
                        
                        console.log("ESP32 active: " + isEsp32Active + ", Recent count: " + recentCount);
                        
                        // Update last status update time
                        lastStatusUpdate = Date.now();
                        
                        // Update device status with ESP32 active check
                        updateStatusIndicator(deviceStatus, isEsp32Active);
                        
                        // Update displays
                        $("#voltage").text(voltage.toFixed(2) + " V");
                        $("#current").text(current.toFixed(2) + " A");
                        $("#power").text(power.toFixed(2) + " W");
                        $("#energy").text(energy.toFixed(2) + " kWh");
                        $("#lastUpdate").text(timeString);
                        
                        // Update charts with new data point
                        dataPoints.push({ x: timestamp, y: voltage });
                        if (dataPoints.length > 60) {
                            dataPoints.shift();
                        }
                        
                        currentDataPoints.push({ x: timestamp, y: current });
                        if (currentDataPoints.length > 60) {
                            currentDataPoints.shift();
                        }
                        
                        powerDataPoints.push({ x: timestamp, y: power });
                        if (powerDataPoints.length > 60) {
                            powerDataPoints.shift();
                        }
                        
                        energyDataPoints.push({ x: timestamp, y: energy });
                        if (energyDataPoints.length > 60) {
                            energyDataPoints.shift();
                        }
                        
                        // Render all charts
                        voltageChart.render();
                        currentChart.render();
                        powerChart.render();
                        energyChart.render();
                    } else {
                        console.error("Phản hồi không hợp lệ từ get_data.php:", response);
                    }
                }).fail(function(jqxhr, textStatus, error) {
                    console.error("Yêu cầu thất bại:", textStatus, error);
                });
            }
            
            // Initial update
            updateCharts();
            
            // Set interval for updates
            setInterval(updateCharts, updateInterval);
        }
    </script>
</body>
</html> 