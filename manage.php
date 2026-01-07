<?php
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: landing.php');
    exit;
}

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'];

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Handle POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf'], $_POST['csrf'])
    ) {
        die("CSRF violation");
    }

    if (!empty($_POST['token']) && !empty($_POST['action'])) {
        $token = $_POST['token'];

        if ($_POST['action'] === 'delete') {
            $stmt = $db->prepare(
                "SELECT stored_filename FROM files
                 WHERE token = ? AND owner_id = ?"
            );
            $stmt->execute([$token, $user_id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                $path = __DIR__ . "/uploads/" . $file['stored_filename'];
                if (is_file($path)) {
                    unlink($path);
                }

                $del = $db->prepare(
                    "DELETE FROM files WHERE token = ? AND owner_id = ?"
                );
                $del->execute([$token, $user_id]);
            }
        }

        if ($_POST['action'] === 'extend') {
            $newExpires = (new DateTime('+1 hour'))
                ->format('Y-m-d H:i:s');

            $upd = $db->prepare(
                "UPDATE files
                 SET expires_at = ?
                 WHERE token = ? AND owner_id = ?"
            );
            $upd->execute([$newExpires, $token, $user_id]);
        }
    }

    header('Location: manage.php');
    exit;
}

/* ---------- Fetch files ---------- */
$stmt = $db->prepare(
    "SELECT * FROM files
     WHERE owner_id = ?
     ORDER BY uploaded_at DESC"
);
$stmt->execute([$user_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Files</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Your Files</h1>
</header>

<main>
<?php if (empty($files)): ?>
    <p>No files uploaded yet.</p>
<?php else: ?>
    <?php foreach ($files as $file): ?>
        <div class="file-card">
            <p>
                <strong><?= htmlspecialchars($file['original_filename']) ?></strong><br>
                Uploaded: <?= htmlspecialchars($file['uploaded_at']) ?><br>
                Expires: <?= htmlspecialchars($file['expires_at']) ?><br>
                Token: <code><?= htmlspecialchars($file['token']) ?></code>
            </p>

            <p>
                <a href="download.php?token=<?= urlencode($file['token']) ?>">
                    Download
                </a>
            </p>

            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf"
                       value="<?= $_SESSION['csrf'] ?>">
                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($file['token']) ?>">

                <button type="submit" name="action" value="extend">
                    Extend 1 hour
                </button>

                <button type="submit" name="action" value="delete"
                    onclick="return confirm('Delete this file permanently?')">
                    Delete
                </button>
            </form>
        </div>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<footer>
    <form action="index.php" method="get" style="display:inline;">
    <button type="submit">Go Back</button>
</form>
    <p>&copy; <?= date('Y') ?> Secure File Share</p>
    <p style="font-size:0.9em; color:#666;">Version 2.1</p>
</footer>

</body>
</html>
