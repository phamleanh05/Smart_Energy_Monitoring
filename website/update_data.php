<?php
require 'database.php';

// Lấy dữ liệu từ JSON
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    // Lấy giá trị từ JSON
    $device_name = $data['device_name'];
    $voltage = $data['voltage'];
    $current = $data['current'];
    $power = $data['power'];
    $energy_consumed = $data['energy_consumed'];
    $status_read_sensor_pzem = $data['status_read_sensor_pzem'];
	$created_at = date('Y-m-d H:i:s');

    // Kết nối database
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cập nhật dữ liệu
    $sql = "INSERT INTO energy_readings (device_name, voltage, current, power, energy_consumed, status_read_sensor_pzem, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $q = $pdo->prepare($sql);
    $q->execute([$device_name, $voltage, $current, $power, $energy_consumed, $status_read_sensor_pzem, $created_at]);

    Database::disconnect();

    echo json_encode(["status" => "success", "message" => "Data updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid JSON or no data received"]);
}
?>
