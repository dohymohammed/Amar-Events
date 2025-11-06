<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM organization WHERE authorid = ?");
$stmt->execute([$user_id]);
$organization = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organization) {
    die("Organization not found for current user.");
}

$updateMsg = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payments'])) {

    function validateNumber($num) {
        return preg_match('/^\d{11}$/', $num);
    }

    $wallets = ['bkash', 'nagad', 'rocket'];
    $fields = [];
    $params = [];

    foreach ($wallets as $w) {
        $toggle = isset($_POST[$w . '_toggle']) ? 1 : 0;
        $number = trim($_POST[$w] ?? '');

        if ($toggle) {
            if ($number === '' || !validateNumber($number)) {
                $errors[] = ucfirst($w) . " number must be exactly 11 digits.";
            } else {
                $fields[] = $w . 'number = ?';
                $params[] = $number;
            }
        } else {
            $fields[] = $w . 'number = NULL';
        }
    }

    if (empty($errors) && !empty($fields)) {
        $params[] = $organization['id'];
        $sql = "UPDATE organization SET " . implode(', ', $fields) . " WHERE id = ?";
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);
        $updateMsg = "Payment wallet numbers updated successfully.";

        $stmt = $pdo->prepare("SELECT * FROM organization WHERE authorid = ?");
        $stmt->execute([$user_id]);
        $organization = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Payment Configuration - Amar Events</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="/assests/navbar.css">
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">

<?php
require_once __DIR__ . '/../config/navbar.php';
?>

<main class="md:ml-72 p-6 flex-grow max-w-3xl mx-auto w-full">
    <h1 class="text-3xl font-bold mb-6">Payment Wallet Configuration</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-700 text-red-100 rounded p-4 mb-6">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($updateMsg): ?>
        <div class="bg-green-700 text-green-100 rounded p-4 mb-6"><?= htmlspecialchars($updateMsg) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 bg-[#1e1e2f] p-6 rounded-xl shadow">
        <?php
        $wallets = [
            'bkash' => $organization['bkashnumber'] ?? null,
            'nagad' => $organization['nagadnumber'] ?? null,
            'rocket' => $organization['rocketnumber'] ?? null
        ];
        foreach ($wallets as $name => $value):
            $checked = !empty($value);
        ?>
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label for="<?= $name ?>" class="font-semibold capitalize"><?= $name ?> Number</label>

                <label class="relative inline-block w-12 h-6 cursor-pointer">
                    <input type="checkbox" name="<?= $name ?>_toggle" id="<?= $name ?>_toggle" class="sr-only peer" <?= $checked ? 'checked' : '' ?>>

                    <span class="absolute inset-0 bg-gray-700 rounded-full transition-colors duration-200 peer-checked:bg-purple-500"></span>

                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-md border-4 border-white transition-transform duration-200 peer-checked:translate-x-6"></span>
                </label>
            </div>

            <div id="<?= $name ?>_input_wrapper" class="overflow-hidden transition-all duration-300 max-h-0 <?= $checked ? 'max-h-20 mt-2' : '' ?>">
                <div class="flex items-center">
                    <span class="bg-gray-700 text-gray-300 rounded-l px-3 py-2 select-none">+88</span>
                    <input id="<?= $name ?>" name="<?= $name ?>" type="text" inputmode="numeric" maxlength="11" placeholder="Enter 11-digit <?= ucfirst($name) ?> number"
                           value="<?= htmlspecialchars($value ?? '') ?>"
                           class="flex-grow p-2 rounded-r bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none text-white" />
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" name="save_payments" class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">Save</button>
        <p class="mt-6 text-center text-gray-400 italic">Setup your Payment wallet numbers above</p>
    </form>
</main>

<footer class="bg-[#1e1e2f] text-center text-sm text-gray-400 py-4 mt-auto">
    All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.
</footer>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<script>
    lucide.createIcons();

    const sidebar = document.getElementById("sidebar");
    document.getElementById("toggleSidebar")?.addEventListener("click", () => {
        sidebar.classList.toggle("-translate-x-full");
    });

    const paymentBtn = document.getElementById('paymentDropdownBtn');
    const paymentDropdown = document.getElementById('paymentDropdown');
    paymentBtn?.addEventListener('click', () => {
        paymentDropdown.classList.toggle('hidden');
    });

    ["bkash","nagad","rocket"].forEach(name => {
        const toggle = document.getElementById(name+"_toggle");
        const wrapper = document.getElementById(name+"_input_wrapper");
        toggle?.addEventListener("change", () => {
            if(toggle.checked){
                wrapper.classList.add("max-h-20","mt-2");
            } else {
                wrapper.classList.remove("max-h-20","mt-2");
            }
        });
    });
</script>

</body>
</html>


