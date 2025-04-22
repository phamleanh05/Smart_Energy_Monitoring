<!DOCTYPE html>
<html lang="vi">
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
</head>
<body>
    <h2>PZEM Sensor Data</h2>
    <div class="container">
        <div class="sensor-box"><h3>Voltage</h3><p id="voltage">Loading...</p></div>
        <div class="sensor-box"><h3>Current</h3><p id="current">Loading...</p></div>
        <div class="sensor-box"><h3>Power</h3><p id="power">Loading...</p></div>
        <div class="sensor-box"><h3>Energy</h3><p id="energy">Loading...</p></div>
    </div>
    <div class="container">
        <div class="chart-container">
            <h3>Voltage Chart</h3>
            <canvas id="voltageChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Current Chart</h3>
            <canvas id="currentChart"></canvas>
        </div>
    </div>
    <script>
        let voltageCtx = document.getElementById('voltageChart').getContext('2d');
        let currentCtx = document.getElementById('currentChart').getContext('2d');
        
        let voltageChart = new Chart(voltageCtx, {
			type: 'line',
			data: {
				labels: [],
				datasets: [{
					label: "Voltage (V)",
					data: [],
					borderColor: "blue",
					borderWidth: 2,
					fill: false,
					pointRadius: 0,
					pointStyle: 'line'
				}]
			},
			options: {
				responsive: true,
				scales: {
					x: { 
						type: 'category',
						ticks: { 
							maxRotation: 0,
							autoSkip: true,
							maxTicksLimit: 10
						}
					},
					y: { beginAtZero: true }
				}
			}
		});

		let currentChart = new Chart(currentCtx, {
			type: 'line',
			data: {
				labels: [],
				datasets: [{
					label: "Current (A)",
					data: [],
					borderColor: "red",
					borderWidth: 2,
					fill: false,
					pointRadius: 0,
					pointStyle: 'line'
				}]
			},
			options: {
				responsive: true,
				scales: {
					x: { 
						type: 'category',
						ticks: { 
							maxRotation: 0,
							autoSkip: true,
							maxTicksLimit: 10
						}
					},
					y: { beginAtZero: true }
				}
			}
		});
        
		let timeCounter = 0;  
		let startTime = new Date(); 

		function fetchData() {
			$.getJSON("get_data.php", function(data) {
				if (!data || isNaN(data.voltage) || isNaN(data.current)) {
					console.error("Dữ liệu không hợp lệ", data);
					return;
				}

				$("#voltage").text(data.voltage + " V");
				$("#current").text(data.current + " A");
				$("#power").text(data.power + " W");
				$("#energy").text(data.energy_consumed + " kWh");

				timeCounter++;
				let currentTime = new Date();
				let elapsedSeconds = Math.floor((currentTime - startTime) / 1000);

				let timeLabel = (elapsedSeconds % 5 === 0) ? currentTime.toLocaleTimeString() : "";

				voltageChart.data.labels.push(timeLabel);
				currentChart.data.labels.push(timeLabel);
				voltageChart.data.datasets[0].data.push(data.voltage);
				currentChart.data.datasets[0].data.push(data.current);

				if (voltageChart.data.labels.length > 20) {
					voltageChart.data.labels = voltageChart.data.labels.slice(-20);
					voltageChart.data.datasets[0].data = voltageChart.data.datasets[0].data.slice(-20);
				}
				if (currentChart.data.labels.length > 20) {
					currentChart.data.labels = currentChart.data.labels.slice(-20);
					currentChart.data.datasets[0].data = currentChart.data.datasets[0].data.slice(-20);
				}
				
				voltageChart.update();
				currentChart.update();

				console.log("Voltage Labels:", voltageChart.data.labels);
				console.log("Voltage Data:", voltageChart.data.datasets[0].data);
			}).fail(function(jqxhr, textStatus, error) {
				console.error("Lỗi khi lấy dữ liệu:", textStatus, error);
			});
		}

		setInterval(fetchData, 1000);
		fetchData();


    </script>
</body>
</html>
