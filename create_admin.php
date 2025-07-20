<?php
require_once 'db_config.php';

// This is a one-time script to create the initial admin user
// Remove or secure this file after use

$username = 'pwpl';
$password = '1234'; // Change this to a strong password
$full_name = 'ADMIN PWPL';
$email = 'welfare7@company.com';

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM welfare_admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        die('Admin user already exists');
    }
    
    // Create admin
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO welfare_admins 
        (username, password_hash, full_name, email) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$username, $password_hash, $full_name, $email]);
    
    echo 'Admin user created successfully. Username: ' . $username . ', Password: ' . $password;
    
} catch (PDOException $e) {
    die('Error creating admin: ' . $e->getMessage());
}
?>