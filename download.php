<?php
$SODIUM_AVAILABLE = extension_loaded('sodium')
    && function_exists('sodium_crypto_secretbox_open');

$skip_login_check = true;
require_once 'session.php';

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_GET['token'])) die("Invalid or missing token.");
$token = $_GET['token'];

$stmt = $db->prepare("SELECT * FROM files WHERE token = ?");
$stmt->execute([$token]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) die("File not found.");

// Identity-only check
if ($file['owner_id'] !== null && !isset($_SESSION['user_id'])) {
    die("Login required for this file.");
}

// Expiration
if (time() > strtotime($file['expires_at'])) {
    $path = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (is_file($path)) unlink($path);
    $db->prepare("DELETE FROM files WHERE token = ?")->execute([$token]);
    die("This link has expired.");
}

// File path
$filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
if (!is_file($filePath)) die("File missing from server.");

$encryptedContent = file_get_contents($filePath);
if ($encryptedContent === false) die("Failed to read file.");

// Status messages
$successMsg = null;
$errorMsg = null;
$downloadReady = false;
$downloadData = null;

// Handle POST passphrase decryption
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passphrase = $_POST['passphrase'] ?? '';
    if (empty($passphrase)) {
        $errorMsg = "Passphrase required.";
    } else {
        $wrappingKey = hash_pbkdf2(
            'sha256',
            $passphrase,
            base64_decode($file['salt']),
            100000,
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            true
        );

        $fileKey = sodium_crypto_secretbox_open(
            base64_decode($file['wrapped_key']),
            base64_decode($file['nonce_wrap']),
            $wrappingKey
        );

        if ($fileKey === false) $errorMsg = "Wrong passphrase.";
        else {
            $plaintext = sodium_crypto_secretbox_open(
                $encryptedContent,
                base64_decode($file['nonce_file']),
                $fileKey
            );
            if ($plaintext === false) $errorMsg = "Decryption failed.";
            else {
                $downloadReady = true;
                $downloadData = base64_encode($plaintext);
                $successMsg = "File decrypted and downloaded";
            }
        }
    }
}

// Handle auto-download for anonymous files
if (empty($file['passphrase_hash']) && isset($_GET['auto'])) {
    if ($SODIUM_AVAILABLE) {
        $plaintext = sodium_crypto_secretbox_open(
            $encryptedContent,
            base64_decode($file['nonce_file']),
            hex2bin('0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef')
        );
        if ($plaintext === false) $errorMsg = "Decryption failed.";
        else {
            $downloadReady = true;
            $downloadData = base64_encode($plaintext);
            $successMsg = "File decrypted and downloaded";
        }
    } else {
        $downloadReady = true;
        $downloadData = base64_encode(base64_decode($encryptedContent));
        $successMsg = "File ready to download";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Download File</title>
<link rel="stylesheet" href="style.css">
<style>
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
</head>
<body>
<header><h1>Download File</h1></header>
<main>
    <?php if ($successMsg): ?>
        <div class="alert-success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

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
    </form>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 2.1</p>
</footer>

<?php if ($downloadReady): ?>
<script>
    // Trigger download via JS but keep page visible
    const data = atob("<?= $downloadData ?>");
    const array = new Uint8Array(data.length);
    for (let i = 0; i < data.length; i++) array[i] = data.charCodeAt(i);

    const blob = new Blob([array], {type: 'application/octet-stream'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = "<?= addslashes($file['original_filename']) ?>";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
</script>
<?php endif; ?>

</body>
</html>
