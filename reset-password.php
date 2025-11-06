<?php
session_start();
require 'config/db.php'; 

require 'config/mailer.php'; 

$error = '';
$success = '';

// requesting post typeo
if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['flash_error'] = "Email not registered.";
    } else {
        $token = bin2hex(random_bytes(16));
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;
        $_SESSION['token_expire'] = time() + 300; 

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?token=$token";

        try {
            $mailer->clearAddresses();
            $mailer->addAddress($email);
            $mailer->Subject = 'Password Reset Request';
            $mailer->isHTML(true);
            $mailer->Body = "
                <h3>Password Reset Request</h3>
                <p>Click the button below to reset your password. This link will expire in 5 minutes.</p>
                <a href='$reset_link' style='display:inline-block;padding:10px 20px;background:#2575fc;color:#fff;text-decoration:none;border-radius:5px;'>Reset Password</a>
                <p>If you didn't request this, ignore this email.</p>
            ";
            $mailer->send();
            $_SESSION['flash_success'] = "Password reset link sent to your email!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Mailer Error: {$mailer->ErrorInfo}";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
        $_SESSION['flash_error'] = "Invalid or expired token.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } elseif (time() > $_SESSION['token_expire']) {
        $_SESSION['flash_error'] = "Token expired.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } elseif (isset($_POST['reset_password'])) {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if ($new_password !== $confirm_password) {
            $_SESSION['flash_error'] = "Passwords do not match.";
        } else {
            $email = $_SESSION['reset_email'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($stmt->execute([$hashed, $email])) {
                $_SESSION['flash_success'] = "Password successfully reset!";
            } else {
                $_SESSION['flash_error'] = "Error updating password.";
            }

            unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['token_expire']);
        }
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Password Reset</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #c9c9c9 ;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .reset-card { 
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 420px;
      padding: 30px;
      text-align: center;
    }
    h2 {
      margin-bottom: 20px;
      color: #333;
    }
    input[type="email"], input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border: 1px solid 

      border-radius: 10px;
      font-size: 15px;
    }
    button {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      background: #2575fc;
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover {
      background: #6a11cb;
    }
    .msg {
      margin: 10px 0;
      padding: 10px;
      border-radius: 8px;
      font-size: 14px;
    }
    .error { background: #ffe5e5; color: #b00020; }
    .success { background: #e6ffed; color: #006400; }
    p {
      margin-top: 15px;
      font-size: 14px;
    }
    a {
      color: #2575fc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<!-- uh reset div -->
<div class="reset-card">
  <h2>Password Reset</h2>

  <?php if ($error) echo "<div class='msg error'>$error</div>"; ?>
  <?php if ($success) echo "<div class='msg success'>$success</div>"; ?>

  <?php if (!isset($_GET['token'])): ?>
    <form method="post">
      <input type="email" name="email" placeholder="Enter your email" required>
      <button type="submit" name="request_reset">Request Reset Link</button>
    </form>
  <?php else: ?>
    <form method="post">
      <input type="password" name="new_password" placeholder="New password" required>
      <input type="password" name="confirm_password" placeholder="Confirm password" required>
      <button type="submit" name="reset_password">Reset Password</button>
    </form>
  <?php endif; ?>

  <p style="font-size:12px;">Remembered your password? <a href="/login">Login here</a></p>
</div>

</body>
</html>

