<?php
require_once 'config.php';
require_once 'auth.php';

function trackLogin($user_id, $username) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, username, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $username, $ip_address, $user_agent);
    $stmt->execute();
    
    $_SESSION['session_id'] = $conn->insert_id;
}

function trackLogout() {
    global $conn;
    
    if (isset($_SESSION['session_id'])) {
        $session_id = $_SESSION['session_id'];
        $stmt = $conn->prepare("UPDATE user_sessions SET logout_time = NOW(), session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW()) WHERE id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
    }
}

// Register shutdown function to track logout
register_shutdown_function('trackLogout');