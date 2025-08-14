<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isChild() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'child';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../dashboard_child.php");
        exit;
    }
}

function redirectIfNotChild() {
    redirectIfNotLoggedIn();
    if (!isChild()) {
        header("Location: ../dashboard_admin.php");
        exit;
    }
}

// Get parent info for child users
function getParentInfo($conn) {
    if (!isChild() || !isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = (SELECT parent_id FROM users WHERE id = ?)");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
?>