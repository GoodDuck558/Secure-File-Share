<?php
$skip_login_check = true;
require_once 'session.php';

// CSRF token generation
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Anonymous Upload</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="page-header">
    <h1>Anonymous File Upload</h1>
    <p>No account. No identity. Just a link.</p>
</header>

<main>
    <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form">

        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
        <input type="hidden" name="mode" value="anonymous">

        <div class="form-group">
            <label for="file">Choose file</label>
            <input type="file" name="file" id="file" required>
        </div>

        <div class="form-group">
            <label for="passphrase">
                Passphrase (required to download)
            </label>
            <input
                type="password"
                name="passphrase"
                id="passphrase"
            >
        </div>

        <button type="submit">Upload & Get Link</button>
    </form>
</main>

<footer>
    <form action="landing.php" method="get" style="display:inline;">
    <button type="submit">Exit</button>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p class="version">Version 2.0</p>
</footer>

</body>
</html>
