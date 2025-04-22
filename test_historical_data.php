<?php
// This script tests the historical data functionality by making a direct request to get_historical_data.php
// and displaying the results in a readable format.

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Historical Data Test</h1>";

// Default date range (last 24 hours)
$startDate = date('Y-m-d', strtotime('-1 day'));
$endDate = date('Y-m-d');
$startTime = '00:00:00';
$endTime = '23:59:59';

// Build the URL with parameters
$url = "get_historical_data.php?start_date=" . urlencode($startDate) . 
       "&start_time=" . urlencode($startTime) . 
       "&end_date=" . urlencode($endDate) . 
       "&end_time=" . urlencode($endTime);

echo "<p>Testing URL: " . htmlspecialchars($url) . "</p>";

// Make the request
$response = file_get_contents($url);

if ($response === FALSE) {
    echo "<p>Error: Could not retrieve data from get_historical_data.php</p>";
    echo "<p>Error details: " . error_get_last()['message'] . "</p>";
} else {
    // Decode the JSON response
    $data = json_decode($response, true);
    
    if ($data === NULL) {
        echo "<p>Error: Could not decode JSON response</p>";
        echo "<p>Raw response: " . htmlspecialchars($response) . "</p>";
    } else {
        echo "<h2>Response Summary</h2>";
        
        if (isset($data['error']) && $data['error']) {
            echo "<p>Error in response: " . htmlspecialchars($data['message']) . "</p>";
        } else {
            echo "<p>Period: " . htmlspecialchars($data['period']['start']) . " to " . htmlspecialchars($data['period']['end']) . "</p>";
            
            echo "<h3>Summary Data</h3>";
            echo "<ul>";
            echo "<li>Total Energy: " . number_format($data['summary']['total_energy'], 2) . " kWh</li>";
            echo "<li>Average Voltage: " . number_format($data['summary']['avg_voltage'], 2) . " V</li>";
            echo "<li>Average Current: " . number_format($data['summary']['avg_current'], 2) . " A</li>";
            echo "<li>Average Power: " . number_format($data['summary']['avg_power'], 2) . " W</li>";
            echo "<li>Record Count: " . $data['summary']['record_count'] . "</li>";
            echo "</ul>";
            
            echo "<h3>Data Points (" . count($data['data']) . " records)</h3>";
            
            if (count($data['data']) > 0) {
                echo "<table border='1'>";
                echo "<tr><th>Time</th><th>Voltage (V)</th><th>Current (A)</th><th>Power (W)</th><th>Energy (kWh)</th></tr>";
                
                foreach ($data['data'] as $row) {
                    $date = new DateTime($row['created_at']);
                    echo "<tr>";
                    echo "<td>" . $date->format('Y-m-d H:i:s') . "</td>";
                    echo "<td>" . number_format($row['voltage'], 2) . "</td>";
                    echo "<td>" . number_format($row['current'], 2) . "</td>";
                    echo "<td>" . number_format($row['power'], 2) . "</td>";
                    echo "<td>" . number_format($row['energy_consumed'], 2) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No data points found for the specified time range.</p>";
            }
        }
    }
}

// Add a link to go back to the historical data page
echo "<p><a href='historical_data.php'>Go to Historical Data Page</a></p>";
?> 