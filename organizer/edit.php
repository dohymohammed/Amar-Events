<?php
session_start();
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Dhaka');
if (!isset($_SESSION['user'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$organizerId = $_SESSION['user']['id'];
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    echo "Event ID missing!";
    exit;
}

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

$privateKey = "imagekit_private_key";
$folder = "folder_url";

function uploadToImageKit($fileTmp, $fileName, $oldUrl = null) {
    global $privateKey, $folder;

    $base64 = base64_encode(file_get_contents($fileTmp));
    $data = [
        "file" => "data:image/jpeg;base64," . $base64,
        "fileName" => uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName),
        "folder" => $folder,
    ];

    $ch = curl_init("https://upload.imagekit.io/api/v1/files/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $privateKey . ":");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['url']) && $oldUrl) {
        deleteFromImageKit($oldUrl);
    }

    return $result['url'] ?? null;
}

function deleteFromImageKit($fileUrl) {
    global $privateKey;

    $path = parse_url($fileUrl, PHP_URL_PATH); 

    $fileName = basename($path);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.imagekit.io/v1/files?name=$fileName",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $privateKey . ":"
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    $files = json_decode($response, true);
    if (!empty($files[0]['fileId'])) {
        $fileId = $files[0]['fileId'];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.imagekit.io/v1/files/$fileId",
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $privateKey . ":"
        ]);
        curl_exec($curl);
        curl_close($curl);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['delete_event'])) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organization IN (SELECT id FROM organization WHERE authorid = ?)");
        $stmt->execute([$eventId, $organizerId]);
        echo json_encode(["success" => true, "deleted" => true, "redirect" => "/organizer/"]);
        exit;
    }

    $fieldsToUpdate = [];
    $params = [];

    $allowedFields = ['name', 'description', 'category', 'location', 'location_link', 'totaltickets', 'prize', 'date', 'deadline'];

    


foreach ($allowedFields as $field) {
    if (!isset($_POST[$field])) continue;

    $newValue = $_POST[$field];

if ($field === 'deadline' && !empty($newValue)) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newValue); // parse datetime-local
    $newValue = $dt->format('Y-m-d H:i:s'); // store in DB as BDT
}


    if ($newValue !== $event[$field]) {
        $fieldsToUpdate[] = "$field = ?";
        $params[] = $newValue;
    }
}

    if (!empty($_FILES['logo']['tmp_name'])) {
        $newLogoUrl = uploadToImageKit($_FILES['logo']['tmp_name'], $_FILES['logo']['name'], $event['logo']);
        if ($newLogoUrl) {
            $fieldsToUpdate[] = "logo = ?";
            $params[] = $newLogoUrl;
        }
    }

    if (!empty($_FILES['banner']['tmp_name'])) {
        $newBannerUrl = uploadToImageKit($_FILES['banner']['tmp_name'], $_FILES['banner']['name'], $event['banner']);
        if ($newBannerUrl) {
            $fieldsToUpdate[] = "banner = ?";
            $params[] = $newBannerUrl;
        }
    }

    if (!empty($fieldsToUpdate)) {
        $params[] = $eventId;
        $sql = "UPDATE events SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true, "message" => "Event updated successfully!", "logo" => $newLogoUrl ?? null, "banner" => $newBannerUrl ?? null]);
        exit;
    } else {
        echo json_encode(["success" => false, "error" => "No changes detected."]);
        exit;
    }
}

$categories = ['Education', 'Sports', 'Gaming'];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Edit Event - Amar Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/assests/navbar.css">
  <style>
    .category-btn.selected { background-color: #2563eb; color: white; }
  </style>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">

<?php
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>



  <main class="md:ml-64 p-6 flex-grow max-w-7xl mx-auto w-full mb-16 md:mb-0">
    

    
    
    <div id="alertBox" class="hidden mb-4 p-3 rounded text-white"></div>

    <div class="mb-4">
      <h1 class="text-2xl font-bold mb-2 text-white">Edit Event: <?= htmlspecialchars($event['name']) ?></h1>


      </div>
    </div>

    <form id="eventForm" method="POST" enctype="multipart/form-data" class="bg-[#1e1e2f] p-6 rounded-xl shadow-md space-y-6 text-white max-w-3xl">

      <div>
        <label class="block font-medium mb-1">Event Title</label>
        <input type="text" name="name" value="<?= htmlspecialchars($event['name']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" required />
      </div>

      <div>
        <label class="block font-medium mb-1">Category</label>
        <div class="flex space-x-2">
          <?php foreach ($categories as $cat): ?>
            <button type="button" class="category-btn px-4 py-2 rounded border border-gray-600 <?= $event['category'] === $cat ? 'selected' : '' ?>" onclick="selectCategory('<?= $cat ?>')"><?= $cat ?></button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" id="categoryInput" name="category" value="<?= htmlspecialchars($event['category']) ?>" />
      </div>

      <div>
        <label class="block font-medium mb-1">Description</label>
        <textarea name="description" rows="6" class="w-full border border-gray-600 bg-[#12121c] p-3 rounded text-white resize-y"><?= htmlspecialchars($event['description']) ?></textarea>
      </div>

      <div>
        <label class="block font-medium mb-1">Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
        <p class="text-sm text-gray-400 mt-1">Automaticly fetch your location from google maps </p>
      </div>

      <div>
        <label class="block font-medium mb-1">Location Link (optional)</label>
        <input type="text" name="location_link" value="<?= htmlspecialchars($event['location_link']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

      <div>
        <label class="block font-medium mb-1">Total Tickets</label>
        <input type="number" name="totaltickets" value="<?= htmlspecialchars($event['totaltickets']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

      <div>
        <label class="block font-medium mb-1">Ticket Price</label>
        <input type="number" name="prize" value="<?= htmlspecialchars($event['prize']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

      <div>
        <label class="block font-medium mb-1">Event Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($event['date']) ?>" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

     <div>
  <label class="block font-medium mb-1 text-gray-200">Registration Deadline</label>
  
<?php

$deadline = '';
if (!empty($event['deadline'])) {
    $dt = new DateTime($event['deadline'], new DateTimeZone('Asia/Dhaka'));
    $deadline = $dt->format('Y-m-d\TH:i');
}


?>
<input
  type="datetime-local"
  name="deadline"
  value="<?= htmlspecialchars($deadline) ?>"
  class="w-full border border-gray-600 bg-[#12121c] p-2 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
/>
<p class="text-sm text-gray-400 mt-1">Deadline will adjust to your local timezone </p>

</div>

  


      <div>
        <label class="block font-medium mb-1">Current Logo</label>
        <?php if ($event['logo']): ?>
          <img id="logoPreview" src="<?= htmlspecialchars($event['logo']) ?>" alt="Logo" class="h-16 mb-2 rounded" />
        <?php else: ?>
          <p class="text-gray-400 mb-2">No logo uploaded</p>
        <?php endif; ?>
        <input type="file" name="logo" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

      <div>
        <label class="block font-medium mb-1">Current Banner</label>
        <?php if ($event['banner']): ?>
          <img id="bannerPreview" src="<?= htmlspecialchars($event['banner']) ?>" alt="Banner" class="h-24 mb-2 rounded" />
        <?php else: ?>
          <p class="text-gray-400 mb-2">No banner uploaded</p>
        <?php endif; ?>
        <input type="file" name="banner" class="w-full border border-gray-600 bg-[#12121c] p-2 rounded text-white" />
      </div>

      <div class="flex items-center">
        <button type="submit" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded font-semibold transition">
          Save Changes
        </button>

        <button 
          type="button" 
          id="deleteBtn" 
          class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded font-semibold transition ml-4"
        >
          Delete Event
        </button>
      </div>
    </form>
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

  const categoryInput = document.getElementById('categoryInput');
  const categoryButtons = document.querySelectorAll('.category-btn');

  function selectCategory(cat) {
    categoryInput.value = cat;
    categoryButtons.forEach(btn => {
      if (btn.textContent === cat) {
        btn.classList.add('selected');
      } else {
        btn.classList.remove('selected');
      }
    });
  }
  window.selectCategory = selectCategory;

  const form = document.getElementById('eventForm');

  const alertBox = document.createElement('div');
  alertBox.className = 'hidden mb-4 p-3 rounded text-white';
  form.parentNode.insertBefore(alertBox, form);

  function showAlert(msg, ok = true) {
    alertBox.textContent = msg;
    alertBox.className = (ok ? 'bg-green-600' : 'bg-red-600') + ' mb-4 p-3 rounded text-white';
    alertBox.classList.remove('hidden');
    setTimeout(() => alertBox.classList.add('hidden'), 5000); // auto-hide after 5s
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    try {
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showAlert(data.message || 'Saved successfully!', true);
        if (data.logo) {
          const lp = document.getElementById('logoPreview');
          if (lp) lp.src = data.logo;
        }
        if (data.banner) {
          const bp = document.getElementById('bannerPreview');
          if (bp) bp.src = data.banner;
        }
      } else {
        showAlert(data.error || 'Something went wrong', false);
      }
    } catch (err) {
      showAlert('Request failed', false);
    }
  });

  document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (!confirm('Are you sure you want to DELETE this event? This action cannot be undone.')) return;
    const fd = new FormData();
    fd.append('delete_event', '1');
    try {
      const res = await fetch(window.location.href, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success && data.deleted) {
        window.location.href = data.redirect || '/organizer/';
      } else {
        showAlert(data.error || 'Failed to delete', false);
      }
    } catch (e) {
      showAlert('Request failed', false);
    }
  });



</script>

</body>
</html>
