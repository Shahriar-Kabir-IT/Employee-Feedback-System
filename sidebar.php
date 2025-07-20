<?php
// Check if user is logged in
if (!isset($_SESSION['welfare_admin'])) {
    header('Location: welfare_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --sidebar-hover: #34495e;
            --sidebar-active: #3498db;
            --error-red: #dc3545;
            --border-radius: 4px;
            --sidebar-width: 250px;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            color: var(--sidebar-text);
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            height: 40px;
            margin-right: 10px;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .menu-item a:hover {
            background-color: var(--sidebar-hover);
        }

        .menu-item a.active {
            background-color: var(--sidebar-active);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--error-red);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                overflow: hidden;
            }
            .sidebar-title, .menu-item span {
                display: none;
            }
            .menu-item i {
                margin-right: 0;
                font-size: 20px;
            }
            .menu-item a {
                justify-content: center;
                padding: 15px 0;
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="com.png" alt="Company Logo" class="sidebar-logo">
            <a href="welfare_logout.php" class="logout-btn">Logout</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="superadmin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'superadmin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="superadmin_issues.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'superadmin_issues.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Issues Management</span>
                </a>
            </li>
        </ul>
    </nav>
</body>
</html>