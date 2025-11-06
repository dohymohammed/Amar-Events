<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$organizerId = $_SESSION['user']['id'];
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$eventId) die("Event ID required");

$stmt = $pdo->prepare("
    SELECT e.*
    FROM events e
    INNER JOIN organization o ON e.organization = o.id
    WHERE e.id = ? AND o.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Event not found or permission denied");

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



$fieldsStmt = $pdo->prepare("SELECT * FROM custom_fields WHERE eventid = ?");
$fieldsStmt->execute([$eventId]);
$fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

$ticketIds = array_column($participants, 'ticket_id');
$customValues = [];
if (!empty($ticketIds)) {
    $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
    $cvStmt = $pdo->prepare("SELECT ticket_id, field_id, value FROM ticket_custom_values WHERE ticket_id IN ($placeholders)");
    $cvStmt->execute($ticketIds);
    while ($row = $cvStmt->fetch(PDO::FETCH_ASSOC)) {
        $customValues[$row['ticket_id']][$row['field_id']] = $row['value'];
    }
}

$phpWord = new PhpWord();
$section = $phpWord->addSection(['marginTop'=>600, 'marginBottom'=>600, 'marginLeft'=>600, 'marginRight'=>600]);

$section->addImage('https://amarevents.zone.id/amar-events-logo.png', ['width'=>80, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
$section->addText('Participants Report', ['bold'=>true, 'size'=>20, 'color'=>'1D4ED8'], ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
$section->addText(htmlspecialchars($event['name']), ['size'=>14, 'color'=>'6B7280'], ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
$section->addText('Generated on '.date("Y-m-d H:i"), ['size'=>10, 'color'=>'9CA3AF'], ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
$section->addTextBreak(1);

$tableStyle = ['borderSize'=>6, 'borderColor'=>'D1D5DB', 'cellMargin'=>80];
$firstRowStyle = ['bgColor'=>'2563EB', 'color'=>'FFFFFF', 'bold'=>true];
$phpWord->addTableStyle('participantsTable', $tableStyle, $firstRowStyle);
$table = $section->addTable('participantsTable');

$table->addRow();
$headers = ['ID','Transaction ID','Name','Email','Number','Amount','Note','Status','BIB'];
foreach ($headers as $h) {
    $table->addCell(2000)->addText($h, ['bold'=>true, 'color'=>'FFFFFF']);
}
foreach ($fields as $f) {
    $table->addCell(2000)->addText($f['field_name'], ['bold'=>true, 'color'=>'FFFFFF']);
}

if (count($participants) === 0) {
    $table->addRow();
    $table->addCell(2000 * (9 + count($fields)))->addText('No successful participants found.', [], ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
} else {
  $serial = 1;
    foreach ($participants as $p) {
        $table->addRow();
        $table->addCell(2000)->addText($serial++);
        $table->addCell(2000)->addText(htmlspecialchars($p['transid'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['payment_name'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['payment_email'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['payment_number'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['payment_amount'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['note'] ?? ''));
        $table->addCell(2000)->addText(htmlspecialchars($p['payment_status'] ?? ''), ['color'=>'16A34A', 'bold'=>true]);
        $table->addCell(2000)->addText(htmlspecialchars($p['ticket_id'] + 198420));

        foreach ($fields as $f) {
            $table->addCell(2000)->addText(htmlspecialchars($customValues[$p['ticket_id']][$f['id']] ?? ''));
        }
    }
}

$section->addTextBreak(1);
$section->addText('© '.date("Y").' Amar Events • Generated Report', ['size'=>10, 'color'=>'9CA3AF'], ['alignment'=>\PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

$filename = 'event_'.$eventId.'_participants.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;