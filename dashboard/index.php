<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_number = $_SESSION['user']['number'] ?? null;

require_once __DIR__ . '/../config/db.php';

if (!$user_number) {
    die("User phone number not found in session.");
}

if (!str_starts_with($user_number, '+88')) {
    $user_number = '+88' . ltrim($user_number, '+');
}


$stmt = $pdo->prepare("
    SELECT 
        COUNT(t.id) AS total_tickets,
        COALESCE(SUM(t.money), 0) AS total_spent
    FROM tickets t
    WHERE t.number = ?
");
$stmt->execute([$user_number]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt2 = $pdo->prepare("
    SELECT 
        e.name AS title,
        e.location,
        e.location_link,
        e.date,
        t.money AS payment,
        t.status
    FROM tickets t
    JOIN events e ON t.eventid = e.id
    WHERE t.number = ?
    ORDER BY e.date ASC
");
$stmt2->execute([$user_number]);
$events = $stmt2->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Amar Events Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[rgba(24,24,26,255)] text-white flex flex-col min-h-screen">

<?php require_once __DIR__ . '/../config/dashboard_navbar.php'; ?>

  <main class="md:ml-72 p-6 flex-grow md:mb-16">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-[#1e1e2f] p-5 rounded-xl shadow hover:shadow-lg transition">
        <h2 class="text-lg font-semibold">Total Tickets</h2>
        <p class="text-3xl mt-2 font-bold"><?= htmlspecialchars($stats['total_tickets']) ?></p>
      </div>
      <div class="bg-[#1e1e2f] p-5 rounded-xl shadow hover:shadow-lg transition">
        <h2 class="text-lg font-semibold">Total Spent</h2>
        <p class="text-3xl mt-2 font-bold"><?= number_format($stats['total_spent'], 2) ?>৳</p>
      </div>
    </div>

    <h2 class="text-xl font-semibold mb-4">Your Registered Events</h2>
    <div class="overflow-x-auto rounded-xl">
      <table class="min-w-full bg-[#1e1e2f] rounded-xl">
        <thead class="bg-[#2a2a3d] text-left">
          <tr>
            <th class="p-4">Title</th>
            <th class="p-4">Location</th>
            <th class="p-4">Event Link</th>
            <th class="p-4">Date</th>
            <th class="p-4">Payment</th>
            <th class="p-4">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($events): ?>
            <?php foreach ($events as $event): ?>
              <tr class="border-t border-[#333] hover:bg-[#1a1a28] transition">
                <td class="p-4"><?= htmlspecialchars($event['title']) ?></td>
                <td class="p-4"><?= htmlspecialchars($event['location']) ?></td>
                <td class="p-4">
                  <?php if (!empty($event['location_link'])): ?>
                    <a href="<?= htmlspecialchars($event['location_link']) ?>" target="_blank" class="text-blue-400 underline">View</a>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td class="p-4"><?= date('d M Y', strtotime($event['date'])) ?></td>
                <td class="p-4"><?= number_format($event['payment'] ?? 0, 2) ?>৳</td>
                <td class="p-4 <?= match (strtolower($event['status'] ?? 'pending')) {
                    'success' => 'text-green-400',
                    'pending' => 'text-yellow-400',
                    'failed'  => 'text-red-400',
                    'expired' => 'text-gray-400',
                    default => 'text-gray-400',
                } ?>">
                  <?= htmlspecialchars($event['status'] ?? 'Pending') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="p-4 text-center text-gray-400">No registered events found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
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


