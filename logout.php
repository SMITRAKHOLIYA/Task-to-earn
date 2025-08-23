<?php
// logout.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/session_tracker.php';

trackLogout();
session_destroy();
header("Location: index.php");
exit;
?>