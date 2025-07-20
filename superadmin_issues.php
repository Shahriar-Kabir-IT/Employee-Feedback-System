<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['welfare_admin']) || $_SESSION['welfare_admin']['username'] !== 'superadmin') {
    header('Location: welfare_login.php');
    exit;
}

$location = $_GET['location'] ?? '';
$admin_id = $_GET['admin_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Issues Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        :root {
            --primary-blue: #2d8fd4;
            --accent-blue: #1ea2fa;
            --error-red: #dc3545;
            --success-green: #28a745;
            --warning-yellow: #ffc107;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --dark-text: #212529;
            --sidebar-bg: #2c3e50;
            --sidebar-active: #34495e;
            --border-radius: 6px;
            --border-radius-large: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.2s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-text);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        /* Main Content */
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

        /* Card styles */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .card-title {
            color: var(--primary-blue);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--medium-gray);
        }

        /* Form styles */
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(45, 143, 212, 0.2);
        }

        .btn {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #1a7bb9;
        }

        /* Table styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: var(--light-gray);
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--medium-gray);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-green);
        }

        .badge-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-yellow);
        }

        /* Progress bar */
        .progress {
            height: 6px;
            background-color: var(--medium-gray);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary-blue);
        }

        /* Toggle button */
        .toggle-btn {
            background: none;
            border: none;
            color: var(--primary-blue);
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Details row */
        .details-row {
            display: none;
        }

        .details-row.active {
            display: table-row;
        }

        .details-content {
            padding: 15px;
            background-color: var(--light-gray);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 48px;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        .empty-state-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Title and button header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-header .card-title {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        /* Responsive styles */
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

            .form-row {
                flex-direction: column;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Super Admin</div>
            <div class="admin-info"><?php echo htmlspecialchars($_SESSION['welfare_admin']['full_name']); ?></div>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="superadmin_dashboard.php" class="menu-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="superadmin_issues.php" class="menu-link active">
                    <i class="fas fa-tasks"></i>
                    <span class="menu-text">Admin Issues</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <header>
            <div class="header-title">Admin Issues Management</div>
            <div class="user-info">
                <button class="logout-btn" onclick="location.href='welfare_logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <div class="container">
            <?php
            try {
                if ($admin_id == 0 && $location) {
                    $stmt = $pdo->prepare("SELECT id FROM welfare_admins WHERE location = ? AND username != 'superadmin' LIMIT 1");
                    $stmt->execute([$location]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    $admin_id = $admin ? $admin['id'] : 0;
                }

                if ($admin_id == 0) {
                    $adminsStmt = $pdo->query("SELECT id, full_name, location FROM welfare_admins WHERE username != 'superadmin' ORDER BY location, full_name");
                    $admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="card">
                        <h3 class="card-title">Select Admin to View Cases</h3>
                        <form method="GET" action="superadmin_issues.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="location">Factory Location</label>
                                    <select id="location" name="location" class="form-control" required>
                                        <option value="">Select Factory</option>
                                        <option value="abm" <?= $location === 'abm' ? 'selected' : ''; ?>>ABM</option>
                                        <option value="ajl" <?= $location === 'ajl' ? 'selected' : ''; ?>>AJL</option>
                                        <option value="agl" <?= $location === 'agl' ? 'selected' : ''; ?>>AGL</option>
                                        <option value="pwpl" <?= $location === 'pwpl' ? 'selected' : ''; ?>>PWPL</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="admin_id">Admin</label>
                                    <select id="admin_id" name="admin_id" class="form-control" required>
                                        <option value="">Select Admin</option>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?= $admin['id']; ?>" data-location="<?= $admin['location']; ?>">
                                                <?= htmlspecialchars($admin['full_name'] . ' (' . strtoupper($admin['location']) . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-search"></i> View Cases
                            </button>
                        </form>
                    </div>

                    <script>
                        document.getElementById('location').addEventListener('change', function() {
                            const location = this.value;
                            const adminSelect = document.getElementById('admin_id');
                            
                            adminSelect.querySelectorAll('option').forEach(option => {
                                option.style.display = '';
                            });
                            
                            if (location) {
                                adminSelect.querySelectorAll('option').forEach(option => {
                                    if (option.value && option.dataset.location !== location) {
                                        option.style.display = 'none';
                                    }
                                });
                                adminSelect.value = '';
                            }
                        });

                        const currentLocation = '<?= $location; ?>';
                        if (currentLocation) {
                            document.getElementById('location').dispatchEvent(new Event('change'));
                        }
                    </script>
                    <?php
                } else {
                    // Fetch admin details
                    $adminStmt = $pdo->prepare("SELECT full_name, location FROM welfare_admins WHERE id = ?");
                    $adminStmt->execute([$admin_id]);
                    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$admin) {
                        echo "<div class='card'><p>Admin not found.</p></div>";
                        return;
                    }
                    
                    // Fetch cases assigned to this admin
                    $tableName = "feedback_" . $admin['location'];
                    $casesStmt = $pdo->prepare("SELECT * FROM $tableName WHERE assigned_admin_id = ? ORDER BY timestamp DESC");
                    $casesStmt->execute([$admin_id]);
                    $cases = $casesStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Only fetch resolution steps if there are cases
                    $stepsByEmployee = [];
                    if (!empty($cases)) {
                        $employeeIds = array_column($cases, 'employee_id');
                        $placeholders = rtrim(str_repeat('?,', count($employeeIds)), ',');
                        
                        $stepsStmt = $pdo->prepare("SELECT * FROM resolution_steps WHERE employee_id IN ($placeholders) ORDER BY timestamp DESC");
                        $stepsStmt->execute($employeeIds);
                        $allSteps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Organize steps by employee_id
                        foreach ($allSteps as $step) {
                            $stepsByEmployee[$step['employee_id']][] = $step;
                        }
                    }
                    ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                Cases for <?= htmlspecialchars($admin['full_name']); ?> (<?= strtoupper($admin['location']); ?>)
                            </h3>
                            <a href="superadmin_issues.php" class="btn">
                                <i class="fas fa-arrow-left"></i> Back to All Admins
                            </a>
                        </div>
                        
                        <?php if (empty($cases)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3 class="empty-state-title">No Cases Found</h3>
                                <p>This admin doesn't have any assigned cases yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Mood</th>
                                            <th>Reported</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cases as $case): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($case['employee_id']); ?></td>
                                                <td>
                                                    <?php 
                                                    $moodIcon = '';
                                                    $moodColor = '';
                                                    switch ($case['mood']) {
                                                        case 'happy':
                                                            $moodIcon = 'fa-smile';
                                                            $moodColor = 'var(--success-green)';
                                                            break;
                                                        case 'neutral':
                                                            $moodIcon = 'fa-meh';
                                                            $moodColor = 'var(--warning-yellow)';
                                                            break;
                                                        case 'sad':
                                                            $moodIcon = 'fa-frown';
                                                            $moodColor = 'var(--error-red)';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="fas <?= $moodIcon; ?>" style="color: <?= $moodColor; ?>"></i>
                                                    <?= ucfirst($case['mood']); ?>
                                                </td>
                                                <td><?= date('M j, Y H:i', strtotime($case['timestamp'])); ?></td>
                                                <td>
                                                    <span class="badge <?= $case['resolved'] ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?= $case['resolved'] ? 'Resolved' : 'Pending'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" style="width: <?= $case['resolution_progress']; ?>%"></div>
                                                    </div>
                                                    <small><?= $case['resolution_progress']; ?>%</small>
                                                </td>
                                                <td>
                                                    <button class="toggle-btn" data-employee-id="<?= $case['employee_id']; ?>">
                                                        <span>Details</span>
                                                        <i class="fas fa-chevron-down"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="details-row" id="details-<?= $case['employee_id']; ?>">
                                                <td colspan="6">
                                                    <div class="details-content">
                                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Employee ID</div>
                                                                <div>#<?= htmlspecialchars($case['employee_id']); ?></div>
                                                            </div>
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Mood</div>
                                                                <div>
                                                                    <i class="fas <?= $moodIcon; ?>" style="color: <?= $moodColor; ?>"></i>
                                                                    <?= ucfirst($case['mood']); ?>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Reported</div>
                                                                <div><?= date('M j, Y H:i', strtotime($case['timestamp'])); ?></div>
                                                            </div>
                                                            <?php if ($case['resolution_timestamp']): ?>
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Resolved</div>
                                                                <div><?= date('M j, Y H:i', strtotime($case['resolution_timestamp'])); ?></div>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Progress</div>
                                                                <div><?= $case['resolution_progress']; ?>%</div>
                                                            </div>
                                                            <div>
                                                                <div style="font-weight: 500; color: var(--dark-gray); margin-bottom: 5px;">Status</div>
                                                                <div>
                                                                    <span class="badge <?= $case['resolved'] ? 'badge-success' : 'badge-warning'; ?>">
                                                                        <?= $case['resolved'] ? 'Resolved' : 'Pending'; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($stepsByEmployee[$case['employee_id']])): ?>
                                                            <h4 style="font-size: 14px; margin: 20px 0 10px; color: var(--dark-gray);">
                                                                <i class="fas fa-clipboard-list"></i> Resolution Notes
                                                            </h4>
                                                            <div style="margin-top: 10px;">
                                                                <?php foreach ($stepsByEmployee[$case['employee_id']] as $note): ?>
                                                                    <div style="background-color: var(--white); border-radius: var(--border-radius); padding: 12px; margin-bottom: 10px; border-left: 3px solid var(--primary-blue);">
                                                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: 500;">
                                                                            <span>
                                                                                <span style="color: var(--primary-blue);"><?= htmlspecialchars($note['step_name']); ?></span>
                                                                                <span style="color: var(--success-green); font-weight: 600;">(<?= $note['step_percentage']; ?>%)</span>
                                                                            </span>
                                                                            <span style="color: var(--dark-gray); font-size: 12px;"><?= date('M j, Y H:i', strtotime($note['timestamp'])); ?></span>
                                                                        </div>
                                                                        <div><?= htmlspecialchars($note['notes']); ?></div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const toggleButtons = document.querySelectorAll('.toggle-btn');
                            
                            toggleButtons.forEach(button => {
                                button.addEventListener('click', function() {
                                    const employeeId = this.getAttribute('data-employee-id');
                                    const detailsRow = document.getElementById(`details-${employeeId}`);
                                    const icon = this.querySelector('i');
                                    
                                    detailsRow.classList.toggle('active');
                                    
                                    if (detailsRow.classList.contains('active')) {
                                        icon.classList.remove('fa-chevron-down');
                                        icon.classList.add('fa-chevron-up');
                                    } else {
                                        icon.classList.remove('fa-chevron-up');
                                        icon.classList.add('fa-chevron-down');
                                    }
                                });
                            });
                        });
                    </script>
                    <?php
                }
            } catch (PDOException $e) {
                die("Database error: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</body>
</html>