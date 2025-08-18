<?php

// complete_task.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
redirectIfNotChild();

$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$wish_id = isset($_GET['wish_id']) ? intval($_GET['wish_id']) : 0;

// Verify the task belongs to the current child
$child_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND child_id = ?");
$stmt->bind_param("ii", $task_id, $child_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: wish_list.php?error=invalid_task");
    exit;
}

// Mark task as completed
$conn->query("UPDATE tasks SET status = 'completed' WHERE id = $task_id");

// Redirect back to wish list with success message
header("Location: wish_list.php?task_completed=true&wish_id=$wish_id");
exit;
