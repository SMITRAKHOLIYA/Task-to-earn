<?php
// includes/config.php
$host  = "localhost";
$username = "root";
$password = "root";
$dbname = "task_to_earn";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected successfully!";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','child') NOT NULL,
    points INT(6) DEFAULT 0,
    parent_id INT(6) UNSIGNED DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    points INT(6) NOT NULL,
    created_by INT(6) UNSIGNED,
    assigned_to INT(6) UNSIGNED,
    status ENUM('pending','completed') DEFAULT 'pending',
    completed_by INT(6) UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
)";
$conn->query($sql);


$sql = "CREATE TABLE IF NOT EXISTS task_completions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    task_id INT(6) UNSIGNED,
    points_earned INT(6) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id)
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS achievements (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,  
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS users_login (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    username VARCHAR(30) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS rewards (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    points INT(6) NOT NULL,
    created_by INT(6) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS wishes (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    child_id INT(6) UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES users(id)
)";
$conn->query($sql);

// Create initial admin if not exists
$conn->query("INSERT IGNORE INTO users (id, username, password, role) VALUES 
    (1, 'admin', '".password_hash('admin123', PASSWORD_DEFAULT)."', 'admin')
");

// Create sample children if not exists
$conn->query("INSERT IGNORE INTO users (id, username, password, role, parent_id) VALUES 
    (2, 'child1', '".password_hash('child123', PASSWORD_DEFAULT)."', 'child', 1),
    (3, 'child2', '".password_hash('child123', PASSWORD_DEFAULT)."', 'child', 1)
");

// Create sample tasks
$conn->query("INSERT IGNORE INTO tasks (id, title, description, points, created_by, assigned_to) VALUES 
    (1, 'Clean Your Room', 'Organize your room and put everything in its place', 50, 1, 2),
    (2, 'Complete Homework', 'Finish all assigned homework before dinner', 75, 1, 2),
    (3, 'Read for 30 Minutes', 'Read a book of your choice for 30 minutes', 40, 1, 3),
    (4, 'Help with Dishes', 'Wash and dry the dishes after dinner', 30, 1, 3)
");

// // Create sample rewards
// $conn->query("INSERT IGNORE INTO rewards (title, description, points, created_by) VALUES 
//     ('Game Time', '30 minutes of extra game time', 100, 1),
//     ('Ice Cream', 'Your favorite ice cream treat', 75, 1)
// ");


?>