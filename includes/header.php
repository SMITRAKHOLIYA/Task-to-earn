<?php
// includes/header.php
require_once 'auth.php';

// Fetch user profile data if available
$profile_pic = null;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $profile_pic = $user['profile_picture'];
    }
}

// Get parent info for child users
$parent_info = null;
if (isChild()) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = (SELECT parent_id FROM users WHERE id = ?)");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $parent = $result->fetch_assoc();
        $parent_info = $parent['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task to Earn | Futuristic Task Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="index.php" class="home-btn" title="Go to Home">
                    <i class="fas fa-home"></i>
                </a>
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="logo-text">Task to Earn</div>
                </div>
            </div>
            
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <div class="user-avatar" id="avatar-container">
                        <?php if ($profile_pic): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?php echo htmlspecialchars(substr($_SESSION['username'] ?? '', 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                        <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
                    </div>
                    <div>
                        <div><?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">
                            <?php echo ucfirst($_SESSION['role']); ?>
                            <?php if ($parent_info): ?>
                                | Parent: <?php echo htmlspecialchars($parent_info); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </header>