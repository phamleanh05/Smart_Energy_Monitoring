<?php
require 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    // Test database connection
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Database connection successful!</p>";
    
    // Check if the table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'energy_readings'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color:green;'>Table 'energy_readings' exists.</p>";
        
        // Get table structure
        $sql = "DESCRIBE energy_readings";
        $result = $pdo->query($sql);
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Table Structure:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Get record count
        $sql = "SELECT COUNT(*) as count FROM energy_readings";
        $result = $pdo->query($sql);
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Total records: " . $count . "</p>";
        
        // Get sample data
        $sql = "SELECT * FROM energy_readings ORDER BY created_at DESC LIMIT 5";
        $result = $pdo->query($sql);
        $sampleData = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Sample Data (5 most recent records):</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Device Name</th><th>Voltage</th><th>Current</th><th>Power</th><th>Energy</th><th>Created At</th></tr>";
        foreach ($sampleData as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['device_name'] . "</td>";
            echo "<td>" . $row['voltage'] . "</td>";
            echo "<td>" . $row['current'] . "</td>";
            echo "<td>" . $row['power'] . "</td>";
            echo "<td>" . $row['energy_consumed'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>Table 'energy_readings' does not exist.</p>";
        
        // Create the table
        echo "<p>Creating table 'energy_readings'...</p>";
        
        $sql = "CREATE TABLE energy_readings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_name VARCHAR(50),
            voltage FLOAT,
            current FLOAT,
            power FLOAT,
            energy_consumed FLOAT,
            status_read_sensor_pzem VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green;'>Table created successfully.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}

Database::disconnect();

// Add links to other pages
echo "<h2>Links:</h2>";
echo "<ul>";
echo "<li><a href='historical_data.php'>Go to Historical Data Page</a></li>";
echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
echo "<li><a href='test_historical_data.php'>Test Historical Data</a></li>";
echo "</ul>";
?> 