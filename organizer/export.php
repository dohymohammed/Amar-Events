<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$organizerId = $_SESSION['user']['id'];
$eventId = $_GET['id'] ?? null;
if (!$eventId) die("Event ID is required.");

$stmt = $pdo->prepare("
    SELECT e.*
    FROM events e
    INNER JOIN organization o ON e.organization = o.id
    WHERE e.id = ? AND o.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Event not found or permission denied.");

$participantsStmt = $pdo->prepare("
    SELECT
        id AS ticket_id,
        creation_date AS ticket_created,
        transid,
        name AS payment_name,
        email AS payment_email,
        number AS payment_number,
        note,
        money AS payment_amount,
        status AS payment_status
    FROM tickets
    WHERE eventid = ? AND status = 'Success'
    ORDER BY creation_date DESC
");
$participantsStmt->execute([$eventId]);
$participants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);



$fields = $pdo->prepare("SELECT * FROM custom_fields WHERE eventid = ?");
$fields->execute([$eventId]);
$fields = $fields->fetchAll(PDO::FETCH_ASSOC);

$ticketIds = array_column($participants, 'ticket_id');
$customValues = [];
if (!empty($ticketIds)) {
    $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
    $cvStmt = $pdo->prepare("
        SELECT ticket_id, field_id, value
        FROM ticket_custom_values
        WHERE ticket_id IN ($placeholders)
    ");
    $cvStmt->execute($ticketIds);
    while ($row = $cvStmt->fetch(PDO::FETCH_ASSOC)) {
        $customValues[$row['ticket_id']][$row['field_id']] = $row['value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Report - <?= htmlspecialchars($event['name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-6">

  <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-xl p-8">

    <div class="text-center mb-8">
      <img src="https://amarevents.zone.id/amar-events-logo.png" alt="Logo" class="h-16 mx-auto mb-4">
      <h1 class="text-3xl font-bold text-blue-700">Participants Report</h1>
      <p class="text-gray-500 text-lg"><?= htmlspecialchars($event['name']) ?></p>
      <p class="text-sm text-gray-400">Generated on <?= date("Y-m-d H:i") ?></p>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-300 text-sm rounded-lg overflow-hidden">
        <thead class="bg-blue-600 text-white">
          <tr>
            <th class="px-4 py-2 border">ID</th>
            <th class="px-4 py-2 border">Transaction ID</th>
            <th class="px-4 py-2 border">Name</th>
            <th class="px-4 py-2 border">Email</th>
            <th class="px-4 py-2 border">Number</th>
            <th class="px-4 py-2 border">Amount</th>
            <th class="px-4 py-2 border">Note</th>
            <th class="px-4 py-2 border">Status</th>
            <th class="px-4 py-2 border">BIB</th>
            <?php foreach ($fields as $f): ?>
              <th class="px-4 py-2 border"><?= htmlspecialchars($f['field_name']) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (count($participants) === 0): ?>
            <tr>
              <td colspan="<?= 9 + count($fields) ?>" class="text-center py-6 text-gray-500">
                No successful participants found.
              </td>
            </tr>
          <?php else: ?>
          <?php $serial = 1; ?>
            <?php foreach ($participants as $p): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border"><?= $serial++ ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['transid'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['payment_name'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['payment_email'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['payment_number'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['payment_amount'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['note'] ?? '') ?></td>
                <td class="px-4 py-2 border font-semibold text-green-600"><?= htmlspecialchars($p['payment_status'] ?? '') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($p['ticket_id'] + 198420) ?></td>

                <?php foreach ($fields as $f): ?>
                  <td class="px-4 py-2 border"><?= htmlspecialchars($customValues[$p['ticket_id']][$f['id']] ?? '') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

<div class="mt-4 flex justify-center">
  <a href="/organizer/participants?id=<?= $eventId ?>" 
     class="px-4 py-2 bg-gray-500 text-white rounded-lg shadow hover:bg-gray-600">
    ← Back
  </a>
</div>

<div class="mt-4 flex justify-center gap-4">
  <a href="" 
     class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700">
    Export as pdf (disabled)
  </a>

  <a href="docx.php?id=<?= $eventId ?>" 
     class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">
    Export as Word
  </a>
</div>

    <div class="mt-12 text-center text-xs text-gray-500 border-t pt-4">
      <p>© <?= date("Y") ?> Amar Events • Generated Report</p>
    </div>
  </div>

</body>
</html>
