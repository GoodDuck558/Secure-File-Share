<?php
$skip_login_check = true;
require_once 'session.php';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header('Location: forgot_password.php');
    exit;
}

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function showMessage($msg, $color='red') {
    $bg = $color === 'green' ? '#d4edda' : '#f8d7da';
    $textColor = $color === 'green' ? '#155724' : '#721c24';
    echo "
    <div style='
        max-width:400px;
        margin:20px auto;
        padding:15px 20px;
        background-color:$bg;
        color:$textColor;
        border:1px solid ".($color==='green'?'#c3e6cb':'#f5c6cb').";
        border-radius:5px;
        font-family:Arial,sans-serif;
        text-align:center;
        box-shadow:0 2px 5px rgba(0,0,0,0.1);
    '>
        $msg
    </div>
    ";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (!$password || !$confirm) {
        showMessage("All fields are required.");
    } elseif (strlen($password) < 8) {
        showMessage("Password too short. Minimum 8 characters.");
    } elseif ($password !== $confirm) {
        showMessage("Passwords do not match.");
    } else {
        $stmt = $db->prepare("UPDATE users SET password_hash=? WHERE email=?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $_SESSION['reset_email']]);

        // Clear session
        unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['otp_verified']);

        showMessage("Password reset successfully!", "green");
        echo "<p style='text-align:center;'><a href='login.php'>Go to login</a></p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reset Password - Secure File Share</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Reset Password</h1>
    <p>Enter your new password</p>
</header>
<main>
<form method="POST">
    <div>
        <label>New Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>
    </div>
    <div>
        <button type="submit">Reset Password</button>
    </div>
</form>
</main>
</body>
</html>
