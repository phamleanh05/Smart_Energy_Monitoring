<?php
// This script creates an admin user with a properly hashed password
require 'database.php';

$username = 'admin';
$password = 'admin123'; // Change this to your desired password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$pdo = Database::connect();

// Check if admin user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    echo "Admin user already exists.<br>";
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
    echo "Hashed password: " . $hashed_password . "<br>";
} else {
    // Create admin user
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $hashed_password])) {
        echo "Admin user created successfully.<br>";
        echo "Username: " . $username . "<br>";
        echo "Password: " . $password . "<br>";
        echo "Hashed password: " . $hashed_password . "<br>";
    } else {
        echo "Error creating admin user.";
    }
}

Database::disconnect();
?> 