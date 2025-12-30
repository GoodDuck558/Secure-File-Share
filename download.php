<?php
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to download files.");
}

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing token.");
}

$token = $_GET['token'];

$stmt = $db->prepare("SELECT * FROM files WHERE token = ?");
$stmt->execute([$token]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) die("File not found.");

// Check expiration
$now = new DateTime();
$expiresAt = new DateTime($file['expires_at']);
if ($now > $expiresAt) {
    $filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (file_exists($filePath)) unlink($filePath);

    $deleteStmt = $db->prepare("DELETE FROM files WHERE token = ?");
    $deleteStmt->execute([$token]);

    die("This link has expired.");
}

// Serve file
$filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
if (!file_exists($filePath)) die("File missing from server.");

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;
