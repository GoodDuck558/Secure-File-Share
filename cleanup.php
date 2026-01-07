<?php
// cleanup.php — cron script to delete expired files and DB records
// Run this script periodically (e.g., every minute) via Windows Task Scheduler or cron equivalent.
// Example Windows Task Scheduler: schtasks /create /tn "SecureFileCleanup" /tr "php C:\xampp\htdocs\Secure-File-Share\cleanup.php" /sc minute /mo 1

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$now = (new DateTime())->format('Y-m-d H:i:s');

// Select expired files
$stmt = $db->prepare("SELECT stored_filename, token FROM files WHERE expires_at < ?");
$stmt->execute([$now]);
$expiredFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expiredFiles as $file) {
    $filePath = __DIR__ . "/uploads/" . $file['stored_filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
        echo "Deleted file: {$file['stored_filename']}\n";
    }

    // Delete DB record
    $deleteStmt = $db->prepare("DELETE FROM files WHERE token = ?");
    $deleteStmt->execute([$file['token']]);
    echo "Deleted DB record for token: {$file['token']}\n";
}

echo "Cleanup completed at " . date('Y-m-d H:i:s') . ".\n";
?>
