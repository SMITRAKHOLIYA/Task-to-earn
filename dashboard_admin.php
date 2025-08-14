<?php
// dashboard_admin.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotAdmin();

$admin_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $points = intval($_POST['points']);
        $difficulty = $_POST['difficulty'];
        $assigned_to = $_POST['child_id'] ?: NULL;
        
        if ($assigned_to) {
            $verifyStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND parent_id = ?");
            $verifyStmt->bind_param("ii", $assigned_to, $admin_id);
            $verifyStmt->execute();
            
            if ($verifyStmt->get_result()->num_rows === 0) {
                $_SESSION['error_message'] = "Invalid child selection";
                header("Location: dashboard_admin.php");
                exit;
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, points, difficulty, created_by, assigned_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissi", $title, $description, $points, $difficulty, $admin_id, $assigned_to);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Task added successfully!";
        header("Location: dashboard_admin.php");
        exit;
    }
    
    if (isset($_POST['delete_task'])) {
        $task_id = intval($_POST['task_id']);
        
        $verifyStmt = $conn->prepare("DELETE tasks FROM tasks WHERE id = ? AND created_by = ?");
        $verifyStmt->bind_param("ii", $task_id, $admin_id);
        
        if ($verifyStmt->execute()) {
            $_SESSION['success_message'] = "Task deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting task";
        }
        
        header("Location: dashboard_admin.php");
        exit;
    }
}

// Get tasks
$sql = "SELECT tasks.*, creator.username AS creator, 
        assigned.username AS assigned_to_name
        FROM tasks 
        JOIN users creator ON tasks.created_by = creator.id
        LEFT JOIN users assigned ON tasks.assigned_to = assigned.id
        WHERE tasks.created_by = ? OR assigned.parent_id = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $admin_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Get children
$sql = "SELECT * FROM users WHERE role = 'child' AND parent_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks LEFT JOIN users assigned ON tasks.assigned_to = assigned.id WHERE tasks.created_by = ? OR assigned.parent_id = ?");
$totalStmt->bind_param("ii", $admin_id, $admin_id);
$totalStmt->execute();
$totalTasks = $totalStmt->get_result()->fetch_assoc()['total'];

$completedStmt = $conn->prepare("SELECT COUNT(*) as completed FROM tasks LEFT JOIN users assigned ON tasks.assigned_to = assigned.id WHERE (tasks.created_by = ? OR assigned.parent_id = ?) AND tasks.status='completed'");
$completedStmt->bind_param("ii", $admin_id, $admin_id);
$completedStmt->execute();
$completedTasks = $completedStmt->get_result()->fetch_assoc()['completed'];

$completionPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$taskSuggestions = [
    "Clean your room" => "Easy",
    "Read for 30 minutes" => "Medium",
    "Complete homework" => "Medium",
    "Help with dishes" => "Easy",
    "Practice musical instrument" => "Hard",
    "Do 30 minutes of exercise" => "Medium",
    "Write in journal" => "Easy",
    "Learn a new skill for 30 minutes" => "Hard",
    "Help with household chores" => "Easy",
    "Organize your study area" => "Medium"
];

// Get completions
$completionSql = $conn->prepare("SELECT u.username, t.title, tc.completed_at, tc.points_earned, t.difficulty FROM task_completions tc JOIN users u ON tc.user_id = u.id JOIN tasks t ON tc.task_id = t.id WHERE u.parent_id = ? ORDER BY tc.completed_at DESC");
$completionSql->bind_param("i", $admin_id);
$completionSql->execute();
$completions = $completionSql->get_result()->fetch_all(MYSQLI_ASSOC);

// Get profile picture
$profile_pic = null;
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $profile_pic = $user['profile_picture'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="dashboard">
    <div class="sidebar">
        <div class="user-card">
            <div class="avatar-container">
                <?php if (!empty($profile_pic)): ?>
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" id="user-avatar">
                <?php else: ?>
                    <div class="user-initial" id="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <?php endif; ?>
                <div class="upload-overlay" id="upload-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display: none;">
            <div>
                <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="role">Admin</div>
            </div>
        </div>
        
        <div class="stats-card">
            <div class="stats-label">TOTAL CHILDREN</div>
            <div class="stats-value"><?php echo count($children); ?></div>
            <div class="stats-label">Registered</div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link active">
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
        </ul>
    </div>
    
    <div class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification show" id="success-notification">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification show error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="section-title">
            <h2>
                <i class="fas fa-tasks"></i>
                Manage Tasks
            </h2>
            <div class="filter-buttons">
                <button id="all-tasks-btn" class="filter-btn active" title="Show All Tasks">
                    <i class="fas fa-list"></i>
                </button>
                <button id="pending-tasks-btn" class="filter-btn" title="Show Pending Tasks">
                    <i class="fas fa-hourglass-half"></i>
                </button>
                <button id="completed-tasks-btn" class="filter-btn" title="Show Completed Tasks">
                    <i class="fas fa-check-circle"></i>
                </button>
            </div>
              <button class="btn" id="add-task-btn">
                <i class="fas fa-plus"></i> Add New Task
            </button>
        </div>
        
        <div class="card-grid">
            <?php foreach ($tasks as $task): ?>
                <div class="card animated" data-status="<?php echo strtolower($task['status']); ?>">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-star"></i>
                            <?php echo htmlspecialchars($task['title']); ?>
                        </div>
                        <div class="card-subtitle">
                            Created by: <?php echo htmlspecialchars($task['creator']); ?>
                            <?php if ($task['assigned_to_name']): ?>
                                <br>Assigned to: <?php echo htmlspecialchars($task['assigned_to_name']); ?>
                            <?php endif; ?>
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
                        <div class="task-status <?php echo $task['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo ucfirst($task['status']); ?>
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
                        <?php if ($task['status'] === 'completed'): ?>
                            <span>Completed</span>
                        <?php else: ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-title">
            <h2><i class="fas fa-chart-pie"></i> Statistics</h2>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Children Performance
                </div>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px; background: var(--glass-bg); padding: 20px; border-radius: 15px;">
                        <h3 style="margin-bottom: 15px;">Top Performers</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php 
                            $topPerformerStmt = $conn->prepare("SELECT username, points FROM users WHERE role = 'child' AND parent_id = ? ORDER BY points DESC LIMIT 5");
                            $topPerformerStmt->bind_param("i", $admin_id);
                            $topPerformerStmt->execute();
                            $topPerformers = $topPerformerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($topPerformers as $child): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span><?php echo $child['username']; ?></span>
                                    <span><?php echo $child['points']; ?> pts</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 200px; background: var(--glass-bg); padding: 20px; border-radius: 15px;">
                        <h3 style="margin-bottom: 15px;">Task Completion</h3>
                        <div style="height: 10px; background: var(--dark); border-radius: 5px; margin-bottom: 10px;">
                            <div style="width: <?php echo $completionPercentage; ?>%; height: 100%; background: var(--success); border-radius: 5px;"></div>
                        </div>
                        <div><?php echo $completionPercentage; ?>% of tasks completed</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-title">
            <h2><i class="fas fa-history"></i> Task Completion History</h2>
        </div>
        <div class="card">
            <div class="card-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 12px; text-align: left;">Child</th>
                            <th style="padding: 12px; text-align: left;">Task</th>
                            <th style="padding: 12px; text-align: left;">Difficulty</th>
                            <th style="padding: 12px; text-align: left;">Completed At</th>
                            <th style="padding: 12px; text-align: left;">Points Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completions as $completion): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;"><?php echo htmlspecialchars($completion['username']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($completion['title']); ?></td>
                                <td style="padding: 12px;">
                                    <span class="difficulty-badge difficulty-<?php echo strtolower($completion['difficulty']); ?>">
                                        <?php echo $completion['difficulty']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($completion['completed_at'])); ?></td>
                                <td style="padding: 12px;"><?php echo $completion['points_earned']; ?> pts</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Task</h2>
        <form method="POST">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="child_id">Assign to Child</label>
                <select id="child_id" name="child_id" class="form-control" required>
                    <option value="">Select a Child</option>
                    <?php foreach ($children as $child): ?>
                        <option value="<?php echo $child['id']; ?>">
                            <?php echo htmlspecialchars($child['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty" class="form-control" required>
                    <option value="Easy">Easy</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>
            <div class="form-group">
                <label for="points">Points (Max 100)</label>
                <input type="number" id="points" name="points" class="form-control" min="1" max="100" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3" required placeholder="Task description..."></textarea>
                <div class="suggestions-container">
                    <p><strong>Suggestions:</strong></p>
                    <ul class="suggestions-list">
                        <?php foreach ($taskSuggestions as $suggestion => $difficulty): ?>
                            <li class="suggestion-item" data-suggestion="<?php echo htmlspecialchars($suggestion); ?>" data-difficulty="<?php echo $difficulty; ?>">
                                <i class="fas fa-lightbulb"></i> 
                                <?php echo htmlspecialchars($suggestion); ?>
                                <span class="difficulty-badge difficulty-<?php echo strtolower($difficulty); ?>">
                                    <?php echo $difficulty; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="submit" name="add_task" class="btn">Add Task</button>
        </form>
    </div>
</div>

<script>
    // Task filtering functionality
    document.addEventListener('DOMContentLoaded', function() {
        const allTasksBtn = document.getElementById('all-tasks-btn');
        const pendingTasksBtn = document.getElementById('pending-tasks-btn');
        const completedTasksBtn = document.getElementById('completed-tasks-btn');
        const taskCards = document.querySelectorAll('.card');

        allTasksBtn.addEventListener('click', function() {
            setActiveFilter(this);
            taskCards.forEach(card => {
                card.style.display = 'block';
            });
        });

        pendingTasksBtn.addEventListener('click', function() {
            setActiveFilter(this);
            taskCards.forEach(card => {
                if (card.dataset.status === 'pending') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        completedTasksBtn.addEventListener('click', function() {
            setActiveFilter(this);
            taskCards.forEach(card => {
                if (card.dataset.status === 'completed') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        function setActiveFilter(activeBtn) {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            activeBtn.classList.add('active');
        }
    });

    // Modal functionality
    const modal = document.getElementById("addTaskModal");
    const btn = document.getElementById("add-task-btn");
    const span = document.getElementsByClassName("close")[0];
    
    if (btn) btn.onclick = function() { modal.style.display = "block"; }
    if (span) span.onclick = function() { modal.style.display = "none"; }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
    // Task suggestion functionality
    document.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
            document.getElementById('description').value = this.getAttribute('data-suggestion');
            document.getElementById('difficulty').value = this.getAttribute('data-difficulty');
        });
    });
    
    // Avatar Upload Functionality
    document.getElementById('upload-overlay').addEventListener('click', function() {
        document.getElementById('avatar-upload').click();
    });

    document.getElementById('avatar-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        if (!file.type.match('image.*')) {
            showNotification('Please select an image file (JPG, PNG, GIF)', 'error');
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            showNotification('File size must be less than 2MB', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('avatar', file);
        
        const avatarContainer = document.getElementById('user-avatar');
        const originalContent = avatarContainer.innerHTML;
        avatarContainer.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (avatarContainer.tagName === 'IMG') {
                    avatarContainer.src = data.path + '?' + new Date().getTime();
                } else {
                    const newAvatar = document.createElement('img');
                    newAvatar.src = data.path + '?' + new Date().getTime();
                    newAvatar.alt = 'Profile';
                    newAvatar.id = 'user-avatar';
                    avatarContainer.replaceWith(newAvatar);
                }
                showNotification('Avatar updated successfully!', 'success');
            } else {
                showNotification(data.error || 'Avatar update failed', 'error');
                avatarContainer.innerHTML = originalContent;
            }
        })
        .catch(error => {
            showNotification('Upload failed. Please try again.', 'error');
            avatarContainer.innerHTML = originalContent;
        });
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification show ${type}`;
        notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
        
        document.querySelector('.main-content').prepend(notification);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
</script>

<style>
    .avatar-container {
        position: relative;
        width: 60px;
        height: 60px;
    }

    .upload-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }

    .avatar-container:hover .upload-overlay {
        opacity: 1;
    }

    .upload-overlay i {
        color: white;
        font-size: 1.5rem;
    }

    .user-card img,
    .user-card .user-initial {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-card .user-initial {
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary);
        color: white;
        font-size: 24px;
        font-weight: bold;
    }

    .notification.error {
        background: rgba(225, 112, 85, 0.2);
        border-left: 4px solid var(--danger);
    }

    .suggestions-container {
        background: var(--glass-bg);
        padding: 15px;
        border-radius: 10px;
        margin-top: 10px;
    }
    
    .suggestions-list {
        list-style: none;
        padding: 0;
        margin: 10px 0 0;
    }
    
    .suggestion-item {
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 5px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .suggestion-item:hover {
        background: rgba(108, 92, 231, 0.2);
    }
    
    .suggestion-item i {
        color: var(--warning);
        margin-right: 8px;
    }
    
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

    .filter-buttons {
        display: flex;
        gap: 10px;
        margin-right: auto;
        margin-left: 15px;
    }
    
    .filter-btn {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--text);
        transition: all 0.2s;
    }
    
    .filter-btn:hover {
        background: rgba(108, 92, 231, 0.2);
    }
    
    .filter-btn.active {
        background: var(--primary);
        color: white;
    }

    .status-pending {
        color: var(--warning);
        font-weight: bold;
    }

    .status-completed {
        color: var(--success);
        font-weight: bold;
    }
</style>

<?php include 'includes/footer.php'; ?>