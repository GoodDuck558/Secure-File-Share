<?php
$skip_login_check = true;
require_once 'session.php';

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


// ---------------- PHPMailer via Composer ----------------
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------------- send OTP function ----------------
function sendOTP($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $config = require 'config.php';

        $mail->Username = $config['SMTP_USER'];
        $mail->Password = $config['SMTP_PASS'];

        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom($config['SMTP_FROM'], 'Secure File Share');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Secure File Share';
        $mail->Body    = "Your OTP code is: <b>$otp</b>. It expires in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<p style='color:red;'>Mailer Error: {$mail->ErrorInfo}</p>";
        return false;
    }
}

// ------------------ POST handling ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $step = $_POST['step'] ?? '';

    // Step 1: Register submission
    if (isset($_POST['step']) && $_POST['step'] === 'register') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm  = $_POST['confirm_password'];
        $email    = trim($_POST['email']);

        if (!$username || !$password || !$email || !$confirm) {
            showMessage("All fields are required.");
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            showMessage("Username invalid. 3-30 chars, letters, numbers, underscores only.");
        } elseif (strlen($password) < 8) {
            showMessage("Password too short. Minimum 8 characters.");
        } elseif ($password !== $confirm) {
            showMessage("Passwords do not match.");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            showMessage("Invalid email address.");
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                showMessage("Username or email already exists.");
            } else {
                $_SESSION['reg_temp'] = [
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email
                ];
                $_SESSION['reg_otp'] = rand(100000, 999999);
                $_SESSION['reg_otp_time'] = time();

                if(sendOTP($email, $_SESSION['reg_otp'])) {
                    showMessage("OTP sent to $email. Check your inbox.", "green");
                } else {
                    showMessage("Failed to send OTP. Check SMTP settings.");
                }
            }
        }
    }
    // Step 2: Verify OTP
    elseif ($step === 'verify') {
        
        $otp = trim($_POST['otp']);
        if (!isset($_SESSION['reg_otp'])) {
            showMessage("OTP expired or not generated.");
        } elseif ($otp != $_SESSION['reg_otp']) {
            showMessage("Incorrect OTP.");
        } elseif (time() - $_SESSION['reg_otp_time'] > 300) {
            showMessage("OTP expired. Please restart registration.");
            unset($_SESSION['reg_temp'], $_SESSION['reg_otp'], $_SESSION['reg_otp_time']);
        } else {
            $data = $_SESSION['reg_temp'];
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['username'], $data['password_hash'], $data['email'], date('Y-m-d H:i:s')]);
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $data['username'];

            unset($_SESSION['reg_temp'], $_SESSION['reg_otp'], $_SESSION['reg_otp_time']);
            header('Location: index.php');
            exit;
        }
    }

    // Step 3: Resend OTP
    elseif ($step === 'resend') {
        if (!isset($_SESSION['reg_temp'])) {
        showMessage("No registration in progress. Start over.");
        } else {
            $_SESSION['reg_otp'] = rand(100000, 999999);
            $_SESSION['reg_otp_time'] = time();
            $email = $_SESSION['reg_temp']['email'];
            if(sendOTP($email, $_SESSION['reg_otp'])) {
                showMessage("OTP resent to $email. Check your inbox.", "green");
            } else {
                showMessage("Failed to resend OTP.");
            }
        }
    }

    // Step 4: Go back
    elseif ($step === 'back') {
        unset($_SESSION['reg_temp'], $_SESSION['reg_otp'], $_SESSION['reg_otp_time']);
        header('Location: register.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register - Secure File Share</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Register</h1>
    <p>Create your secure account</p>
</header>
<main>
<?php if (!isset($_SESSION['reg_otp'])): ?>
<form method="POST">
    <input type="hidden" name="step" value="register">
    <div>
      <label>Email:</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label>Username:</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
     <label>Confirm Password:</label>
    <input type="password" name="confirm_password" required>
</div>
    <div>
      
    <div>
        <button type="submit">Register & Send OTP</button>
    </div>
</form>
<?php else: ?>
<form method="POST">
    <input type="hidden" name="step" value="verify">
    <div>
        <label>Enter OTP sent to your email:</label>
        <input type="text" name="otp" required>
    </div>
    <div>
        <button type="submit">Verify & Complete Registration</button>
    </div>
</form>

<form method="POST" style="margin-top:10px;">
    <input type="hidden" name="step" value="resend">
    <button type="submit">Resend OTP</button>
</form>

<form method="POST" style="margin-top:10px;">
    <input type="hidden" name="step" value="back">
    <button type="submit">Go Back</button>
</form>
<?php endif; ?>

<p>Already have an account? <a href="login.php">Login here</a>.</p>
</main>
<footer>
    <form action="landing.php" method="get" style="display:inline;">
    <button type="submit">Choose mode</button>
<p>&copy; <?= date("Y") ?> Secure File Share</p>
<p style="font-size:0.9em; color:#666;">Version 2.0</p>
</footer>
</body>
</html>
