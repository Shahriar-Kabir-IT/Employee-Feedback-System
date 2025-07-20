<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['welfare_admin'])) {
    die("Unauthorized access");
}

$employee_id = $_GET['employee_id'] ?? '';

try {
    // Determine which feedback table to use by checking which one contains this employee
    $tables = ['feedback_abm', 'feedback_agl', 'feedback_ajl', 'feedback_pwpl'];
    $feedbackTable = null;
    $feedbackData = null;
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        if ($stmt->rowCount() > 0) {
            $feedbackTable = $table;
            $feedbackData = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
        }
    }

    if (!$feedbackData) {
        die("Case not found");
    }

    // Get all resolution steps for this employee
    $stepsStmt = $pdo->prepare("
        SELECT rs.*, wa.full_name as admin_name 
        FROM resolution_steps rs
        LEFT JOIN welfare_admins wa ON rs.admin_id = wa.id
        WHERE rs.employee_id = ?
        ORDER BY rs.timestamp
    ");
    $stepsStmt->execute([$employee_id]);
    $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get assigned admin details
    $adminStmt = $pdo->prepare("SELECT full_name FROM welfare_admins WHERE id = ?");
    $adminStmt->execute([$feedbackData['assigned_admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<div class="case-details">
    <div class="detail-row">
        <div class="detail-label">Employee ID:</div>
        <div class="detail-value"><?php echo htmlspecialchars($feedbackData['employee_id']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Status:</div>
        <div class="detail-value">
            <?php if ($feedbackData['resolved']): ?>
                <span class="status-resolved">Resolved</span>
            <?php else: ?>
                <span class="status-pending">Pending (<?php echo $feedbackData['resolution_progress'] ?? 0; ?>%)</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Assigned Admin:</div>
        <div class="detail-value"><?php echo htmlspecialchars($admin['full_name'] ?? 'Not assigned'); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Last Step:</div>
        <div class="detail-value"><?php echo htmlspecialchars($feedbackData['last_resolution_step'] ?? 'Not started'); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Reported:</div>
        <div class="detail-value"><?php echo date('d M Y H:i', strtotime($feedbackData['timestamp'])); ?></div>
    </div>
    <?php if ($feedbackData['resolved']): ?>
    <div class="detail-row">
        <div class="detail-label">Resolved:</div>
        <div class="detail-value"><?php echo date('d M Y H:i', strtotime($feedbackData['resolution_timestamp'])); ?></div>
    </div>
    <?php endif; ?>
</div>

<div class="notes-container">
    <h3>Resolution Steps (<?php echo count($steps); ?>)</h3>
    
    <?php if (empty($steps)): ?>
        <p>No resolution steps recorded.</p>
    <?php else: ?>
        <?php foreach ($steps as $step): ?>
        <div class="note">
            <div class="note-header">
                <div class="note-admin">
                    <?php echo $step['admin_id'] ? htmlspecialchars($step['admin_name']) : 'System'; ?>
                </div>
                <div class="note-date">
                    <?php echo date('d M Y H:i', strtotime($step['timestamp'])); ?>
                </div>
            </div>
            <div class="note-content">
                <p><strong><?php echo htmlspecialchars($step['step_name']); ?></strong> (<?php echo $step['step_percentage']; ?>%)</p>
                <?php if (!empty($step['notes'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($step['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>