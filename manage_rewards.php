<?php
// manage_rewards.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/pagination.php';
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

// Setup pagination
global $pagination;
$pagination = new Pagination($conn, 4); // Adjust records per page as needed, e.g., 10
$sql = "SELECT * FROM rewards WHERE created_by = ? ORDER BY id DESC";
$params = [$_SESSION['user_id']];
$param_types = "i";
$setup = $pagination->setup($sql, $params, $param_types);

// Get paginated rewards
$paged_sql = $sql . " LIMIT ?, ?";
$stmt = $conn->prepare($paged_sql);
$params[] = $setup['offset'];
$params[] = $setup['records_per_page'];
$param_types .= "ii";
$stmt->bind_param($param_types, ...$params);
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

<style>
/* Enhanced styles for the suggestions feature */
.suggestions-container {
    position: relative;
    width: 100%;
    margin-bottom: 5px;
}

.suggestions-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--dark);
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 250px;
    overflow-y: auto;
    display: none;
    color:white;
}

.suggestion-item {
    padding: 12px 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggestion-item:hover {
    background-color: #5d7fa7ff;
}

.suggestion-item:not(:last-child) {
    border-bottom: 1px solid #eee;
}

.suggestion-title {
    font-weight: 500;
    color: #ffffffff;
    flex: 1;
}

.suggestion-points {
    font-size: 0.85rem;
    color: #4a6cf7;
    background-color: #f0f5ff;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 600;
}

/* Make sure the input field has rounded bottom corners when suggestions are visible */
.suggestions-visible {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    border-color: #4a6cf7;
    box-shadow: 0 0 0 2px rgba(74, 108, 247, 0.1);
}

/* Add a nice header to the suggestions */
.suggestions-header {
    padding: 10px 15px;
    background-color: #5d7fa7ff;
    font-size: 0.85rem;
    color: #6c757d;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

.suggestions-close {
    cursor: pointer;
    color: #4a6cf7;
    font-weight: bold;
}

/* Scrollbar styling for suggestions */
.suggestions-list::-webkit-scrollbar {
    width: 6px;
}

.suggestions-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 0 0 4px 0;
}

.suggestions-list::-webkit-scrollbar-thumb {
    background: #c2c2c2;
    border-radius: 3px;
}

.suggestions-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Animation for suggestions */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.suggestions-list {
    animation: fadeIn 0.2s ease-out;
}
</style>

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
                <?php echo $pagination->render(); ?>
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
                <div class="suggestions-container">
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Reward title" value="<?php echo htmlspecialchars($title); ?>">
                    <div class="suggestions-list" id="suggestions-list">
                        <!-- Suggestions will be populated by JavaScript -->
                    </div>
                </div>
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
    // Reward suggestions data
    const rewardSuggestions = [
        { title: "Extra screen time (30 minutes)", points: 50 },
        { title: "Choose a movie for family night", points: 100 },
        { title: "Stay up 30 minutes later", points: 60 },
        { title: "Pick a special dessert", points: 70 },
        { title: "One-time skip on a chore", points: 100 },
        { title: "Special one-on-one time with parent", points: 80 },
        { title: "New book of choice", points: 100 },
        { title: "Trip to the park", points: 150 },
        { title: "Favorite home-cooked meal", points: 120 },
        { title: "Small toy or game", points: 200 }
    ];

    // Modal functionality
    const modal = document.getElementById("addRewardModal");
    const btn = document.getElementById("add-reward-btn");
    const span = document.getElementsByClassName("close")[0];
    const titleInput = document.getElementById('title');
    const pointsInput = document.getElementById('points');
    const suggestionsList = document.getElementById('suggestions-list');
    
    btn.onclick = function() {
        modal.style.display = "block";
    }
    
    span.onclick = function() {
        modal.style.display = "none";
        hideSuggestions();
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            hideSuggestions();
        }
    }
    
    // Show suggestions when title input is focused
    titleInput.addEventListener('focus', function() {
        showSuggestions();
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!titleInput.contains(e.target) && !suggestionsList.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    // Function to show suggestions
    function showSuggestions() {
        // Clear previous suggestions
        suggestionsList.innerHTML = '';
        
        // Add header to suggestions
        const header = document.createElement('div');
        header.className = 'suggestions-header';
        header.innerHTML = `
            <span>Reward suggestions</span>
            <span class="suggestions-close" id="close-suggestions">×</span>
        `;
        suggestionsList.appendChild(header);
        
        // Add close event to the close button
        document.getElementById('close-suggestions').addEventListener('click', function(e) {
            e.stopPropagation();
            hideSuggestions();
        });
        
        // Add each suggestion to the list
        rewardSuggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `
                <div class="suggestion-title">${suggestion.title}</div>
                <div class="suggestion-points">${suggestion.points} pts</div>
            `;
            
            // Add click event to populate the form
            item.addEventListener('click', function() {
                titleInput.value = suggestion.title;
                pointsInput.value = suggestion.points;
                hideSuggestions();
                
                // Auto-focus on description field for better UX
                document.getElementById('description').focus();
            });
            
            suggestionsList.appendChild(item);
        });
        
        // Show the suggestions
        suggestionsList.style.display = 'block';
        titleInput.classList.add('suggestions-visible');
    }
    
    // Function to hide suggestions
    function hideSuggestions() {
        suggestionsList.style.display = 'none';
        titleInput.classList.remove('suggestions-visible');
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