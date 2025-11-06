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

$client_id = 'discord_cliend_id';
$client_secret = 'discord_client_secret';
$scope = 'identify email guilds.join';
$bot_token = 'discord_bot_token';
$guild_id = 'guild_id';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
             || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$redirect_uri = $protocol . $domain . $_SERVER['PHP_SELF'];

$error = '';

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; 

    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$phoneSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone']);
if ($phoneSubmitted && isset($_SESSION['discord_user'])) {
    $discord_user = $_SESSION['discord_user'];

    $email = $discord_user['email'];
    $discord_user_id = $discord_user['id'];
    $username_db = $discord_user['username'];
    $ip_address = getUserIP();
    $type = 'user';
    

    $phoneRaw = trim($_POST['phone']);
    $phone = preg_replace('/\D+/', '', $phoneRaw);

    if (strlen($phone) !== 11) {
        $error = "Phone number must be exactly 11 digits.";
    } else {
        if (substr($phone, 0, 2) !== '+88') $phone = '+88' . $phone;

        $stmtEmail = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmtEmail->execute([$email]);
        $stmtPhone = $pdo->prepare("SELECT * FROM users WHERE number = ?");
        $stmtPhone->execute([$phone]);

        if ($stmtEmail->fetch()) {
            $error = "User with this email already exists.";
        } elseif ($stmtPhone->fetch()) {
            $error = "Phone number already in use.";
        } else {
            $randomPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("INSERT INTO users (username, email, password, discord_id, number, ipaddress, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$username_db, $email, $hashedPassword, $discord_user_id, $phone, $ip_address, $type]);

            $newUserId = $pdo->lastInsertId();
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->execute([$newUserId]);
            $_SESSION['user'] = $stmtUser->fetch(PDO::FETCH_ASSOC);

            unset($_SESSION['discord_user']); 

            header("Location: /");
            exit();
        }
    }
}

if (!isset($_GET['code']) && !$phoneSubmitted) {
    die('No OAuth2 code provided.');
}

if (isset($_GET['error'])) {
    die('Error: ' . htmlspecialchars($_GET['error']));
}

if (!$phoneSubmitted && isset($_GET['code'])) {
    $code = $_GET['code'];

    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'scope' => $scope
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($response, true);
    $access_token = $token['access_token'] ?? null;
    if (!$access_token) die('Failed to get access token.');

    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user = curl_exec($ch);
    curl_close($ch);

    $user_info = json_decode($user, true);
    $email = $user_info['email'] ?? null;
    $username = $user_info['username'] ?? 'User';
    $discord_user_id = $user_info['id'] ?? null;

    if (!$email || !$discord_user_id) die('Email or Discord ID not found.');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $db_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_user) {
        $_SESSION['user'] = $db_user;

        $join_data = ['access_token' => $access_token];
        $ch = curl_init("https://discord.com/api/v10/guilds/{$guild_id}/members/{$discord_user_id}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $bot_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($join_data));
        curl_exec($ch);
        curl_close($ch);

        header("Location: /");
        exit();
    } else {

        $_SESSION['discord_user'] = $user_info;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
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

<form method="post" class="space-y-6" novalidate>
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
        />
    </div>

    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-md transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
        Complete Registration
    </button>
</form>
</div>
</body>
</html>


