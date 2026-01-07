<?php
$SODIUM_AVAILABLE = extension_loaded('sodium')
    && function_exists('sodium_crypto_secretbox_open');

$skip_login_check = true;
require_once 'session.php';

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/
$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
|--------------------------------------------------------------------------
| Token
|--------------------------------------------------------------------------
*/
if (empty($_GET['token'])) {
    die("Invalid or missing token.");
}
$token = $_GET['token'];

$stmt = $db->prepare("SELECT * FROM files WHERE token = ?");
$stmt->execute([$token]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) {
    die("File not found.");
}

/*
|--------------------------------------------------------------------------
| Identity-only check
|--------------------------------------------------------------------------
*/
if ($file['owner_id'] !== null && !isset($_SESSION['user_id'])) {
    die("Login required for this file.");
}

/*
|--------------------------------------------------------------------------
| Expiration
|--------------------------------------------------------------------------
*/
if (time() > strtotime($file['expires_at'])) {
    $path = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (is_file($path)) unlink($path);
    $db->prepare("DELETE FROM files WHERE token = ?")->execute([$token]);
    die("This link has expired.");
}

/*
|--------------------------------------------------------------------------
| Load encrypted file
|--------------------------------------------------------------------------
*/
$filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
if (!is_file($filePath)) {
    die("File missing from server.");
}

$encryptedContent = file_get_contents($filePath);
if ($encryptedContent === false) {
    die("Failed to read file.");
}

/*
|--------------------------------------------------------------------------
| Master key (must match upload.php)
|--------------------------------------------------------------------------
*/
define('SERVER_MASTER_KEY', hex2bin(
    '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
));

/*
|--------------------------------------------------------------------------
| AUTO DOWNLOAD (no passphrase)
|--------------------------------------------------------------------------
*/
if (empty($file['passphrase_hash']) && isset($_GET['auto'])) {

    if ($SODIUM_AVAILABLE) {
        $nonce_file = base64_decode($file['nonce_file']);
        $plaintext = sodium_crypto_secretbox_open(
            $encryptedContent,
            $nonce_file,
            SERVER_MASTER_KEY
        );
        if ($plaintext === false) die("Decryption failed.");
    } else {
        $plaintext = base64_decode($encryptedContent); // FAKE
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
    header('Content-Length: ' . strlen($plaintext));
    echo $plaintext;
    exit;
}

/*
|--------------------------------------------------------------------------
| PASSPHRASE DECRYPT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['passphrase'])) {
        die("Passphrase required.");
    }

    if ($SODIUM_AVAILABLE) {
        $wrappingKey = hash_pbkdf2(
            'sha256',
            $_POST['passphrase'],
            "24",
            100000,
            32,
            true
        );

        $fileKey = sodium_crypto_secretbox_open(
            base64_decode($file['wrapped_key']),
            base64_decode($file['nonce_wrap']),
            $wrappingKey
        );

        if ($fileKey === false) die("Wrong passphrase.");

        $plaintext = sodium_crypto_secretbox_open(
            $encryptedContent,
            base64_decode($file['nonce_file']),
            $fileKey
        );

        if ($plaintext === false) die("Decryption failed.");

    } else {
        // FAKE MODE
        $plaintext = base64_decode($encryptedContent);
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
    header('Content-Length: ' . strlen($plaintext));
    echo $plaintext;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Download File</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Download File</h1></header>
<main>
<?php if (!empty($file['passphrase_hash'])): ?>
    <p>Enter passphrase to decrypt:</p>
    <form method="POST">
        <input type="password" name="passphrase" required>
        <button type="submit">Decrypt & Download</button>
    </form>
<?php else: ?>
    <p>No passphrase required.</p>
    <a href="?token=<?= htmlspecialchars($token) ?>&auto=1">Download File</a>
<?php endif; ?>
</main>
<footer>
    <form action="landing.php" method="get" style="display:inline;">
    <button type="submit">Homepage</button>
<p>&copy; <?= date("Y") ?> Secure File Share</p>
<p style="font-size:0.9em; color:#666;">Version 2.0</p>
</footer>
</body>
</html>
