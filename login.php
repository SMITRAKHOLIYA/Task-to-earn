<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// session_start(); // Uncommented for session functionality

require_once 'includes/config.php';
require_once 'includes/auth.php';

$login_error = "";
if (isset($_GET['registered'])) {
    $login_message = "Registration successful. Please login.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if ($user['role'] === $role) {
                    // Store session data
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['points']    = $user['points'];

                    // Track login if session tracker exists
                    if (file_exists('includes/session_tracker.php')) {
                        require_once 'includes/session_tracker.php';
                        if (function_exists('trackLogin')) {
                            trackLogin($user['id'], $username);
                        }
                    }

                    // Redirect to proper dashboard
                    if ($user['role'] === 'admin') {
                        header("Location: dashboard_admin.php");
                    } else {
                        header("Location: dashboard_child.php");
                    }
                    exit;
                } else {
                    $login_error = "This account is registered as a " . $user['role'] . ". Please select the correct role.";
                }
            } else {
                $login_error = "Incorrect password. Please try again.";
            }
        } else {
            $login_error = "User not found. Please <a href='register.php'>register</a> first.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task to Earn</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Login to Task to Earn</h1>
    </div>
    
    <?php if (isset($login_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $login_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($login_error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $login_error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" required 
                   placeholder="Enter your username">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required 
                   placeholder="Enter your password">
        </div>
        
        <div class="form-group">
            <label>Login As</label>
            <div class="role-selector">
                <div class="role-option selected" id="admin-option" onclick="selectRole('admin')">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </div>
                <div class="role-option" id="child-option" onclick="selectRole('child')">
                    <i class="fas fa-child"></i>
                    <span>Child</span>
                </div>
            </div>
            <input type="hidden" id="role" name="role" value="admin">
        </div>
        
        <button type="submit" name="login" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register as Admin</a></p>
            <p><a href="#">Forgot password?</a></p>
        </div>
    </form>
</div>

<script>
    function selectRole(role) {
        document.getElementById('role').value = role;
        document.querySelectorAll('.role-option').forEach(el => {
            el.classList.remove('selected');
        });
        document.getElementById(role + '-option').classList.add('selected');
    }
</script>
</body>
</html>