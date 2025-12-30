<?php
require_once 'session.php'; // handles session, fixation, timeouts, login

function errorPage($msg) {
    $msgEsc = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Upload Error</title>
        <link rel='stylesheet' href='style.css'>
    </head>
    <body>
    <header>
        <h1>Upload Failed</h1>
    </header>
    <main>
        <p style='color:red;'><strong>Error:</strong> {$msgEsc}</p>
        <p><a href='index.php'>Go back and try again</a></p>
    </main>
    <footer>
        <p>&copy; " . date('Y') . " Secure File Share</p>
        <p style='font-size:0.9em; color:#666;'>Version 1.2</p>
    </footer>
    </body>
    </html>";
    exit;
}

// Rate-limit
$_SESSION['upload_count'] = ($_SESSION['upload_count'] ?? 0) + 1;
if ($_SESSION['upload_count'] > 10) errorPage("Too many uploads this session.");

// DB connection
try {
    $db = new PDO('sqlite:secure_file_share.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    errorPage("Database connection failed.");
}

// File validation & processing
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    errorPage("No file uploaded or upload error.");
}

$file = $_FILES['file'];
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) errorPage("File too large. Maximum 5MB.");

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
if (!in_array($mime, $allowed, true)) errorPage("Invalid file type.");

// Generate filenames
$token = bin2hex(random_bytes(32));
$storedBase = bin2hex(random_bytes(32));
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$ext = preg_match('/^[a-z0-9]+$/', $ext) ? $ext : 'dat';
$storedName = $storedBase . ($ext ? '.' . $ext : '');
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0700, true)) errorPage("Failed to create upload directory.");

$uploadPath = $uploadDir . $storedName;

// Process images
if ($mime === 'image/jpeg') {
    $img = @imagecreatefromjpeg($file['tmp_name']) ?: errorPage("Image processing failed.");
    $clean = imagecreatetruecolor(imagesx($img), imagesy($img));
    imagecopy($clean, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
    if (!imagejpeg($clean, $uploadPath, 90)) errorPage("Failed to save clean image.");
    imagedestroy($img);
    imagedestroy($clean);
} elseif ($mime === 'image/png') {
    $img = @imagecreatefrompng($file['tmp_name']) ?: errorPage("Image processing failed.");
    $clean = imagecreatetruecolor(imagesx($img), imagesy($img));
    imagealphablending($clean, false);
    imagesavealpha($clean, true);
    $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
    imagefilledrectangle($clean, 0, 0, imagesx($img), imagesy($img), $transparent);
    imagecopy($clean, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
    if (!imagepng($clean, $uploadPath)) errorPage("Failed to save clean image.");
    imagedestroy($img);
    imagedestroy($clean);
} else {
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) errorPage("Failed to save file.");
}

// Insert DB record
$stmt = $db->prepare(
    "INSERT INTO files (original_filename, stored_filename, token, uploaded_at, expires_at)
     VALUES (?, ?, ?, ?, ?)"
);
$now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt->execute([$file['name'], $storedName, $token, $now, $expires]);

$tokenEsc = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Successful</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>File Upload Complete!</h1>
</header>
<main>
    <p>Your file has been uploaded successfully.</p>
    <p>
        <strong>Download it here:</strong><br>
        <a href='download.php?token=<?= $tokenEsc ?>' target='_blank' rel='noopener noreferrer'>Download File</a>
    </p>
    <p><a href='index.php'>Upload another file</a></p>
</main>
<footer>
    <p>&copy; <?= date('Y') ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 1.2</p>
</footer>
</body>
</html>
