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

// ---------------- PHPMailer ----------------
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTP($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'securefileshareweb@gmail.com';
        $mail->Password   = 'zpvp qznr nkjc eyaz';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('securefileshareweb@gmail.com', 'Secure File Share');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - Secure File Share';
        $mail->Body    = "Your OTP code for password reset is: <b>$otp</b>. It expires in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<p style='color:red;'>Mailer Error: {$mail->ErrorInfo}</p>";
        return false;
    }
}

// ---------------- POST Handling ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '';

    // Step 1: User submits email
    if ($step === 'request') {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            showMessage("Enter a valid email address.");
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email=?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                showMessage("No account found with that email.");
            } else {
                // Generate OTP
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = rand(100000, 999999);
                $_SESSION['reset_otp_time'] = time();

                if (sendOTP($email, $_SESSION['reset_otp'])) {
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
        if (!isset($_SESSION['reset_otp'])) {
            showMessage("OTP expired. Start over.");
        } elseif ($otp != $_SESSION['reset_otp']) {
            showMessage("Incorrect OTP.");
        } elseif (time() - $_SESSION['reset_otp_time'] > 300) {
            showMessage("OTP expired. Start over.");
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time']);
        } else {
            $_SESSION['otp_verified'] = true;
            header('Location: reset_password.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Forgot Password - Secure File Share</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Forgot Password</h1>
    <p>Enter your email to reset your password</p>
</header>
<main>
<?php if (!isset($_SESSION['reset_otp'])): ?>
<form method="POST">
    <input type="hidden" name="step" value="request">
    <div>
        <label>Email:</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <button type="submit">Send OTP</button>
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
        <button type="submit">Verify OTP</button>
    </div>
</form>
<form method="POST" style="margin-top:10px;">
    <input type="hidden" name="step" value="resend">
    <button type="submit">Resend OTP</button>
</form>
<?php endif; ?>
<p><a href="login.php">Back to login</a></p>
</main>
</body>
</html>
