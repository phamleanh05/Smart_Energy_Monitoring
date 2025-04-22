<?php
require 'database.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request parameters
error_log("Historical data request - Start Date: " . (isset($_GET['start_date']) ? $_GET['start_date'] : 'not set') . 
          ", End Date: " . (isset($_GET['end_date']) ? $_GET['end_date'] : 'not set') . 
          ", Start Time: " . (isset($_GET['start_time']) ? $_GET['start_time'] : 'not set') . 
          ", End Time: " . (isset($_GET['end_time']) ? $_GET['end_time'] : 'not set'));

// Get parameters from request
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 day'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$startTime = isset($_GET['start_time']) ? $_GET['start_time'] : '00:00:00';
$endTime = isset($_GET['end_time']) ? $_GET['end_time'] : '23:59:59';

// Format dates for SQL query
$startDateTime = $startDate . ' ' . $startTime;
$endDateTime = $endDate . ' ' . $endTime;

error_log("Formatted date range: " . $startDateTime . " to " . $endDateTime);

try {
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check if the table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'energy_readings'");
    if ($tableCheck->rowCount() == 0) {
        throw new Exception("Table 'energy_readings' does not exist in the database");
    }
    
    // Get data for the specified time period
    $sql = "SELECT device_name, voltage, current, power, energy_consumed, 
                   UNIX_TIMESTAMP(created_at) * 1000 AS timestamp,
                   created_at
            FROM energy_readings 
            WHERE created_at BETWEEN ? AND ?
            ORDER BY created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Retrieved " . count($data) . " records from database");
    
    // Calculate total energy consumption for the period
    $sqlTotal = "SELECT 
                    MAX(energy_consumed) - MIN(energy_consumed) AS total_energy,
                    AVG(voltage) AS avg_voltage,
                    AVG(current) AS avg_current,
                    AVG(power) AS avg_power,
                    MAX(energy_consumed) AS max_energy,
                    MIN(energy_consumed) AS min_energy,
                    COUNT(*) AS record_count
                 FROM energy_readings 
                 WHERE created_at BETWEEN ? AND ?";
    
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([$startDateTime, $endDateTime]);
    $summary = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    error_log("Summary data: " . json_encode($summary));
    
    // Prepare response
    $response = [
        "data" => $data,
        "summary" => $summary,
        "period" => [
            "start" => $startDateTime,
            "end" => $endDateTime
        ]
    ];
    
    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Database error in get_historical_data.php: " . $e->getMessage());
    echo json_encode([
        "error" => true,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_historical_data.php: " . $e->getMessage());
    echo json_encode([
        "error" => true,
        "message" => "Error: " . $e->getMessage()
    ]);
}

Database::disconnect();
?> 