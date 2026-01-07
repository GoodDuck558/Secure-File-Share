<?php
// session.php — hardened session management

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

/* ---------- Fixation protection ---------- */
if (!isset($_SESSION['rotated_at'])) {
    session_regenerate_id(true);
    $_SESSION['rotated_at'] = time();
} elseif (time() - $_SESSION['rotated_at'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['rotated_at'] = time();
}

/* ---------- Timeouts ---------- */
$inactiveLimit = 600;
$absoluteLimit = 7200;

$_SESSION['created_at'] ??= time();
$_SESSION['last_activity'] ??= time();

if (time() - $_SESSION['created_at'] > $absoluteLimit) {
    session_unset();
    session_destroy();
    header('Location: landing.php');
    exit;
}

if (time() - $_SESSION['last_activity'] > $inactiveLimit) {
    session_unset();
    session_destroy();
    header('Location: landing.php');
    exit;
}

$_SESSION['last_activity'] = time();

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Auth enforcement ---------- */
if (empty($skip_login_check) && empty($_SESSION['user_id'])) {
    header('Location: landing.php');
    exit;
}

/* ---------- Rate limiting ---------- */
$_SESSION['upload_count'] ??= 0;
