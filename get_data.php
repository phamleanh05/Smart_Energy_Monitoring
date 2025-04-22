<?php
	require 'database.php';
	header('Content-Type: application/json');

	// Configuration parameters - adjust these to control timing
	$data_freshness_threshold = 10000; // 10 seconds - how old data can be before considered stale
	$min_recent_records = 2; // Minimum number of recent records needed to consider ESP32 active
	
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
	
	// Check if we have enough recent records within the freshness threshold
	$recent_count = 0;
	foreach ($recent_records as $record) {
		if ($current_time - $record['timestamp'] < $data_freshness_threshold) {
			$recent_count++;
		}
	}
	
	// ESP32 is considered active if we have enough recent records
	$is_esp32_active = $recent_count >= $min_recent_records;
	
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
		"recent_count" => $recent_count,
		"config" => [
			"data_freshness_threshold" => $data_freshness_threshold,
			"min_recent_records" => $min_recent_records
		]
	];
	
	Database::disconnect();

	echo json_encode($response);
?>
