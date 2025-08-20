<?php
// reports.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotAdmin();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $username = $row['username'];
    $profile_pic = $row['profile_picture'] ?? null;
} else {
    // Handle error, e.g., redirect to logout
    header("Location: logout.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Get children belonging to this admin
$childrenStmt = $conn->prepare("
    SELECT * 
    FROM users 
    WHERE role = 'child' 
    AND parent_id = ? 
    ORDER BY points DESC
");
$childrenStmt->bind_param("i", $admin_id);
$childrenStmt->execute();
$children = $childrenStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle child selection
$selected_child_id = null;
$selected_child_name = "All Children";
if (isset($_GET['child_id']) && $_GET['child_id'] !== 'all') {
    $selected_child_id = (int)$_GET['child_id'];
    
    // Validate selected child belongs to this admin
    foreach ($children as $child) {
        if ($child['id'] == $selected_child_id) {
            $selected_child_name = $child['username'];
            break;
        }
    }
}

// Determine which report to show (default to all-time)
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'all_time';

// Prepare condition for SQL queries
$child_condition = $selected_child_id ? "AND u.id = ?" : "";
$bind_types = $selected_child_id ? "ii" : "i";
$bind_params = $selected_child_id ? [$admin_id, $selected_child_id] : [$admin_id];

// Get task completion history for admin's children (with optional child filter)
$historySql = "
    SELECT u.username, t.title, tc.completed_at, tc.points_earned, t.difficulty
    FROM task_completions tc
    JOIN users u ON tc.user_id = u.id
    JOIN tasks t ON tc.task_id = t.id
    WHERE u.parent_id = ?
    $child_condition
    ORDER BY tc.completed_at DESC
";

$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param($bind_types, ...$bind_params);
$historyStmt->execute();
$completionHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Weekly task completions
$startOfWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
$endOfWeek = date('Y-m-d 23:59:59', strtotime('sunday this week'));

$weeklySql = "
    SELECT u.username, t.title, tc.completed_at, tc.points_earned, t.difficulty
    FROM task_completions tc
    JOIN users u ON tc.user_id = u.id
    JOIN tasks t ON tc.task_id = t.id
    WHERE u.parent_id = ?
    AND tc.completed_at BETWEEN ? AND ?
    $child_condition
    ORDER BY tc.completed_at DESC
";

$weeklyStmt = $conn->prepare($weeklySql);
if ($selected_child_id) {
    $weeklyStmt->bind_param("issi", $admin_id, $startOfWeek, $endOfWeek, $selected_child_id);
} else {
    $weeklyStmt->bind_param("iss", $admin_id, $startOfWeek, $endOfWeek);
}
$weeklyStmt->execute();
$weeklyCompletionHistory = $weeklyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Monthly task completions
$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth = date('Y-m-t 23:59:59');

$monthlySql = "
    SELECT u.username, t.title, tc.completed_at, tc.points_earned, t.difficulty
    FROM task_completions tc
    JOIN users u ON tc.user_id = u.id
    JOIN tasks t ON tc.task_id = t.id
    WHERE u.parent_id = ?
    AND tc.completed_at BETWEEN ? AND ?
    $child_condition
    ORDER BY tc.completed_at DESC
";

$monthlyStmt = $conn->prepare($monthlySql);
if ($selected_child_id) {
    $monthlyStmt->bind_param("issi", $admin_id, $startOfMonth, $endOfMonth, $selected_child_id);
} else {
    $monthlyStmt->bind_param("iss", $admin_id, $startOfMonth, $endOfMonth);
}
$monthlyStmt->execute();
$monthlyCompletionHistory = $monthlyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Points distribution (filtered if child is selected)
$filtered_children = $children;
if ($selected_child_id) {
    $filtered_children = array_filter($children, function($child) use ($selected_child_id) {
        return $child['id'] == $selected_child_id;
    });
}

$pointsDistribution = array_map(function($child) {
    return [
        'username' => $child['username'],
        'points' => $child['points']
    ];
}, $filtered_children);

// Calculate statistics based on filtered children
$totalPoints = array_sum(array_column($pointsDistribution, 'points'));
$averagePoints = count($pointsDistribution) > 0 
    ? round($totalPoints / count($pointsDistribution)) 
    : 0;
?>

<?php include 'includes/header.php'; ?>

<div class="dashboard">
    <div class="sidebar">
        <div class="user-card">
            <?php if (!empty($profile_pic)): ?>
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile">
            <?php else: ?>
                <div class="user-initial"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <?php endif; ?>
            <div>
                <div class="username"><?php echo htmlspecialchars($username); ?></div>
                <div class="role">Parent</div>
            </div>
        </div>
        
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
                <a href="reports.php" class="nav-link active">
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
        </ul>
    </div>
    
    <div class="main-content">
        <!-- Child Selection Button in Top Left Corner -->
        <div class="child-select-button-container">
            <button class="child-select-button" onclick="toggleChildDropdown()">
                <i class="fas fa-child"></i>
                Select Child
                <i class="fas fa-chevron-down"></i>
            </button>
            
            <div class="child-dropdown" id="childDropdown">
                <form method="GET" action="reports.php">
                    <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                    <div class="child-option <?= !$selected_child_id ? 'selected' : '' ?>">
                        <input type="radio" name="child_id" value="all" id="child_all" 
                            <?= !$selected_child_id ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label for="child_all">All Children</label>
                    </div>
                    <?php foreach ($children as $child): ?>
                        <div class="child-option <?= $selected_child_id == $child['id'] ? 'selected' : '' ?>">
                            <input type="radio" name="child_id" value="<?= $child['id'] ?>" 
                                id="child_<?= $child['id'] ?>" 
                                <?= $selected_child_id == $child['id'] ? 'checked' : '' ?> 
                                onchange="this.form.submit()">
                            <label for="child_<?= $child['id'] ?>"><?= htmlspecialchars($child['username']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>
        
        <div class="section-title">
            <h2>
                <i class="fas fa-chart-line"></i>
                Children Reports
            </h2>
            <div class="current-selection">
                <i class="fas fa-user"></i> Viewing: 
                <strong><?= htmlspecialchars($selected_child_name) ?></strong>
            </div>
        </div>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-trophy"></i>
                        Points Leaderboard
                    </div>
                </div>
                <div class="card-body">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 8px; text-align: left;">Rank</th>
                                <th style="padding: 8px; text-align: left;">Child</th>
                                <th style="padding: 8px; text-align: left;">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_children as $index => $child): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 8px;"><?php echo $index + 1; ?></td>
                                    <td style="padding: 8px;"><?php echo htmlspecialchars($child['username']); ?></td>
                                    <td style="padding: 8px;"><?php echo $child['points']; ?> pts</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Points Distribution
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="pointsChart"></canvas>
                    </div>
                    <div style="margin-top: 20px;">
                        <div><strong>Total Points:</strong> <?php echo $totalPoints; ?></div>
                        <div><strong>Average Points:</strong> <?php echo $averagePoints; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Type Selection -->
        <div class="section-title">
            <h2><i class="fas fa-chart-bar"></i> Task Completion Reports</h2>
            <div class="report-type-selector">
                <a href="?child_id=<?= $selected_child_id ?>&report_type=weekly" 
                   class="btn <?= $report_type === 'weekly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-week"></i> Weekly
                </a>
                <a href="?child_id=<?= $selected_child_id ?>&report_type=monthly" 
                   class="btn <?= $report_type === 'monthly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Monthly
                </a>
                <a href="?child_id=<?= $selected_child_id ?>&report_type=all_time" 
                   class="btn <?= $report_type === 'all_time' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> All Time
                </a>
            </div>
        </div>
        
        <?php if ($report_type === 'weekly'): ?>
            <!-- Weekly Report Section -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-calendar-week"></i>
                        Weekly Task Completion
                    </div>
                    <div class="card-subtitle">
                        <?= date('M d, Y', strtotime($startOfWeek)) ?> - <?= date('M d, Y', strtotime($endOfWeek)) ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($weeklyCompletionHistory)): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 12px; text-align: left;">Child</th>
                                <th style="padding: 12px; text-align: left;">Task</th>
                                <th style="padding: 12px; text-align: left;">Difficulty</th>
                                <th style="padding: 12px; text-align: left;">Completed At</th>
                                <th style="padding: 12px; text-align: left;">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weeklyCompletionHistory as $history): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['username']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['title']); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="difficulty-badge difficulty-<?php echo strtolower($history['difficulty']); ?>">
                                            <?php echo $history['difficulty']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($history['completed_at'])); ?></td>
                                    <td style="padding: 12px;"><?php echo $history['points_earned']; ?> pts</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No tasks completed this week</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($report_type === 'monthly'): ?>
            <!-- Monthly Report Section -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-calendar-alt"></i>
                        Monthly Task Completion
                    </div>
                    <div class="card-subtitle">
                        <?= date('F Y', strtotime($startOfMonth)) ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($monthlyCompletionHistory)): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 12px; text-align: left;">Child</th>
                                <th style="padding: 12px; text-align: left;">Task</th>
                                <th style="padding: 12px; text-align: left;">Difficulty</th>
                                <th style="padding: 12px; text-align: left;">Completed At</th>
                                <th style="padding: 12px; text-align: left;">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyCompletionHistory as $history): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['username']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['title']); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="difficulty-badge difficulty-<?php echo strtolower($history['difficulty']); ?>">
                                            <?php echo $history['difficulty']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($history['completed_at'])); ?></td>
                                    <td style="padding: 12px;"><?php echo $history['points_earned']; ?> pts</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No tasks completed this month</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- All Time History Section -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-history"></i>
                        All Task Completions
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($completionHistory)): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 12px; text-align: left;">Child</th>
                                <th style="padding: 12px; text-align: left;">Task</th>
                                <th style="padding: 12px; text-align: left;">Difficulty</th>
                                <th style="padding: 12px; text-align: left;">Completed At</th>
                                <th style="padding: 12px; text-align: left;">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completionHistory as $history): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['username']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($history['title']); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="difficulty-badge difficulty-<?php echo strtolower($history['difficulty']); ?>">
                                            <?php echo $history['difficulty']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($history['completed_at'])); ?></td>
                                    <td style="padding: 12px;"><?php echo $history['points_earned']; ?> pts</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No task completions recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Child selection button and dropdown */
    .child-select-button-container {
        position: relative;
        margin-bottom: 20px;
    }
    
    .child-select-button {
        padding: 10px 20px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .child-select-button:hover {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
    }
    
    .child-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        padding: 10px;
        z-index: 100;
        min-width: 200px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .child-dropdown.show {
        display: block;
    }
    
    .child-option {
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .child-option:hover {
        background: rgba(108, 92, 231, 0.2);
    }
    
    .child-option.selected {
        background: rgba(108, 92, 231, 0.3);
    }
    
    .child-option input[type="radio"] {
        margin: 0;
    }
    
    .current-selection {
        font-size: 0.9rem;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Report type selector buttons */
    .report-type-selector {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .report-type-selector .btn {
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid var(--glass-border);
        color: var(--light);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .report-type-selector .btn:hover {
        background: rgba(108, 92, 231, 0.3);
    }
    
    .report-type-selector .btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-color: var(--primary);
        color: white;
    }
    
    /* Difficulty badges */
    .difficulty-badge {
        padding: 3px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
        color: var(--dark);
    }
    
    .difficulty-easy {
        background: var(--success);
    }
    
    .difficulty-medium {
        background: var(--warning);
    }
    
    .difficulty-hard {
        background: var(--danger);
    }
    
    /* Empty state styling */
    .empty-state {
        text-align: center;
        padding: 40px 0;
    }
    
    .empty-state i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 15px;
        color: var(--accent);
    }
    
    .empty-state p {
        font-size: 1.1rem;
        opacity: 0.8;
    }
    
    /* Card subtitle */
    .card-subtitle {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-top: 5px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Toggle child dropdown
    function toggleChildDropdown() {
        const dropdown = document.getElementById('childDropdown');
        dropdown.classList.toggle('show');
    }
    
    // Close the dropdown if clicked outside
    window.onclick = function(event) {
        if (!event.target.matches('.child-select-button') && 
            !event.target.closest('.child-select-button')) {
            const dropdown = document.getElementById('childDropdown');
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }
    
    // Points distribution chart
    const ctx = document.getElementById('pointsChart').getContext('2d');
    const labels = <?php echo json_encode(array_column($pointsDistribution, 'username')); ?>;
    const data = <?php echo json_encode(array_column($pointsDistribution, 'points')); ?>;
    
    const chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#6c5ce7', '#a29bfe', '#00cec9', '#b200b8ff', '#fdcb6e',
                    '#e17055', '#d63031', '#e84393', '#0984e3', '#00cec9'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#f7f9fc',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>