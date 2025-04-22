<?php
	require 'database.php';
	header('Content-Type: application/json');

	$pdo = Database::connect();
	
	// Get the latest reading
	$sql_latest = "SELECT device_name, voltage, current, power, energy_consumed, 
                   UNIX_TIMESTAMP(created_at) * 1000 AS timestamp
            FROM energy_readings ORDER BY created_at DESC LIMIT 1";
	$q_latest = $pdo->query($sql_latest);
	$latest_data = $q_latest->fetch(PDO::FETCH_ASSOC);
	
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
		"history" => $history_data
	];
	
	Database::disconnect();

	echo json_encode($response);
?>
