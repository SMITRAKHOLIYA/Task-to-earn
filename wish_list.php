<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotChild();

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

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get all wishes for the current child
$wishes = $conn->query("
    SELECT w.*, t.title AS task_title, t.status AS task_status 
    FROM wishes w
    LEFT JOIN tasks t ON w.task_id = t.id
    WHERE w.child_id = $user_id
    ORDER BY w.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

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
                <div class="role">Parent</div>
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
            <div class="notification show success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="notification show error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="section-title">
            <h2><i class="fas fa-lightbulb"></i> My Wish List</h2>
            <button class="btn btn-primary" id="add-wish-btn">
                <i class="fas fa-plus"></i> Add New Wish
            </button>
        </div>
        
        <div class="wish-list-container">
            <?php if (count($wishes) > 0): ?>
                <?php foreach ($wishes as $wish): ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-lightbulb"></i>
                                <?php echo htmlspecialchars($wish['title']); ?>
                            </div>
                            <div class="card-subtitle">
                                Status: 
                                <span class="status-<?php echo $wish['status']; ?>">
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
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Approved
                                </span>
                            <?php elseif ($wish['status'] === 'rejected'): ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-times"></i> Rejected
                                </span>
                            <?php elseif ($wish['task_id']): ?>
                                <a href="dashboard_child.php?complete_task=<?php echo $wish['task_id']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-check"></i> Complete Task
                                </a>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-lightbulb fa-4x text-muted mb-3"></i>
                        <h3>No wishes yet</h3>
                        <p>Submit your first wish using the button above!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
                <input type="text" id="title" name="title" class="form-control" required 
                       placeholder="What would you like?">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" required
                          placeholder="Tell your parent why you want this..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Wish
            </button>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById("addWishModal");
    const btn = document.getElementById("add-wish-btn");
    const span = document.querySelector(".close");
    
    btn.onclick = () => modal.style.display = "block";
    span.onclick = () => modal.style.display = "none";
    window.onclick = (e) => e.target == modal ? modal.style.display = "none" : null;
</script>

<?php include 'includes/footer.php'; ?>