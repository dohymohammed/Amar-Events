<?php
session_start();

require_once __DIR__ . '/config/variables.php';
require_once DB;
require_once SMTP;

if (isset($_SESSION['user'])) {
    header("Location: /");
    exit;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success' => false, 'errors' => [], 'step' => 1];

    if (isset($_POST['email'], $_POST['password'], $_POST['confirm_password']) && !isset($_POST['verify_code'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (!preg_match('/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|protonmail\.com)$/i', $email)) {
            $response['errors'][] = "Only gmail.com, yahoo.com and protonmail.com emails are allowed.";
        }

        if ($password !== $confirm_password) {
            $response['errors'][] = "Passwords do not match.";
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $response['errors'][] = "Email already registered.";

        if (empty($response['errors'])) {
            $code = strval(rand(100000, 999999));
            $sent = sendVerificationEmail($email, $code);
            if (!$sent) $response['errors'][] = "Failed to send verification email.";
            else {
                $_SESSION['verify_code'] = $code;
                $_SESSION['reg_email'] = $email;
                $_SESSION['reg_password'] = password_hash($password, PASSWORD_DEFAULT);
                $response['success'] = true;
                $response['step'] = 2;
            }
        }
    } elseif (isset($_POST['verify_code'])) {
        $input_code = trim($_POST['verify_code']);
        if (!isset($_SESSION['verify_code']) || $input_code !== $_SESSION['verify_code']) {
            $response['errors'][] = "Verification code incorrect.";
            $response['step'] = 2;
        } else $response['success'] = true; $response['step'] = 3;
    } elseif (isset($_POST['username'], $_POST['number'])) {
        $username = trim($_POST['username']);
        $number = trim($_POST['number']);
        $email = $_SESSION['reg_email'] ?? null;
        $passwordHash = $_SESSION['reg_password'] ?? null;

        if (!$email || !$passwordHash) {
            $response['errors'][] = "Session expired.";
            $response['step'] = 1;
        } else {
            if (!preg_match('/^[0-9]{11}$/', $number)) $response['errors'][] = "Phone number must be 11 digits.";
            $fullNumber = '+88' . $number;
           

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) $response['errors'][] = "Username already taken.";

            $stmt = $pdo->prepare("SELECT id FROM users WHERE number = ?");
            $stmt->execute([$fullNumber]);
            if ($stmt->fetch()) $response['errors'][] = "Phone number already registered.";

            if (empty($response['errors'])) {

$ipadress = getUserIP();
                $stmt = $pdo->prepare("INSERT INTO users (username,email,password,number,type,creation_date,ipaddress) VALUES (?,?,?,?, 'user',NOW(),?)");
                $inserted = $stmt->execute([$username, $email, $passwordHash, $fullNumber, $ipadress]);
                if ($inserted) {
                    $userId = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
                    $stmt->execute([$userId]);
                    $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    unset($_SESSION['reg_email'], $_SESSION['reg_password'], $_SESSION['verify_code']);
                    $response['success'] = true;
                    $response['redirect'] = '/';
                } else $response['errors'][] = "Database error.";
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<?php include "config/meta.php" ?>
<title>Sign Up - AmarWorld</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans">
<div class="bg-white w-full max-w-md rounded-xl shadow-lg p-8" id="reg-container">
<?php include "config/google.php" ?>
<h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Create your AmarWorld account</h2>
<div id="error-box" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 font-semibold"></div>
<div id="form-box"></div>
</div>

<script>
let step = 1;
function renderForm() {
    let html = '';
    if(step===1){
        html = `<form id="reg-form" class="space-y-4">
            <input type="email" name="email" placeholder="Email (gmail, yahoo, protonmail)" required autofocus class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            <input type="password" name="password" placeholder="Password" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-colors">Register</button>
        </form>
        <p class="text-center text-gray-600 mt-4 text-sm">Already have an account? <a href="/login.php" class="text-blue-600 font-semibold hover:underline">Login here</a></p>`;
    } else if(step===2){
        html = `<form id="reg-form" class="space-y-4">
            <input type="text" name="verify_code" placeholder="Enter verification code" required autofocus class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-colors">Verify</button>
        </form>`;
    } else if(step===3){
        html = `<form id="reg-form" class="space-y-4">
            <input type="text" name="username" placeholder="Choose a username" required minlength="3" maxlength="30" autofocus class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            <label for="phone" class="block text-gray-700 font-medium">Phone number</label>
            <div class="flex gap-2">
                <span class="inline-flex items-center px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-gray-700 select-none">+88</span>
                <input type="tel" name="number" id="phone" placeholder="Enter 11 digit phone number" required pattern="[0-9]{11}" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-colors">Complete Registration</button>
        </form>`;
    }
    document.getElementById('form-box').innerHTML = html;
    document.getElementById('reg-form').addEventListener('submit', submitForm);
}

function submitForm(e){
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    data.append('ajax', '1');

    fetch('', {method:'POST', body:data})
    .then(res=>res.json())
    .then(res=>{
        const box = document.getElementById('error-box');
        if(res.errors.length>0){
            box.classList.remove('hidden');
            box.innerHTML = res.errors.join('<br>');
        } else {
            box.classList.add('hidden');
        }
        if(res.redirect) window.location = res.redirect;
        if(res.success && res.step) { step = res.step; renderForm(); }
    });
}

renderForm();
</script>
</body>
</html>
