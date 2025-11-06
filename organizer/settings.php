<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM organization WHERE authorid = ?");
$stmt->execute([$user_id]);
$organization = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$organization) die("Organization not found for current user.");

$org_id = $organization['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = [];

    if (isset($_POST['rename_organization'])) {
        $newName = trim($_POST['organization_name'] ?? '');
        if ($newName === '') $response['error'] = "Organization name cannot be empty.";
        elseif (strlen($newName) > 255) $response['error'] = "Organization name is too long.";
        else {
            $stmt = $pdo->prepare("UPDATE organization SET name = ? WHERE id = ?");
            $stmt->execute([$newName, $org_id]);
            $organization['name'] = $newName;
            $response['success'] = "Organization name updated successfully.";
        }
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['upload_pfp'])) {
        if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['pfp']['tmp_name'];
            $filename = basename($_FILES['pfp']['name']);
            $cfile = new CURLFile($tmp, mime_content_type($tmp), $filename);

            $apiKey = 'api_key_imagekit'; // <--- uh key here
            $ch = curl_init("https://api.imghippo.com/v1/upload?api_key={$apiKey}");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);

            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                $response['error'] = curl_error($ch);
                echo json_encode($response);
                exit;
            }
            curl_close($ch);

            $resData = json_decode($res, true);
            if ($resData && isset($resData['data']['view_url'])) {
                $pfpUrl = $resData['data']['view_url'];
                $stmt = $pdo->prepare("UPDATE organization SET pfp = ? WHERE id = ?");
                $stmt->execute([$pfpUrl, $org_id]);
                $organization['pfp'] = $pfpUrl;
                $response['success'] = "Profile picture updated successfully.";
                $response['pfp'] = $pfpUrl;
            } else {
                $response['error'] = $resData['message'] ?? "Failed to upload profile picture.";
            }
        } else {
            $response['error'] = "No image uploaded or upload error.";
        }
        echo json_encode($response);
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Configure Organization - Amar Events</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assests/navbar.css">
<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">
<?php require_once __DIR__ . '/../config/navbar.php'; ?>
<main class="md:ml-72 md:mb-16 mb-0 p-6 w-full max-w-3xl mx-auto">
<h1 class="text-2xl font-bold mb-6">Configure Organization</h1>

<div id="messages"></div>

<form id="pfpForm" enctype="multipart/form-data" class="space-y-4 mb-8">
<label class="block text-sm font-semibold mb-1">Profile Picture</label>
<div class="flex items-center space-x-4">
<img id="pfpPreview" src="<?= htmlspecialchars($organization['pfp'] ?? 'https://via.placeholder.com/80') ?>" alt="Profile Picture" class="w-20 h-20 rounded-full object-cover border border-gray-700">
<div id="dropArea" class="w-32 h-20 flex items-center justify-center bg-[#1e1e2f] border border-gray-700 rounded cursor-pointer text-gray-400 text-sm">Drag & Drop or Click</div>
<input type="file" name="pfp" accept="image/*" class="hidden" id="pfpInput">
</div>
<button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">Upload</button>
<div id="pfpLoader" class="hidden text-sm text-gray-400 mt-2">Uploading...</div>
</form>

<form id="nameForm" class="space-y-4 mb-8">
<label class="block text-sm font-semibold mb-1">Organization Name</label>
<input type="text" name="organization_name" value="<?= htmlspecialchars($organization['name']) ?>" class="w-full bg-[#1e1e2f] border border-gray-700 p-3 rounded text-white" required />
<button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">Save Changes</button>
</form>

<div class="mb-8">
<h2 class="text-xl font-bold mb-2">Current Plan</h2>
<p class="bg-[#1e1e2f] border border-gray-700 p-3 rounded">Free</p>
</div>
</main>

<footer class="bg-[#1e1e2f] text-center text-sm text-gray-400 py-4 mt-auto">
All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.
</footer>

<script>
if (window.history.replaceState) window.history.replaceState(null,null,window.location.href);
lucide.createIcons();

function showMessage(msg,type='success'){
const container=document.getElementById('messages');
container.innerHTML=`<div class="mb-4 p-4 ${type==='success'?'bg-green-600':'bg-red-600'} text-white rounded">${msg}</div>`;
}

const dropArea=document.getElementById('dropArea');
const pfpInput=document.getElementById('pfpInput');
dropArea.addEventListener('click',()=>pfpInput.click());
dropArea.addEventListener('dragover',e=>{e.preventDefault(); dropArea.classList.add('bg-gray-700');});
dropArea.addEventListener('dragleave',e=>{e.preventDefault(); dropArea.classList.remove('bg-gray-700');});
dropArea.addEventListener('drop',e=>{
e.preventDefault();
dropArea.classList.remove('bg-gray-700');
if(e.dataTransfer.files.length) pfpInput.files=e.dataTransfer.files;
});

document.getElementById('pfpForm').addEventListener('submit',function(e){
e.preventDefault();
const loader=document.getElementById('pfpLoader');
loader.classList.remove('hidden');
const formData=new FormData(this);
formData.append('upload_pfp',1);
fetch('',{method:'POST',body:formData})
.then(res=>res.json())
.then(data=>{
loader.classList.add('hidden');
if(data.success){
showMessage(data.success,'success');
document.getElementById('pfpPreview').src=data.pfp;
} else if(data.error) showMessage(data.error,'error');
}).catch(()=>{loader.classList.add('hidden'); showMessage('Upload failed.','error');});
});

document.getElementById('nameForm').addEventListener('submit',function(e){
e.preventDefault();
const formData=new FormData(this);
formData.append('rename_organization',1);
fetch('',{method:'POST',body:formData})
.then(res=>res.json())
.then(data=>{
if(data.success) showMessage(data.success,'success');
else if(data.error) showMessage(data.error,'error');
}).catch(()=>showMessage('Update failed.','error'));
});
</script>
</body>
</html>
