
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']['id'])) {
    header("Location: /login");
    exit;
}

$userId = $_SESSION['user']['id'];


$stmt = $pdo->prepare("SELECT admin FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['admin'] != 1) {  
    http_response_code(403); 
    die("Forbidden: You don't have permission to access this page.");
}


$_SESSION['user']['admin'] = $user['admin'];
