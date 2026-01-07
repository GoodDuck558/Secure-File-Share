<?php
$skip_login_check = false;
require_once 'session.php';

// DB connection
$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// E2EE check
$SODIUM_AVAILABLE = extension_loaded('sodium')
    && function_exists('sodium_crypto_secretbox');

// CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("Invalid CSRF token.");
    }
}

// rate limiting per session
if (!isset($_SESSION['uploads'])) $_SESSION['uploads'] = 0;

$rateLimitExceeded = false;
if ($_SESSION['uploads'] >= 5) {
    $rateLimitExceeded = true;
} else {
    $_SESSION['uploads']++;
}


// basic vars
$mode = $_POST['mode'] ?? 'anonymous';
$owner_id = ($mode === 'identity' && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : null;

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) die("File upload failed.");

// generate stored filename & token
$storedName = bin2hex(random_bytes(16)) . "_" . basename($file['name']);
$token = bin2hex(random_bytes(16));
$now = date('c');
$expires = date('c', time() + 3600);

// passphrase handling
$passphrase = $_POST['passphrase'] ?? '';
$passphrase_hash = !empty($passphrase) ? password_hash($passphrase, PASSWORD_DEFAULT) : null;

// generate file encryption key (32 bytes)
$fileKey = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

// generate nonce for file (24 bytes)
$nonce_file = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

// wrap key if passphrase is given
if ($SODIUM_AVAILABLE && !empty($passphrase)) {
    $salt = random_bytes(16); // 16-byte salt
    $nonce_wrap = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $wrappingKey = hash_pbkdf2('sha256', $passphrase, $salt, 100000, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, true);
    $wrappedKey = sodium_crypto_secretbox($fileKey, $nonce_wrap, $wrappingKey);
} else {
    $salt = '';
    $nonce_wrap = '';
    $wrappedKey = $fileKey; // use raw key for no passphrase
}

// encrypt file
$plaintext = file_get_contents($file['tmp_name']);
if ($SODIUM_AVAILABLE) {
    $encryptedContent = sodium_crypto_secretbox($plaintext, $nonce_file, $fileKey);
} else {
    $encryptedContent = base64_encode($plaintext); // fallback
}

// move uploaded file to storage
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
file_put_contents($uploadDir . '/' . $storedName, $encryptedContent);

// insert DB record
$stmt = $db->prepare("
INSERT INTO files
(original_filename, stored_filename, token, uploaded_at, expires_at,
 owner_id, passphrase_hash, nonce_file, nonce_wrap, iv, salt, wrapped_key)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $file['name'],
    $storedName,
    $token,
    $now,
    $expires,
    $owner_id,
    $passphrase_hash,
    base64_encode($nonce_file),
    base64_encode($nonce_wrap),
    base64_encode(random_bytes(24)), // future-proof IV
    base64_encode($salt),
    base64_encode($wrappedKey)
]);

// return link
$downloadLink = "download.php?token=" . $token;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Successful</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Upload Complete</h1></header>
<main>
    <?php if ($rateLimitExceeded): ?>
    <div style="padding: 10px; background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; border-radius: 5px; margin-bottom: 20px;">
        Too many uploads this session. Please wait before uploading more files.
    </div>
<?php endif; ?>

    <p><strong>Download link:</strong></p>
    <a href="<?= htmlspecialchars($downloadLink) ?>" target="_blank"><?= htmlspecialchars($downloadLink) ?></a>

    <?php if (!empty($passphrase)): ?>
        <p><strong>Passphrase:</strong> <?= htmlspecialchars($passphrase) ?></p>
    <?php endif; ?>

    <p><a href="<?= $mode === 'identity' ? 'index.php' : 'anonymous_upload.php' ?>">Upload another</a></p>
</main>

<footer>
    <form action="landing.php" method="get" style="display:inline;">
        <button type="submit">Homepage</button>
    </form>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 2.1</p>
</footer>
</body>
</html>
