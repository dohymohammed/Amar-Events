<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$organizerId = $_SESSION['user']['id'];
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    echo "Event ID missing!";
    exit;
}


$fieldsStmt = $pdo->prepare("SELECT field_name FROM custom_fields WHERE eventid = ?");
$fieldsStmt->execute([$eventId]);
$customFields = $fieldsStmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT events.* 
    FROM events 
    INNER JOIN organization ON events.organization = organization.id 
    WHERE events.id = ? AND organization.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event not found or you do not have permission.";
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_email') {
    header('Content-Type: application/json');

    $emailTitle = trim($_POST['emailtitle'] ?? '');
    $emailMessage = trim($_POST['emailmessage'] ?? '');

    if ($emailTitle === '') {
        echo json_encode(['status' => 'error', 'message' => 'Email title cannot be empty.']);
        exit;
    }
    if ($emailMessage === '') {
        echo json_encode(['status' => 'error', 'message' => 'Email message cannot be empty.']);
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE events SET emailtitle = ?, emailmessage = ? WHERE id = ?");
    $updateStmt->execute([$emailTitle, $emailMessage, $eventId]);

    echo json_encode(['status' => 'success', 'message' => 'Email updated successfully!']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Edit Event Email - Amar Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">

<?php
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>

<main class="md:ml-72 p-6 flex-grow max-w-3xl mx-auto w-full mb-16 md:mb-0">
    <h1 class="text-2xl font-bold mb-6">
        Edit Email for Event: <?= htmlspecialchars($event['name']) ?>
    </h1>

<?php
$stmtEmailFields = $pdo->prepare("SELECT field_name FROM custom_fields WHERE eventid = ?");
$stmtEmailFields->execute([$event['id']]);
$emailFields = $stmtEmailFields->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-[#1e1e2d] border border-gray-700 rounded-lg p-4 mb-6">
    <h2 class="text-lg font-semibold mb-2 text-blue-400">Placeholder Guide</h2>
    <p class="text-gray-300 text-sm mb-2">
        You can use these placeholders in your email title or message.  
        They will be automatically replaced when the email is sent:
    </p>
    <ul class="list-disc pl-6 text-gray-400 text-sm space-y-1">
        <li><code class="text-blue-400 font-mono">{name}</code> → Participant’s full name</li>
        <li><code class="text-blue-400 font-mono">{event}</code> → Event name (<?= htmlspecialchars($event['name']) ?>)</li>
        <li><code class="text-blue-400 font-mono">{location}</code> → Event location (<?= htmlspecialchars($event['location']) ?>)</li>
        <li><code class="text-blue-400 font-mono">{date}</code> → Event date (<?= htmlspecialchars($event['date']) ?>)</li>
        <li><code class="text-blue-400 font-mono">{prize}</code> → Event prize (<?= htmlspecialchars($event['prize']) ?>)</li>
        <li><code class="text-blue-400 font-mono">{id}</code> →  Participant ID </li>
        <?php foreach ($emailFields as $field): ?>
            <?php $placeholder = '{' . preg_replace('/\s+/', ' ', $field['field_name']) . '}'; ?>
            <li>
                <code class="text-blue-400 font-mono"><?= $placeholder ?></code>
                → Event <?= htmlspecialchars($field['field_name']) ?> Value
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div id="responseBox"></div>

<form id="emailForm" method="POST" class="space-y-4">
  <input type="hidden" name="action" value="save_email">
  <div>
    <label for="emailtitle" class="block font-medium mb-1">Email Title</label>
    <input 
      type="text" 
      id="emailtitle" 
      name="emailtitle" 
      value="<?= htmlspecialchars($event['emailtitle'] ?? '') ?>" 
      class="w-full p-3 rounded bg-[#12121c] border border-gray-600 text-white" 
      required 
    />
  </div>

  <div>
    <label for="emailmessage" class="block font-medium mb-1">Email Message</label>
    <textarea 
      id="emailmessage" 
      name="emailmessage" 
      rows="10" 
      class="w-full p-3 rounded bg-[#12121c] border border-gray-600 text-white resize-y" 
      required
    ><?= htmlspecialchars($event['emailmessage'] ?? '') ?></textarea>
  </div>



  <div class="bg-[#1c1c2b] border border-gray-700 rounded p-3">
    <p class="font-semibold mb-2">Available Placeholders (click to insert):</p>
    <div class="flex flex-wrap gap-2">
      <?php
      $defaultPlaceholders = [
        '{name}' => "Participant’s Name",
        '{event}' => "Event Title",
        '{location}' => "Event Location",
        '{date}' => "Event Date",
        '{prize}' => "Ticket Price",
        '{id}' => "Participant ID"
      ];
      foreach ($defaultPlaceholders as $ph => $desc): ?>
        <button 
          type="button" 
          onclick="insertPlaceholder('<?= $ph ?>')" 
          class="px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 text-sm"
          title="<?= $desc ?>"
        ><?= $ph ?></button>
      <?php endforeach; ?>

      <?php if (!empty($customFields)): ?>
        <?php foreach ($customFields as $field): ?>
          <button 
            type="button" 
            onclick="insertPlaceholder('{<?= $field ?>}')" 
            class="px-2 py-1 rounded bg-purple-700 hover:bg-purple-600 text-sm"
            title="Custom Field"
          >{<?= htmlspecialchars($field) ?>}</button>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <button 
    type="submit" 
    class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold transition"
  >
    Save Email
  </button>
</form>

<script>
function insertPlaceholder(ph) {
  const textarea = document.getElementById('emailmessage');
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = textarea.value;
  textarea.value = text.substring(0, start) + ph + text.substring(end);
  textarea.focus();
  textarea.selectionStart = textarea.selectionEnd = start + ph.length;
}

document.getElementById('emailForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  const res = await fetch("", {
    method: "POST",
    body: formData
  });
  const data = await res.json();

  const box = document.getElementById('responseBox');
  if (data.status === 'success') {
    box.innerHTML = `<div class="mb-4 p-3 rounded bg-green-700">${data.message}</div>`;
  } else {
    box.innerHTML = `<div class="mb-4 p-3 rounded bg-red-700">${data.message}</div>`;
  }
});
</script>

</main>

<script>
  lucide.createIcons();

  const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("toggleSidebar");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("-translate-x-full");
    });
  }

  document.getElementById('eventsDropdownBtn').addEventListener('click', () => {
    document.getElementById('eventsDropdown').classList.toggle('hidden');
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
