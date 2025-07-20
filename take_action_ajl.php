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
$action_step = isset($_POST['action_step']) ? trim($_POST['action_step']) : '';
$action_notes = isset($_POST['action_notes']) ? trim($_POST['action_notes']) : '';
$admin_id = $_SESSION['welfare_admin']['id'];

if (empty($employee_id) || empty($action_step)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Determine percentage based on action step
$percentage_map = [
    'Talked with the employee' => 30,
    'Finding Problem' => 40,
    'Taking action' => 60,
    'Give Support' => 70,
    'Followup' => 75
];

$step_percentage = $percentage_map[$action_step] ?? 0;

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Record the step
    $stmt = $pdo->prepare("
        INSERT INTO resolution_steps 
        (employee_id, step_name, step_percentage, notes, admin_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$employee_id, $action_step, $step_percentage, $action_notes, $admin_id]);
    
    // Update the feedback record - removed the auto-resolve at 75%
    $stmt = $pdo->prepare("
        UPDATE feedback_ajl
        SET resolution_progress = ?, 
            last_resolution_step = ?
        WHERE employee_id = ?
    ");
    $stmt->execute([$step_percentage, $action_step, $employee_id]);
    
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>