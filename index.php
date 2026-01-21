<?php
$skip_login_check = true;
require_once 'session.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure File Share - Choose Mode</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function chooseMode(mode) {
            if(mode === 'anonymous') window.location.href = 'anonymous_upload.php';
            if(mode === 'identity') window.location.href = 'register.php';
        }
    </script>
</head>
<body>
    <header class="page-header">
        <h1>Secure File Share</h1>
        <p>Choose how you want to share your files securely.</p>
    </header>

    <main>
        <div class="mode-selection">
            <div class="mode-option mode-anonymous" onclick="chooseMode('anonymous')">
                <div class="mode-content">
                    <h3>Anonymous Mode</h3>
                    <p>Upload files without creating an account. Get a download link immediately.</p>
                </div>
            </div>
            <div class="mode-option mode-identity" onclick="chooseMode('identity')">
                <div class="mode-content">
                    <h3>Identity Mode</h3>
                    <p>Create an account to manage your files. List, delete, and extend expiration of your uploads.</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> Secure File Share</p>
        <p class="version">Version 2.1</p>
    </footer>
</body>
</html>
