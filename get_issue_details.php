<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['welfare_admin']) || $_SESSION['welfare_admin']['username'] !== 'superadmin') {
    die("Unauthorized access");
}

$issue_id = $_GET['id'] ?? 0;

try {
    // Get issue details
    $issueStmt = $pdo->prepare("
        SELECT i.*, e.full_name as employee_name, a.full_name as admin_name
        FROM issues i
        JOIN employees e ON i.employee_id = e.id
        JOIN welfare_admins a ON i.admin_id = a.id
        WHERE i.id = ?
    ");
    $issueStmt->execute([$issue_id]);
    $issue = $issueStmt->fetch(PDO::FETCH_ASSOC);

    if (!$issue) {
        die("Issue not found");
    }

    // Get all notes for this issue
    $notesStmt = $pdo->prepare("
        SELECT n.*, a.full_name as admin_name 
        FROM issue_notes n
        LEFT JOIN welfare_admins a ON n.admin_id = a.id
        WHERE n.issue_id = ?
        ORDER BY n.created_at
    ");
    $notesStmt->execute([$issue_id]);
    $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<div class="issue-details">
    <div class="detail-row">
        <div class="detail-label">Issue ID:</div>
        <div class="detail-value"><?php echo $issue['id']; ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Employee:</div>
        <div class="detail-value"><?php echo htmlspecialchars($issue['employee_name']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Assigned Admin:</div>
        <div class="detail-value"><?php echo htmlspecialchars($issue['admin_name']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Category:</div>
        <div class="detail-value"><?php echo htmlspecialchars($issue['category']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Description:</div>
        <div class="detail-value"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Status:</div>
        <div class="detail-value">
            <?php if ($issue['status'] === 'pending'): ?>
                <span class="status-pending">Pending</span>
            <?php else: ?>
                <span class="status-resolved">Resolved</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Created:</div>
        <div class="detail-value"><?php echo date('d M Y H:i', strtotime($issue['created_at'])); ?></div>
    </div>
    <?php if ($issue['status'] === 'resolved'): ?>
    <div class="detail-row">
        <div class="detail-label">Resolved:</div>
        <div class="detail-value"><?php echo date('d M Y H:i', strtotime($issue['resolved_at'])); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Resolution:</div>
        <div class="detail-value"><?php echo nl2br(htmlspecialchars($issue['resolution'])); ?></div>
    </div>
    <?php endif; ?>
</div>

<div class="notes-container">
    <h3>Notes (<?php echo count($notes); ?>)</h3>
    
    <?php if (empty($notes)): ?>
        <p>No notes available for this issue.</p>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
        <div class="note">
            <div class="note-header">
                <div class="note-admin">
                    <?php echo $note['is_admin_note'] ? htmlspecialchars($note['admin_name']) : 'Employee'; ?>
                </div>
                <div class="note-date">
                    <?php echo date('d M Y H:i', strtotime($note['created_at'])); ?>
                </div>
            </div>
            <div class="note-content">
                <?php echo nl2br(htmlspecialchars($note['note'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>