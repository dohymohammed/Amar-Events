<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$imageKitPrivateKey = 'imagekit_private_key';
$imageKitFolderPath = 'folder_path';

if (!isset($_GET['event'])) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.date, e.banner
        FROM events e
        INNER JOIN organization o ON o.id = e.organization
        WHERE o.authorid = ?
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
   <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>My Event Galleries</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assests/navbar.css">

</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex">
<?php
require_once __DIR__ . '/../config/navbar.php';
?>
  <main class="flex-1 min-h-screen pt-20 md:pt-10 px-6 md:ml-72">
    <h1 class="text-3xl font-bold mb-6">My Events</h1>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($events as $ev): ?>
        <div class="bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition">
          <?php if (!empty($ev['banner'])): ?>
            <img src="<?= htmlspecialchars($ev['banner']) ?>" alt="Event Banner" class="w-full h-40 object-cover" />
          <?php endif; ?>
          <div class="p-4">
            <h2 class="text-xl font-semibold"><?= htmlspecialchars($ev['name']) ?></h2>
            <p class="text-gray-400 text-sm mb-3"><?= htmlspecialchars($ev['date']) ?></p>
            <a href="gallery.php?event=<?= $ev['id'] ?>" class="inline-block px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-white text-sm">Manage Gallery</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <script>

    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });

    const paymentDropdownBtn = document.getElementById('paymentDropdownBtn');
    const paymentDropdown = document.getElementById('paymentDropdown');

    paymentDropdownBtn.addEventListener('click', () => {
      paymentDropdown.classList.toggle('hidden');
    });

   lucide.createIcons();

  </script>
</body>
</html>

    <?php
    exit;
}

$event_id = intval($_GET['event']);
$stmt = $pdo->prepare("
    SELECT e.*, o.authorid
    FROM events e
    INNER JOIN organization o ON o.id = e.organization
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event || $event['authorid'] != $user_id) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    for ($i = 1; $i <= 6; $i++) {
        $fieldName = "img$i";
        if (isset($_POST["delete_$fieldName"])) {
            $stmt = $pdo->prepare("SELECT $fieldName FROM gallery WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $oldImg = $stmt->fetchColumn();
            if ($oldImg) {
                $fileId = getImageKitFileId($oldImg, $imageKitPrivateKey);
                if ($fileId) deleteImageKitFile($fileId, $imageKitPrivateKey);
                $pdo->prepare("UPDATE gallery SET $fieldName = NULL WHERE event_id = ?")->execute([$event_id]);
            }
        }
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $stmt = $pdo->prepare("SELECT $fieldName FROM gallery WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $oldImg = $stmt->fetchColumn();
            if ($oldImg) {
                $fileId = getImageKitFileId($oldImg, $imageKitPrivateKey);
                if ($fileId) deleteImageKitFile($fileId, $imageKitPrivateKey);
            }
            $fileTmpPath = $_FILES[$fieldName]['tmp_name'];
            $fileName = $_FILES[$fieldName]['name'];
            $data = [
                'file' => new CURLFile($fileTmpPath),
                'fileName' => $fileName,
                'folder' => $imageKitFolderPath . $event_id
            ];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://upload.imagekit.io/api/v1/files/upload',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $imageKitPrivateKey . ':',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $uploadResult = json_decode($response, true);
            if (!empty($uploadResult['url'])) {
                $pdo->prepare("UPDATE gallery SET $fieldName = ? WHERE event_id = ?")
                    ->execute([$uploadResult['url'], $event_id]);
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM gallery WHERE event_id = ?");
$stmt->execute([$event_id]);
$gallery = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Manage Gallery - <?= htmlspecialchars($event['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assests/navbar.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex">

<?php
require_once __DIR__ . '/../config/navbar.php';
?>

  <main class="flex-1 min-h-screen pt-20 md:pt-10 px-6 md:ml-72 mb-24 md:mb-0">
    <h1 class="text-3xl font-bold mb-6">Manage Gallery for <?= htmlspecialchars($event['name']) ?></h1>
    <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
        <?php for ($i = 1; $i <= 6; $i++): $fieldName = "img$i"; ?>
            <div class="bg-gray-800 p-4 rounded-xl shadow-md">
                <h3 class="font-semibold mb-3">Image <?= $i ?></h3>
                <?php if (!empty($gallery[$fieldName])): ?>
                    <img src="<?= htmlspecialchars($gallery[$fieldName]) ?>" class="rounded-lg mb-3 max-h-48 object-cover">
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($gallery[$fieldName]) ?>" target="_blank" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 rounded text-white text-sm">View</a>
                        <button type="submit" name="delete_<?= $fieldName ?>" class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-white text-sm">Delete</button>
                    </div>
                <?php endif; ?>
                <input type="file" name="<?= $fieldName ?>" class="mt-3 w-full text-sm text-gray-300 bg-gray-700 rounded px-2 py-1" />
            </div>
        <?php endfor; ?>
        <div class="col-span-full">
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-semibold">Save Changes</button>
        </div>
    </form>
  </main>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
  <script>

    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');

    toggleBtn.addEventListener('click', () => {
      if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
      } else {
        sidebar.classList.add('-translate-x-full');
      }
    });

    const paymentDropdownBtn = document.getElementById('paymentDropdownBtn');
    const paymentDropdown = document.getElementById('paymentDropdown');

    paymentDropdownBtn.addEventListener('click', () => {
      paymentDropdown.classList.toggle('hidden');
    });

  lucide.createIcons();
  </script>
</body>
</html>

<?php
function getImageKitFileId($imageUrl, $privateKey) {
    $fileName = basename($imageUrl);
    $searchUrl = "https://api.imagekit.io/v1/files?name=" . urlencode($fileName);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $searchUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $privateKey . ':',
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response, true);
    return isset($data[0]['fileId']) ? $data[0]['fileId'] : null;
}

function deleteImageKitFile($fileId, $privateKey) {
    $deleteUrl = "https://api.imagekit.io/v1/files/" . $fileId;
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $deleteUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_USERPWD => $privateKey . ':',
    ]);
    curl_exec($curl);
    curl_close($curl);
}
?>