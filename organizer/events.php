<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$organizerId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM organization WHERE authorid = ?");
$stmt->execute([$organizerId]);
$orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orgIds = array_column($orgs, 'id');

$createError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {

    $name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $banner = '';
if (isset($_FILES['event_banner_upload']) && $_FILES['event_banner_upload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['event_banner_upload']['tmp_name'];
    $fileName = $_FILES['event_banner_upload']['name'];

    $imageKitPrivateKey = 'imagekit_private_key';

    $curl = curl_init();

    $data = [
        'file' => new CURLFile($fileTmpPath),
        'fileName' => $fileName,
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://upload.imagekit.io/api/v1/files/upload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $imageKitPrivateKey . ':',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        $createError = "Image upload error: $err";
    } else {
        $uploadResult = json_decode($response, true);
        if (isset($uploadResult['url'])) {
            $banner = $uploadResult['url']; 

        } else {
            $createError = "Image upload failed. Please try again.";
        }
    }
} else {
    $createError = "Please select a valid banner image.";
}

    $location = trim($_POST['event_location'] ?? '');
    $location_link = trim($_POST['event_location_link'] ?? '');
    $totaltickets = intval($_POST['event_tickets'] ?? 0);
    $date = trim($_POST['event_date'] ?? '');

    if (!$name || !$location || $totaltickets <= 0 || !$date) {
        $createError = "Please fill all required fields, tickets > 0 and date.";
    } elseif (!$orgIds) {
        $createError = "You do not have any organizations to create events.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $createError = "Date must be in YYYY-MM-DD format and valid.";
    } else {

        $totalSlots = 0;
        foreach ($orgs as $org) {
            $totalSlots += (int)$org['eventcount'];
        }

        if ($totalSlots <= 0) {
            $createError = "Sorry, no event slots available.";
        } else {

            $orgIdForEvent = null;
            foreach ($orgs as $org) {
                if ((int)$org['eventcount'] > 0) {
                    $orgIdForEvent = $org['id'];
                    break;
                }
            }

if (!$orgIdForEvent) {
    $createError = "Sorry, no event slots available.";
} else {

    $insert = $pdo->prepare("INSERT INTO events (name, description, banner, location, location_link, totaltickets, organization, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([$name, $description, $banner, $location, $location_link, $totaltickets, $orgIdForEvent, $date]);

    $eventId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO gallery (event_id, organization_id) VALUES (?, ?)")
        ->execute([$eventId, $orgIdForEvent]);

    $updateSlots = $pdo->prepare("UPDATE organization SET eventcount = eventcount - 1 WHERE id = ?");
    $updateSlots->execute([$orgIdForEvent]);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

        }
    }
}

if (!$orgIds) {
    $events = [];
} else {
    $inQuery = implode(',', array_fill(0, count($orgIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM events WHERE organization IN ($inQuery) ORDER BY id DESC");
    $stmt->execute($orgIds);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function short_desc($text, $max = 120) {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max) . '...';
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Organizer Events - Amar Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">

<?php
require_once __DIR__ . '/../config/navbar.php';
?>

  <main class="md:ml-72 p-6 flex-grow max-w-7xl mx-auto w-full mb-16 md:mb-0">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Your Events</h1>
      <button
        id="openCreateModal"
        class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded font-semibold transition"
      >
        Create Event
      </button>
    </div>

    <?php if ($createError): ?>
      <p class="mb-4 text-red-500 font-semibold"><?= htmlspecialchars($createError) ?></p>
    <?php endif; ?>

    <?php if (empty($events)): ?>
      <p class="text-gray-400">You have not created any events yet.</p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($events as $event): ?>
<div class="bg-[#1e1e2f] rounded-xl shadow overflow-hidden flex flex-col md:flex-row">
  <div class="md:w-80 md:h-auto h-48 flex-shrink-0">
    <img
      src="<?= htmlspecialchars($event['banner'] ?: 'https://ik.imagekit.io/amarworld/default_event_banner.jpg') ?>"
      alt="Event Banner"
      class="w-full h-full object-cover"
    />
  </div>
  <div class="p-6 flex flex-col justify-between flex-grow">
    <div>
      <h3 class="text-2xl font-bold mb-2"><?= htmlspecialchars($event['name']) ?></h3>
      <p class="text-gray-400 mb-1">Location: <?= htmlspecialchars($event['location'] ?? '') ?></p>
      <p class="text-gray-400 mb-1">Date: <?= htmlspecialchars(date('d M Y', strtotime($event['date'] ?? ''))) ?></p>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
      <a href="/event?id=<?= $event['id'] ?>" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">View</a>
      <a href="/organizer/participants?id=<?= $event['id'] ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded font-semibold transition">Manage</a>
    </div>
  </div>
</div>

        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <div
    id="createModal"
    class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-[1001] hidden"
  >
    <div class="bg-[#1e1e2f] rounded-xl max-w-lg w-full p-6 relative">
      <button
        id="closeCreateModal"
        class="absolute top-3 right-3 text-gray-400 hover:text-white"
        aria-label="Close modal"
      >
        <i data-lucide="x" class="w-6 h-6"></i>
      </button>
      <h2 class="text-2xl font-bold mb-4">Create New Event</h2>
      <form method="POST" class="space-y-4" novalidate enctype="multipart/form-data">
        <input type="hidden" name="create_event" value="1" />
        <div>
          <label class="block mb-1 font-semibold" for="event_name">Event Name *</label>
          <input
            id="event_name"
            name="event_name"
            type="text"
            required
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
            value="<?= htmlspecialchars($_POST['event_name'] ?? '') ?>"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold" for="event_description">Event Description</label>
          <textarea
            id="event_description"
            name="event_description"
            rows="3"
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
          ><?= htmlspecialchars($_POST['event_description'] ?? '') ?></textarea>
        </div>

<input
  id="event_banner"
  name="event_banner_upload"
  type="file"
  accept="image/*"
  class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
  required
/>

        <div>
          <label class="block mb-1 font-semibold" for="event_location">Event Location *</label>
          <input
            id="event_location"
            name="event_location"
            type="text"
            required
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
            value="<?= htmlspecialchars($_POST['event_location'] ?? '') ?>"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold" for="event_location_link">Event Location Link</label>
          <input
            id="event_location_link"
            name="event_location_link"
            type="url"
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
            placeholder="https://maps.google.com/..."
            value="<?= htmlspecialchars($_POST['event_location_link'] ?? '') ?>"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold" for="event_tickets">Total Tickets *</label>
          <input
            id="event_tickets"
            name="event_tickets"
            type="number"
            min="1"
            required
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
            value="<?= htmlspecialchars($_POST['event_tickets'] ?? '') ?>"
          />
        </div>

        <div>
          <label class="block mb-1 font-semibold" for="event_date">Event Date *</label>
          <input
            id="event_date"
            name="event_date"
            type="date"
            required
            class="w-full p-2 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none"
            value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>"
          />
        </div>

        <button
          type="submit"
          class="w-full bg-green-600 hover:bg-green-700 py-2 rounded font-semibold transition"
        >
          Create Event
        </button>
      </form>
    </div>
  </div>

  <footer class="bg-[#1e1e2f] text-center text-sm text-gray-400 py-4 mt-auto">
    All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.
  </footer>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
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

  const openModalBtn = document.getElementById('openCreateModal');
  const closeModalBtn = document.getElementById('closeCreateModal');
  const modal = document.getElementById('createModal');

  function showModal() {
    modal.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  openModalBtn.addEventListener('click', showModal);
  closeModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  window.addEventListener('click', e => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });

  <?php if ($createError): ?>
    showModal();
  <?php endif; ?>
</script>

</body>
</html>

