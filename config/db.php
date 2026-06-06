<?php
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$dbname   = getenv('DB_NAME')     ?: 'smart_vehicle_db';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: '';
$port     = getenv('DB_PORT')     ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    ]);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Only register the DB session handler if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);

    session_set_save_handler(
        function($path, $name) { return true; },
        function() { return true; },
        function($id) use ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT session_data FROM php_sessions WHERE session_id = ? AND session_expiry > ?");
                $stmt->execute([$id, time()]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row['session_data'] : '';
            } catch(Exception $e) { return ''; }
        },
        function($id, $data) use ($pdo) {
            try {
                $expiry = time() + 86400;
                $stmt = $pdo->prepare("REPLACE INTO php_sessions (session_id, session_data, session_expiry) VALUES (?, ?, ?)");
                return $stmt->execute([$id, $data, $expiry]);
            } catch(Exception $e) { return false; }
        },
        function($id) use ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM php_sessions WHERE session_id = ?");
                return $stmt->execute([$id]);
            } catch(Exception $e) { return false; }
        },
        function($maxlifetime) use ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM php_sessions WHERE session_expiry < ?");
                return $stmt->execute([time()]);
            } catch(Exception $e) { return false; }
        }
    );
    // Note: each PHP file calls session_start() itself
}
?>