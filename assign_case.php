<?php
session_start();
require_once 'db_config.php';

// Check if admin is logged in
if (!isset($_SESSION['welfare_admin'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$employee_id = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';

if (empty($employee_id)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing employee ID']);
    exit;
}

$admin_id = $_SESSION['welfare_admin']['id'];

try {
    // Check if case is already assigned
    $stmt = $pdo->prepare("SELECT assigned_admin_id FROM feedback_abm WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($case && $case['assigned_admin_id'] !== null) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'This case is already assigned to another admin']);
        exit;
    }
    
    // Assign case
    $stmt = $pdo->prepare("UPDATE feedback_abm SET assigned_admin_id = ? WHERE employee_id = ?");
    $stmt->execute([$admin_id, $employee_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}