<?php
$skip_login_check = true;
require_once 'session.php';

/*
|--------------------------------------------------------------------------
| Sodium availability (DEMO MODE SAFE)
|--------------------------------------------------------------------------
*/
$SODIUM_AVAILABLE = extension_loaded('sodium')
    && function_exists('sodium_crypto_secretbox');

/*
|--------------------------------------------------------------------------
| CSRF check
|--------------------------------------------------------------------------
*/
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    die("CSRF violation");
}

/*
|--------------------------------------------------------------------------
| Error page helper
|--------------------------------------------------------------------------
*/
function errorPage($msg) {
    $msgEsc = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Upload Error</title>
        <link rel='stylesheet' href='style.css'>
    </head>
    <body>
    <header><h1>Upload Failed</h1></header>
    <main>
        <p style='color:red;'><strong>Error:</strong> {$msgEsc}</p>
        <p><a href='index.php'>Go back</a></p>
    </main>
    </body></html>";
    exit;
}

/*
|--------------------------------------------------------------------------
| Rate limiting
|--------------------------------------------------------------------------
*/
$_SESSION['upload_count'] = ($_SESSION['upload_count'] ?? 0) + 1;
if ($_SESSION['upload_count'] > 10) {
    errorPage("Too many uploads this session.");
}

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/
try {
    $db = new PDO('sqlite:secure_file_share.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    errorPage("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| File validation
|--------------------------------------------------------------------------
*/
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    errorPage("No file uploaded.");
}

$file = $_FILES['file'];
if ($file['size'] > 5 * 1024 * 1024) {
    errorPage("File too large (max 5MB).");
}

/*
|--------------------------------------------------------------------------
| Filenames & token
|--------------------------------------------------------------------------
*/
$token = bin2hex(random_bytes(32));
$storedBase = bin2hex(random_bytes(32));
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$ext = preg_match('/^[a-z0-9]+$/', $ext) ? $ext : 'dat';
$storedName = $storedBase . '.' . $ext;

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0700, true);
$uploadPath = $uploadDir . $storedName;

/*
|--------------------------------------------------------------------------
| Timestamps
|--------------------------------------------------------------------------
*/
$now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', time() + 3600);

/*
|--------------------------------------------------------------------------
| Owner / mode
|--------------------------------------------------------------------------
*/
$mode = $_POST['mode'] ?? 'anonymous';
$owner_id = null;
if ($mode === 'identity') {
    if (!isset($_SESSION['user_id'])) {
        errorPage("Login required.");
    }
    $owner_id = $_SESSION['user_id'];
}

/*
|--------------------------------------------------------------------------
| Passphrase
|--------------------------------------------------------------------------
*/
$passphrase_hash = null;
if (!empty($_POST['passphrase'])) {
    $passphrase_hash = password_hash($_POST['passphrase'], PASSWORD_DEFAULT);
}

/*
|--------------------------------------------------------------------------
| Encryption setup (FAKED SAFELY)
|--------------------------------------------------------------------------
*/
$NONCE_LEN = 24;
$nonce_file = random_bytes($NONCE_LEN);
$nonce_wrap = random_bytes($NONCE_LEN);

$fileKey = random_bytes(32);
$salt = base64_encode("24"); // DEMO FIXED SALT

define('SERVER_MASTER_KEY', hex2bin(
    '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
));

if (!empty($_POST['passphrase'])) {
    $wrappingKey = hash_pbkdf2(
        'sha256',
        $_POST['passphrase'],
        "24",
        100000,
        32,
        true
    );
} else {
    $wrappingKey = SERVER_MASTER_KEY;
}

/*
|--------------------------------------------------------------------------
| Key wrapping
|--------------------------------------------------------------------------
*/
if ($SODIUM_AVAILABLE) {
    $wrapped_key = sodium_crypto_secretbox($fileKey, $nonce_wrap, $wrappingKey);
} else {
    $wrapped_key = base64_encode($fileKey); // FAKE
}

$wrapped_key_b64 = base64_encode($wrapped_key);

/*
|--------------------------------------------------------------------------
| Save uploaded file
|--------------------------------------------------------------------------
*/
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    errorPage("Failed to save file.");
}
chmod($uploadPath, 0600);

/*
|--------------------------------------------------------------------------
| Encrypt file content
|--------------------------------------------------------------------------
*/
$content = file_get_contents($uploadPath);

if ($SODIUM_AVAILABLE) {
    $encrypted = sodium_crypto_secretbox($content, $nonce_file, $fileKey);
} else {
    $encrypted = base64_encode($content); // FAKE
}

file_put_contents($uploadPath, $encrypted);

/*
|--------------------------------------------------------------------------
| Database insert
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
INSERT INTO files
(original_filename, stored_filename, token, uploaded_at, expires_at,
 owner_id, passphrase_hash, nonce_file, nonce_wrap, salt, wrapped_key)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
    $salt,
    $wrapped_key_b64
]);

$downloadLink = "http://localhost/Secure-File-Share/download.php?token=" . urlencode($token);
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
    <p><strong>Download link:</strong></p>
    <a href="<?= $downloadLink ?>" target="_blank"><?= $downloadLink ?></a>

    <?php if (!empty($_POST['passphrase'])): ?>
        <p><strong>Passphrase:</strong> <?= htmlspecialchars($_POST['passphrase']) ?></p>
    <?php endif; ?>

    <p><a href="<?= $mode === 'identity' ? 'index.php' : 'anonymous_upload.php' ?>">Upload another</a></p>
</main>
</body>
</html>
