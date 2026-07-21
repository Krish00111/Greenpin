<?php
// login.php
session_start();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    if (!$username || !$password) $err = 'Enter username and password.';
    else {
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                if ($remember) {
                    setcookie('greenpin_user', $username, time() + 60*60*24*30, '/'); // 30 days
                } else {
                    setcookie('greenpin_user', '', time() - 3600, '/');
                }
                header('Location: index.php'); exit;
            } else {
                $err = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $err = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Login - GreenPin</title><link rel="stylesheet" href="styles.css"></head>
<body>
  <div class="auth-form">
    <h2>Login</h2>
    <?php if ($err) echo "<div class='card' style='color:red;'>".htmlspecialchars($err)."</div>"; ?>
    <form method="post">
      <label>Username <input name="username" type="text" required></label>
      <label>Password <input name="password" type="password" required></label>
      <label style="display:inline-block; font-weight: normal;"><input name="remember" type="checkbox" style="width:auto; margin-right: 8px;"> Remember me</label>
      <button type="submit" class="btn primary" style="width:100%; margin-top: 15px;">Login</button>
      <p style="text-align:center; margin-top: 15px;">
        Don't have an account? <a href="register.php">Register here</a>
      </p>
    </form>
  </div>
</body>
</html>