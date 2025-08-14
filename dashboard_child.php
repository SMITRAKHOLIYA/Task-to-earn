<?php
// dashboard_child.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotChild();

session_start(); // Ensure session is started

// Initialize notifications array
if (!isset($_SESSION['notifications'])) {
    $_SESSION['notifications'] = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['redeem_reward'])) {
        $reward = $_POST['reward'];
        $cost = intval($_POST['cost']);
        $user_id = $_SESSION['user_id'];
        
        // Begin transaction for atomic operations
        $conn->begin_transaction();
        
        try {
            // Verify points and update
            $stmt = $conn->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && $user['points'] >= $cost) {
                // Deduct points
                $stmt = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
                $stmt->bind_param("ii", $cost, $user_id);
                $stmt->execute();
                
                // Update session points
                $_SESSION['points'] = $user['points'] - $cost;
                
                // Add redemption record (optional)
                $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward, cost) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $user_id, $reward, $cost);
                $stmt->execute();
                
                // Add success notification
                $_SESSION['notifications'][] = [
                    'type' => 'success',
                    'message' => 'Reward redeemed successfully!'
                ];
                
                $conn->commit();
            } else {
                $_SESSION['notifications'][] = [
                    'type' => 'error',
                    'message' => 'Not enough points to redeem this reward!'
                ];
                $conn->rollback();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['notifications'][] = [
                'type' => 'error',
                'message' => 'Error processing redemption: ' . $e->getMessage()
            ];
        }
        
        header("Location: dashboard_child.php#rewards");
        exit;
    }
}

// Task completion handling
if (isset($_GET['complete_task'])) {
    $task_id = $_GET['complete_task'];
    $user_id = $_SESSION['user_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get task details with lock
        $stmt = $conn->prepare("SELECT points, difficulty FROM tasks WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Task not found");
        }
        
        $task = $result->fetch_assoc();
        $points = $task['points'];
        
        // Update task status
        $stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $user_id, $task_id);
        $stmt->execute();
        
        // Add completion record
        $stmt = $conn->prepare("INSERT INTO task_completions (user_id, task_id, points_earned, difficulty) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $task_id, $points, $task['difficulty']);
        $stmt->execute();
        
        // Update user points
        $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->bind_param("ii", $points, $user_id);
        $stmt->execute();
        
        // Update session points
        $_SESSION['points'] += $points;
        
        // Check if this task is linked to a wish
        $wishCheck = $conn->query("SELECT id FROM wishes WHERE task_id = $task_id");
        if ($wishCheck && $wishCheck->num_rows > 0) {
            $wish = $wishCheck->fetch_assoc();
            $conn->query("UPDATE wishes SET status = 'approved' WHERE id = " . $wish['id']);
        }
        
        checkAchievements($user_id, $conn);
        
        $conn->commit();
        
        $_SESSION['notifications'][] = [
            'type' => 'success',
            'message' => "Task completed! +{$points} points earned!"
        ];
        
        header("Location: dashboard_child.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['notifications'][] = [
            'type' => 'error',
            'message' => 'Error completing task: ' . $e->getMessage()
        ];
        header("Location: dashboard_child.php");
        exit;
    }
}

// Get tasks assigned to this child
$sql = "SELECT tasks.*, users.username AS creator 
        FROM tasks 
        JOIN users ON tasks.created_by = users.id 
        WHERE status = 'pending' 
        AND assigned_to = ? 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get completed tasks
$completedSql = "SELECT tasks.*, task_completions.completed_at 
                 FROM tasks 
                 JOIN task_completions ON tasks.id = task_completions.task_id
                 WHERE task_completions.user_id = ? 
                 ORDER BY task_completions.completed_at DESC";
$stmt = $conn->prepare($completedSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$completedTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get achievements
$achievementsSql = "SELECT * FROM achievements WHERE user_id = ?";
$stmt = $conn->prepare($achievementsSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$achievements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user profile
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

// Get parent info
$parent_info = null;
$stmt = $conn->prepare("SELECT username FROM users WHERE id = (SELECT parent_id FROM users WHERE id = ?)");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $parent = $result->fetch_assoc();
    $parent_info = $parent['username'];
}

// Function to check achievements
function checkAchievements($user_id, $conn) {
    // Check for task completion achievements
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM task_completions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $taskCount = $result->fetch_assoc()['count'];
    
    if ($taskCount >= 5) {
        $achievementCheck = $conn->prepare("SELECT id FROM achievements WHERE user_id = ? AND type = 'task_completion' AND level = 5");
        $achievementCheck->bind_param("i", $user_id);
        $achievementCheck->execute();
        
        if ($achievementCheck->get_result()->num_rows === 0) {
            $conn->query("INSERT INTO achievements (user_id, type, title, description, earned_at) VALUES ($user_id, 'task_completion', 'Task Master', 'Completed 5 tasks!', NOW())");
        }
    }
    
    // Check for difficulty-specific achievements
    $difficulties = ['Easy', 'Medium', 'Hard'];
    foreach ($difficulties as $difficulty) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM task_completions WHERE user_id = ? AND difficulty = ?");
        $stmt->bind_param("is", $user_id, $difficulty);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count >= 3) {
            $title = ucfirst($difficulty) . " Task Champion";
            $description = "Completed 3 " . $difficulty . " tasks!";
            
            $achievementCheck = $conn->prepare("SELECT id FROM achievements WHERE user_id = ? AND type = 'difficulty' AND title = ?");
            $achievementCheck->bind_param("is", $user_id, $title);
            $achievementCheck->execute();
            
            if ($achievementCheck->get_result()->num_rows === 0) {
                $conn->query("INSERT INTO achievements (user_id, type, title, description, earned_at) VALUES ($user_id, 'difficulty', '$title', '$description', NOW())");
            }
        }
    }
}

include 'includes/header.php';
?>

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
                <div class="role">child</div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stats-label">YOUR POINTS</div>
            <div class="stats-value"><?php echo $_SESSION['points']; ?></div>
            <div class="stats-label">Available</div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard_child.php#available-tasks" class="nav-link active">
                    <i class="fas fa-tasks"></i>
                    <span>Available Tasks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboard_child.php#completed-tasks" class="nav-link">
                    <i class="fas fa-check-circle"></i>
                    <span>Completed Tasks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboard_child.php#rewards" class="nav-link">
                    <i class="fas fa-gift"></i>
                    <span>Rewards Shop</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboard_child.php#achievements" class="nav-link">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="wish_list.php" class="nav-link">
                    <i class="fas fa-lightbulb"></i>
                    <span>My Wish List</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <!-- Notification Display Area -->
        <div class="notification-container">
            <?php if (!empty($_SESSION['notifications'])): ?>
                <?php foreach ($_SESSION['notifications'] as $notification): ?>
                    <div class="notification show <?php echo $notification['type'] === 'success' ? 'success' : 'error'; ?>">
                        <?php echo $notification['message']; ?>
                    </div>
                <?php endforeach; ?>
                <?php unset($_SESSION['notifications']); ?>
            <?php endif; ?>
        </div>
        
        <div id="available-tasks">
            <div class="section-title">
                <h2><i class="fas fa-tasks"></i> Tasks Assigned to You</h2>
            </div>
            
            <div class="card-grid">
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="card animated">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-star"></i>
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </div>
                                <div class="card-subtitle">
                                    Created by: <?php echo htmlspecialchars($task['creator']); ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="task-difficulty">
                                    <i class="fas fa-signal"></i>
                                    Difficulty: 
                                    <span class="difficulty-badge difficulty-<?php echo strtolower($task['difficulty']); ?>">
                                        <?php echo $task['difficulty']; ?>
                                    </span>
                                </div>
                                <div class="task-status status-pending">
                                    Pending
                                </div>
                                <div class="task-points">
                                    <i class="fas fa-coins" style="color: var(--warning);"></i>
                                    Reward: <span class="points-badge"><?php echo $task['points']; ?> pts</span>
                                </div>
                                <div class="task-description">
                                    <?php echo htmlspecialchars($task['description']); ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                </div>
                                <a href="?complete_task=<?php echo $task['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-check"></i> Complete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                            <h3>No tasks assigned</h3>
                            <p>Your parent hasn't assigned you any tasks yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="completed-tasks" style="display: none;">
            <div class="section-title">
                <h2><i class="fas fa-check-circle"></i> Completed Tasks</h2>
            </div>
            
            <?php if (count($completedTasks) > 0): ?>
                <div class="card-grid">
                    <?php foreach ($completedTasks as $task): ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-star"></i>
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="task-difficulty">
                                    <i class="fas fa-signal"></i>
                                    Difficulty: 
                                    <span class="difficulty-badge difficulty-<?php echo strtolower($task['difficulty']); ?>">
                                        <?php echo $task['difficulty']; ?>
                                    </span>
                                </div>
                                <div class="task-status status-completed">
                                    Completed
                                </div>
                                <div class="task-points">
                                    <i class="fas fa-coins" style="color: var(--warning);"></i>
                                    Reward: <span class="points-badge"><?php echo $task['points']; ?> pts</span>
                                </div>
                                <div class="task-description">
                                    <?php echo htmlspecialchars($task['description']); ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($task['completed_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                        <h3>No tasks completed yet</h3>
                        <p>Complete some tasks to see them here!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="rewards" style="display: none;">
            <div class="section-title">
                <h2><i class="fas fa-gift"></i> Rewards Shop</h2>
            </div>
            <div class="card-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-gamepad"></i>
                            Game Time
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="task-points">
                            <i class="fas fa-coins" style="color: var(--warning);"></i>
                            Cost: <span class="points-badge">100 pts</span>
                        </div>
                        <div class="task-description">
                            30 minutes of extra game time on your favorite console
                        </div>
                    </div>
                    <div class="card-footer">
                        <form method="POST">
                            <input type="hidden" name="reward" value="game_time">
                            <input type="hidden" name="cost" value="100">
                            <button type="submit" name="redeem_reward" class="btn" <?php echo $_SESSION['points'] < 100 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> Redeem
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-ice-cream"></i>
                            Ice Cream Treat
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="task-points">
                            <i class="fas fa-coins" style="color: var(--warning);"></i>
                            Cost: <span class="points-badge">75 pts</span>
                        </div>
                        <div class="task-description">
                            Your favorite ice cream from the local shop
                        </div>
                    </div>
                    <div class="card-footer">
                        <form method="POST">
                            <input type="hidden" name="reward" value="ice_cream">
                            <input type="hidden" name="cost" value="75">
                            <button type="submit" name="redeem_reward" class="btn" <?php echo $_SESSION['points'] < 75 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> Redeem
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-film"></i>
                            Movie Night
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="task-points">
                            <i class="fas fa-coins" style="color: var(--warning);"></i>
                            Cost: <span class="points-badge">150 pts</span>
                        </div>
                        <div class="task-description">
                            Choose a movie for family movie night
                        </div>
                    </div>
                    <div class="card-footer">
                        <form method="POST">
                            <input type="hidden" name="reward" value="movie_night">
                            <input type="hidden" name="cost" value="150">
                            <button type="submit" name="redeem_reward" class="btn" <?php echo $_SESSION['points'] < 150 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> Redeem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="achievements" style="display: none;">
            <div class="section-title">
                <h2><i class="fas fa-trophy"></i> Your Achievements</h2>
            </div>
            
            <?php if (count($achievements) > 0): ?>
                <div class="card-grid">
                    <?php foreach ($achievements as $achievement): ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-trophy" style="color: var(--warning);"></i>
                                    <?php echo htmlspecialchars($achievement['title']); ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="task-description">
                                    <?php echo htmlspecialchars($achievement['description']); ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <i class="far fa-calendar"></i>
                                    Earned on <?php echo date('M d, Y', strtotime($achievement['earned_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 40px;">
                        <i class="fas fa-trophy" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                        <h3>No achievements yet</h3>
                        <p>Complete tasks to earn achievements!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Navigation functionality
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Only prevent default for dashboard section links
            if (this.getAttribute('href').includes('dashboard_child.php#')) {
                e.preventDefault();
                
                // Hide all sections
                document.querySelectorAll('.main-content > div[id]').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show selected section
                const target = this.getAttribute('href').split('#')[1];
                document.getElementById(target).style.display = 'block';
                
                // Update active link
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update URL without reload
                history.pushState(null, null, this.getAttribute('href'));
            }
            // External links (like wish_list.php) will follow normally
        });
    });
    
    // Show available tasks by default if no hash in URL
    if (!window.location.hash) {
        document.getElementById('available-tasks').style.display = 'block';
    } else {
        const target = window.location.hash.substring(1);
        document.getElementById(target).style.display = 'block';
        
        // Update active link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `dashboard_child.php${window.location.hash}`) {
                link.classList.add('active');
            }
        });
    }
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', function() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            document.querySelectorAll('.main-content > div[id]').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(hash).style.display = 'block';
            
            // Update active link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `dashboard_child.php${window.location.hash}`) {
                    link.classList.add('active');
                }
            });
        }
    });
    
    // Auto-hide notifications after 3 seconds
    document.querySelectorAll('.notification').forEach(notification => {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    });
</script>

<style>
    /* Notification styles */
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 350px;
    }
    
    .notification {
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 5px;
        color: white;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }
    
    .notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .notification.success {
        background-color: #4CAF50;
        border-left: 5px solid #388E3C;
    }
    
    .notification.error {
        background-color: #F44336;
        border-left: 5px solid #D32F2F;
    }
    
    /* Difficulty badge styles */
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
    
    .task-difficulty {
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Status styles */
    .task-status {
        padding: 3px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.8rem;
        display: inline-block;
        margin: 5px 0;
    }
    
    .status-pending {
        background-color: #FFE082;
        color: #5D4037;
    }
    
    .status-completed {
        background-color: #C8E6C9;
        color: #1B5E20;
    }
    
    /* Points badge */
    .points-badge {
        font-weight: bold;
        color: var(--waning);
    }
</style>

<?php include 'includes/footer.php'; ?>