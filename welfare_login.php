<?php
session_start();
require_once 'db_config.php';

// Check if already logged in
if (isset($_SESSION['welfare_admin'])) {
    // Redirect based on user type and location
    if ($_SESSION['welfare_admin']['username'] === 'superadmin') {
        header('Location: superadmin_dashboard.php');
    } else {
        $location = $_SESSION['welfare_admin']['location'] ?? 'abm';
        redirectToDashboard($location);
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM welfare_admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Login successful
            $_SESSION['welfare_admin'] = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'full_name' => $admin['full_name'],
                'is_superadmin' => ($admin['username'] === 'superadmin'),
                'location' => $admin['location'] ?? 'abm' // Add location to session
            ];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE welfare_admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            // Redirect based on user type and location
            if ($admin['username'] === 'superadmin') {
                header('Location: superadmin_dashboard.php');
            } else {
                $location = $admin['location'] ?? 'abm';
                redirectToDashboard($location);
            }
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

function redirectToDashboard($location) {
    switch (strtolower($location)) {
        case 'agl':
            header('Location: welfare_dashboard_agl.php');
            break;
        case 'ajl':
            header('Location: welfare_dashboard_ajl.php');
            break;
        case 'pwpl':
            header('Location: welfare_dashboard_pwpl.php');
            break;
        case 'abm':
        default:
            header('Location: welfare_dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welfare Admin Login</title>
    <style>
        :root {
            --primary-blue: #2d8fd4;
            --accent-blue: #1ea2fa;
            --error-red: #dc3545;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --dark-text: #212529;
            --border-radius: 6px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
        }

        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-blue);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(45, 143, 212, 0.2);
        }

        .error-message {
            color: var(--error-red);
            margin-bottom: 20px;
            text-align: center;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .login-btn:hover {
            background-color: #1a7bb9;
        }

        .company-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-logo img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="company-logo">
            <img src="com.png" alt="Company Logo">
        </div>
        
        <h1 class="login-title">Welfare Admin Login</h1>
        
        <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="welfare_login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</body>
</html>