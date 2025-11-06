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
require_once __DIR__ . '/config/db.php';

$clientID = 'app_client_id';

$idToken = $_POST['id_token'] ?? $_GET['id_token'] ?? null;
$phoneSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone']) && isset($_POST['email']);

$error = '';

if (!$idToken && !$phoneSubmitted) {
    die('No ID token provided.');
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function verifyGoogleToken($idToken, $clientID) {
    $tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (!$data || isset($data['error_description'])) {
        die('Invalid ID token: ' . ($data['error_description'] ?? 'Unknown error'));
    }

    if ($data['aud'] !== $clientID) {
        die('Token audience mismatch.');
    }

    if ($data['exp'] < time()) {
        die('Token expired.');
    }

    return $data;
}

function cleanPhoneNumber($phone) {

    return preg_replace('/\D+/', '', $phone);
}

if ($phoneSubmitted) {
    $email = trim($_POST['email']);
    $phoneRaw = trim($_POST['phone']);
    $phone = cleanPhoneNumber($phoneRaw);
    $name = trim($_POST['name'] ?? 'User');
    $type = 'user';
    $ipadress = getUserIP();

    if (strlen($phone) !== 11) {
        $error = "Phone number must be exactly 11 digits.";
    } else {

        if (substr($phone, 0, 2) !== '88') {
            $phone = '+88' . $phone;
        }

        $stmtCheck = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error = "User with this email already exists. Try logging in.";
        } else {
            $stmtPhone = $pdo->prepare("SELECT * FROM users WHERE number = ?");
            $stmtPhone->execute([$phone]);
            if ($stmtPhone->fetch()) {
                $error = "Phone number already in use.";
            } else {
                $randomPassword = bin2hex(random_bytes(8));
                $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

                $username = substr(preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name)), 0, 100);
                if (empty($username)) {
                    $username = 'user' . time();
                }

                $insert = $pdo->prepare("INSERT INTO users (username, email, password, number, type, ipaddress) 
                         VALUES (?, ?, ?, ?, ?, ?)");
$insert->execute([$username, $email, $hashedPassword, $phone, $type, $ipadress]);

                $newUserId = $pdo->lastInsertId();

                $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmtUser->execute([$newUserId]);
                $newUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

                $_SESSION['user'] = $newUser;

                header("Location: /");
                exit();
            }
        }
    }
} else {
    $data = verifyGoogleToken($idToken, $clientID);

    $email = $data['email'] ?? null;
    $name = $data['name'] ?? 'User';

    if (!$email) {
        die('Email not found in token.');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: /");
        exit();
    }

}

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Complete Registration</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white shadow-lg rounded-lg max-w-md w-full p-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Almost done! Please enter your phone number</h2>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline"><?=htmlspecialchars($error)?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-6" novalidate>
      <input type="hidden" name="email" value="<?=htmlspecialchars($email)?>" />
      <input type="hidden" name="name" value="<?=htmlspecialchars($name)?>" />

      <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number <span class="text-sm text-gray-400">(11 digits, Bangladesh only)</span></label>

      <div class="flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
        <span class="bg-gray-100 text-gray-600 px-3 select-none">+88</span>
        <input
          id="phone"
          name="phone"
          type="tel"
          maxlength="11"
          pattern="[0-9]{11}"
          placeholder="1XXXXXXXXX"
          required
          autocomplete="tel"
          class="flex-1 p-3 text-gray-900 placeholder-gray-400 focus:outline-none"
          oninput="this.value=this.value.replace(/[^0-9]/g,'');"
          aria-describedby="phoneHelp"
        />
      </div>
      <p id="phoneHelp" class="text-xs text-gray-500 mt-1 mb-4">Enter your 11-digit phone number without country code.</p>

      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-md transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
      >
        Complete Registration
      </button>
    </form>
  </div>
</body>
</html>