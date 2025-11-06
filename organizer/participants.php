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

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {







if (isset($_POST['delete_selected'])) {
    $selected = $_POST['selected'] ?? [];

    if (!empty($selected)) {
        $ticketIds = array_filter($selected, fn($id) => is_numeric($id));

        if (!empty($ticketIds)) {
            $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));

            $stmt = $pdo->prepare("SELECT id, transid FROM tickets WHERE id IN ($placeholders) AND eventid = ?");
            $stmt->execute(array_merge($ticketIds, [$eventId]));
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ticketIds = array_column($tickets, 'id');

            if (!empty($ticketIds)) {

                $ticketPlaceholders = implode(',', array_fill(0, count($ticketIds), '?'));
                $delCVStmt = $pdo->prepare("DELETE FROM ticket_custom_values WHERE ticket_id IN ($ticketPlaceholders)");
                $delCVStmt->execute($ticketIds);

                $delTicketsStmt = $pdo->prepare("DELETE FROM tickets WHERE id IN ($ticketPlaceholders) AND eventid = ?");
                $delTicketsStmt->execute(array_merge($ticketIds, [$eventId]));
            }

            $success = count($ticketIds) . " participants and their custom values deleted.";
        } else {
            $error = "No valid participants selected.";
        }
    } else {
        $error = "No participants selected for deletion.";
    }
}




















    if (isset($_POST['mail_selected'])) {
    $selected = $_POST['selected'] ?? [];

    if (count($selected) > 100) {
        $error = "You can mail a maximum of 100 participants at once.";
    } elseif (count($selected) == 0) {
        $error = "No participants selected for mailing.";
    } else {
        $ids = array_filter($selected, fn($id) => is_numeric($id));
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $mailStmt = $pdo->prepare("
                SELECT email
                FROM tickets
                WHERE id IN ($placeholders)
                  AND eventid = ?
                  AND email IS NOT NULL
                  AND email != ''
            ");
            $mailStmt->execute(array_merge($ids, [$eventId]));
            $emails = $mailStmt->fetchAll(PDO::FETCH_COLUMN, 0);

            if (count($emails) == 0) {
                $error = "No valid email addresses found for selected participants.";
            } else {
                $success = "Prepared to mail " . count($emails) . " participants. (Mail sending not implemented)";
            }
        }
    }
}


    
    
    
    
    
    
    
if (isset($_POST['edit_participant'])) {
    $ticketId = $_POST['ticket_id'] ?? null;

    if ($ticketId && is_numeric($ticketId)) {
        $payment_transid = $_POST['payment_transid'] ?? '';
        $payment_name = $_POST['payment_name'] ?? '';
        $payment_email = $_POST['payment_email'] ?? '';
        $payment_number = $_POST['payment_number'] ?? '';
        $payment_note = $_POST['payment_note'] ?? '';
        $payment_money = $_POST['payment_money'] ?? 0;
        $payment_status = $_POST['payment_status'] ?? 'Pending';

        try {
            $pdo->beginTransaction();

            $updateTicketStmt = $pdo->prepare("
                UPDATE tickets SET
                    transid = ?,
                    name = ?,
                    email = ?,
                    number = ?,
                    note = ?,
                    money = ?,
                    status = ?
                WHERE id = ? AND eventid = ?
            ");
            $updateTicketStmt->execute([
                $payment_transid,
                $payment_name,
                $payment_email,
                $payment_number,
                $payment_note,
                $payment_money,
                $payment_status,
                $ticketId,
                $eventId
            ]);

            $pdo->commit();
            $success = "Participant payment info updated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update participant info: " . $e->getMessage();
        }
    } else {
        $error = "Invalid ticket ID for editing.";
    }
}


}




if (!empty($_POST['custom_values'])) {
    foreach ($_POST['custom_values'] as $tid => $fields) {
        $tid = (int)$tid; 

        foreach ($fields as $field_id => $value) {
            $field_id = (int)$field_id;
            $value = trim($value);

            $existsStmt = $pdo->prepare("SELECT id FROM ticket_custom_values WHERE ticket_id = ? AND field_id = ?");
            $existsStmt->execute([$tid, $field_id]);
            $existingId = $existsStmt->fetchColumn();

            if ($existingId) {
                $updateStmt = $pdo->prepare("UPDATE ticket_custom_values SET value = ? WHERE id = ?");
                $updateStmt->execute([$value, $existingId]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticket_custom_values (ticket_id, field_id, value) VALUES (?, ?, ?)");
                $insertStmt->execute([$tid, $field_id, $value]);
            }
        }
    }
}

$participantsStmt = $pdo->prepare("
    SELECT
        id AS ticket_id,
        creation_date AS ticket_created,
        transid AS payment_transid,
        name AS payment_name,
        email AS payment_email,
        number AS payment_number,
        note AS payment_note,
        money AS payment_amount,
        status AS payment_status
    FROM tickets
    WHERE eventid = ?
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




$eventTitle = $event['emailtitle'];

if (isset($_POST['mail_selected'])) {
    $selected = $_POST['selected'] ?? [];

    if (count($selected) > 100) {
        $error = "You can mail a maximum of 100 participants at once.";
    } elseif (count($selected) == 0) {
        $error = "No participants selected for mailing.";
    } else {
        $ids = array_filter($selected, fn($id) => is_numeric($id));
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $mailStmt = $pdo->prepare("
                SELECT name AS username, email, id AS ticketid
                FROM tickets
                WHERE id IN ($placeholders)
                  AND eventid = ?
                  AND status = 'Success'
                  AND email IS NOT NULL AND email <> ''
            ");
            $mailStmt->execute(array_merge($ids, [$eventId]));
            $emails = $mailStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($emails) == 0) {
                $error = "No valid email addresses found for selected participants.";
            } else {
                require_once __DIR__ . '/../config/mailer.php';

                $msgStmt = $pdo->prepare("
                    SELECT emailmessage, emailtitle, name AS eventname, location, date, prize
                    FROM events
                    WHERE id = ?
                ");
                $msgStmt->execute([$eventId]);
                $eventInfo = $msgStmt->fetch(PDO::FETCH_ASSOC);

                if (!$eventInfo || !$eventInfo['emailmessage']) {
                    $error = "No email message configured for this event.";
                } else {
                    $failed = [];
                    foreach ($emails as $row) {
                        $placeholders = [
                            '{name}'     => htmlspecialchars($row['username'] ?? 'Participant'),
                            '{event}'    => htmlspecialchars($eventInfo['eventname'] ?? 'Event'),
                            '{location}' => htmlspecialchars($eventInfo['location'] ?? 'Location TBD'),
                            '{date}'     => htmlspecialchars($eventInfo['date'] ?? 'Date TBD'),
                            '{prize}'    => htmlspecialchars($eventInfo['prize'] ?? '0'),
                            '{ticketid}' => htmlspecialchars($row['ticketid'] ?? ''),
                        ];

                        $finalTitle   = strtr($eventInfo['emailtitle'], $placeholders);
                        $finalMessage = strtr($eventInfo['emailmessage'], $placeholders);

                        try {
                            $mailer->clearAddresses();
                            $mailer->addAddress($row['email']);
                            $mailer->Subject = $finalTitle;
                            $mailer->Body    = nl2br($finalMessage);
                            $mailer->isHTML(true);
                            $mailer->send();
                        } catch (Exception $e) {
                            $failed[] = $row['email'];
                        }
                    }

                    if (count($failed) > 0) {
                        $error = "Failed to send to: " . implode(', ', $failed);
                    } else {
                        $success = "Emails sent successfully to " . count($emails) . " participants.";
                    }
                }
            }
        }
    }
}



?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Event Participants - <?= htmlspecialchars($event['name']) ?> - Amar Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    ::-webkit-scrollbar { height: 8px; width: 8px; }
    ::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 4px; }
    th { position: sticky; top: 0; background-color: #1f2937; z-index: 10; }
    tr:hover { background-color: #2563eb !important; }
  </style>
</head>
<body class="bg-[#12121c] text-white min-h-screen flex flex-col">

<?php
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>



  <main class="flex-grow md:ml-72 p-6 container mx-auto max-w-full overflow-auto mb-16 md:mb-0">
    <h1 class="text-3xl font-bold mb-6">
      Participants for event: <?= htmlspecialchars($event['name']) ?>
    </h1>

    <?php if ($error): ?>
      <div class="mb-4 rounded bg-red-700 p-4 font-semibold"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="mb-4 rounded bg-green-700 p-4 font-semibold"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" id="participantsForm" class="space-y-4">
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <button type="submit" name="delete_selected" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold" onclick="return confirm('Delete selected participants?')">
          Delete Selected
        </button>
        <button type="submit" name="mail_selected" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-semibold" onclick="return confirm('Mail selected participants? Max 100')">
          Mail Selected
        </button>

<a href="export.php?id=<?= $eventId ?>" 
   class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded shadow ">
    Export
</a>



        <span class="text-gray-400 text-sm">Select participants to delete or mail (max 100 for mailing)</span>
      </div>

      <div class="overflow-auto rounded border border-gray-700 shadow">
        <table class="min-w-full text-sm">
<thead>
  <tr>
    <th class="p-2 bg-gray-800 text-center"><input type="checkbox" id="selectAll" /></th>
    <th class="p-2 bg-gray-800">ID</th>
    <th class="p-2 bg-gray-800">Transaction ID</th>
    <th class="p-2 bg-gray-800">Name</th>
    <th class="p-2 bg-gray-800">Email</th>
    <th class="p-2 bg-gray-800">Number</th>
    <th class="p-2 bg-gray-800">Amount</th>
    <th class="p-2 bg-gray-800">Note</th>
    <th class="p-2 bg-gray-800">Status</th>

    <?php foreach ($fields as $f): ?>
      <th class="p-2 bg-gray-800"><?= htmlspecialchars($f['field_name']) ?></th>
    <?php endforeach; ?>
<th class="p-2 bg-gray-800">Ticket Created</th>
    <th class="p-2 bg-gray-800">Actions</th>
  </tr>
</thead>
<tbody>
<?php if (count($participants) === 0): ?>
  <tr>
    <td colspan="<?= 11 + count($fields) ?>" class="text-center p-4 text-gray-400">No participants found.</td>
  </tr>
<?php else: ?>
<?php $serial = 1; ?>
  <?php foreach ($participants as $p): ?>
  <tr class="hover:bg-blue-600">
    <td class="text-center p-2">
      <input type="checkbox" name="selected[]" value="<?= (int)$p['ticket_id'] ?>" class="rowCheckbox cursor-pointer"/>
    </td>
    <td class="p-2"><?= $serial++ ?></td>
<td class="p-2"><?= htmlspecialchars($p['payment_transid'] ?? '') ?></td>
    <td class="p-2"><?= htmlspecialchars($p['payment_name'] ?? '') ?></td>
    <td class="p-2"><?= htmlspecialchars($p['payment_email'] ?? '') ?></td>
    <td class="p-2"><?= htmlspecialchars($p['payment_number'] ?? '') ?></td>
    <td class="p-2"><?= htmlspecialchars($p['payment_amount'] ?? '') ?></td>
<td class="p-2 max-w-xs truncate" title="<?= htmlspecialchars($p['payment_note'] ?? '') ?>"><?= htmlspecialchars($p['payment_note'] ?? '') ?></td>
    <td class="p-2"><?= htmlspecialchars($p['payment_status'] ?? '') ?></td>

    <?php foreach ($fields as $f): ?>
      <td class="p-2"><?= htmlspecialchars($customValues[$p['ticket_id']][$f['id']] ?? '') ?></td>
    <?php endforeach; ?>

<td class="p-2"><?= htmlspecialchars($p['ticket_created']) ?></td>

    <td class="p-2 flex gap-2 justify-center">
      <button type="button" class="bg-blue-600 hover:bg-blue-700 rounded px-2 py-1 text-xs font-semibold"
        onclick='openEditModal(<?= (int)$p['ticket_id'] ?>, <?= json_encode($p, JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_APOS|JSON_HEX_AMP) ?>)'>
        Edit
      </button>

      <input type="hidden" name="ticket_id" value="<?= (int)$p['ticket_id'] ?>"/>
    </td>
  </tr>
  <?php endforeach; ?>
<?php endif; ?>
</tbody>

        </table>
      </div>
    </form>
  </main>

<div id="editModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-[1001] p-4">
  <div class="bg-[#1e1e2f] rounded-lg w-full max-w-3xl relative 
              min-h-[16rem] max-h-[90vh] overflow-y-auto p-6">
    <h2 class="text-xl font-bold mb-4 text-white">Edit Participant Payment Info</h2>
    <form method="POST" id="editParticipantForm" class="space-y-4 text-white">
      <input type="hidden" name="ticket_id" id="edit_ticket_id" />
      <input type="hidden" name="edit_participant" value="1" />

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="edit_payment_transid" class="block mb-1 font-semibold">Transaction ID</label>
          <input type="text" id="edit_payment_transid" name="payment_transid" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        </div>
        <div>
          <label for="edit_payment_name" class="block mb-1 font-semibold">Payment Name</label>
          <input type="text" id="edit_payment_name" name="payment_name" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        </div>
        <div>
          <label for="edit_payment_email" class="block mb-1 font-semibold">Payment Email</label>
          <input type="email" id="edit_payment_email" name="payment_email" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        </div>
        <div>
          <label for="edit_payment_number" class="block mb-1 font-semibold">Payment Number</label>
          <input type="text" id="edit_payment_number" name="payment_number" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        </div>
        <div>
          <label for="edit_payment_money" class="block mb-1 font-semibold">Payment Amount</label>
          <input type="number" step="0.01" id="edit_payment_money" name="payment_money" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        </div>
        <div>
          <label for="edit_payment_status" class="block mb-1 font-semibold">Payment Status</label>
          <select id="edit_payment_status" name="payment_status" class="w-full rounded px-2 py-1 bg-gray-700 text-white">
            <option value="Pending">Pending</option>
            <option value="Success">Success</option>
            <option value="Failed">Failed</option>
          </select>
        </div>

        <div class="col-span-2">
          <label for="edit_payment_note" class="block mb-1 font-semibold">Payment Note</label>
          <textarea id="edit_payment_note" name="payment_note" rows="2" class="w-full rounded px-2 py-1 bg-gray-700 text-white"></textarea>
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" id="closeEditModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleSidebarBtn = document.getElementById('toggleSidebar');
  if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });
  }

  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', function() {
      const checked = this.checked;
      document.querySelectorAll('.rowCheckbox').forEach(cb => cb.checked = checked);
    });
  }

  const editModal = document.getElementById('editModal');
  const closeEditModalBtn = document.getElementById('closeEditModal');

  function openEditModal(ticketId, participant) {
    document.getElementById('edit_ticket_id').value = ticketId;
document.getElementById('edit_payment_transid').value = participant.payment_transid || '';
    document.getElementById('edit_payment_name').value = participant.payment_name || '';
    document.getElementById('edit_payment_email').value = participant.payment_email || '';
    document.getElementById('edit_payment_number').value = participant.payment_number || '';
    document.getElementById('edit_payment_note').value = participant.payment_note || '';
    document.getElementById('edit_payment_money').value = participant.payment_amount || '';
    document.getElementById('edit_payment_status').value = participant.payment_status || 'Pending';

    const container = editModal.querySelector('.grid');
    container.querySelectorAll('.custom-field').forEach(el => el.remove());

    if(customFields.length) {
      customFields.forEach(f => {
        const val = ticketCustomValues[ticketId] && ticketCustomValues[ticketId][f.id] ? ticketCustomValues[ticketId][f.id] : '';
        const div = document.createElement('div');
        div.className = 'col-span-2 custom-field';
        div.innerHTML = `
          <label class="block mb-1 font-semibold">${f.field_name}</label>
          <input type="text" name="custom_values[${ticketId}][${f.id}]" value="${val.replace(/"/g, '&quot;')}" class="w-full rounded px-2 py-1 bg-gray-700 text-white" />
        `;
        container.appendChild(div);
      });
    }

    editModal.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  window.openEditModal = openEditModal;

  if (closeEditModalBtn) {
    closeEditModalBtn.addEventListener('click', () => {
      editModal.classList.add('hidden');
    });
  }

  if (editModal) {
    editModal.addEventListener('click', e => {
      if (e.target === editModal) {
        editModal.classList.add('hidden');
      }
    });
  }

  if (window.lucide) {
    window.lucide.replace();
    lucide.createIcons();
  }
});
</script>

<script>
  const customFields = <?= json_encode($fields, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
  const ticketCustomValues = <?= json_encode($customValues, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
</script>
</body>
</html>
