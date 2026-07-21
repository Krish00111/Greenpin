<?php

session_start();
$err = '';


$formClass = 'auth-form';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$username || !$password) {
        $err = 'Enter username and password.';
    } else {
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          
            //  username already exists chhe ke nai te chek karvanu
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);

            if ($checkStmt->fetch()) {
                // jo user exists hoy to error
                $err = 'Username already exists. Please choose another.';
            } else {
                //  user NOT exist hoy to create them
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);

                // Log the new user in and redirect
                $id = $db->lastInsertId();
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                setcookie('greenpin_user', $username, time() + 60*60*24*7, '/'); // 7 days
                header('Location: index.php'); 
                exit;
            }
         

        } catch (Exception $e) {
    
            $err = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register - GreenPin</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="<?= $formClass ?>">
    <h2>Create account</h2>
    
    <?php if ($err): ?>
      <div class='card' style='color:red; background: #fee;'><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Username
        <input name="username" type="text" value="<?= htmlspecialchars($username ?? '') ?>" required>
      </label>
      <label>Password
        <input name="password" type="password" required>
      </label>
      <button type="submit" class="btn primary" style="width:100%; margin-top: 15px;">Create</button>
      <p style="text-align:center; margin-top: 15px;">
        Already have an account? <a href="login.php">Login here</a>
      </p>
    </form>
  </div>
</body>
</html>