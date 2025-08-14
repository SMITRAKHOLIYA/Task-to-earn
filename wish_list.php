<?php
// wish_list.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
redirectIfNotChild();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $child_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO wishes (child_id, title, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $child_id, $title, $description);
        
        if ($stmt->execute()) {
            $success_message = "Wish submitted successfully!";
        } else {
            $error_message = "Error submitting wish: " . $conn->error;
        }
    } else {
        $error_message = "Please fill in both title and description";
    }
}

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

// Get all wishes for the current child
$child_id = $_SESSION['user_id'];
$wishes = $conn->query("SELECT * FROM wishes WHERE child_id = $child_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
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
                <a href="dashboard_child.php#available-tasks" class="nav-link">
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
                <a href="wish_list.php" class="nav-link active">
                    <i class="fas fa-lightbulb"></i>
                    <span>My Wish List</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="notification show" id="success-notification">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <script>
                setTimeout(() => {
                    document.getElementById('success-notification').classList.remove('show');
                }, 3000);
            </script>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="notification show" style="background: rgba(225, 112, 85, 0.2); border-left: 4px solid var(--danger);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification.show').classList.remove('show');
                }, 3000);
            </script>
        <?php endif; ?>
        
        <div class="section-title">
            <h2><i class="fas fa-lightbulb"></i> My Wish List</h2>
            <button class="btn" id="add-wish-btn">
                <i class="fas fa-plus"></i> Add New Wish
            </button>
        </div>
        
        <?php if (count($wishes) > 0): ?>
            <div class="card-grid">
                <?php foreach ($wishes as $wish): ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-lightbulb"></i>
                                <?php echo htmlspecialchars($wish['title']); ?>
                            </div>
                            <div class="card-subtitle">
                                Status: 
                                <span class="
                                    <?php 
                                    if ($wish['status'] === 'approved') echo 'status-completed';
                                    elseif ($wish['status'] === 'rejected') echo 'status-pending';
                                    else echo 'status-pending';
                                    ?>
                                ">
                                    <?php echo ucfirst($wish['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="task-description">
                                <?php echo htmlspecialchars($wish['description']); ?>
                            </div>
                        </div>
                            <div class="card-footer">
                                <div>
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($wish['created_at'])); ?>
                                </div>
                                <?php if ($wish['status'] === 'approved'): ?>
                                    <span class="points-badge">
                                        <i class="fas fa-check"></i> Approved
                                    </span>
                                <?php elseif ($wish['status'] === 'rejected'): ?>
                                    <span class="points-badge" style="background: var(--danger);">
                                        <i class="fas fa-times"></i> Rejected
                                    </span>
                                <?php elseif ($wish['task_id']): ?>
                                    <a href="dashboard_child.php?complete_task=<?php echo $wish['task_id']; ?>" 
                                    class="btn btn-success">
                                        <i class="fas fa-check"></i> Complete Task
                                    </a>
                                <?php else: ?>
                                    <span class="points-badge" style="background: var(--warning); color: var(--dark);">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 40px;">
                    <i class="fas fa-lightbulb" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                    <h3>No wishes yet</h3>
                    <p>You haven't submitted any wishes yet. Click "Add New Wish" to get started!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Wish Modal -->
<div id="addWishModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Wish</h2>
        <form method="POST">
            <div class="form-group">
                <label for="title">Wish Title</label>
                <input type="text" id="title" name="title" class="form-control" required placeholder="What would you like to wish for?">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" required placeholder="Tell your parent more about your wish..."></textarea>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Submit Wish
            </button>
        </form>
    </div>
</div>


<script>
    // Modal functionality
    const modal = document.getElementById("addWishModal");
    const btn = document.getElementById("add-wish-btn");
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
</script>

<style>
    .status-completed {
        background: rgba(0, 184, 148, 0.2);
        color: var(--success);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
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