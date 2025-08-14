<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// login.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$login_error = "";
if (isset($_GET['registered'])) {
    $login_message = "Registration successful. Please login.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['points'] = $user['points'];

            // Add this right after successful login (around line 30):
            require_once 'includes/session_tracker.php';
            trackLogin($user['id'], $username);
                        
            // // Record login
            // $stmt = $conn->prepare("INSERT INTO users_login (user_id, username) VALUES (?, ?)");
            // $stmt->bind_param("is", $user['id'], $username);
            // $stmt->execute();
            
            if ($user['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_child.php");
            }
            exit;
        } else {
            $login_error = "Invalid password.";
        }
    } else {
        $login_error = "User not found. Please <a href='register.php'>register</a> first.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task to Earn | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: #f7f9fc;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            max-width: 450px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
    </style>
</head>
<body>
    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    
    <div class="login-container animated">
        <div class="login-header">
            <h1>Task to Earn</h1>
            <p>Complete tasks and earn rewards in our futuristic platform</p>
        </div>
        
        <?php if (isset($login_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $login_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($login_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Enter your username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>
            
            <button type="submit" name="login" class="btn login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register</a></p>
            <p><a href="#" id="forgot-password">Forgot password?</a></p>
        </div>
    </div>
</body>
</html>