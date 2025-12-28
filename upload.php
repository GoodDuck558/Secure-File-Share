<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO('sqlite:secure_file_share.db'); // make sure database exists
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_FILES['file'])) {
    die("No file uploaded.");
}

$file = $_FILES['file'];

// limit size to 5MB
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    die("File too large.");
}

// allowed types
$allowed = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
if (!in_array($file['type'], $allowed)) {
    die("File type not allowed.");
}

// generate unique token
$token = bin2hex(random_bytes(16));

// get extension
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$storedName = $token . "." . $ext;

// move to uploads
$uploadDir = __DIR__ . "/uploads/";
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
    die("Failed to move uploaded file.");
}

// insert into DB
$stmt = $db->prepare(
    "INSERT INTO files (original_filename, stored_filename, token, uploaded_at, expires_at) 
     VALUES (?, ?, ?, ?, ?)"
);
$createdAt = date('Y-m-d H:i:s');
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt->execute([$file['name'], $storedName, $token, $createdAt, $expiresAt]);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Upload Successful</title>
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


