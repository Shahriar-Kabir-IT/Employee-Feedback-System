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

// Validate input
$employee_id = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
$severity_level = isset($_POST['severity_level']) ? trim($_POST['severity_level']) : '';
$issue_type = isset($_POST['issue_type']) ? trim($_POST['issue_type']) : '';
$is_repeated = isset($_POST['is_repeated']) ? (int)$_POST['is_repeated'] : 0;
$initial_notes = isset($_POST['initial_notes']) ? trim($_POST['initial_notes']) : '';
$admin_id = $_SESSION['welfare_admin']['id'];

if (empty($employee_id) || empty($severity_level) || empty($issue_type) || empty($initial_notes)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Update the feedback record with initial assessment
    $stmt = $pdo->prepare("
        UPDATE feedback_pwpl
        SET severity = ?, 
            issue_type = ?,
            is_repeated = ?,
            resolution_progress = 20,
            last_resolution_step = 'Initial Assessment'
        WHERE employee_id = ?
    ");
    $stmt->execute([$severity_level, $issue_type, $is_repeated, $employee_id]);
    
    // Record the initial assessment as a step
    $stmt = $pdo->prepare("
        INSERT INTO resolution_steps 
        (employee_id, step_name, step_percentage, notes, admin_id)
        VALUES (?, 'Initial Assessment', 20, ?, ?)
    ");
    $stmt->execute([$employee_id, $initial_notes, $admin_id]);
    
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Initial assessment recorded successfully']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>