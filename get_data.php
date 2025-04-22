<?php
	require 'database.php';
	header('Content-Type: application/json');

	$pdo = Database::connect();
	
	// Get the latest reading
	$sql_latest = "SELECT device_name, voltage, current, power, energy_consumed, status_read_sensor_pzem,
                   UNIX_TIMESTAMP(created_at) * 1000 AS timestamp,
                   created_at
            FROM energy_readings ORDER BY created_at DESC LIMIT 1";
	$q_latest = $pdo->query($sql_latest);
	$latest_data = $q_latest->fetch(PDO::FETCH_ASSOC);
	
	// Check if ESP32 is actively sending data by looking at recent records
	$current_time = time() * 1000; // Convert to milliseconds
	$data_age = $current_time - ($latest_data['timestamp'] ?? 0);
	
	// Get the last 3 records to check for consistent activity
	$sql_recent = "SELECT UNIX_TIMESTAMP(created_at) * 1000 AS timestamp
                   FROM energy_readings 
                   ORDER BY created_at DESC LIMIT 3";
	$q_recent = $pdo->query($sql_recent);
	$recent_records = $q_recent->fetchAll(PDO::FETCH_ASSOC);
	
	// Check if we have at least 2 recent records within 10 seconds
	$recent_count = 0;
	foreach ($recent_records as $record) {
		if ($current_time - $record['timestamp'] < 10000) { // 10 seconds threshold
			$recent_count++;
		}
	}
	
	// ESP32 is considered active if we have at least 2 recent records
	$is_esp32_active = $recent_count >= 2;
	
	// If ESP32 is not active (not sending new data), set status to FAILED
	if (!$is_esp32_active) {
		$latest_data['status_read_sensor_pzem'] = 'FAILED';
	}
	
	// Get historical data for charts (last 60 readings instead of 100 for faster updates)
	$sql_history = "SELECT voltage, current, 
                   UNIX_TIMESTAMP(created_at) * 1000 AS timestamp
            FROM energy_readings ORDER BY created_at DESC LIMIT 60";
	$q_history = $pdo->query($sql_history);
	$history_data = $q_history->fetchAll(PDO::FETCH_ASSOC);
	
	// Reverse the history data to show oldest to newest
	$history_data = array_reverse($history_data);
	
	// Combine the data
	$response = [
		"latest" => $latest_data,
		"history" => $history_data,
		"is_esp32_active" => $is_esp32_active,
		"recent_count" => $recent_count
	];
	
	Database::disconnect();

	echo json_encode($response);
?>
