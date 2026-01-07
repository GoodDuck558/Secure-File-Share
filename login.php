<?php
$skip_login_check = true;
require_once 'session.php';

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - Secure File Share</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<h1>Login</h1>
<p>Access your secure file share account</p>
</header>
<main>
<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
    <div>
        <label>Username:</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <button type="submit">Login</button>
    </div>
</form>
<p>No account? <a href="register.php">Register here</a>.</p>
</main>
<footer>
    <form action="landing.php" method="get" style="display:inline;">
    <button type="submit">Choose mode</button>
<p>&copy; <?= date("Y") ?> Secure File Share</p>
<p style="font-size:0.9em; color:#666;">Version 2.0</p>
</footer>
</body>
</html>
