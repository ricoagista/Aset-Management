<?php
// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

$servername = $_ENV['DB_HOST'] ?? "127.0.0.1";
$username = $_ENV['DB_USER'] ?? "rico";
$password = $_ENV['DB_PASS'] ?? "sriwijaya1";
$dbname = $_ENV['DB_NAME'] ?? "aset_management";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi gagal: " . $e->getMessage());
}
?>
