<?php
// php/config.php - Konfigurasi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'webdev_orders';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>