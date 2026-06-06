<?php
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$dbname   = getenv('DB_NAME')     ?: 'smart_vehicle_db';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: '';
$port     = getenv('DB_PORT')     ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT         => true,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    ]);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>