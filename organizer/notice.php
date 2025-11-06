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

if (!isset($_GET['id'])) {
    die("Event ID missing.");
}

$eventId = intval($_GET['id']);
$organizerId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT events.* 
    FROM events 
    INNER JOIN organization ON events.organization = organization.id 
    WHERE events.id = ? AND organization.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Event not found.");

$notices = $event['notice'] ? json_decode($event['notice'], true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'upload' && isset($_FILES['image'])) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $oldUrl = $_POST['old_url'] ?? '';

        if ($oldUrl) {
            $fileId = getImageKitFileId($oldUrl, $imageKitPrivateKey);
            if ($fileId) deleteImageKitFile($fileId, $imageKitPrivateKey);
        }

        $data = [
            'file' => new CURLFile($fileTmpPath),
            'fileName' => $fileName,
            'folder' => $imageKitFolderPath . $eventId
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
        $result = json_decode($response, true);
        echo json_encode(['url' => $result['url'] ?? '']);
        exit;
    }

    if ($action === 'delete-image') {
        $imageUrl = $_POST['imageUrl'] ?? '';
        if ($imageUrl) {
            $fileId = getImageKitFileId($imageUrl, $imageKitPrivateKey);
            if ($fileId) deleteImageKitFile($fileId, $imageKitPrivateKey);
        }
        echo json_encode(['status'=>'ok']);
        exit;
    }

    if ($action === 'save-all') {

        $noticesData = json_decode($_POST['notices'], true);
        $stmt = $pdo->prepare("UPDATE events SET notice=? WHERE id=?");
        $stmt->execute([json_encode($noticesData), $eventId]);
        echo json_encode(['status'=>'ok']);
        exit;
    }

    echo json_encode(['status'=>'error']);
    exit;
}

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
    return $data[0]['fileId'] ?? null;
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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Manage Notices - <?= htmlspecialchars($event['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<link rel="stylesheet" href="/assests/navbar.css">
</head>
<body class="bg-[#12121c] text-white min-h-screen flex flex-col">

<?php require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>

  <main class="flex-grow md:ml-72 p-6 container mx-auto max-w-full overflow-auto mb-16 md:mb-0">
<h1 class="text-3xl font-bold mb-6">Manage Notices for <?= htmlspecialchars($event['name']) ?></h1>

<div id="notice-container" class="grid md:grid-cols-2 gap-6">
    <?php foreach ($notices as $index => $notice): ?>
    <div class="bg-gray-800 p-4 rounded-xl shadow-md" data-index="<?= $index ?>">
        <input type="text" class="w-full mb-2 px-2 py-1 rounded bg-gray-700 text-gray-200" placeholder="Notice name" value="<?= htmlspecialchars($notice['name'] ?? '') ?>" />
        <?php if (!empty($notice['image'])): ?>
        <div class="mb-2">
            <img src="<?= htmlspecialchars($notice['image']) ?>" class="rounded-lg max-h-48 object-cover mb-2">
            <button class="delete-image bg-red-600 hover:bg-red-500 px-3 py-1 rounded text-white text-sm">Delete Image</button>
        </div>
        <?php endif; ?>
        <input type="file" class="notice-image-input w-full text-sm text-gray-300 bg-gray-700 rounded px-2 py-1" />
        <div class="progress-bar bg-gray-700 rounded h-2 mt-2 hidden">
            <div class="progress bg-purple-600 h-2 rounded w-0"></div>
        </div>
        <button class="delete-notice mt-2 px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-white text-sm">Delete Notice</button>
    </div>
    <?php endforeach; ?>
</div>

<button id="add-notice" class="mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-500 rounded text-white font-semibold">Add Notice</button>
<button id="save-notices" class="mt-4 ml-2 px-6 py-2 bg-green-600 hover:bg-green-500 rounded text-white font-semibold">Save All Changes</button>
</main>

<script>
function createNoticeCard(notice = {name:'', image:''}) {
    const container = document.getElementById('notice-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'bg-gray-800 p-4 rounded-xl shadow-md';
    div.dataset.index = index;
    div.innerHTML = `
        <input type="text" class="w-full mb-2 px-2 py-1 rounded bg-gray-700 text-gray-200" placeholder="Notice name" value="${notice.name || ''}" />
        ${notice.image ? `<div class="mb-2">
            <img src="${notice.image}" class="rounded-lg max-h-48 object-cover mb-2">
            <button class="delete-image bg-red-600 hover:bg-red-500 px-3 py-1 rounded text-white text-sm">Delete Image</button>
        </div>` : ''}
        <input type="file" class="notice-image-input w-full text-sm text-gray-300 bg-gray-700 rounded px-2 py-1" />
        <div class="progress-bar bg-gray-700 rounded h-2 mt-2 hidden">
            <div class="progress bg-purple-600 h-2 rounded w-0"></div>
        </div>
        <button class="delete-notice mt-2 px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-white text-sm">Delete Notice</button>
    `;
    container.appendChild(div);
}

document.getElementById('add-notice').addEventListener('click', () => createNoticeCard());

document.getElementById('notice-container').addEventListener('click', async function(e){
    const card = e.target.closest('div[data-index]');
    if(!card) return;

    if(e.target.classList.contains('delete-notice')){
        card.remove();
    }
    if(e.target.classList.contains('delete-image')){
        const imgTag = e.target.previousElementSibling;
        const url = imgTag.src;
        try {
            await axios.post('', new URLSearchParams({action:'delete-image', imageUrl:url}));
            imgTag.remove();
            e.target.remove();
        } catch(err){
            alert('Error deleting image.');
        }
    }
});

document.getElementById('save-notices').addEventListener('click', async function(){
    const cards = document.querySelectorAll('#notice-container > div');
    let noticesData = [];
    for(let card of cards){
        const name = card.querySelector('input[type=text]').value;
        const fileInput = card.querySelector('.notice-image-input');
        let image = card.querySelector('img') ? card.querySelector('img').src : '';
        if(fileInput.files.length > 0){
            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('action','upload');
            const progressBar = card.querySelector('.progress-bar');
            const progress = progressBar.querySelector('.progress');
            progressBar.classList.remove('hidden');

            const response = await axios.post('', formData, {
                headers:{'Content-Type':'multipart/form-data'},
                onUploadProgress: function(progressEvent){
                    const percent = Math.round((progressEvent.loaded*100)/progressEvent.total);
                    progress.style.width = percent+'%';
                }
            });
            image = response.data.url;
        }
        noticesData.push({name, image});
    }

    const formDataSave = new FormData();
    formDataSave.append('action', 'save-all');
    formDataSave.append('notices', JSON.stringify(noticesData));

    try{
        await axios.post('', formDataSave);
        alert('Notices saved successfully!');
        window.location.reload();
    }catch(err){
        alert('Error saving notices.');
    }
});
</script>

</body>
</html>