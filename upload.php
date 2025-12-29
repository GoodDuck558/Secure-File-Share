<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- DB Connection ---
$db = new PDO('sqlite:secure_file_share.db'); 
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Check file upload ---
if (!isset($_FILES['file'])) {
    die("No file uploaded.");
}

$file = $_FILES['file'];

// --- File size limit (5MB) ---
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    die("File too large.");
}

// --- Allowed MIME types ---
$allowed = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
if (!in_array($file['type'], $allowed)) {
    die("File type not allowed.");
}

// --- Generate unique token and stored filename ---
$token = bin2hex(random_bytes(16));
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$storedName = $token . "." . $ext;
$uploadDir = __DIR__ . "/uploads/";

// --- Metadata stripping for images ---
if ($file['type'] === 'image/jpeg') {
    $img = @imagecreatefromjpeg($file['tmp_name']);
    if (!$img) die("Image processing failed.");
    
    $width = imagesx($img);
    $height = imagesy($img);
    $clean = imagecreatetruecolor($width, $height);
    
    imagecopy($clean, $img, 0, 0, 0, 0, $width, $height);
    
    if (!imagejpeg($clean, $uploadDir . $storedName, 90)) {
        imagedestroy($img);
        imagedestroy($clean);
        die("Failed to save clean image.");
    }
    
    imagedestroy($img);
    imagedestroy($clean);
}
elseif ($file['type'] === 'image/png') {
    $img = @imagecreatefrompng($file['tmp_name']);
    if (!$img) die("Image processing failed.");
    
    $width = imagesx($img);
    $height = imagesy($img);
    $clean = imagecreatetruecolor($width, $height);
    
    // preserve transparency
    imagealphablending($clean, false);
    imagesavealpha($clean, true);
    $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
    imagefilledrectangle($clean, 0, 0, $width, $height, $transparent);
    
    imagecopy($clean, $img, 0, 0, 0, 0, $width, $height);
    
    if (!imagepng($clean, $uploadDir . $storedName)) {
        imagedestroy($img);
        imagedestroy($clean);
        die("Failed to save clean image.");
    }
    
    imagedestroy($img);
    imagedestroy($clean);
}
else {
    // Non-image files, just move
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
        die("Failed to move uploaded file.");
    }
}

// --- Insert into DB ---
$stmt = $db->prepare(
    "INSERT INTO files (original_filename, stored_filename, token, uploaded_at, expires_at) 
     VALUES (?, ?, ?, ?, ?)"
);
$createdAt = date('Y-m-d H:i:s');
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt->execute([$file['name'], $storedName, $token, $createdAt, $expiresAt]);

echo "<!DOCTYPE html>

<head>
    <meta charset='UTF-8'>
    <title>Upload Successful</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>File Upload Complete!</h1>
    </header>

    <main>
        <section>
            <p>Your file has been uploaded successfully.</p>
            <p>
                <strong>Download it here:</strong>
                <br>
                <a href='download.php?token=$token' target='_blank'>Download File</a>
            </p>
        </section>

        <section>
            <p>
                <a href='index.html'>Upload another file</a>
            </p>
        </section>
    </main>

    <footer>
        <p>&copy; " . date('Y') . " Secure File Share</p>
    </footer>
</body>
</html>";
