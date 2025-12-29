<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO('sqlite:secure_file_share.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!$username || !$password) {
        $error = "Username and password are required.";
    } else {
        // hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hash, date('Y-m-d H:i:s')]);
            $success = "Registration successful. <a href='login.php'>Login here</a>.";
        } catch (PDOException $e) {
            $error = "Username already exists.";
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
    </footer>
</body>
</html>
