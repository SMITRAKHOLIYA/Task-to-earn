<?php
// manage_wishes.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_wish'])) {
        $wish_id = intval($_POST['wish_id']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE wishes SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $wish_id);
        $stmt->execute();
    }
    
    if (isset($_POST['delete_wish'])) {
        $wish_id = intval($_POST['wish_id']);
        $stmt = $conn->prepare("DELETE FROM wishes WHERE id = ?");
        $stmt->bind_param("i", $wish_id);
        $stmt->execute();
    }

    if (isset($_POST['assign_task'])) {
        $wish_id = intval($_POST['wish_id']);
        $child_id = intval($_POST['child_id']);
        $title = $_POST['title'];
        $description = $_POST['description'];
        $points = intval($_POST['points']);
        $created_by = $_SESSION['user_id'];

        // Create task
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, points, created_by, assigned_to, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssiii", $title, $description, $points, $created_by, $child_id);
        
        if ($stmt->execute()) {
            $task_id = $conn->insert_id;
            
            // Link task to wish
            $updateStmt = $conn->prepare("UPDATE wishes SET task_id = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $task_id, $wish_id);
            $updateStmt->execute();
            
            $_SESSION['success_message'] = "Task assigned successfully! Wish will be approved when task is completed.";
        } else {
            $error = "Error assigning task: " . $conn->error;
        }
    }
}

// Get all children for this admin
$children = $conn->query("SELECT * FROM users WHERE role = 'child' AND parent_id = " . $_SESSION['user_id'])->fetch_all(MYSQLI_ASSOC);

// Get all wishes with child names and task info
$wishes = $conn->query("
    SELECT w.*, u.username AS child_name, t.title AS task_title, t.status AS task_status
    FROM wishes w
    JOIN users u ON w.child_id = u.id
    LEFT JOIN tasks t ON w.task_id = t.id
    WHERE u.parent_id = " . $_SESSION['user_id'] . "
    ORDER BY w.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

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
                <a href="manage_rewards.php" class="nav-link">
                    <i class="fas fa-gift"></i>
                    <span>Manage Rewards</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_wishes.php" class="nav-link active">
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
        
        <div class="section-title">
            <h2><i class="fas fa-lightbulb"></i> Manage Wishes</h2>
        </div>
        
        <?php if (count($wishes) > 0): ?>
            <div class="card">
                <div class="card-body">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 12px; text-align: left;">Child</th>
                                <th style="padding: 12px; text-align: left;">Title</th>
                                <th style="padding: 12px; text-align: left;">Description</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Task</th>
                                <th style="padding: 12px; text-align: left;">Created At</th>
                                <th style="padding: 12px; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wishes as $wish): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($wish['child_name']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($wish['title']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($wish['description']); ?></td>
                                    <td style="padding: 12px;">
                                        <span class="status-<?php echo $wish['status']; ?>">
                                            <?php echo ucfirst($wish['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php if ($wish['task_id']): ?>
                                            <span class="status-<?php echo $wish['task_status']; ?>">
                                                <?php echo htmlspecialchars($wish['task_title']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($wish['created_at'])); ?></td>
                                    <td style="padding: 12px;">
                                        <?php if ($wish['status'] == 'pending' && !$wish['task_id']): ?>
                                            <button class="btn btn-primary assign-task-btn" 
                                                    data-wish-id="<?php echo $wish['id']; ?>"
                                                    data-child-id="<?php echo $wish['child_id']; ?>"
                                                    data-wish-title="<?php echo htmlspecialchars($wish['title']); ?>">
                                                <i class="fas fa-tasks"></i> Assign Task
                                            </button>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="wish_id" value="<?php echo $wish['id']; ?>">
                                            <select name="status" class="form-control" style="display: inline-block; width: auto; margin-right: 5px;">
                                                <option value="pending" <?php echo $wish['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $wish['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $wish['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_wish" class="btn btn-primary" style="display: inline-block;">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="wish_id" value="<?php echo $wish['id']; ?>">
                                            <button type="submit" name="delete_wish" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 40px;">
                    <i class="fas fa-lightbulb" style="font-size: 3rem; opacity: 0.3; margin-bottom: 20px;"></i>
                    <h3>No wishes found</h3>
                    <p>Your children haven't submitted any wishes yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Task Modal -->
<div id="assignTaskModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Assign Task for Wish</h2>
        <form method="POST">
            <input type="hidden" name="wish_id" id="assign_wish_id">
            <input type="hidden" name="child_id" id="assign_child_id">
            <div class="form-group">
                <label for="assign_title">Title</label>
                <input type="text" id="assign_title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="assign_description">Description</label>
                <textarea id="assign_description" name="description" class="form-control" rows="3" required
                          placeholder="Task the child must complete to get this wish approved"></textarea>
            </div>
            <div class="form-group">
                <label for="assign_points">Points</label>
                <input type="number" id="assign_points" name="points" class="form-control" min="1" required>
            </div>
            <button type="submit" name="assign_task" class="btn">
                <i class="fas fa-tasks"></i> Assign Task
            </button>
        </form>
    </div>
</div>

<style>
    .status-pending {
        background: rgba(253, 203, 110, 0.2);
        color: var(--warning);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-approved {
        background: rgba(0, 184, 148, 0.2);
        color: var(--success);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-rejected {
        background: rgba(225, 112, 85, 0.2);
        color: var(--danger);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<script>
    // Assign Task Modal functionality
    const assignModal = document.getElementById("assignTaskModal");
    const assignBtns = document.querySelectorAll(".assign-task-btn");
    const span = assignModal.querySelector(".close");
    
    assignBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('assign_wish_id').value = this.dataset.wishId;
            document.getElementById('assign_child_id').value = this.dataset.childId;
            document.getElementById('assign_title').value = "Complete: " + this.dataset.wishTitle;
            assignModal.style.display = "block";
        });
    });
    
    span.onclick = function() {
        assignModal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == assignModal) {
            assignModal.style.display = "none";
        }
    }
</script>

<?php include 'includes/footer.php'; ?>