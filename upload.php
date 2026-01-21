<?php
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
| CSRF
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("Invalid CSRF token.");
    }
}

/*
|--------------------------------------------------------------------------
| Rate limit (soft, user-visible)
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['uploads'])) $_SESSION['uploads'] = 0;
if ($_SESSION['uploads'] >= 5) {
    $rateLimitError = "Upload limit reached for this session. Please wait or refresh.";
} else {
    $_SESSION['uploads']++;
}

/*
|--------------------------------------------------------------------------
| Abort early if rate limited
|--------------------------------------------------------------------------
*/
if (isset($rateLimitError)) {
    echo "<p style='color:red; padding:20px;'>$rateLimitError</p>";
    exit;
}

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/
$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    die("File upload failed.");
}

$mode = $_POST['mode'] ?? 'anonymous';
$owner_id = ($mode === 'identity' && isset($_SESSION['user_id']))
    ? $_SESSION['user_id']
    : null;

/*
|--------------------------------------------------------------------------
| Metadata
|--------------------------------------------------------------------------
*/
$storedName = bin2hex(random_bytes(16)) . "_" . basename($file['name']);
$token = bin2hex(random_bytes(16));
$now = date('c');
$expires = date('c', time() + 3600);

/*
|--------------------------------------------------------------------------
| Passphrase
|--------------------------------------------------------------------------
*/
$passphrase = $_POST['passphrase'] ?? '';
$passphrase_hash = !empty($passphrase)
    ? password_hash($passphrase, PASSWORD_DEFAULT)
    : null;

/*
|--------------------------------------------------------------------------
| Crypto
|--------------------------------------------------------------------------
*/
$SODIUM_AVAILABLE = extension_loaded('sodium')
    && function_exists('sodium_crypto_secretbox');

$fileKey   = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
$nonceFile = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$nonceWrap = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$salt      = random_bytes(16);

if (!empty($passphrase)) {
    $wrappingKey = hash_pbkdf2(
        'sha256',
        $passphrase,
        $salt,
        100000,
        SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        true
    );
    $wrapped_key = sodium_crypto_secretbox($fileKey, $nonceWrap, $wrappingKey);
} else {
    $wrapped_key = sodium_crypto_secretbox(
        $fileKey,
        $nonceWrap,
        random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
    );
}

/*
|--------------------------------------------------------------------------
| Encrypt file
|--------------------------------------------------------------------------
*/
$plaintext = file_get_contents($file['tmp_name']);
$encrypted = $SODIUM_AVAILABLE
    ? sodium_crypto_secretbox($plaintext, $nonceFile, $fileKey)
    : base64_encode($plaintext);

/*
|--------------------------------------------------------------------------
| Store file
|--------------------------------------------------------------------------
*/
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
file_put_contents("$uploadDir/$storedName", $encrypted);

/*
|--------------------------------------------------------------------------
| DB insert
|--------------------------------------------------------------------------
*/
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
    base64_encode($nonceFile),
    base64_encode($nonceWrap),
    base64_encode(random_bytes(16)),
    base64_encode($salt),
    base64_encode($wrapped_key)
]);

/*
|--------------------------------------------------------------------------
| SUCCESS PAGE (NO REDIRECT — EVER)
|--------------------------------------------------------------------------
*/
$downloadLink = "download.php?token=" . htmlspecialchars($token);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Successful</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Upload Complete</h1>
</header>

<main>
    <div style="background:#d4edda;color:#155724;padding:12px;border-radius:6px;">
        File uploaded successfully.
    </div>

    <p><strong>Download link:</strong></p>
    <input type="text" value="<?= $downloadLink ?>" readonly style="width:100%;padding:8px;">

    <p style="margin-top:10px;">
        <a href="<?= $downloadLink ?>">Go to download page</a>
    </p>

    <?php if (!empty($passphrase)): ?>
        <p><strong>Passphrase:</strong> <?= htmlspecialchars($passphrase) ?></p>
    <?php endif; ?>
</main>

<footer>
    <form action="index.php" method="get">
        <button type="submit">Homepage</button>
    </form>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p class="version">Version 2.1</p>
</footer>

</body>
</html>
