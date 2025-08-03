<?php
session_start();
require_once 'db_config.php';

// Check if admin is logged in
if (!isset($_SESSION['welfare_admin'])) {
    header('Location: welfare_login.php');
    exit;
}

// Get admin info
$admin_id = $_SESSION['welfare_admin']['id'];
$admin_name = $_SESSION['welfare_admin']['full_name'];

// Determine active tab from URL or default to dashboard
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Handle assign to me action
if (isset($_POST['assign_to_me'])) {
    $employee_id = $_POST['employee_id'];

    try {
        $stmt = $pdo->prepare("UPDATE feedback_agl SET assigned_admin_id = ? WHERE employee_id = ?");
        $stmt->execute([$admin_id, $employee_id]);

        // Record the assignment as a first step
        $stmt = $pdo->prepare("
            INSERT INTO resolution_steps 
            (employee_id, step_name, step_percentage, notes, admin_id)
            VALUES (?, 'Case Assigned', 10, 'Case assigned to admin', ?)
        ");
        $stmt->execute([$employee_id, $admin_id]);

        // Update the feedback record with initial progress
        $stmt = $pdo->prepare("
            UPDATE feedback_agl
            SET resolution_progress = 10, 
                last_resolution_step = 'Case Assigned'
            WHERE employee_id = ?
        ");
        $stmt->execute([$employee_id]);

        header('Location: welfare_dashboard_agl.php?tab=' . $active_tab);
        exit;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Get stats for dashboard
try {
    // Today's counts
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN mood = 'happy' THEN 1 ELSE 0 END) as happy_count,
            SUM(CASE WHEN mood = 'sad' THEN 1 ELSE 0 END) as sad_count,
            SUM(CASE WHEN resolved = 1 AND DATE(resolution_timestamp) = CURDATE() 
                     AND assigned_admin_id = ? THEN 1 ELSE 0 END) as resolved_today
        FROM feedback_agl
        WHERE DATE(timestamp) = CURDATE()
    ");
    $stmt->execute([$admin_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Current admin stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN f.resolved = 1 THEN f.employee_id END) as resolved_count,
            COUNT(DISTINCT f.employee_id) as total_cases
        FROM welfare_admins wa
        LEFT JOIN feedback_agl f ON wa.id = f.assigned_admin_id
        WHERE wa.id = ?
        GROUP BY wa.id
    ");
    $stmt->execute([$admin_id]);
    $current_admin_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Active sad cases - only show cases assigned to current admin or unassigned cases
    $stmt = $pdo->prepare("
        SELECT f.*, 
               (SELECT MAX(step_percentage) FROM resolution_steps WHERE employee_id = f.employee_id) as max_step,
               (SELECT admin_id FROM resolution_steps WHERE employee_id = f.employee_id ORDER BY timestamp LIMIT 1) as first_admin_id
        FROM feedback_agl f
        WHERE f.mood = 'sad' AND (f.resolved = 0 OR f.resolved IS NULL)
        ORDER BY f.timestamp DESC
    ");
    $stmt->execute();
    $all_sad_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter cases - only show cases where current admin is the first to take action or unassigned cases
    $sad_cases = array_filter($all_sad_cases, function ($case) use ($admin_id) {
        return $case['first_admin_id'] === null || $case['first_admin_id'] == $admin_id;
    });

    // All happy employees (today)
    $stmt = $pdo->prepare("
        SELECT * FROM feedback_agl 
        WHERE mood = 'happy' AND DATE(timestamp) = CURDATE()
        ORDER BY timestamp DESC
    ");
    $stmt->execute();
    $happy_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // All sad employees (today) - only show cases assigned to current admin or unassigned
    $stmt = $pdo->prepare("
        SELECT f.*,
               (SELECT admin_id FROM resolution_steps WHERE employee_id = f.employee_id ORDER BY timestamp LIMIT 1) as first_admin_id
        FROM feedback_agl f
        WHERE f.mood = 'sad' AND DATE(f.timestamp) = CURDATE()
        ORDER BY f.timestamp DESC
    ");
    $stmt->execute();
    $all_sad_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sad_employees = array_filter($all_sad_employees, function ($employee) use ($admin_id) {
        return $employee['first_admin_id'] === null || $employee['first_admin_id'] == $admin_id;
    });

    // Resolved cases - show all resolved cases for current admin
    $stmt = $pdo->prepare("
        SELECT f.*, 
               (SELECT GROUP_CONCAT(CONCAT(step_name, ': ', notes) SEPARATOR '\n\n') 
                FROM resolution_steps 
                WHERE employee_id = f.employee_id) as resolution_details,
               (SELECT full_name FROM welfare_admins WHERE id = f.assigned_admin_id) as resolved_by,
               severity, issue_type, is_repeated, escalated_to
        FROM feedback_agl f
        WHERE f.resolved = 1 AND f.assigned_admin_id = ?
        ORDER BY f.resolution_timestamp DESC
    ");
    $stmt->execute([$admin_id]);
    $resolved_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all issue types for dropdown
    $issue_types = $pdo->query("SELECT * FROM issue_types")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Welfare Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2d8fd4;
            --accent-blue: #1ea2fa;
            --success-green: #28a745;
            --warning-orange: #ffc107;
            --error-red: #dc3545;
            --hover-green: #0cdf17;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #343a40;
            --dark-text: #212529;
            --sidebar-bg: #2c3e50;
            --sidebar-active: #34495e;
            --border-radius: 6px;
            --border-radius-large: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.2s;
            --critical: #dc3545;
            --high: #fd7e14;
            --moderate: #ffc107;
            --low: #28a745;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-text);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            transition: width 0.3s;
            flex-shrink: 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .admin-info {
            font-size: 14px;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: background-color var(--transition-fast);
        }

        .menu-link:hover,
        .menu-link.active {
            background-color: var(--sidebar-active);
        }

        .menu-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            overflow-x: hidden;
        }

        header {
            background-color: var(--primary-blue);
            color: white;
            padding: 15px 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background-color: var(--error-red);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
        }

        .happy-card {
            border-top: 4px solid var(--success-green);
        }

        .sad-card {
            border-top: 4px solid var(--error-red);
        }

        .resolved-card {
            border-top: 4px solid var(--accent-blue);
        }

        .admin-card {
            border-top: 4px solid var(--warning-orange);
        }

        .section {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--medium-gray);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--medium-gray);
        }

        th {
            background-color: var(--primary-blue);
            color: white;
            font-weight: 500;
        }

        tr:nth-child(even) {
            background-color: var(--light-gray);
        }

        tr:hover {
            background-color: rgba(45, 143, 212, 0.1);
        }

        .progress-container {
            width: 100%;
            height: 20px;
            background-color: var(--medium-gray);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary-blue);
            border-radius: var(--border-radius);
            transition: width 0.3s ease;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color var(--transition-fast);
            font-size: 14px;
        }

        .primary-btn {
            background-color: var(--primary-blue);
            color: white;
        }

        .primary-btn:hover {
            background-color: #1a7bb9;
        }

        .success-btn {
            background-color: var(--success-green);
            color: white;
        }

        .success-btn:hover {
            background-color: #218838;
        }

        .warning-btn {
            background-color: var(--warning-orange);
            color: var(--dark-text);
        }

        .warning-btn:hover {
            background-color: #e0a800;
        }

        .danger-btn {
            background-color: var(--error-red);
            color: white;
        }

        .danger-btn:hover {
            background-color: #c82333;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-title {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            border: none;
            transition: background-color var(--transition-fast);
        }

        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background-color: #1a7bb9;
        }

        .btn-secondary {
            background-color: var(--medium-gray);
            color: var(--dark-text);
        }

        .btn-secondary:hover {
            background-color: #d1d7dc;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-critical {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #721c24;
        }

        .badge-high {
            background-color: #ffe3cd;
            color: #a04100;
            border: 1px solid #a04100;
        }

        .badge-moderate {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #856404;
        }

        .badge-low {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #155724;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .resolution-details {
            white-space: pre-line;
            padding: 10px;
            background-color: var(--light-gray);
            border-radius: var(--border-radius);
            margin-top: 10px;
        }

        .issue-info {
            margin-top: 10px;
            padding: 10px;
            background-color: var(--light-gray);
            border-radius: var(--border-radius);
        }

        .issue-info p {
            margin-bottom: 5px;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar-header,
            .menu-text {
                display: none;
            }

            .menu-link {
                justify-content: center;
                padding: 12px 0;
            }

            .menu-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 10px 0;
            }

            .sidebar-menu {
                display: flex;
                overflow-x: auto;
            }

            .menu-item {
                margin-bottom: 0;
                margin-right: 10px;
            }

            .menu-link {
                padding: 10px 15px;
                white-space: nowrap;
            }

            .menu-text {
                display: inline;
                margin-left: 10px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Welfare Admin AGL</div>
            <div class="admin-info"><?php echo htmlspecialchars($admin_name); ?></div>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="?tab=dashboard" class="menu-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="?tab=happy" class="menu-link <?php echo $active_tab === 'happy' ? 'active' : ''; ?>">
                    <i class="fas fa-smile"></i>
                    <span class="menu-text">Happy Employees</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="?tab=sad" class="menu-link <?php echo $active_tab === 'sad' ? 'active' : ''; ?>">
                    <i class="fas fa-frown"></i>
                    <span class="menu-text">Sad Employees</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="?tab=history" class="menu-link <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Resolved History</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <header>
            <div class="header-title">
                <?php
                switch ($active_tab) {
                    case 'dashboard':
                        echo 'Dashboard Overview';
                        break;
                    case 'happy':
                        echo 'Happy Employees';
                        break;
                    case 'sad':
                        echo 'Sad Employees';
                        break;
                    case 'history':
                        echo 'Resolved Cases History';
                        break;
                    default:
                        echo 'Employee Welfare Dashboard';
                }
                ?>
            </div>
            <div class="user-info">
                <button class="logout-btn" onclick="location.href='welfare_logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <div class="container">
            <?php if ($active_tab === 'dashboard'): ?>
                <!-- Dashboard Tab Content -->
                <div class="dashboard-grid">
                    <div class="stat-card happy-card">
                        <h3>Happy Employees Today</h3>
                        <div class="value"><?php echo $stats['happy_count'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card sad-card">
                        <h3>Sad Employees Today</h3>
                        <div class="value"><?php echo $stats['sad_count'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card resolved-card">
                        <h3>Your Resolved Today</h3>
                        <div class="value"><?php echo $stats['resolved_today'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card admin-card">
                        <h3>Your Total Resolved</h3>
                        <div class="value"><?php echo $current_admin_stats['resolved_count'] ?? 0; ?> of <?php echo $current_admin_stats['total_cases'] ?? 0; ?></div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">Your Active Sad Cases</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Reported At</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sad_cases as $case):
                                $progress = max($case['resolution_progress'], $case['max_step'] ?? 0);
                                $is_assigned_to_me = $case['first_admin_id'] == $admin_id;
                                $is_unassigned = $case['first_admin_id'] === null;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['employee_id']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($case['timestamp'])); ?></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                        <small><?php echo $progress; ?>%</small>
                                    </td>
                                    <td>
                                        <?php
                                        if ($case['resolved'] == 1) {
                                            echo '<span class="badge badge-success">Resolved</span>';
                                        } elseif ($progress == 0) {
                                            echo '<span class="badge badge-danger">New</span>';
                                        } elseif ($progress < 75) {
                                            echo '<span class="badge badge-warning">In Progress</span>';
                                        } else {
                                            echo '<span class="badge badge-info">Waiting for Employee</span>';
                                        }
                                        ?>
                                        <?php if ($is_assigned_to_me): ?>
                                            <span class="badge badge-success">Assigned to you</span>
                                        <?php elseif ($is_unassigned): ?>
                                            <span class="badge badge-warning">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_assigned_to_me): ?>
                                            <button class="action-btn primary-btn"
                                                onclick="openActionModal('<?php echo $case['employee_id']; ?>', <?php echo $progress; ?>)">
                                                Take Action
                                            </button>
                                            <?php if ($progress >= 75): ?>
                                                <button class="action-btn warning-btn"
                                                    onclick="openEscalateModal('<?php echo $case['employee_id']; ?>')">
                                                    Escalate
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($is_unassigned): ?>
                                            <form method="POST" action="" style="display: inline-block;">
                                                <input type="hidden" name="employee_id" value="<?php echo $case['employee_id']; ?>">
                                                <button type="submit" name="assign_to_me" class="action-btn success-btn">
                                                   Acknowledged
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn" disabled>
                                                Assigned to another admin
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sad_cases)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No active sad cases assigned to you</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'happy'): ?>
                <!-- Happy Employees Tab Content -->
                <div class="section">
                    <h2 class="section-title">Happy Employees Today</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Reported At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($happy_employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($employee['timestamp'])); ?></td>
                                    <td>
                                        <span class="badge badge-success">Happy</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($happy_employees)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No happy employees today</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'sad'): ?>
                <!-- Sad Employees Tab Content -->
                <div class="section">
                    <h2 class="section-title">Your Sad Employees Today</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Reported At</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sad_employees as $employee):
                                $progress = $employee['resolution_progress'] ?? 0;
                                $is_assigned_to_me = $employee['first_admin_id'] == $admin_id;
                                $is_unassigned = $employee['first_admin_id'] === null;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($employee['timestamp'])); ?></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                        <small><?php echo $progress; ?>%</small>
                                    </td>
                                    <td>
                                        <?php
                                        if ($progress == 0) {
                                            echo '<span class="badge badge-danger">New</span>';
                                        } elseif ($progress < 75) {
                                            echo '<span class="badge badge-warning">In Progress</span>';
                                        } else {
                                            echo '<span class="badge badge-info">Waiting for Employee</span>';
                                        }
                                        ?>
                                        <?php if ($is_assigned_to_me): ?>
                                            <span class="badge badge-success">Assigned to you</span>
                                        <?php elseif ($is_unassigned): ?>
                                            <span class="badge badge-warning">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_assigned_to_me): ?>
                                            <button class="action-btn primary-btn"
                                                onclick="openActionModal('<?php echo $employee['employee_id']; ?>', <?php echo $progress; ?>)">
                                                Take Action
                                            </button>
                                            <?php if ($progress >= 75): ?>
                                                <button class="action-btn warning-btn"
                                                    onclick="openEscalateModal('<?php echo $employee['employee_id']; ?>')">
                                                    Escalate
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($is_unassigned): ?>
                                            <form method="POST" action="" style="display: inline-block;">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                                <button type="submit" name="assign_to_me" class="action-btn success-btn">
                                                    Assign to Me
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn" disabled>
                                                Assigned to another admin
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sad_employees)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No sad employees assigned to you today</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'history'): ?>
                <!-- Resolved History Tab Content -->
                <div class="section">
                    <h2 class="section-title">Your Resolved Cases</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Reported At</th>
                                <th>Resolved At</th>
                                <th>Issue Details</th>
                                <th>Resolution Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resolved_cases as $case): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['employee_id']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($case['timestamp'])); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($case['resolution_timestamp'])); ?></td>
                                    <td>
                                        <?php if ($case['severity']): ?>
                                            <div class="issue-info">
                                                <p><strong>Severity:</strong> 
                                                    <span class="badge badge-<?php echo $case['severity']; ?>">
                                                        <?php echo ucfirst($case['severity']); ?>
                                                    </span>
                                                </p>
                                                <p><strong>Type:</strong> <?php echo ucwords(str_replace('_', ' ', $case['issue_type'])); ?></p>
                                                <p><strong>Repeated:</strong> <?php echo $case['is_repeated'] ? 'Yes' : 'No'; ?></p>
                                                <?php if ($case['escalated_to']): ?>
                                                    <p><strong>Escalated to:</strong> <?php echo ucwords(str_replace('_', ' ', $case['escalated_to'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            No assessment data
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="action-btn primary-btn"
                                            onclick="toggleResolutionDetails('details-<?php echo $case['id']; ?>')">
                                            View Details
                                        </button>
                                        <div id="details-<?php echo $case['id']; ?>" class="resolution-details" style="display: none;">
                                            <?php echo htmlspecialchars($case['resolution_details'] ?? 'No details available'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($resolved_cases)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No resolved cases found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal" id="actionModal">
        <div class="modal-content">
            <h3 class="modal-title">Take Action</h3>
            <form id="actionForm" method="POST" action="take_action_agl.php">
                <input type="hidden" name="employee_id" id="modalEmployeeId">
                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

                <div class="form-group">
                    <label for="actionStep">Action Step</label>
                    <select name="action_step" id="actionStep" required>
                        <option value="">Select an action</option>
                        <option value="Talked with the employee">Step 1: Talked with the employee (30%)</option>
                        <option value="Finding Problem">Step 2: Finding Problem (40%)</option>
                        <option value="Taking action">Step 3: Taking action (60%)</option>
                        <option value="Give Support">Step 4: Give Support (70%)</option>
                        <option value="Followup">Step 5: Followup (75%)</option>
                    </select>
                </div>

                <!-- Assessment fields (shown only for 30% action) -->
                <div id="assessmentFields" style="display: none;">
                    <div class="form-group">
                        <label for="severityLevel">Severity Level</label>
                        <select name="severity" id="severityLevel" required>
                            <option value="">Select severity</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="moderate">Moderate</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="issueType">Issue Type</label>
                        <select name="issue_type" id="issueType" required>
                            <option value="">Select issue type</option>
                            <?php foreach ($issue_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['name']); ?>">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type['name']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="isRepeated">Is this a repeated issue?</label>
                        <select name="is_repeated" id="isRepeated" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="actionNotes">Notes (Required)</label>
                    <textarea name="action_notes" id="actionNotes" placeholder="Enter details about this action..." required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Action</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Escalate Modal -->
    <div class="modal" id="escalateModal">
        <div class="modal-content">
            <h3 class="modal-title">Escalate Case</h3>
            <form id="escalateForm" method="POST" action="escalate_case_agl.php">
                <input type="hidden" name="employee_id" id="escalateEmployeeId">
                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

                <div class="form-group">
                    <label for="escalateTo">Escalate To</label>
                    <select name="escalate_to" id="escalateTo" required>
                        <option value="">Select recipient</option>
                        <option value="issue_resolution_committee">Issue Resolution Committee</option>
                        <option value="head_of_hr">Head of HR</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="escalationReason">Reason for Escalation</label>
                    <textarea name="escalation_reason" id="escalationReason" placeholder="Explain why this case needs escalation..." required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEscalateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Escalate Case</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openActionModal(employeeId, currentProgress) {
            document.getElementById('modalEmployeeId').value = employeeId;

            const stepSelect = document.getElementById('actionStep');
            const options = stepSelect.options;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.value) {
                    const percent = parseInt(option.text.match(/\((\d+)%\)/)[1]);
                    option.disabled = percent <= currentProgress;
                    if (percent === currentProgress + 10) {
                        option.selected = true;
                        // Trigger change event to show/hide assessment fields
                        const event = new Event('change');
                        stepSelect.dispatchEvent(event);
                    }
                }
            }

            document.getElementById('actionModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        function openEscalateModal(employeeId) {
            document.getElementById('escalateEmployeeId').value = employeeId;
            document.getElementById('escalateModal').style.display = 'flex';
        }

        function closeEscalateModal() {
            document.getElementById('escalateModal').style.display = 'none';
        }

        function toggleResolutionDetails(detailsId) {
            const details = document.getElementById(detailsId);
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }

        // Show/hide assessment fields based on selected action
        document.getElementById('actionStep').addEventListener('change', function() {
            const assessmentFields = document.getElementById('assessmentFields');
            if (this.value === 'Talked with the employee') {
                assessmentFields.style.display = 'block';
                // Make fields required
                document.getElementById('severityLevel').required = true;
                document.getElementById('issueType').required = true;
                document.getElementById('isRepeated').required = true;
            } else {
                assessmentFields.style.display = 'none';
                // Remove required attribute
                document.getElementById('severityLevel').required = false;
                document.getElementById('issueType').required = false;
                document.getElementById('isRepeated').required = false;
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('actionModal');
            if (event.target === modal) {
                closeModal();
            }

            const escalateModal = document.getElementById('escalateModal');
            if (event.target === escalateModal) {
                closeEscalateModal();
            }
        };

        // Handle form submissions
        function submitForm(form, actionUrl) {
            const formData = new FormData(form);

            fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Action completed successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
        }

        document.getElementById('actionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'take_action_agl.php');
        });

        document.getElementById('escalateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'escalate_case_agl.php');
        });
    </script>
</body>
</html>