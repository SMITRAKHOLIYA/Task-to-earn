<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotChild();

if (isset($_POST['reward_id'])) {
    $reward_id = $_POST['reward_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get reward details
    $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reward = $result->fetch_assoc();
    
    if ($reward && $_SESSION['points'] >= $reward['points']) {
        // Deduct points
        $new_points = $_SESSION['points'] - $reward['points'];
        $stmt = $conn->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_points, $user_id);
        $stmt->execute();
        
        // Update session
        $_SESSION['points'] = $new_points;
        
        // Show success message
        $_SESSION['success_message'] = "Reward '{$reward['title']}' redeemed successfully!";
        header("Location: dashboard_child.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Not enough points to redeem this reward!";
        header("Location: dashboard_child.php");
        exit;
    }
} else {
    header("Location: dashboard_child.php");
    exit;
}
?>