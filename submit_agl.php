<?php
date_default_timezone_set('Asia/Dhaka');
require_once 'db_config.php';

// Validate and sanitize input
$mood = isset($_POST['mood']) ? strtolower(trim($_POST['mood'])) : '';
$employee_id = isset($_POST['emp_id']) ? trim($_POST['emp_id']) : '';

if (!in_array($mood, ['happy', 'sad']) || empty($employee_id)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Check if this is a mood change from sad to happy
    $stmt = $pdo->prepare("SELECT mood, resolution_progress FROM feedback_agl WHERE employee_id = ? ORDER BY timestamp DESC LIMIT 1");
    $stmt->execute([$employee_id]);
    $previous = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $resolved = 0;
    $resolution_progress = 0;
    
    if ($previous && $previous['mood'] === 'sad' && $mood === 'happy') {
        // If previous mood was sad and now happy, mark as resolved if progress was at least 75%
        if ($previous['resolution_progress'] >= 75) {
            $resolved = 1;
            $resolution_progress = 100;
        }
    }
    
    // Insert or update the feedback
    $stmt = $pdo->prepare("
        INSERT INTO feedback_agl (employee_id, mood, timestamp, resolved, resolution_progress, resolution_timestamp) 
        VALUES (:employee_id, :mood, NOW(), :resolved, :resolution_progress, IF(:resolved = 1, NOW(), NULL))
        ON DUPLICATE KEY UPDATE 
        mood = VALUES(mood),
        timestamp = VALUES(timestamp),
        resolved = VALUES(resolved),
        resolution_progress = VALUES(resolution_progress),
        resolution_timestamp = VALUES(resolution_timestamp)
    ");
    
    $stmt->execute([
        ':employee_id' => $employee_id,
        ':mood' => $mood,
        ':resolved' => $resolved,
        ':resolution_progress' => $resolution_progress
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>