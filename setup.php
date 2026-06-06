<?php
// ONE-TIME SETUP FILE - DELETE AFTER RUNNING
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$dbname   = getenv('DB_NAME')     ?: 'smart_vehicle_db';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: '';
$port     = getenv('DB_PORT')     ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS php_sessions (
        session_id VARCHAR(128) NOT NULL PRIMARY KEY,
        session_data TEXT NOT NULL,
        session_expiry INT(11) NOT NULL
    )");

    echo "<h2 style='color:green'>✅ php_sessions table created successfully!</h2>";
    echo "<p>Now delete this file (setup.php) from your project for security.</p>";

} catch(PDOException $e) {
    echo "<h2 style='color:red'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
