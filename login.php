<?php

$lifetime = 60 * 60 * 24 * 30; 


session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => 'amarevents.zone.id',   
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', 
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.gc_maxlifetime', $lifetime); 


session_start();
require_once 'config/db.php';

if (isset($_SESSION['user'])) {
    header("Location: /");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success'=>false,'errors'=>[]];

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("UPDATE users SET ipaddress=? WHERE id=?");
        $stmt->execute([$ip,$user['id']]);

        if (!empty($user['number'])) {
            $stmt = $pdo->prepare("UPDATE tickets SET userid=? WHERE userid IS NULL AND number=?");
            $stmt->execute([$user['id'],$user['number']]);
            $stmt = $pdo->prepare("UPDATE tickets SET userid=? WHERE userid IS NULL AND number=?");
            $stmt->execute([$user['id'],$user['number']]);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$user['id']]);
        $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['redirect'] = '/';
    } else {
        $response['errors'][] = "Invalid email or password. Reset password <a href='/reset-password.php' style='color:blue'>click here</a>";
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Login - AmarWorld</title>
  <?php include 'config/meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }</style>
</head>
<body class="min-h-screen flex">

  <div class="hidden md:flex flex-1 bg-cover bg-center" style="background-image: url('https://i.ibb.co.com/350H2d6J/Gear-5-Luffy-from-One-Piece.jpg');"></div>

  <div class="flex flex-col flex-1 bg-white justify-center p-10 md:p-16 max-w-md w-full mx-auto">
    <?php include "config/google.php" ?>

    <div id="error-box" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm"></div>

    <form id="login-form" class="space-y-6">
      <h2 class="text-3xl font-semibold text-gray-900 mb-6 text-center">Login to AmarWorld</h2>

      <div>
        <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
        <input type="email" id="email" name="email" required autofocus
               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-600"/>
      </div>

      <div>
        <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-600"/>
      </div>

      <button type="submit"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-1">
        Login
      </button>

      <p class="text-center text-gray-600 text-sm mt-4">
        Don't have an account? <a href="/signup.php" class="text-indigo-600 hover:underline font-medium">Sign up</a>
      </p>
    </form>
  </div>

<script>
const form = document.getElementById('login-form');
const errorBox = document.getElementById('error-box');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(form);
  formData.append('ajax', '1');

  try {
    const res = await fetch('', { method: 'POST', body: formData });
    const data = await res.json();

    if(data.errors && data.errors.length){
      errorBox.classList.remove('hidden');
      errorBox.innerHTML = data.errors.join('<br>');
    } else {
      errorBox.classList.add('hidden');
    }

    if(data.success && data.redirect){
      window.location.href = data.redirect;
    }
  } catch(err){
    errorBox.classList.remove('hidden');
    errorBox.innerHTML = "Network error. Please try again.";
  }
});
</script>

</body>
</html>
