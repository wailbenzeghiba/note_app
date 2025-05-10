<?php
$host = 'localhost';
$port = 3307;
$db   = 'notes_app';
$user = 'root';
$pass = ''; // default for WAMP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("DB connection failed: " . $e->getMessage());
}
?>
