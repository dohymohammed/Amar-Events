<?php
session_start();
include_once 'config/db.php';
include_once 'config/mailer.php';

$mail = $mailer; 

// rapidsms weblink: https://fmsms.biz/ 

$rapidSmsUser = "rapid_sms_user_id";
$rapidSmsPass = "sms_password";
$discordsms = "sms_loging_discord_webhook_token";
$discordlog = "participant_log_discord_for_participant_logs";

$loggedInUserId = $_SESSION['user']['id'] ?? null;

$step = 'form';
$error = '';

$eventid = $_GET['event'] ?? null;

$stmt = $pdo->prepare("SELECT totaltickets, prize, name, organization, deadline, banner, date, location, payment FROM events WHERE id = ?");
$stmt->execute([$eventid]);
$eventData = $stmt->fetch(PDO::FETCH_ASSOC);


if ($eventData['payment'] == 1) {
    header("Location: /register.php?event=" . urlencode($eventid));
    exit;
}





$organizer = $eventData['organization'];
if (!$eventData) {
    echo '

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include "config/meta.php" ?>
    <title>No Event Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen px-4">

    <div class="max-w-xl w-full bg-gray-800 rounded-xl shadow-2xl overflow-hidden border border-gray-700">

        <div class="bg-gradient-to-r from-amber-600 to-yellow-500 text-center py-8">
            <h1 class="text-3xl font-bold text-gray-900">No Event Found</h1>
            <p class="text-gray-900/80 mt-2 text-lg">We couldn’t find the event you’re looking for.</p>
        </div>

        <div class="p-8">
            <p class="text-gray-300 text-center mb-6 leading-relaxed">
                This event may have ended, been removed, or the link is incorrect.  
                Explore our upcoming events to find something that inspires you.
            </p>

            <div class="bg-gray-700 rounded-lg p-5 mb-8 border border-gray-600">
                <p class="text-amber-300 text-center font-medium">
                    Our events are updated regularly — don’t miss out on the next one!
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <a href="/events" class="flex-1 bg-amber-500 hover:bg-amber-600 transition text-gray-900 text-center py-3 rounded-lg font-semibold shadow-md">
                    Browse Events
                </a>
                <a href="/" class="flex-1 bg-gray-700 hover:bg-gray-600 transition text-white text-center py-3 rounded-lg font-semibold shadow-md">
                    Go Home
                </a>
            </div>
        </div>
    </div>

</body>
</html>

';
    exit;
}

$ticketLimit = (int)$eventData['totaltickets'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE eventid = ?");
$stmt->execute([$eventid]);
$ticketsSold = (int)$stmt->fetchColumn();

$registrationDeadline = $eventData['deadline'] ?? null;
$timezone = new DateTimeZone('Asia/Dhaka');

$now = new DateTime('now', $timezone);

if ($ticketsSold >= $ticketLimit) {

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include "config/meta.php" ?>
        <title>Tickets Sold Out</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background-color: #111;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                width: 100%;
                background-color: #1c1c1c;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            }
            .header {
                background: linear-gradient(90deg, 

                text-align: center;
                padding: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 2.5em;
                font-weight: bold;
            }
            .header p {
                margin-top: 5px;
                font-size: 1.1em;
                color: #f0f0f0;
            }
            .content {
                padding: 20px;
            }
            .content h2 {
                margin-top: 0;
                font-size: 1.5em;
            }
            .content p {
                color: #ccc;
                line-height: 1.5;
            }
            .info {
                background-color: #2a2a2a;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .info p {
                margin: 5px 0;
            }
            .buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .buttons a {
                text-decoration: none;
                text-align: center;
                padding: 12px;
                border-radius: 8px;
                font-weight: bold;
                transition: background 0.3s;
            }
            .buttons a.primary {
                background-color: #2563eb;
                color: #fff;
            }
            .buttons a.primary:hover {
                background-color: #1e4bb8;
            }
            .buttons a.secondary {
                background-color: #444;
                color: #fff;
            }
            .buttons a.secondary:hover {
                background-color: #333;
            }
            @media (min-width: 480px) {
                .buttons {
                    flex-direction: row;
                }
                .buttons a {
                    flex: 1;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Sold Out</h1>
                <p>No tickets available</p>
            </div>
            <div class="content">
                <h2>' . htmlspecialchars($eventData['name']) . '</h2>
                <p>We\'re sorry, but all tickets for this event have been sold out. Don\'t worry — we have plenty of exciting events coming up!</p>
                <div class="info">
                    <p><strong>Date:</strong> ' . htmlspecialchars($eventData['date'] ?? 'TBA') . '</p>
                    <p><strong>Location:</strong> ' . htmlspecialchars($eventData['location'] ?? 'TBA') . '</p>
                </div>
                <div class="buttons">
                    <a href="/events" class="primary">Browse Other Events</a>
                    <a href="/" class="secondary">Go Home</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

if ($registrationDeadline) {
    $deadline = new DateTime($registrationDeadline, $timezone);
    if ($now > $deadline) {

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include "config/meta.php" ?>
        <title>Registration Closed</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background-color: #111;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                width: 100%;
                background-color: #1c1c1c;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            }
            .header {
                background: linear-gradient(90deg, 

                text-align: center;
                padding: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 2.5em;
                font-weight: bold;
            }
            .header p {
                margin-top: 5px;
                font-size: 1.1em;
                color: #f0f0f0;
            }
            .content {
                padding: 20px;
            }
            .content h2 {
                margin-top: 0;
                font-size: 1.5em;
            }
            .content p {
                color: #ccc;
                line-height: 1.5;
            }
            .info {
                background-color: #2a2a2a;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .info p {
                margin: 5px 0;
            }
            .buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .buttons a {
                text-decoration: none;
                text-align: center;
                padding: 12px;
                border-radius: 8px;
                font-weight: bold;
                transition: background 0.3s;
            }
            .buttons a.primary {
                background-color: #2563eb;
                color: #fff;
            }
            .buttons a.primary:hover {
                background-color: #1e4bb8;
            }
            .buttons a.secondary {
                background-color: #444;
                color: #fff;
            }
            .buttons a.secondary:hover {
                background-color: #333;
            }
            @media (min-width: 480px) {
                .buttons {
                    flex-direction: row;
                }
                .buttons a {
                    flex: 1;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header" style="background: linear-gradient(90deg, #f59e0b, #fbbf24);">
                <h1>Registration Closed</h1>
                <p>Deadline has passed</p>
            </div>
            <div class="content">
                <h2>' . htmlspecialchars($eventData['name']) . '</h2>
                <p>We\'re sorry, but the registration deadline for this event has ended. Please check our upcoming events for other opportunities to join.</p>
                <div class="info">
                    <p><strong>Date:</strong> ' . htmlspecialchars($eventData['date'] ?? 'TBA') . '</p>
                    <p><strong>Location:</strong> ' . htmlspecialchars($eventData['location'] ?? 'TBA') . '</p>
                </div>
                <div class="buttons">
                    <a href="/events" class="primary">Browse Other Events</a>
                    <a href="/" class="secondary">Go Home</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
}





if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'register') {
    $name = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $eula = isset($_POST['eula']);

$stmtFields = $pdo->prepare("SELECT * FROM custom_fields WHERE eventid = ? ORDER BY id ASC");
$stmtFields->execute([$eventid]);
$customFields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

$customValues = [];
foreach ($customFields as $field) {
    $key = 'custom_' . $field['id'];
    $val = $_POST[$key] ?? '';

    if (is_array($val)) {

        $customValues[$key] = json_encode($val);
    } else {
        $customValues[$key] = trim($val);
    }
}

$_SESSION['reg_user']['custom_values'] = $customValues;

    if (!$name || strlen($phone) !== 11 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$eula) {
        $error = 'Please fill all fields correctly.';
    } else {
        $_SESSION['reg_user'] = array_merge(compact('name', 'phone', 'email', 'note'), [     'custom_values' => $customValues ]);
        $code = rand(100000, 999999);
        $_SESSION['code'] = $code;

        try {
    $mailer->clearAddresses();
    $mailer->addAddress($email, $name);
    $mailer->Subject = 'Verify Your Email for AmarEvents';
    $mailer->isHTML(true);
    $mailer->Body = "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Verify Email</title>
  <style>
    body { margin:0; padding:0; background:#f5f6fa; font-family:Arial, sans-serif; color:#333; }
    .container { max-width:600px; margin:30px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.08); }
    .header { background:#1d3557; color:#fff; text-align:center; padding:20px; font-size:22px; font-weight:bold; }
    .content { padding:30px 25px; text-align:center; }
    .content h2 { margin:0 0 12px; font-size:20px; color:#111; }
    .content p { font-size:16px; margin:0 0 22px; }
    .code { display:inline-block; background:#f5f6fa; padding:14px 26px; font-size:28px; font-weight:bold; border-radius:8px; border:2px solid #1d3557; letter-spacing:3px; color:#1d3557; }
    .footer { background:#fafafa; padding:18px; text-align:center; font-size:13px; color:#555; }
    .footer a { color:#1d3557; text-decoration:none; margin:0 6px; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>AmarEvents Email Verification</div>
    <div class='content'>
      <h2>Hello $name!</h2>
      <p>Before you can continue booking with <strong>AmarEvents</strong>, please verify your email address.</p>
      <div class='code'>$code</div>
      <p style='margin-top:20px;'>Enter this code on the verification page to proceed with your booking request.</p>
    </div>
    <div class='footer'>
      <a href='https://amarevents.zone.id' target='_blank'>Web</a> |
      <a href='https://www.trustpilot.com/review/amarevents.zone.id' target='_blank'>Trustpilot</a> |
      <a href='https://amarevents.zone.id/rules' target='_blank'>Rules</a><br><br>
      © " . date('Y') . " AmarEvents. All rights reserved.
    </div>
  </div>
</body>
</html>";
    $mailer->AltBody = "Hello $name! Your AmarEvents verification code is: $code";
    $mailer->send();
    $step = 'verify';
} catch (Exception $e) {
    $error = 'Mailer error: ' . $mailer->ErrorInfo;
}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'verify') {
    if ($_POST['verifyCode'] == ($_SESSION['code'] ?? '')) {
        $_SESSION['verified'] = true;
        $step = 'confirmation';
    } else {
        $error = 'Incorrect verification code.';
        $step = 'verify';
    }
}





if ($step === 'confirmation') {
  
    $regUser = $_SESSION['reg_user'] ?? null;
    
    if (!$regUser) {
        $error = 'Registration session expired. Please register again.';
        $step = 'form';
    } else {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE eventid = ?");
        $stmt->execute([$eventid]);
        $ticketsSold = (int)$stmt->fetchColumn();

        if ($ticketsSold >= $ticketLimit) {
            echo '
            <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 text-center px-4">
                    <div class="bg-white shadow-lg rounded-lg p-8 max-w-md">
                        <h1 class="text-3xl font-bold text-red-600 mb-4">Tickets Sold Out</h1>
                        <p class="text-gray-700 mb-6">
                            Sorry, while you were registering, the last ticket for <strong>' . htmlspecialchars($eventData['name']) . '</strong> was taken.
                        </p>
                        <a href="/events" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg">
                            Browse Events
                        </a>
                    </div>
                </div>';
            exit;
        }

        $fullPhone = '+88' . $regUser['phone'];



        try {
            $buyer = $loggedInUserId ?? null;
            
            $transid = substr(bin2hex(random_bytes(8)), 0, rand(10,15));
            
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO tickets 
                    (organization, eventid, buyer, name, email, number, note, transid,  status, custom_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Success', ?)
            ");
            $stmt->execute([
                $organizer,
                $eventid,
                $buyer,
                $regUser['name'],
                $regUser['email'],
                $fullPhone,
                $regUser['note'],
                $transid,
                json_encode($regUser['custom_values'] ?? [])
            ]);

            $ticketId = $pdo->lastInsertId();

            if (!empty($regUser['custom_values']) && is_array($regUser['custom_values'])) {
                $stmtCustom = $pdo->prepare("INSERT INTO ticket_custom_values (ticket_id, field_id, value) VALUES (?, ?, ?)");

                foreach ($regUser['custom_values'] as $key => $value) {
                    $fieldId = (int) str_replace('custom_', '', $key);
                    $valueToStore = is_array($value) ? json_encode($value) : trim((string)$value);

                    if ($valueToStore !== '') {
                        $stmtCustom->execute([$ticketId, $fieldId, $valueToStore]);
                    }
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("DB Error: " . $e->getMessage());
        }
            
            

        $ticket_id = $ticketId;
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
                e.date,
                e.prize,
                e.email AS gmail,
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




        if ($info['gmail'] == 1 || $info['sms'] == 1){
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
    











?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register & Pay - AmarEvents</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<?php include "config/meta.php" ?>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f9fafb] text-gray-800 bg-cover bg-center min-h-screen flex items-center justify-center p-4">

  <div class="bg-black bg-opacity-75 w-full max-w-md rounded-2xl shadow-xl text-white overflow-hidden">
    <form method="post" class="p-6 space-y-4">
      <?php if ($error): ?><p class="text-red-400 text-sm text-center"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<?php if ($step === 'form'): ?>
    <input type="hidden" name="step" value="register" />
    <section id="stepForm" class="space-y-4">
        <img src="https://app.amarevents.zone.id/amar-events-logo.png" class="h-16 mx-auto mb-2" />
        <h2 class="text-xl font-semibold text-center">Amar Gateway</h2>

        <input type="text" name="fullname" placeholder="Full Name" class="w-full p-3 rounded bg-gray-800 focus:outline-none" />

        <div class="flex items-center bg-gray-800 rounded">
            <span class="text-gray-400 px-3 select-none">+88</span>
            <input type="tel" name="phone" maxlength="11" placeholder="01XXXXXXXXX" class="w-full p-3 bg-transparent text-white focus:outline-none" />
        </div>

        <input type="email" name="email" placeholder="Email" class="w-full p-3 rounded bg-gray-800 focus:outline-none" />
        <textarea name="note" placeholder="Note (optional)" class="w-full p-3 rounded bg-gray-800 focus:outline-none"></textarea>




        <?php
$stmtFields = $pdo->prepare("SELECT * FROM custom_fields WHERE eventid = ? ORDER BY id ASC");
$stmtFields->execute([$eventid]);
$customFields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

foreach ($customFields as $field):
    $fieldName  = htmlspecialchars($field['field_name'] ?? '');
    $fieldKey   = 'custom_' . ($field['id'] ?? 0);
    $isRequired = !empty($field['required']);
    $fieldType  = $field['field_type'] ?? 'text';
?>
    <div class="w-full mb-3">
        <label class="block mb-1">
            <?= $fieldName ?><?= $isRequired ? ' *' : '' ?>
        </label>

        <?php if (in_array($fieldType, ['text','email','number'])): ?>
            
            <input 
                type="<?= $fieldType ?>" 
                name="<?= $fieldKey ?>" 
                class="w-full p-3 rounded bg-gray-800 focus:outline-none" 
                <?= $isRequired ? 'required' : '' ?> 
            />

        <?php elseif ($fieldType === 'dropdown'): 
            $options = explode(',', $field['options'] ?? '');
        ?>
            <select 
                name="<?= $fieldKey ?>" 
                class="w-full p-3 rounded bg-gray-800 focus:outline-none" 
                <?= $isRequired ? 'required' : '' ?>
            >
                <option value="">Select <?= $fieldName ?></option>
                <?php foreach ($options as $opt): ?>
                    <option value="<?= htmlspecialchars(trim($opt)) ?>">
                        <?= htmlspecialchars(trim($opt)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($fieldType === 'radio'): 
            $options = explode(',', $field['options'] ?? '');
            foreach ($options as $index => $opt): 
        ?>
            <label class="inline-flex items-center mr-4">
                <input 
                    type="radio" 
                    name="<?= $fieldKey ?>" 
                    value="<?= htmlspecialchars(trim($opt)) ?>" 
                    <?= ($isRequired && $index === 0) ? 'required' : '' ?> 
                />
                <span class="ml-2"><?= htmlspecialchars(trim($opt)) ?></span>
            </label>
        <?php endforeach; ?>

        <?php elseif ($fieldType === 'checkbox'): 
            $options = explode(',', $field['options'] ?? '');
            foreach ($options as $opt): 
        ?>
            <label class="inline-flex items-center mr-4">
                <input 
                    type="checkbox" 
                    name="<?= $fieldKey ?>[]" 
                    value="<?= htmlspecialchars(trim($opt)) ?>" 
                    <?= $isRequired ? 'data-required="true"' : '' ?> 
                />
                <span class="ml-2"><?= htmlspecialchars(trim($opt)) ?></span>
            </label>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelector("form")?.addEventListener("submit", function (e) {
        const groups = document.querySelectorAll('input[type="checkbox"][data-required="true"]');
        if (groups.length > 0) {
            const grouped = {};
            groups.forEach(cb => {
                let name = cb.name;
                if (!grouped[name]) grouped[name] = [];
                grouped[name].push(cb);
            });

            for (const name in grouped) {
                if (!grouped[name].some(cb => cb.checked)) {
                    alert("Please select at least one option for required fields");
                    e.preventDefault();
                    return false;
                }
            }
        }
    });
});





</script>


<script>
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>





        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="eula" class="mt-1" required />
            I agree to the Terms and Conditions
        </label>

        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-black font-semibold rounded-lg px-4 py-3">Continue</button>
    </section>

      <?php elseif ($step === 'verify'): ?>
        <input type="hidden" name="step" value="verify" />
        <section class="p-6">
          <h2 class="text-xl font-semibold text-center mb-4">Verify Your Email</h2>
          <p class="text-sm text-gray-300 text-center mb-2">Enter the verification code sent to your email.</p>
          <input type="text" name="verifyCode" placeholder="Enter Code" class="w-full p-3 rounded bg-gray-800 focus:outline-none mb-4" />
          <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-black font-semibold rounded-lg px-4 py-3">Verify & Proceed</button>
        </section>



      <?php elseif ($step === 'confirmation'): ?>
        <section class="p-6 text-center space-y-4 text-gray-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-14 h-14 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
          </svg>
          <p class="text-lg font-semibold">You've Registered</p>

          <p>You can check your ticket details with dashboard OR contact with the organizer.
          <br>
          Note: AmarEvents doesn't provide support for tickets.</p>
          <a href="/" class="inline-block bg-green-400 hover:bg-green-500 text-black font-semibold px-6 py-3 rounded-lg mt-4">Back</a>
          <a href="/signup" class="inline-block bg-green-400 hover:bg-green-500 text-black font-semibold px-6 py-3 rounded-lg mt-4">create an account</a>
          <p>please use the same number you've used to book ticket for signup to get your ticket in dashboard.</p>
        </section>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>