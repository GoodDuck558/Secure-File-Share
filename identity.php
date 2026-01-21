<?php
require_once 'session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$is_logged_in = true;

// Generate CSRF token if missing
if(!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure File Share - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!--For later-->
    <script src="crypto.js"></script>
    <script src="script.js"></script>
</head>
<body>
<header>
    <h1>Secure File Share</h1>
    <p>Upload and share files privately and securely. Files expire after 1 hour.</p>
    <p style="text-align:right;">
        Logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> |
        <a href="manage.php" style="color:#00e0ff;">Manage Files</a> |
        <a href="logout.php" style="color:#00e0ff;">Logout</a>
    </p>
</header>

<main>
    <section>
        <h2>Identity Upload</h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="identity">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div>
                <label for="identity-file">Select a file:</label>
                <input type="file" name="file" id="identity-file" required>
            </div>
            <div>
                <label for="passphrase">Passphrase (for encryption):</label>
                <input type="password" name="passphrase" id="passphrase" required>
            </div>
            <div>
                <button type="submit">Upload with Identity</button>
            </div>
        </form>
    </section>

    <section>
        <h2>Supported File Types & Limits</h2>
        <ul>
            <li>JPEG, PNG, PDF, TXT</li>
            <li>Maximum size: 5MB per file</li>
        </ul>
    </section>

    <section>
        <h2>How it Works</h2>
        <ol>
            <li>Upload your file using the form above.</li>
            <li>You will receive a secure download link immediately.</li>
            <li>Files are stored temporarily and automatically expire after 1 hour.</li>
            <li>Metadata is yet to be stripped from supported file types (JPEG, PNG, PDF, TXT) for privacy.</li>
            <li>After expiration, the file is deleted and the link becomes invalid.</li>
            <li>Use "Manage Files" to view and manage your identity uploads.</li>
        </ol>
    </section>

    <section>
        <h2>Important</h2>
        <p>This service is for educational/demo purposes. Treat it as private within your environment.</p>
    </section>
</main>

<footer>
    <p>&copy; <?= date("Y") ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 2.1</p>
</footer>
</body>
</html>
