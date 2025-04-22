<?php
require 'database.php'; // Import class Database

// Thử kết nối
$conn = Database::connect();

if ($conn) {
    echo "Kết nối thành công!";
} else {
    echo "Kết nối thất bại!";
}

// Đóng kết nối
Database::disconnect();
?>
