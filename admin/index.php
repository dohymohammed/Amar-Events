<?php
/*
Amar Events - brief description
Copyright (C) 2025 Harun Abdullah

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version. 
*/

require_once 'inc/db.php';
require_once 'inc/auth.php';


$totalEvents   = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPayments = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$totalTickets  = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

$userStats = $pdo->query("
    SELECT DATE(creation_date) AS date, COUNT(*) AS count
    FROM users
    WHERE creation_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(creation_date)
")->fetchAll(PDO::FETCH_ASSOC);

$paymentStats = $pdo->query("
    SELECT DATE(creation_date) AS date, COUNT(*) AS count
    FROM tickets
    WHERE creation_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(creation_date)
")->fetchAll(PDO::FETCH_ASSOC);

function formatChartData($rows) {
    $labels = [];
    $counts = [];
    foreach ($rows as $row) {
        $labels[] = date('M j', strtotime($row['date']));
        $counts[] = $row['count'];
    }
    return ['labels' => $labels, 'counts' => $counts];
}

$userChart = formatChartData($userStats);
$paymentChart = formatChartData($paymentStats);

$recentUsers    = $pdo->query("SELECT * FROM users ORDER BY creation_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recentPayments = $pdo->query("SELECT * FROM tickets ORDER BY creation_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recentEvents   = $pdo->query("SELECT * FROM events ORDER BY creation_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recentTickets  = $pdo->query("SELECT * FROM tickets ORDER BY creation_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AmarEvents Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

<nav class="bg-white shadow-md p-4 flex flex-col md:flex-row items-center justify-between">
  <h1 class="text-xl font-bold text-gray-800 mb-2 md:mb-0">AmarEvents Admin Dashboard</h1>
  <div class="flex space-x-4">
    <a href="/admin/users" class="text-blue-600 hover:underline font-medium">Users</a>
    <a href="/admin/donations" class="text-blue-600 hover:underline font-medium">Donations</a>
    <a href="/logout" class="text-red-600 hover:underline font-medium">Logout</a>
  </div>
</nav>

<main class="p-6 space-y-8">

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 shadow rounded text-center">
      <h2 class="text-gray-500">Events</h2>
      <p class="text-3xl font-bold text-blue-500"><?= $totalEvents ?></p>
    </div>
    <div class="bg-white p-4 shadow rounded text-center">
      <h2 class="text-gray-500">Users</h2>
      <p class="text-3xl font-bold text-green-500"><?= $totalUsers ?></p>
    </div>
    <div class="bg-white p-4 shadow rounded text-center">
      <h2 class="text-gray-500">Payments</h2>
      <p class="text-3xl font-bold text-purple-500"><?= $totalPayments ?></p>
    </div>
    <div class="bg-white p-4 shadow rounded text-center">
      <h2 class="text-gray-500">Tickets</h2>
      <p class="text-3xl font-bold text-yellow-500"><?= $totalTickets ?></p>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-4 shadow rounded">
      <h3 class="font-semibold mb-2 text-gray-700">Users (Last 7 Days)</h3>
      <canvas id="userChart"></canvas>
    </div>
    <div class="bg-white p-4 shadow rounded">
      <h3 class="font-semibold mb-2 text-gray-700">Payments (Last 7 Days)</h3>
      <canvas id="paymentChart"></canvas>
    </div>
  </div>

  <div class="space-y-6">

    <div class="bg-white p-4 rounded shadow overflow-auto">
      <h4 class="text-lg font-semibold mb-2 text-gray-700">Recent Users</h4>
      <table class="min-w-full text-sm">
        <thead><tr class="bg-gray-100 text-left">
          <?php foreach (array_keys($recentUsers[0] ?? []) as $col): ?>
            <th class="p-2"><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($recentUsers as $row): ?>
            <tr class="border-t">
              <?php foreach ($row as $val): ?>
                <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string)$val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="bg-white p-4 rounded shadow overflow-auto">
      <h4 class="text-lg font-semibold mb-2 text-gray-700">Recent Payments</h4>
      <table class="min-w-full text-sm">
        <thead><tr class="bg-gray-100 text-left">
          <?php foreach (array_keys($recentPayments[0] ?? []) as $col): ?>
            <th class="p-2"><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($recentPayments as $row): ?>
            <tr class="border-t">
              <?php foreach ($row as $val): ?>
                <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string)$val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="bg-white p-4 rounded shadow overflow-auto">
      <h4 class="text-lg font-semibold mb-2 text-gray-700">Recent Events</h4>
      <table class="min-w-full text-sm">
        <thead><tr class="bg-gray-100 text-left">
          <?php foreach (array_keys($recentEvents[0] ?? []) as $col):
            if ($col === 'description') continue; ?>
            <th class="p-2"><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($recentEvents as $row): ?>
            <tr class="border-t">
              <?php foreach ($row as $key => $val):
                if ($key === 'description') continue; ?>
                <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string)$val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="bg-white p-4 rounded shadow overflow-auto">
      <h4 class="text-lg font-semibold mb-2 text-gray-700">Recent Tickets</h4>
      <table class="min-w-full text-sm">
        <thead><tr class="bg-gray-100 text-left">
          <?php foreach (array_keys($recentTickets[0] ?? []) as $col): ?>
            <th class="p-2"><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($recentTickets as $row): ?>
            <tr class="border-t">
              <?php foreach ($row as $val): ?>
                <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string)$val) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

</main>

<script>
  new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($userChart['labels']) ?>,
      datasets: [{
        label: 'New Users',
        data: <?= json_encode($userChart['counts']) ?>,
        backgroundColor: '#34D399'
      }]
    }
  });

  new Chart(document.getElementById('paymentChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($paymentChart['labels']) ?>,
      datasets: [{
        label: 'Payments',
        data: <?= json_encode($paymentChart['counts']) ?>,
        backgroundColor: '#818CF8'
      }]
    }
  });
</script>

</body>
</html>
