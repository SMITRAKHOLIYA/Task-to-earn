<?php

// manage_rewards.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
redirectIfNotAdmin();

// Get children for this admin
$children = [];
$sql = "SELECT * FROM users WHERE role = 'child' AND parent_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);

// Initialize variables
$title = $description = '';
$points = 0;
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_reward'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $points = intval($_POST['points']);
        $created_by = $_SESSION['user_id'];
        
        if (empty($title)) {
            $error = "Title is required";
        } elseif (empty($description)) {
            $error = "Description is required";
        } elseif ($points <= 0) {
            $error = "Points must be a positive number";
        } else {
            $stmt = $conn->prepare("INSERT INTO rewards (title, description, points, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $title, $description, $points, $created_by);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Reward added successfully!";
                header("Location: manage_rewards.php");
                exit;
            } else {
                $error = "Error adding reward: " . $conn->error;
            }
        }
    }
    
    if (isset($_POST['delete_reward'])) {
        $reward_id = intval($_POST['reward_id']);
        
        // Verify the reward belongs to current admin before deleting
        $verifyStmt = $conn->prepare("DELETE FROM rewards WHERE id = ? AND created_by = ?");
        $verifyStmt->bind_param("ii", $reward_id, $_SESSION['user_id']);
        
        if ($verifyStmt->execute()) {
            $_SESSION['success_message'] = "Reward deleted successfully!";
            header("Location: manage_rewards.php");
            exit;
        } else {
            $error = "Error deleting reward: " . $conn->error;
        }
    }
}

// Get rewards created by current admin
$stmt = $conn->prepare("SELECT * FROM rewards WHERE created_by = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$rewards = $result->fetch_all(MYSQLI_ASSOC);

// Fetch user profile data
$profile_pic = null;
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $profile_pic = $user['profile_picture'];
}
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
                <a href="manage_rewards.php" class="nav-link active">
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
            <script>
                setTimeout(() => {
                    document.getElementById('success-notification').classList.remove('show');
                }, 3000);
            </script>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="notification show" style="background: rgba(225, 112, 85, 0.2); border-left: 4px solid var(--danger);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification.show').classList.remove('show');
                }, 5000);
            </script>
        <?php endif; ?>
        
        <div class="section-title">
            <h2><i class="fas fa-gift"></i> Manage Rewards</h2>
            <button class="btn" id="add-reward-btn">
                <i class="fas fa-plus"></i> Add New Reward
            </button>
        </div>
        
        <div class="card-grid">
            <?php if (count($rewards) > 0): ?>
                <?php foreach ($rewards as $reward): ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-gift"></i>
                                <?php echo htmlspecialchars($reward['title']); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="task-points">
                                <i class="fas fa-coins"></i>
                                Cost: <span class="points-badge"><?php echo $reward['points']; ?> pts</span>
                            </div>
                            <div class="task-description">
                                <?php echo htmlspecialchars($reward['description']); ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <form method="POST">
                                <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                <button type="submit" name="delete_reward" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 40px;">
                        <i class="fas fa-gift" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                        <h3>No rewards yet</h3>
                        <p>Click "Add New Reward" to create your first reward.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Reward Modal -->
<div id="addRewardModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Reward</h2>
        <form method="POST" id="reward-form">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" class="form-control" required 
                       placeholder="Reward title" value="<?php echo htmlspecialchars($title); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="3" required 
                          placeholder="Reward description"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label for="points">Points *</label>
                <input type="number" id="points" name="points" class="form-control" min="1" required 
                       placeholder="Point cost" value="<?php echo $points > 0 ? $points : ''; ?>">
            </div>
            <button type="submit" name="add_reward" class="btn">Add Reward</button>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById("addRewardModal");
    const btn = document.getElementById("add-reward-btn");
    const span = document.getElementsByClassName("close")[0];
    
    btn.onclick = function() {
        modal.style.display = "block";
    }
    
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
    // Form validation
    document.getElementById('reward-form').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const points = document.getElementById('points').value;
        
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the reward');
            return;
        }
        
        if (!description) {
            e.preventDefault();
            alert('Please enter a description for the reward');
            return;
        }
        
        if (!points || points < 1) {
            e.preventDefault();
            alert('Please enter a valid number of points (at least 1)');
            return;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>