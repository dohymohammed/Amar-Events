<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user']['id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $message = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match.";
    } else {

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            $message = "Current password is incorrect.";
        } else {

            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$new_hashed, $user_id]);
            $message = "Password changed successfully.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Account Settings - Amar Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[rgba(24,24,26,255)] text-white flex flex-col min-h-screen">
  
<?php
  require_once __DIR__ . '/../config/dashboard_navbar.php'; 
 ?>

  <main class="md:ml-72 p-6 flex-grow max-w-md mx-auto w-full md:mb-16">
    <h1 class="text-2xl font-semibold mb-6">Account Settings</h1>

    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded <?= strpos($message, 'successfully') !== false ? 'bg-green-600' : 'bg-red-600' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="bg-[#1e1e2f] p-6 rounded-xl shadow-md" novalidate>
      <div class="mb-4">
        <label for="current_password" class="block mb-1">Current Password</label>
        <input type="password" id="current_password" name="current_password" required
          class="w-full p-2 rounded bg-[#12121c] border border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="mb-4">
        <label for="new_password" class="block mb-1">New Password</label>
        <input type="password" id="new_password" name="new_password" required
          class="w-full p-2 rounded bg-[#12121c] border border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="mb-6">
        <label for="confirm_password" class="block mb-1">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required
          class="w-full p-2 rounded bg-[#12121c] border border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">
        Change Password
      </button>
    </form>
  </main>

  <footer class="bg-[#1e1e2f] text-center text-sm text-gray-400 py-4 mt-auto">
    All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.
  </footer>

  <script>
    lucide.createIcons();
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggleSidebar");
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("-translate-x-full");
    });
  </script>


</body>
</html>
