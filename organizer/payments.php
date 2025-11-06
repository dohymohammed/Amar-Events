<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

$mail = $mailer; 

$rapidSmsUser = "rapid_sms_id";
$rapidSmsPass = "password";
$discordsms = "discord_webhook";
$discordlog = "discord_general_webhook";

$user_id = $_SESSION['user']['id'];

if (!empty($_GET['id'])) {
    $eventId     = (int) $_GET['id'];
    $organizerId = $_SESSION['user']['id'];

    $check = $pdo->prepare("
        SELECT e.* 
        FROM events e
        INNER JOIN organization o ON e.organization = o.id
        WHERE e.id = ? AND o.authorid = ?
    ");
    $check->execute([$eventId, $organizerId]);
    $eventcheck = $check->fetch(PDO::FETCH_ASSOC);

    if (!$eventcheck) {
        die("Event not found.");
    }
}


$stmt = $pdo->prepare("SELECT id FROM organization WHERE authorid = ?");
$stmt->execute([$user_id]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);


    $org_id = $org['id']; 

     
    $events = $pdo->prepare("SELECT id, name, banner, prize, emailtitle, emailmessage, location, date FROM events WHERE organization = ? ORDER BY id DESC");
    $events->execute([$org_id]);
    $eventList = $events->fetchAll(PDO::FETCH_ASSOC);






$smsError = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ticket_id'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $action = $_POST['action'] === 'approve' ? 'Success' : 'Failed';

    $update = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $update->execute([$action, $ticket_id]);

    if ($action === 'Success') {

        $stmt = $pdo->prepare("
            SELECT 
                t.email,
                t.name AS username,
                t.number,
                t.id,
                t.transid,
                e.smscount,
                e.id AS event_id,
                e.name AS eventname,
                e.location,
                e.email AS gmail,
                e.date,
                e.prize,
                e.emailtitle,
                e.emailmessage,
                e.sms,
                e.smsmessage
            FROM tickets t
            JOIN events e ON e.id = t.eventid
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);



        if ($info['gmail'] == 1 || $info['sms'] == 1) {
            try {
              
              if($info['gmail'] == 1){
                $mail->clearAllRecipients();
                $mail->addAddress($info['email']);

                $customFieldsData = [];
                $ticketStmt = $pdo->prepare("
                    SELECT cf.field_name, tcv.value
                    FROM ticket_custom_values tcv
                    JOIN custom_fields cf ON tcv.field_id = cf.id
                    WHERE tcv.ticket_id = ?
                ");
                $ticketStmt->execute([$ticket_id]);
                foreach ($ticketStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $customFieldsData[$row['field_name']] = $row['value'];
                }

                $placeholders = [
                    '{name}'     => htmlspecialchars($info['username'] ?? 'Participant'),
                    '{event}'    => htmlspecialchars($info['eventname'] ?? 'Event'),
                    '{location}' => htmlspecialchars($info['location'] ?? 'Location TBD'),
                    '{date}'     => htmlspecialchars($info['date'] ?? 'Date TBD'),
                    '{prize}'    => htmlspecialchars($info['prize'] ?? '0'),
                    '{id}'       => htmlspecialchars($info['id'] ?? '0'),
                ];
                foreach ($customFieldsData as $fieldName => $fieldValue) {
                    $placeholders['{' . $fieldName . '}'] = htmlspecialchars($fieldValue);
                }

                $finalTitle   = strtr($info['emailtitle'], $placeholders);
                $finalMessage = strtr($info['emailmessage'], $placeholders);

                $finalMessage = preg_replace_callback('/sum\[(.*?)\]/', function($matches) use ($placeholders) {
                    $expr = $matches[1];
                    foreach ($placeholders as $key => $val) $expr = str_replace($key, $val, $expr);
                    $expr = preg_replace('/[^0-9+\-*\/(). ]/', '', $expr);
                    $result = 0;
                    try { $result = eval('return '.$expr.';'); } catch (\Throwable $e) { $result = 0; }
                    return $result;
                }, $finalMessage);

                $mail->Subject = $finalTitle;
                $mail->isHTML(true);

                $url = sprintf(
                    'https://amarevents.zone.id/tickets?participant=%s&email=%s&transid=%s',
                    urlencode($info['id']),
                    urlencode($info['email']),
                    urlencode($info['transid'])
                );

                $mail->Body = "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Ticket Confirmation</title>
  <style>
    body { margin:0; padding:0; background:#ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color:#111; }
    .container { max-width:600px; margin:30px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,.1); padding:30px; }
    .username { background:#f5f5f5; border-radius:10px; padding:20px; text-align:center; font-size:22px; font-weight:bold; color:#111; margin-bottom:25px; }
    .message { background:#fafafa; border-radius:10px; padding:25px; font-size:16px; line-height:1.6; color:#000; font-weight:500; text-align:left; }
    .message strong { color:#000; font-weight:600; }
    .btn { display:inline-block; margin-top:20px; padding:10px 20px; background:#007bff; color:#fff !important; text-decoration:none; font-size:14px; border-radius:6px; font-weight:600; letter-spacing:0.3px; transition:background 0.2s ease-in-out; }
    .btn:hover { background:#0056b3; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='username'>" . htmlspecialchars($info['username'] ?? 'Participant') . "</div>
    <div class='message'>" . nl2br($finalMessage) . "<br><br>
      <a href='" . $url . "' class='btn' target='_blank'>Verify/Download as PDF</a>
    </div>
  </div>
</body>
</html>";

                $mail->AltBody = strip_tags($finalMessage);
                $mail->send();
}
                if (!empty($info['sms']) && $info['sms'] == 1 && !empty($info['smsmessage'])) {
                    $smsPlaceholders = $placeholders;
                    $smsMessage = strtr($info['smsmessage'], $smsPlaceholders);
                    $smsMessage = preg_replace_callback('/sum\[(.*?)\]/', function($matches) use ($smsPlaceholders) {
                        $expr = $matches[1];
                        foreach ($smsPlaceholders as $key => $val) $expr = str_replace($key, $val, $expr);
                        $expr = preg_replace('/[^0-9+\-*\/(). ]/', '', $expr);
                        $result = 0;
                        try { $result = eval('return '.$expr.';'); } catch (\Throwable $e) { $result = 0; }
                        return $result;
                    }, $smsMessage);

                    $smsNumber = preg_replace('/^\+/', '', $info['number']);
                    $smsUrl = "https://sms.rapidsms.xyz/request.php?user_id=" . urlencode($rapidSmsUser) .
                              "&password=" . urlencode($rapidSmsPass) .
                              "&number=" . urlencode($smsNumber) .
                              "&message=" . urlencode($smsMessage);

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $smsUrl);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_exec($curl);
                    curl_close($curl);

                    $newSmsCount = ($info['smscount'] ?? 0) + 1;
                    $updateSmsCount = $pdo->prepare("UPDATE events SET smscount = ? WHERE id = ?");
                    $updateSmsCount->execute([$newSmsCount, $info['event_id']]);
                }

            } catch (Exception $e) {
                die("Mailer/SMS Error: " . $e->getMessage());
            }
        }
    }
}

$selectedEventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$ticketsByStatus = ['Pending' => [], 'Success' => [], 'Failed' => []];

if ($selectedEventId) {
    $ticketsStmt = $pdo->prepare("
        SELECT id, name, email, number, transid, status, method
        FROM tickets
        WHERE eventid = ?
    ");
    $ticketsStmt->execute([$selectedEventId]);
    foreach ($ticketsStmt->fetchAll(PDO::FETCH_ASSOC) as $ticket) {
        $ticketsByStatus[$ticket['status']][] = $ticket;
    }
}


?>



<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  
    <title>Organizer Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assests/navbar.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">
<?php
require_once __DIR__ . '/../config/navbar.php';
?>
<div class="flex justify-center md:ml-72 mt-6">
  <div class="bg-[#1e1e2f] p-6 rounded-3xl shadow-xl w-full max-w-lg flex justify-center">
    <a href="/organizer/payment-config"
       class="w-[95%] bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-indigo-600 hover:to-blue-500 text-white font-bold py-3 rounded-2xl shadow-lg transform hover:scale-105 transition-all duration-300 text-center">
       Edit Payment Details
    </a>
  </div>
</div>
<main class="md:ml-72 p-6 flex-grow mb-16 md:mb-0">

    <h1 class="text-3xl font-bold mb-6">Event Payments</h1>
    <?php if (!$selectedEventId): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <?php foreach ($eventList as $event): ?>
                <a href="?id=<?= $event['id'] ?>" class="bg-[#1e1e2f] p-4 rounded-xl hover:bg-[#2a2a3d] transition shadow">
                    <img src="<?= htmlspecialchars($event['banner']) ?>" class="h-40 w-full object-cover rounded mb-4">
                    <h2 class="text-xl font-bold"><?= htmlspecialchars($event['name']) ?></h2>
                    <p class="text-sm text-gray-400 mt-1">Ticket Cost: BDT <?= $event['prize'] ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="mb-4 flex gap-4">
<?php if(!empty($smsError)): ?>
    <div class="alert alert-warning"><?= $smsError ?></div>
<?php endif; ?>
            <button onclick="showTab('Pending')" class="bg-yellow-600 px-4 py-2 rounded">Pending</button>
            <button onclick="showTab('Success')" class="bg-green-600 px-4 py-2 rounded">Success</button>
            <button onclick="showTab('Failed')" class="bg-red-600 px-4 py-2 rounded">Failed</button>
            <a href="/organizer/payments.php" class="ml-auto px-4 py-2 bg-sky-600 rounded text-sm">Back</a>
        </div>
<?php foreach ($ticketsByStatus as $status => $list): ?>
    <div id="<?= $status ?>Tab" class="tab hidden">
        <h2 class="text-3xl font-bold mb-6 text-gray-100 tracking-wide">
            <?= $status ?> Payments
        </h2>

        <?php if (empty($list)): ?>
            <p class="text-gray-400 italic">No <?= strtolower($status) ?> payments found.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($list as $pay): ?>
                    <div
                        class="rounded-2xl bg-gradient-to-br from-[#222238] to-[#1a1a2e]
                               p-6 shadow-lg transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="space-y-2 text-gray-200">
                            <p><span class="font-semibold text-gray-300">Name:</span> <?= htmlspecialchars($pay['name']) ?></p>
                            <p><span class="font-semibold text-gray-300">Email:</span> <?= htmlspecialchars($pay['email']) ?></p>
                            <p><span class="font-semibold text-gray-300">Number:</span> <?= htmlspecialchars($pay['number']) ?></p>
                            <p><span class="font-semibold text-gray-300">Method:</span> <?= htmlspecialchars($pay['method']) ?></p>
                            
                            <p><span class="font-semibold text-gray-300">Transaction ID:</span> <?= htmlspecialchars($pay['transid']) ?></p>
                        </div>

                        <?php if ($status === 'Pending'): ?>
                            <form method="POST" class="mt-5 flex flex-wrap gap-3">
                                <input type="hidden" name="ticket_id" value="<?= $pay['id'] ?>">
                                <button type="submit" name="action" value="approve"
                                    class="px-5 py-2 rounded-full bg-emerald-600 text-white font-medium
                                           transition-colors duration-200 hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-400">
                                    Approve
                                </button>
                                <button type="submit" name="action" value="deny"
                                    class="px-5 py-2 rounded-full bg-rose-600 text-white font-medium
                                           transition-colors duration-200 hover:bg-rose-700 focus:ring-2 focus:ring-rose-400">
                                    Deny
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


    <?php endif; ?>
</main>

<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<script>
    lucide.createIcons();
    function showTab(status) {
        document.querySelectorAll('.tab').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(status + 'Tab').classList.remove('hidden');
    }
    showTab('Pending');
</script>
<script>
  lucide.createIcons();
  const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("toggleSidebar");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("-translate-x-full");
    });
  }
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
