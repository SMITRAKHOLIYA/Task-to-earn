<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['avatar'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG and GIF are allowed.']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'File size exceeds 2MB limit.']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
        exit;
    }
    
    // Create uploads directory if not exists
    if (!is_dir('uploads')) {
        if (!mkdir('uploads', 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create upload directory.']);
            exit;
        }
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $target_path = 'uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Delete old avatar if exists
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $old_avatar = $result->fetch_assoc()['profile_picture'];
            if ($old_avatar && file_exists($old_avatar)) {
                @unlink($old_avatar);
            }
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $target_path, $user_id);
        if ($stmt->execute()) {
            $_SESSION['profile_picture'] = $target_path;
            echo json_encode(['success' => true, 'path' => $target_path]);
            exit;
        } else {
            @unlink($target_path); // Clean up if DB update fails
        }
    }
}

echo json_encode(['success' => false, 'error' => 'File upload failed. Please try again.']);
?>