<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$organizerId = $_SESSION['user']['id'];
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    echo "Event ID missing!";
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.* 
    FROM events e
    INNER JOIN organization o ON e.organization = o.id
    WHERE e.id = ? AND o.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event not found or you do not have permission.";
    exit;
}

if (!in_array((int)($event['sms'] ?? 0), [1,2])) {
    header('Location: /organizer/');
    exit;
}

function replacePlaceholders($template, $participant, $event) {
    return str_replace(
        ['{name}', '{event}', '{location}', '{date}', '{prize}'],
        [
            $participant['fullname'] ?? '',
            $event['name'] ?? '',
            $event['location'] ?? '',
            $event['date'] ?? '',
            $event['prize'] ?? ''
        ],
        $template
    );
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_sms'])) {
        $current = (int)($event['sms'] ?? 0);
        $newStatus = $current === 1 ? 2 : 1;
        $updateStmt = $pdo->prepare("UPDATE events SET sms = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $eventId]);
        $stmt->execute([$eventId, $organizerId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        $message = ((int)$event['sms'] === 1) ? 'SMS turned ON.' : 'SMS turned OFF.';
    } elseif (isset($_POST['save_sms_message'])) {
        $smsMessage = trim($_POST['smsmessage'] ?? '');
        if ($smsMessage === '') {
            $error = 'SMS message cannot be empty.';
        } else {
            $updateStmt = $pdo->prepare("UPDATE events SET smsmessage = ? WHERE id = ?");
            $updateStmt->execute([$smsMessage, $eventId]);
            $message = "SMS message updated successfully.";
            $stmt->execute([$eventId, $organizerId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit SMS Message - Amar Events</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assests/navbar.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">

<?php
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>

<main class="md:ml-72 p-6 flex-grow max-w-3xl mx-auto w-full mb-16 md:mb-0"">
    <h1 class="text-2xl font-bold mb-6">
        Edit SMS Message for Event: <?= htmlspecialchars($event['name']) ?>
    </h1>

    <?php if ((int)($event['sms'] ?? 0) === 1 || (int)($event['sms'] ?? 0) === 2): ?>
    <div class="bg-[#1e1e2d] border border-gray-700 rounded-lg p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-lg font-semibold">SMS System</p>
            <p class="text-gray-400 text-sm">SMS Sent: <?= (int)($event['smscount'] ?? 0) ?></p>
            <p class="text-gray-400 text-sm">Current Status:
                <span class="<?= ((int)$event['sms'] === 1) ? 'text-green-400' : 'text-red-400' ?>">
                    <?= ((int)$event['sms'] === 1) ? 'ON' : 'OFF' ?>
                </span>
            </p>
        </div>

        <div class="flex space-x-3 mt-4 md:mt-0">
            <form method="POST">
                <button type="submit" name="toggle_sms" class="px-4 py-2 rounded-lg font-semibold <?= ((int)$event['sms'] === 1) ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' ?>">
                    <?= ((int)$event['sms'] === 1) ? 'Turn OFF SMS' : 'Turn ON SMS' ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-[#1e1e2d] border border-gray-700 rounded-lg p-4 mb-6">
        <h2 class="text-lg font-semibold mb-2 text-blue-400">Placeholder Guide</h2>
        <p class="text-gray-300 text-sm mb-2">You can use these placeholders in your SMS message. They will be automatically replaced when sending:</p>
        <ul class="list-disc pl-6 text-gray-400 text-sm space-y-1">
            <li><code class="text-blue-400 font-mono">{name}</code> → Participant’s full name</li>
            <li><code class="text-blue-400 font-mono">{event}</code> → Event name (<?= htmlspecialchars($event['name']) ?>)</li>
            <li><code class="text-blue-400 font-mono">{location}</code> → Event location (<?= htmlspecialchars($event['location']) ?>)</li>
            <li><code class="text-blue-400 font-mono">{date}</code> → Event date (<?= htmlspecialchars($event['date']) ?>)</li>
            <li><code class="text-blue-400 font-mono">{prize}</code> → Event prize (<?= htmlspecialchars($event['prize']) ?>)</li>
        </ul>
    </div>

    <?php if ($message): ?>
        <div class="mb-4 p-3 rounded bg-green-700 text-white"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-4 p-3 rounded bg-red-700 text-white"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ((int)$event['sms'] === 1): ?>
    <form method="POST" class="space-y-4">
        <div>
            <label for="smsmessage" class="block font-medium mb-1">SMS Message</label>
            <textarea id="smsmessage" name="smsmessage" rows="6" class="w-full p-3 rounded bg-[#12121c] border border-gray-600 text-white resize-y" required><?= htmlspecialchars($event['smsmessage'] ?? '') ?></textarea>
        </div>

        <button type="submit" name="save_sms_message" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold transition">
            Save SMS Message
        </button>
    </form>
    <?php endif; ?>
</main>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<script>
    lucide.createIcons();
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggleSidebar");
    if (toggleBtn) toggleBtn.addEventListener("click", () => sidebar.classList.toggle("-translate-x-full"));

    const eventsDropdownBtn = document.getElementById('eventsDropdownBtn');
    if (eventsDropdownBtn) eventsDropdownBtn.addEventListener('click', () => {
        const dd = document.getElementById('eventsDropdown');
        if (dd) dd.classList.toggle('hidden');
    });
const paymentBtn = document.getElementById('paymentDropdownBtn');
  const paymentDropdown = document.getElementById('paymentDropdown');
  if (paymentBtn && paymentDropdown) {
    paymentBtn.addEventListener('click', () => {
      paymentDropdown.classList.toggle('hidden');
    });
  }
</script>
</body>
</html>
