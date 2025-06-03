<?php
$host = "localhost";
$dbname = "your_db_name";
$user = "root";
$pass = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
