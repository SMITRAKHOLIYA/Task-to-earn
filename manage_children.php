<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// manage_children.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotAdmin();

// Fetch user info
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new child
    if (isset($_POST['add_child'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $points = intval($_POST['points']);
        $parent_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, points, parent_id) VALUES (?, ?, 'child', ?, ?)");
        $stmt->bind_param("ssii", $username, $password, $points, $parent_id);
        $stmt->execute();
        
        header("Location: manage_children.php");
        exit;
    }
    
    // Update child
    if (isset($_POST['update_child'])) {
        $child_id = intval($_POST['child_id']);
        $username = $_POST['username'];
        $points = intval($_POST['points']);
        
        // Check if password was provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, points = ? WHERE id = ?");
            $stmt->bind_param("ssii", $username, $password, $points, $child_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, points = ? WHERE id = ?");
            $stmt->bind_param("sii", $username, $points, $child_id);
        }
        
        $stmt->execute();
        header("Location: manage_children.php");
        exit;
    }
    
    // Delete child
    if (isset($_POST['delete_child'])) {
        $child_id = intval($_POST['child_id']);
        
        // Delete achievements first
        $stmt = $conn->prepare("DELETE FROM achievements WHERE user_id = ?");
        $stmt->bind_param("i", $child_id);
        $stmt->execute();

        // Now delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'child'");
        $stmt->bind_param("i", $child_id);
        $stmt->execute();
        
        header("Location: manage_children.php");
        exit;
    }
}

// Get all children for this admin
$sql = "SELECT * FROM users WHERE role = 'child' AND parent_id = ? ORDER BY username ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$children = $result->fetch_all(MYSQLI_ASSOC);
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
                <a href="manage_children.php" class="nav-link active">
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
        <div class="section-title">
            <h2>
                <i class="fas fa-users"></i>
                Manage Your Children
            </h2>
            <button class="btn" id="add-child-btn">
                <i class="fas fa-plus"></i> Add New Child
            </button>
        </div>
        
        <div class="card">
            <div class="card-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 12px; text-align: left;">Username</th>
                            <th style="padding: 12px; text-align: left;">Points</th>
                            <th style="padding: 12px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children as $child): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 12px;"><?php echo htmlspecialchars($child['username']); ?></td>
                                <td style="padding: 12px;"><?php echo $child['points']; ?> pts</td>
                                <td style="padding: 12px;">
                                    <button class="btn btn-primary edit-child-btn" 
                                            data-id="<?php echo $child['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($child['username']); ?>"
                                            data-points="<?php echo $child['points']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                        <button type="submit" name="delete_child" class="btn btn-danger">
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
    </div>
</div>

<!-- Add Child Modal -->
<div id="addChildModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Child</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="points">Starting Points</label>
                <input type="number" id="points" name="points" class="form-control" min="0" value="0" required>
            </div>
            <button type="submit" name="add_child" class="btn">Add Child</button>
        </form>
    </div>
</div>

<!-- Edit Child Modal -->
<div id="editChildModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Child</h2>
        <form method="POST">
            <input type="hidden" name="child_id" id="edit_child_id">
            <div class="form-group">
                <label for="edit_username">Username</label>
                <input type="text" id="edit_username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_password">Password (Leave blank to keep current)</label>
                <input type="password" id="edit_password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_points">Points</label>
                <input type="number" id="edit_points" name="points" class="form-control" min="0" required>
            </div>
            <button type="submit" name="update_child" class="btn">Update Child</button>
        </form>
    </div>
</div>

<script>
    const addModal = document.getElementById("addChildModal");
    const addBtn = document.getElementById("add-child-btn");
    const addSpan = addModal.getElementsByClassName("close")[0];
    
    addBtn.onclick = function() {
        addModal.style.display = "block";
    }
    
    addSpan.onclick = function() {
        addModal.style.display = "none";
    }
    
    // Edit modal functionality
    const editModal = document.getElementById("editChildModal");
    const editSpans = editModal.getElementsByClassName("close");
    const editBtns = document.querySelectorAll(".edit-child-btn");
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_child_id').value = this.dataset.id;
            document.getElementById('edit_username').value = this.dataset.username;
            document.getElementById('edit_points').value = this.dataset.points;
            editModal.style.display = "block";
        });
    });
    
    editSpans[0].onclick = function() {
        editModal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == addModal) {
            addModal.style.display = "none";
        }
        if (event.target == editModal) {
            editModal.style.display = "none";
        }
    }
</script>

<script src="../assets/js/script.js"></script>
<?php include 'includes/footer.php'; ?>