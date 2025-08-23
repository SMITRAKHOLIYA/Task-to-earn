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
                <div class="suggestions-container">
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="What would you like?">
                    <div class="suggestions-list" id="suggestions-list">
                        <!-- Suggestions will be populated by JavaScript -->
                    </div>
                </div>
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
    // Wish suggestions data
    const wishSuggestions = [
        "New video game",
        "Trip to the amusement park",
        "Sleepover with friends",
        "New bicycle",
        "Art supplies set",
        "Movie night with family",
        "Special dessert",
        "Staying up 30 minutes later",
        "New book series",
        "Sports equipment",
        "Music lessons",
        "Board game",
        "Science kit",
        "Cooking class",
        "Outdoor camping experience"
    ];

    // Modal functionality
    const modal = document.getElementById("addWishModal");
    const btn = document.getElementById("add-wish-btn");
    const span = document.querySelector(".close");
    const titleInput = document.getElementById('title');
    const suggestionsList = document.getElementById('suggestions-list');
    
    btn.onclick = () => modal.style.display = "block";
    span.onclick = () => {
        modal.style.display = "none";
        hideSuggestions();
    };
    window.onclick = (e) => {
        if (e.target == modal) {
            modal.style.display = "none";
            hideSuggestions();
        }
    };
    
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
            <span>Wish ideas</span>
            <span class="suggestions-close" id="close-suggestions">×</span>
        `;
        suggestionsList.appendChild(header);
        
        // Add close event to the close button
        document.getElementById('close-suggestions').addEventListener('click', function(e) {
            e.stopPropagation();
            hideSuggestions();
        });
        
        // Add each suggestion to the list
        wishSuggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `
                <div class="suggestion-title">${suggestion}</div>
            `;
            
            // Add click event to populate the form
            item.addEventListener('click', function() {
                titleInput.value = suggestion;
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
</script>

<?php include 'includes/footer.php'; ?>