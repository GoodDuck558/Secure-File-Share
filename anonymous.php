<?php
// download.php — secure client-side decryption download

$skip_login_check = true;
require_once 'session.php';

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

// Enforce identity-only files
if ($file['owner_id'] !== null && !isset($_SESSION['user_id'])) {
    die("Login required for this file.");
}

// Expiration check
$now = new DateTime();
$expiresAt = new DateTime($file['expires_at']);

if ($now > $expiresAt) {
    $filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (is_file($filePath)) {
        unlink($filePath);
    }

    $del = $db->prepare("DELETE FROM files WHERE token = ?");
    $del->execute([$token]);

    die("This link has expired.");
}

// Load encrypted file
$filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
if (!is_file($filePath)) {
    die("File missing from server.");
}

$ciphertextB64 = base64_encode(file_get_contents($filePath));

// Raw values from DB (already base64)
$ivB64         = $file['nonce_file'];
$saltB64       = $file['salt'];
$wrappedKeyB64 = $file['wrapped_key'];
$filename      = $file['original_filename'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Download File</title>
    <link rel="stylesheet" href="style.css">
    <script src="crypto.js"></script>
</head>
<body>
<header>
    <h1>Download Encrypted File</h1>
</header>

<main>
    <p>Enter your passphrase to decrypt and download the file.</p>
    <input type="password" id="passphrase" placeholder="Passphrase" required>
    <button id="decryptBtn">Decrypt & Download</button>
    <p id="status"></p>
</main>

<footer>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 1.2</p>
</footer>

<script>
const FILE_DATA = {
    ciphertext: <?= json_encode($ciphertextB64) ?>,
    iv: <?= json_encode($ivB64) ?>,
    salt: <?= json_encode($saltB64) ?>,
    wrappedKey: <?= json_encode($wrappedKeyB64) ?>,
    filename: <?= json_encode($filename) ?>
};

document.getElementById('decryptBtn').addEventListener('click', async () => {
    const btn = document.getElementById('decryptBtn');
    const status = document.getElementById('status');
    const passphrase = document.getElementById('passphrase').value;

    if (!passphrase) {
        status.textContent = "Passphrase required.";
        return;
    }

    btn.disabled = true;
    status.textContent = "Decrypting…";

    try {
        const ciphertext = Uint8Array.from(atob(FILE_DATA.ciphertext), c => c.charCodeAt(0));
        const iv = Uint8Array.from(atob(FILE_DATA.iv), c => c.charCodeAt(0));
        const salt = Uint8Array.from(atob(FILE_DATA.salt), c => c.charCodeAt(0));
        const wrappedKey = Uint8Array.from(atob(FILE_DATA.wrappedKey), c => c.charCodeAt(0));

        const plaintext = await decryptFileEnvelope(
            ciphertext,
            iv,
            salt,
            wrappedKey,
            passphrase
        );

        const blob = new Blob([plaintext], { type: "application/octet-stream" });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = FILE_DATA.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        status.textContent = "File downloaded successfully.";
    } catch (err) {
        console.error(err);
        status.textContent = "Decryption failed. Wrong passphrase?";
        btn.disabled = false;
    }
});
</script>
</body>
</html>
