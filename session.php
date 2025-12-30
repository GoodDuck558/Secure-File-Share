<?php
// session.php — include at the top of all protected pages

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);


// Start session
session_start();

// --- Session fixation prevention ---
// Regenerate ID periodically (every 30 minutes) or on login
if (!isset($_SESSION['rotated_at'])) {
    $_SESSION['rotated_at'] = time();
} elseif (time() - $_SESSION['rotated_at'] > 1800) { // 30 min
    session_regenerate_id(true);
    $_SESSION['rotated_at'] = time();
}

// --- Timeouts ---
$inactiveLimit = 600;  // 10 min inactivity
$absoluteLimit = 7200; // 2 hours absolute

// Initialize timestamps if missing
if (!isset($_SESSION['created_at'])) $_SESSION['created_at'] = time();
if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();

// Absolute lifetime: destroy session
if (time() - $_SESSION['created_at'] > $absoluteLimit) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Inactivity timeout
if (time() - $_SESSION['last_activity'] > $inactiveLimit) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// --- Optional: enforce login ---
if (empty($skip_login_check) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Optional: per-session upload limit ---
if (!isset($_SESSION['upload_count'])) $_SESSION['upload_count'] = 0;

// Usage example in upload.php:
// $_SESSION['upload_count']++;
// if($_SESSION['upload_count'] > 10) die("Too many uploads this session.");
?>
