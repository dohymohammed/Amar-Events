<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("Event ID missing.");
}

 $eventId = intval($_GET['id']);
$user_id = $_SESSION['user']['id'];

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("
    SELECT e.* 
    FROM events e
    INNER JOIN organization o ON e.organization = o.id
    WHERE e.id = ? AND o.authorid = ?
");
$stmt->execute([ $eventId, $user_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event not found or you do not have permission.");
}

$social_medias = [
    ['title'=>'Behance','iconclass'=>'behance'],
    ['title'=>'WhatsApp','iconclass'=>'whatsapp'],
    ['title'=>'X','iconclass'=>'x'],
    ['title'=>'Telegram','iconclass'=>'telegram'],
    ['title'=>'Messenger','iconclass'=>'messenger'],
    ['title'=>'Codepen','iconclass'=>'codepen'],
    ['title'=>'DeviantART','iconclass'=>'deviantart'],
    ['title'=>'Digg','iconclass'=>'digg'],
    ['title'=>'Dribbble','iconclass'=>'dribbble'],
    ['title'=>'Dropbox','iconclass'=>'dropbox'],
    ['title'=>'Facebook','iconclass'=>'facebook-f'],
    ['title'=>'Flickr','iconclass'=>'flickr'],
    ['title'=>'Foursquare','iconclass'=>'foursquare'],
    ['title'=>'Google+','iconclass'=>'google-plus'],
    ['title'=>'GitHub','iconclass'=>'github'],
    ['title'=>'Instagram','iconclass'=>'instagram'],
    ['title'=>'LinkedIn','iconclass'=>'linkedin'],
    ['title'=>'Medium','iconclass'=>'medium'],
    ['title'=>'Pinterest','iconclass'=>'pinterest-p'],
    ['title'=>'Pocket','iconclass'=>'get-pocket'],
    ['title'=>'Reddit','iconclass'=>'reddit-alien'],
    ['title'=>'Skype','iconclass'=>'skype'],
    ['title'=>'SlideShare','iconclass'=>'slideshare'],
    ['title'=>'Snapchat','iconclass'=>'snapchat-ghost'],
    ['title'=>'SoundCloud','iconclass'=>'soundcloud'],
    ['title'=>'Spotify','iconclass'=>'spotify'],
    ['title'=>'StumbleUpon','iconclass'=>'stumbleupon'],
    ['title'=>'Tumblr','iconclass'=>'tumblr'],
    ['title'=>'Twitch','iconclass'=>'twitch'],
    ['title'=>'Twitter','iconclass'=>'twitter'],
    ['title'=>'Vimeo','iconclass'=>'vimeo'],
    ['title'=>'Vine','iconclass'=>'vine'],
    ['title'=>'VK','iconclass'=>'vk'],
    ['title'=>'Website','iconclass'=>'website'],
    ['title'=>'Yelp','iconclass'=>'yelp'],
    ['title'=>'YouTube','iconclass'=>'youtube'],
];

$socials_db = [];
if (!empty($event['socials'])) {
    $socials_db = json_decode($event['socials'], true);
}

$final_socials = [];
foreach ($social_medias as $sm) {
    $found = false;
    foreach ($socials_db as $s) {
        if ($s['type'] === $sm['iconclass']) {
            $final_socials[] = ['type'=>$sm['iconclass'],'title'=>$sm['title'],'url'=>$s['url']];
            $found = true;
            break;
        }
    }
    if (!$found) $final_socials[] = ['type'=>$sm['iconclass'],'title'=>$sm['title'],'url'=>''];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $socials_input = $_POST['socials'] ?? [];
    $socials_array = [];

    foreach ($social_medias as $sm) {
        $iconclass = $sm['iconclass'];
        $url = trim($socials_input[$iconclass]['url'] ?? '');
        $toggle = $socials_input[$iconclass]['toggle'] ?? '';

        if (!empty($toggle) && $url !== '') {
            $socials_array[] = ['type' => $iconclass, 'url' => $url];
        }
    }

    $stmt = $pdo->prepare("UPDATE events SET socials = ? WHERE id = ?");
    $stmt->execute([json_encode($socials_array),  $eventId]);

    echo json_encode(['success' => true, 'message' => "Social links updated successfully."]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Social Links - Amar Events</title>
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
    <h1 class="text-3xl font-bold mb-6">Social Links Configuration</h1>

    <div id="msg" class="mb-6"></div>

    <form id="socialsForm" class="space-y-6 bg-[#1e1e2f] p-6 rounded-xl shadow">
        <?php foreach ($final_socials as $social): 
            $type = $social['type'];
            $title = $social['title'];
            $url = $social['url'] ?? '';
            $checked = !empty($url);

            // Placeholder
            if($type === 'whatsapp') $placeholder = '+1555123456';
            elseif($type === 'skype') $placeholder = 'SkypeUsername';
            elseif($type === 'website') $placeholder = 'https://example.com';
            else $placeholder = 'https://'. $type .'.com/yourpage';
        ?>
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <label class="font-semibold capitalize"><?= htmlspecialchars($title) ?> <?= $type==='whatsapp'?'Number':'URL' ?></label>
                <label class="relative inline-block w-12 h-6 cursor-pointer">
                    <input type="checkbox" name="socials[<?= htmlspecialchars($type) ?>][toggle]" class="sr-only peer" <?= $checked ? 'checked' : '' ?>>
                    <span class="absolute inset-0 bg-gray-700 rounded-full transition-colors duration-200 peer-checked:bg-purple-500"></span>
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-md border-4 border-white transition-transform duration-200 peer-checked:translate-x-6"></span>
                </label>
            </div>
            <div class="overflow-hidden transition-all duration-300 max-h-0 <?= $checked ? 'max-h-20 mt-2' : '' ?>">
                <input type="<?= $type==='whatsapp'?'tel':'url' ?>" name="socials[<?= htmlspecialchars($type) ?>][url]" 
                       placeholder="<?= htmlspecialchars($placeholder) ?>"
                       value="<?= htmlspecialchars($url) ?>" 
                       class="w-full p-3 rounded bg-[#2a2a3d] border border-gray-600 focus:border-blue-600 outline-none text-white" />
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">Save</button>
        <p class="mt-6 text-center text-gray-400 italic">Setup your social links above</p>
    </form>
</main>

<script>
lucide.createIcons();

const sidebar = document.getElementById("sidebar");
document.getElementById("toggleSidebar")?.addEventListener("click", () => {
    sidebar.classList.toggle("-translate-x-full");
});

document.getElementById('paymentDropdownBtn')?.addEventListener('click', () => {
    document.getElementById('paymentDropdown').classList.toggle('hidden');
});

document.querySelectorAll('input[type="checkbox"][name^="socials"]').forEach(input => {
    const wrapper = input.closest('div').nextElementSibling;
    input.addEventListener('change', () => {
        if(input.checked){
            wrapper.classList.add('max-h-20','mt-2');
        } else {
            wrapper.classList.remove('max-h-20','mt-2');
            wrapper.querySelector('input').value = '';
        }
    });
});

// AJAX form submit
document.getElementById('socialsForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(res => res.json())
    .then(data => {
        const msg = document.getElementById('msg');
        if(data.success){
            msg.innerHTML = `<div class="bg-green-700 text-green-100 rounded p-4">${data.message}</div>`;
        } else {
            msg.innerHTML = `<div class="bg-red-700 text-red-100 rounded p-4"><ul class="list-disc pl-5">${data.errors.map(e=>' <li>'+e+'</li>').join('')}</ul></div>`;
        }
    });
});
</script>
</body>
</html>
