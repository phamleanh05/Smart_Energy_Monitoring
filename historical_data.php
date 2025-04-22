<?php
// Default date range (last 24 hours)
$defaultStartDate = date('Y-m-d', strtotime('-1 day'));
$defaultEndDate = date('Y-m-d');
$defaultStartTime = '00:00:00';
$defaultEndTime = '23:59:59';

// Get parameters from request
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;
$startTime = isset($_GET['start_time']) ? $_GET['start_time'] : $defaultStartTime;
$endTime = isset($_GET['end_time']) ? $_GET['end_time'] : $defaultEndTime;

// Format dates for display
$startDateTime = $startDate . ' ' . $startTime;
$endDateTime = $endDate . ' ' . $endTime;
?>
<!DOCTYPE HTML>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dữ Liệu Lịch Sử</title>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2196F3;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
        }
        .controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .control-group {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #0b7dda;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
            margin: 10px 0;
        }
        .summary-label {
            color: #666;
            font-size: 14px;
        }
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-container {
            height: 300px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow-x: auto;
        }
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            color: #2196F3;
            text-decoration: none;
            margin-right: 15px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .view-toggle {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .view-toggle button {
            background-color: #f2f2f2;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
        }
        .view-toggle button.active {
            background-color: #2196F3;
            color: white;
            border-color: #2196F3;
        }
        .view-section {
            display: none;
        }
        .view-section.active {
            display: block;
        }
        .chart-legend {
            display: flex;
            justify-content: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        .chart-legend-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        .chart-legend-color {
            width: 12px;
            height: 12px;
            margin-right: 5px;
            border-radius: 2px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .controls {
                grid-template-columns: 1fr;
            }
            .summary {
                grid-template-columns: 1fr;
            }
            .charts {
                grid-template-columns: 1fr;
            }
            .chart-container {
                height: 250px;
            }
            .data-table {
                font-size: 14px;
            }
            .data-table th, .data-table td {
                padding: 8px;
            }
            .view-toggle {
                flex-direction: column;
                align-items: stretch;
            }
            .view-toggle button {
                margin: 5px 0;
            }
        }
        #data-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }
        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .data-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .data-actions {
            display: flex;
            gap: 10px;
        }
        .data-actions button {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="dashboard.php">← Quay lại Bảng Điều Khiển</a>
        </div>
        
        <div class="header">
            <h1>Dữ Liệu Lịch Sử</h1>
        </div>
        
        <div class="controls">
            <div class="control-group">
                <label for="start_date">Ngày Bắt Đầu:</label>
                <input type="date" id="start_date" value="<?php echo $startDate; ?>">
            </div>
            <div class="control-group">
                <label for="start_time">Giờ Bắt Đầu:</label>
                <input type="time" id="start_time" value="<?php echo substr($startTime, 0, 5); ?>">
            </div>
            <div class="control-group">
                <label for="end_date">Ngày Kết Thúc:</label>
                <input type="date" id="end_date" value="<?php echo $endDate; ?>">
            </div>
            <div class="control-group">
                <label for="end_time">Giờ Kết Thúc:</label>
                <input type="time" id="end_time" value="<?php echo substr($endTime, 0, 5); ?>">
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button id="fetch-data">Lấy Dữ Liệu</button>
            </div>
        </div>
        
        <div class="summary">
            <div class="summary-card">
                <div class="summary-label">Tổng Điện Năng Tiêu Thụ</div>
                <div class="summary-value" id="total-energy">0.00 kWh</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Điện Áp Trung Bình</div>
                <div class="summary-value" id="avg-voltage">0.00 V</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Dòng Điện Trung Bình</div>
                <div class="summary-value" id="avg-current">0.00 A</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Công Suất Trung Bình</div>
                <div class="summary-value" id="avg-power">0.00 W</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Số Lượng Bản Ghi</div>
                <div class="summary-value" id="record-count">0</div>
            </div>
        </div>
        
        <div class="view-toggle">
            <button id="chart-view-btn" class="active">Xem Biểu Đồ</button>
            <button id="table-view-btn">Xem Bảng Dữ Liệu</button>
        </div>
        
        <div id="chart-view" class="view-section active">
            <div class="charts">
                <div class="chart-container">
                    <div id="voltageChart"></div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #2196F3;"></div>
                            <span>Điện Áp</span>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <div id="currentChart"></div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #4CAF50;"></div>
                            <span>Dòng Điện</span>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <div id="powerChart"></div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #FF9800;"></div>
                            <span>Công Suất</span>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <div id="energyChart"></div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #9C27B0;"></div>
                            <span>Điện Năng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="table-view" class="view-section">
            <div id="data-container">
                <div class="data-header">
                    <h3>Dữ Liệu Chi Tiết</h3>
                    <div class="data-actions">
                        <button id="export-csv">Xuất CSV</button>
                        <button id="print-data">In Dữ Liệu</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thời Gian</th>
                            <th>Điện Áp (V)</th>
                            <th>Dòng Điện (A)</th>
                            <th>Công Suất (W)</th>
                            <th>Điện Năng (kWh)</th>
                        </tr>
                    </thead>
                    <tbody id="data-table-body">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize charts with responsive options
            var chartOptions = {
                zoomEnabled: true,
                height: 250,
                animationEnabled: true,
                exportEnabled: true,
                exportFileName: "ESP32_Data",
                theme: "light2"
            };

            var voltageChart = new CanvasJS.Chart("voltageChart", {
                ...chartOptions,
                title: { text: "Điện Áp Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss",
                    labelAngle: -45
                },
                axisY: { 
                    suffix: " V",
                    interval: 50,
                    minimum: 0,
                    maximum: 300
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
                    color: "#2196F3",
                    dataPoints: []
                }]
            });
            
            var currentChart = new CanvasJS.Chart("currentChart", {
                ...chartOptions,
                title: { text: "Dòng Điện Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss",
                    labelAngle: -45
                },
                axisY: { 
                    suffix: " A",
                    interval: 1,
                    minimum: 0,
                    maximum: 3
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
                    color: "#4CAF50",
                    dataPoints: []
                }]
            });
            
            var powerChart = new CanvasJS.Chart("powerChart", {
                ...chartOptions,
                title: { text: "Công Suất Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss",
                    labelAngle: -45
                },
                axisY: { 
                    suffix: " W",
                    interval: 100,
                    minimum: 0,
                    maximum: 100
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
                    color: "#FF9800",
                    dataPoints: []
                }]
            });

            var energyChart = new CanvasJS.Chart("energyChart", {
                ...chartOptions,
                title: { text: "Điện Năng Tiêu Thụ Theo Thời Gian" },
                axisX: { 
                    title: "Thời Gian",
                    valueFormatString: "HH:mm:ss",
                    labelAngle: -45
                },
                axisY: { 
                    suffix: " kWh",
                    interval: 50,
                    minimum: 0,
		    maximum: 200
                },
                toolTip: {
                    shared: true,
                    content: function(e) {
                        var date = new Date(e.entries[0].dataPoint.x);
                        var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                        date.getMinutes().toString().padStart(2, '0') + ':' + 
                                        date.getSeconds().toString().padStart(2, '0');
                        var content = "<strong>Thời gian:</strong> " + timeString + "<br/>";
                        content += "<strong>Điện năng:</strong> " + e.entries[0].dataPoint.y.toFixed(2) + " kWh";
                        return content;
                    }
                },
                data: [{
                    type: "line",
                    xValueType: "dateTime",
                    yValueFormatString: "#,###.## kWh",
                    showInLegend: false,
                    color: "#9C27B0",
                    dataPoints: []
                }]
            });
            
            // Function to fetch and display data
            function fetchData() {
                var startDate = $("#start_date").val();
                var startTime = $("#start_time").val() + ":00";
                var endDate = $("#end_date").val();
                var endTime = $("#end_time").val() + ":00";
                
                console.log("Fetching data with parameters:", {
                    start_date: startDate,
                    start_time: startTime,
                    end_date: endDate,
                    end_time: endTime
                });
                
                $.ajax({
                    url: "get_historical_data.php",
                    type: "GET",
                    data: {
                        start_date: startDate,
                        start_time: startTime,
                        end_date: endDate,
                        end_time: endTime
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log("Received response:", response);
                        
                        if (response.error) {
                            console.error("Error in response:", response.message);
                            alert("Lỗi: " + response.message);
                            return;
                        }
                        
                        // Update summary
                        $("#total-energy").text(parseFloat(response.summary.total_energy || 0).toFixed(2) + " kWh");
                        $("#avg-voltage").text(parseFloat(response.summary.avg_voltage || 0).toFixed(2) + " V");
                        $("#avg-current").text(parseFloat(response.summary.avg_current || 0).toFixed(2) + " A");
                        $("#avg-power").text(parseFloat(response.summary.avg_power || 0).toFixed(2) + " W");
                        $("#record-count").text(response.summary.record_count);
                        
                        // Prepare data points for charts
                        var voltageDataPoints = [];
                        var currentDataPoints = [];
                        var powerDataPoints = [];
                        var energyDataPoints = [];
                        
                        // Clear table
                        $("#data-table-body").empty();
                        
                        console.log("Processing " + response.data.length + " data points");
                        
                        // Process data
                        response.data.forEach(function(item) {
                            // Add to charts
                            voltageDataPoints.push({ x: item.timestamp, y: parseFloat(item.voltage) });
                            currentDataPoints.push({ x: item.timestamp, y: parseFloat(item.current) });
                            powerDataPoints.push({ x: item.timestamp, y: parseFloat(item.power) });
                            energyDataPoints.push({ x: item.timestamp, y: parseFloat(item.energy_consumed) });
                            
                            // Add to table
                            var date = new Date(item.timestamp);
                            var timeString = date.getHours().toString().padStart(2, '0') + ':' + 
                                            date.getMinutes().toString().padStart(2, '0') + ':' + 
                                            date.getSeconds().toString().padStart(2, '0');
                            
                            $("#data-table-body").append(
                                "<tr>" +
                                "<td>" + date.toLocaleDateString() + " " + timeString + "</td>" +
                                "<td>" + parseFloat(item.voltage).toFixed(2) + "</td>" +
                                "<td>" + parseFloat(item.current).toFixed(2) + "</td>" +
                                "<td>" + parseFloat(item.power).toFixed(2) + "</td>" +
                                "<td>" + parseFloat(item.energy_consumed).toFixed(2) + "</td>" +
                                "</tr>"
                            );
                        });
                        
                        console.log("Chart data points prepared:", {
                            voltage: voltageDataPoints.length,
                            current: currentDataPoints.length,
                            power: powerDataPoints.length,
                            energy: energyDataPoints.length
                        });
                        
                        // Update charts
                        voltageChart.options.data[0].dataPoints = voltageDataPoints;
                        currentChart.options.data[0].dataPoints = currentDataPoints;
                        powerChart.options.data[0].dataPoints = powerDataPoints;
                        energyChart.options.data[0].dataPoints = energyDataPoints;
                        
                        voltageChart.render();
                        currentChart.render();
                        powerChart.render();
                        energyChart.render();
                        
                        console.log("Charts rendered successfully");
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error:", status, error);
                        console.error("Response text:", xhr.responseText);
                        alert("Lỗi khi tải dữ liệu: " + error);
                    }
                });
            }
            
            // Event handler for fetch button
            $("#fetch-data").click(function() {
                console.log("Fetch button clicked");
                fetchData();
            });
            
            // Also add a direct call to fetchData when the page loads
            $(document).ready(function() {
                console.log("Document ready, fetching initial data");
                fetchData();
            });

            // View toggle functionality
            $("#chart-view-btn").click(function() {
                $("#chart-view").addClass("active");
                $("#table-view").removeClass("active");
                $(this).addClass("active");
                $("#table-view-btn").removeClass("active");
            });
            
            $("#table-view-btn").click(function() {
                $("#table-view").addClass("active");
                $("#chart-view").removeClass("active");
                $(this).addClass("active");
                $("#chart-view-btn").removeClass("active");
            });
            
            // Export to CSV functionality
            $("#export-csv").click(function() {
                var csvContent = "data:text/csv;charset=utf-8,";
                csvContent += "Thời Gian,Điện Áp (V),Dòng Điện (A),Công Suất (W),Điện Năng (kWh)\n";
                
                $("#data-table-body tr").each(function() {
                    var rowData = [];
                    $(this).find("td").each(function() {
                        rowData.push($(this).text());
                    });
                    csvContent += rowData.join(",") + "\n";
                });
                
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "esp32_data_" + new Date().toISOString().slice(0,10) + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
            
            // Print data functionality
            $("#print-data").click(function() {
                window.print();
            });
        });
    </script>
</body>
</html> 
