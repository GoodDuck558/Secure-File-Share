<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
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
        <section>
            <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <div>
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div>
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div>
                    <button type="submit">Login</button>
                </div>
            </form>
            <p>No account? <a href="register.php">Register here</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Secure File Share</p>
    </footer>
</body>
</html>
