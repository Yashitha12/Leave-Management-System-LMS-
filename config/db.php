<?php
// config/db.php
$dsn = "mysql:host=localhost;dbname=lms_db;charset=utf8mb4";
$user = "root";
$pass = ""; // XAMPP default; change as needed

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (PDOException $e) {
  exit("DB connection failed: " . $e->getMessage());
}
