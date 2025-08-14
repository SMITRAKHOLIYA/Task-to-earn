<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// user_sessions.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

redirectIfNotAdmin();

// Get all session logs
$sessions = $conn->query("
    SELECT us.*, u.role 
    FROM user_sessions us
    JOIN users u ON us.user_id = u.id
    ORDER BY us.login_time DESC
")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) AS total_sessions,
        COUNT(DISTINCT user_id) AS unique_users,
        AVG(session_duration) AS avg_duration,
        MAX(session_duration) AS max_duration
    FROM user_sessions
")->fetch_assoc();
?>

<div class="dashboard">
    <div class="sidebar">
        
        <div class="stats-card">
            <div class="stats-label">TOTAL CHILDREN</div>
            <div class="stats-value"><?php echo count($children); ?></div>
            <div class="stats-label">Registered</div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link">
                    <i class="fas fa-tasks"></i>
                    <span>All Tasks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_children.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manage Children</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_rewards.php" class="nav-link">
                    <i class="fas fa-gift"></i>
                    <span>Manage Rewards</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_wishes.php" class="nav-link">
                    <i class="fas fa-lightbulb"></i>
                    <span>Manage Wishes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="user_sessions.php" class="nav-link active">
                    <i class="fas fa-history"></i>
                    <span>Session Logs</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="section-title">
            <h2><i class="fas fa-history"></i> User Session Logs</h2>
        </div>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Session Statistics
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['unique_users']; ?></div>
                            <div class="stat-label">Unique Users</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo gmdate("H:i:s", $stats['avg_duration']); ?></div>
                            <div class="stat-label">Avg Duration</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo gmdate("H:i:s", $stats['max_duration']); ?></div>
                            <div class="stat-label">Max Duration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 12px; text-align: left;">User</th>
                            <th style="padding: 12px; text-align: left;">Role</th>
                            <th style="padding: 12px; text-align: left;">IP Address</th>
                            <th style="padding: 12px; text-align: left;">Login Time</th>
                            <th style="padding: 12px; text-align: left;">Logout Time</th>
                            <th style="padding: 12px; text-align: left;">Duration</th>
                            <th style="padding: 12px; text-align: left;">Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;"><?php echo htmlspecialchars($session['username']); ?></td>
                                <td style="padding: 12px;"><?php echo ucfirst($session['role']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($session['ip_address']); ?></td>
                                <td style="padding: 12px;"><?php echo date('M d, Y H:i:s', strtotime($session['login_time'])); ?></td>
                                <td style="padding: 12px;">
                                    <?php echo $session['logout_time'] ? date('M d, Y H:i:s', strtotime($session['logout_time'])) : 'Active'; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php 
                                    if ($session['session_duration']) {
                                        echo gmdate("H:i:s", $session['session_duration']);
                                    } elseif ($session['logout_time']) {
                                        echo '<em>Unknown</em>';
                                    } else {
                                        echo '<span class="status-pending">Active</span>';
                                    }
                                    ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php 
                                    $ua = htmlspecialchars($session['user_agent']);
                                    echo strlen($ua) > 30 ? substr($ua, 0, 30).'...' : $ua;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card {
        background: var(--glass-bg);
        border-radius: 10px;
        padding: 15px;
        flex: 1;
        min-width: 150px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--accent);
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .status-pending {
        background: rgba(253, 203, 110, 0.2);
        color: var(--warning);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<?php include 'includes/footer.php'; ?>