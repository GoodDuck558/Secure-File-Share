<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// connect to SQLite
$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. get token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing token.");
}

$token = $_GET['token'];

// 2. fetch file record
$stmt = $db->prepare("SELECT * FROM files WHERE token = ?");
$stmt->execute([$token]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("File not found.");
}

// 3. check expiration
$now = new DateTime();
$expiresAt = new DateTime($file['expires_at']);

if ($now > $expiresAt) {
    // delete expired file
    $filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $deleteStmt = $db->prepare("DELETE FROM files WHERE token = ?");
    $deleteStmt->execute([$token]);

    die("This link has expired.");
}

// 4. serve file
$filePath = __DIR__ . "/uploads/" . $file['stored_filename'];

if (!file_exists($filePath)) {
    die("File missing from server.");
}

// detect mime type safely
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// force download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');
header('Pragma: no-cache');

readfile($filePath);
exit;
