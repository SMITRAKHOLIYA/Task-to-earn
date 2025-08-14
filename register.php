<?php
require_once 'includes/config.php';

$register_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $register_error = "Username already taken.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert new user as admin
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
            $stmt->bind_param("ss", $username, $hashed_password);
            if ($stmt->execute()) {
                // Registration successful, redirect to login
                header("Location: login.php?registered=1");
                exit;
            } else {
                $register_error = "Error registering user: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task to Earn | Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 40px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            color: var(--light);
            animation: fadeIn 0.6s ease-out;
        }

        .register-container h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(90deg, var(--accent), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }

        .register-container .alert {
            margin-bottom: 25px;
        }

        .register-container .form-group {
            margin-bottom: 25px;
        }

        .register-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--light);
        }

        .register-container .form-control {
            width: 100%;
            padding: 15px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .register-container .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 206, 201, 0.2);
        }

        .register-container .btn {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .register-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .register-footer a {
            color: var(--accent);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .register-footer a:hover {
            opacity: 1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    
    <div class="register-container animated">
        <h1>Admin Registration</h1>
        <?php if ($register_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $register_error; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="btn register-btn">
                <i class="fas fa-user-plus"></i> Register as Admin
            </button>
        </form>
        
        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>