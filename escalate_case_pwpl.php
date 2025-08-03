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
$escalate_to = isset($_POST['escalate_to']) ? trim($_POST['escalate_to']) : '';
$escalation_reason = isset($_POST['escalation_reason']) ? trim($_POST['escalation_reason']) : '';
$admin_id = $_SESSION['welfare_admin']['id'];

if (empty($employee_id) || empty($escalate_to) || empty($escalation_reason)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Update the feedback record with escalation
    $stmt = $pdo->prepare("
        UPDATE feedback_pwpl 
        SET escalated_to = ?,
            escalated_at = NOW(),
            resolution_progress = 90,
            last_resolution_step = CONCAT('Escalated to ', ?)
        WHERE employee_id = ?
    ");
    $stmt->execute([$escalate_to, $escalate_to, $employee_id]);
    
    // Record the escalation as a step
    $stmt = $pdo->prepare("
        INSERT INTO resolution_steps 
        (employee_id, step_name, step_percentage, notes, admin_id)
        VALUES (?, CONCAT('Escalated to ', ?), 90, ?, ?)
    ");
    $stmt->execute([$employee_id, $escalate_to, $escalation_reason, $admin_id]);
    
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Case escalated successfully']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>