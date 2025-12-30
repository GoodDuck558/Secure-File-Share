<?php
$skip_login_check = true;
require_once 'session.php';  // handles cookie flags, strict mode, timeouts

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function showMessage($msg, $color='red') {
    echo "<p style='color:$color;'>$msg</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        showMessage("Username and password are required.");
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        showMessage("Username invalid. 3-30 chars, letters, numbers, underscores only.");
    } elseif (strlen($password) < 8) {
        showMessage("Password too short. Minimum 8 characters.");
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hash, date('Y-m-d H:i:s')]);
            showMessage("Registration successful. <a href='login.php'>Login here</a>.", 'lightgreen');
        } catch (PDOException $e) {
            showMessage("Username already exists.");
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register - Secure File Share</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <header>
        <h1>Register</h1>
        <p>Create your secure account</p>
    </header>

    <main>
        <section>
            <?php 
                if(isset($error)) echo "<p style='color:red;'>$error</p>"; 
                if(isset($success)) echo "<p style='color:lightgreen;'>$success</p>"; 
            ?>
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
                    <button type="submit">Register</button>
                </div>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Secure File Share</p>
          <p style="font-size:0.9em; color:#666;">Version 1.2</p>

    </footer>
</body>
</html>
