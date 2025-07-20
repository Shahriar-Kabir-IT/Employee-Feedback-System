<?php
session_start();
require_once 'db_config.php';

// Check if admin is logged in and is superadmin
if (!isset($_SESSION['welfare_admin']) || $_SESSION['welfare_admin']['username'] !== 'superadmin') {
    header('Location: welfare_login.php');
    exit;
}

// Fetch data for all factories
try {
    $factories = ['abm', 'agl', 'ajl', 'pwpl'];
    $happinessData = [];

    foreach ($factories as $factory) {
        $table = "feedback_$factory";

        $happyStmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE mood = 'happy'");
        $happyStmt->execute();
        $happyCount = $happyStmt->fetchColumn();

        $sadStmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE mood = 'sad'");
        $sadStmt->execute();
        $sadCount = $sadStmt->fetchColumn();

        $total = $happyCount + $sadCount;
        $ratio = ($happyCount > 0 && $sadCount > 0) ? round($happyCount / $sadCount, 2) : ($happyCount > 0 ? '∞' : '0');

        $happinessData[] = [
            'location' => strtoupper($factory),
            'happy' => $happyCount,
            'sad' => $sadCount,
            'total' => $total,
            'ratio' => $ratio
        ];
    }

    // Get list of all admins
    $adminsStmt = $pdo->query("SELECT id, username, full_name, location FROM welfare_admins WHERE username != 'superadmin' ORDER BY location, full_name");
    $admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .dashboard-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-blue);
            font-size: 24px;
        }

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

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }

        .happy-stat {
            color: var(--success-green);
        }

        .sad-stat {
            color: var(--error-red);
        }

        .ratio-stat {
            color: var(--primary-blue);
        }

        .stat-label {
            color: var(--dark-gray);
            font-size: 14px;
        }

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
            .stats-grid {
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

            .form-row {
                flex-direction: column;
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
                <a href="superadmin_dashboard.php" class="menu-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="superadmin_issues.php" class="menu-link">
                    <i class="fas fa-tasks"></i>
                    <span class="menu-text">Admin Issues</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <header>
            <div class="header-title">Super Admin Dashboard</div>
            <div class="user-info">
                <button class="logout-btn" onclick="location.href='welfare_logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <div class="container">
            <h2 class="dashboard-title">Employee Happiness Overview</h2>

            <div class="stats-grid">
                <?php foreach ($happinessData as $data): ?>
                    <div class="stat-card">
                        <div class="stat-label"><?php echo $data['location']; ?></div>
                        <div class="stat-value happy-stat"><?php echo $data['happy']; ?> Happy</div>
                        <div class="stat-value sad-stat"><?php echo $data['sad']; ?> Sad</div>
                        <div class="stat-value">Total: <?php echo $data['total']; ?></div>
                        <div class="stat-value ratio-stat">Ratio: <?php echo $data['ratio']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h3 class="card-title">Happiness Distribution by Factory</h3>
                <div class="chart-container">
                    <canvas id="happinessChart"></canvas>
                </div>
            </div>

           

    <script>
        // Happiness Chart
        const ctx = document.getElementById('happinessChart').getContext('2d');
        const happinessChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($happinessData, 'location')); ?>,
                datasets: [{
                        label: 'Happy Employees',
                        data: <?php echo json_encode(array_column($happinessData, 'happy')); ?>,
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Sad Employees',
                        data: <?php echo json_encode(array_column($happinessData, 'sad')); ?>,
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Factory Location'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const ratio = <?php echo json_encode(array_column($happinessData, 'ratio')); ?>[dataIndex];
                                return `Happy/Sad Ratio: ${ratio}`;
                            }
                        }
                    }
                }
            }
        });

        // Update admin dropdown based on selected location
        document.getElementById('location').addEventListener('change', function() {
            const location = this.value;
            const adminSelect = document.getElementById('admin_id');

            // Enable all options first
            adminSelect.querySelectorAll('option').forEach(option => {
                option.style.display = '';
            });

            if (location) {
                // Hide options that don't match the selected location
                adminSelect.querySelectorAll('option').forEach(option => {
                    if (option.value && option.dataset.location !== location) {
                        option.style.display = 'none';
                    }
                });

                // Reset selection
                adminSelect.value = '';
            }
        });
    </script>
</body>

</html>