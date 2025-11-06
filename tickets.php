<?php
require 'config/db.php';
require 'vendor/autoload.php'; 

use Dompdf\Dompdf;

$paymentId = intval($_GET['participant'] ?? 0);
$email     = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
$transid   = $_GET['transid'] ?? '';
$download  = isset($_GET['download']); 

if (!$paymentId || !$email || !$transid) {
    die("Invalid request.");
}

try {
    $stmt = $pdo->prepare("
    SELECT 
        t.name AS buyer_name,
        t.email,
        t.transid,
        t.number AS number,
        t.creation_date AS payment_date,
        t.money,
        e.name AS event_name,
        e.location AS event_location,
        t.number AS ticket_number
    FROM tickets t
    JOIN events e ON e.id = t.eventid
    WHERE t.id = :ticketid
      AND t.email = :email
      AND t.transid = :transid
      AND t.status = 'Success'
    LIMIT 1
");
$stmt->execute([
    ':ticketid' => $paymentId, // <--- fixed
    ':email'    => $email,
    ':transid'  => $transid
]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) die("Invalid Request. Contact the organizer!");

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}


$ticketUrl = "https://amarevents.zone.id/tickets?participant={$paymentId}&email=" . urlencode($ticket['email']) . "&transid=" . urlencode($ticket['transid']);
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($ticketUrl);

$qrImage   = @file_get_contents($qrCodeUrl);
$qrBase64  = $qrImage ? "data:image/png;base64,".base64_encode($qrImage) : $qrCodeUrl;

$footerHtml = '';
if (!$download) {
    $footerHtml = '
        <br>
        <a href="?participant='.$paymentId.'&email='.urlencode($ticket['email']).'&transid='.urlencode($ticket['transid']).'&download=1" class="btn-download">Download PDF</a>
    ';
}

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family:"Poppins",sans-serif;
    background:#e3e6f0;
    margin:0;
    padding:20px;
    color:#333;
}
.card {
    max-width:820px;
    margin:20px auto;
    background:#fff;
    border-radius:20px;
    box-shadow:0 15px 40px rgba(0,0,0,0.12);
    overflow:hidden;
    border-top:6px solid 

    page-break-inside: avoid;
}
.card-header {
    background:linear-gradient(135deg,
#3b82f6);

    color:#fff;
    padding:20px;
    text-align:center;
}
.card-header h1 {margin:0; font-size:26px;}
.card-header p {margin:8px 0 0; font-size:14px; font-weight:500; color:rgba(255,255,255,0.85);}
.card-body {
    display:flex;
    flex-wrap:wrap;
    padding:20px;
    gap:20px;
    justify-content:space-between;
}
.section {
    flex:1 1 240px;
    min-width:220px;
    background:#f8f9fc;
    padding:15px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}
.section h2 {
    font-size:16px;
    margin-bottom:10px;
    color:#4f46e5;
    border-bottom:2px solid 

    padding-bottom:5px;
}
.info-table {width:100%; border-collapse:collapse;}
.info-table td {padding:4px 6px; vertical-align:top; font-size:14px;}
.info-table td.label {font-weight:600; color:#555; width:110px;}
.info-table td.value {color:#111;}
.qr-code {
    flex:0 0 160px;
    height:160px;
    border:2px dashed 

    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:auto;
}
.qr-code img {width:140px; height:140px;}
.card-footer {
    text-align:center;
    padding:15px;
    font-size:13px;
    color:#666;
    background:#f7f8fc;
}
.btn-download {
    display:inline-block;
    margin-top:12px;
    padding:10px 20px;
    background:#4f46e5;
    color:#fff;
    text-decoration:none;
    font-weight:600;
    border-radius:8px;
}

@media (max-width:600px) {
    .card-body {flex-direction:column; align-items:stretch;}
    .section {min-width:100%;}
    .qr-code {margin-top:15px;}
}

@page { size: A4; margin: 10mm; }
</style>
</head>
<body>
<div class="card">
<div class="card-header">
<h1>'.htmlspecialchars($ticket['event_name']).'</h1>
<p>Official Admit Card</p>
</div>
<div class="card-body">
<div class="section">
<h2>Event Details</h2>
<table class="info-table">
<tr><td class="label">Event Name:</td><td class="value">'.htmlspecialchars($ticket['event_name']).'</td></tr>
<tr><td class="label">Location:</td><td class="value">'.htmlspecialchars($ticket['event_location']).'</td></tr>
<tr><td class="label">Date & Time:</td><td class="value">'.date("d M Y, h:i A", strtotime($ticket["payment_date"])).'</td></tr>
<tr><td class="label">Price:</td><td class="value">৳'.htmlspecialchars($ticket['money']).'</td></tr>
</table>
</div>
<div class="section">
<h2>Participant Details</h2>
<table class="info-table">
<tr><td class="label">Name:</td><td class="value">'.htmlspecialchars($ticket['buyer_name']).'</td></tr>
<tr><td class="label">Email:</td><td class="value">'.htmlspecialchars($ticket['email']).'</td></tr>
<tr>
  <td class="label">Phone: </td>
  <td class="value">'.htmlspecialchars($ticket['number'] ?? '').'</td>
</tr>


</table>
</div>
<div class="section">
<h2>Payment Details</h2>
<table class="info-table">
<tr><td class="label">Transaction ID:</td><td class="value">'.htmlspecialchars($ticket['transid']).'</td></tr>
<tr><td class="label">Payment Date:</td><td class="value">'.date("d M Y", strtotime($ticket["payment_date"])).'</td></tr>
<tr><td class="label">Amount Paid:</td><td class="value">৳'.htmlspecialchars($ticket['money']).'</td></tr>
<tr><td class="label">Status:</td><td class="value">Success</td></tr>
</table>
</div>
<div class="section qr-code">
<img src="'.$qrBase64.'" alt="QR Code">
</div>
</div>
<div class="card-footer">
Present this admit card at the event entrance.
'.$footerHtml.'
</div>
</div>
</body>
</html>
';

if ($download) {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream($ticket['buyer_name']."-card.pdf", ["Attachment" => false]);
    exit;
}

echo $html;