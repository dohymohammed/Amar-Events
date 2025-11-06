<?php
session_start();
require_once __DIR__ . '/config/db.php';


$loggedInUserId = $_SESSION['user']['id'] ?? null;

function nameToSlug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

$event = null;

if (!empty($_GET['slug'])) {
    $slug = $_GET['slug'];
    $stmt = $pdo->prepare("SELECT * FROM events");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($events as $e) {
        if (nameToSlug($e['name']) === $slug) {
            $event = $e;
            break;
        }
    }

    if (!$event) die("Event not found.");

} elseif (!empty($_GET['name'])) {
    $name = $_GET['name'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE name = ?");
    $stmt->execute([$name]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) die("Event not found.");

    $slug = nameToSlug($event['name']);
    header("Location: /event/$slug");
    exit;

} elseif (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) die("Event not found.");

    $slug = nameToSlug($event['name']);
    header("Location: /event/$slug");
    exit;

} else {
    die("No event provided.");
}

$eventId = $event['id'];



$org = null;
if (!empty($event['organization'])) {
    $orgStmt = $pdo->prepare("SELECT name,authorid FROM organization WHERE id = ?");
    $orgStmt->execute([$event['organization']]);
    $org = $orgStmt->fetch(PDO::FETCH_ASSOC);
    $orgAuthorId = $org['authorid'] ?? null;
}

$totalTickets = (int)($event['totaltickets'] ?? 0);
$ticketsStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE eventid = ?");
$ticketsStmt->execute([$eventId]);
$ticketsSold = (int)$ticketsStmt->fetchColumn();
$ticketsRemaining = max($totalTickets - $ticketsSold, 0);

if (!empty($event['deadline'])) {
    $dt = new DateTime($event['deadline'], new DateTimeZone('Asia/Dhaka'));
    $deadline = $dt->format('F j, Y g:i A');
}

$prize = !empty($event['prize']) ? '৳' . $event['prize'] : 'Free';

$nowDt = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$deadlineDt = new DateTime($event['deadline'], new DateTimeZone('Asia/Dhaka'));

$diff = $deadlineDt->getTimestamp() - $nowDt->getTimestamp();
$diff = max($diff, 0);

$years = floor($diff / (365*86400));
$months = floor(($diff % (365*86400)) / (30*86400));
$days = floor(($diff % (30*86400)) / 86400);
$hours = floor(($diff % 86400) / 3600);
$minutes = floor(($diff % 3600) / 60);
$seconds = $diff % 60;

$countdownDisplay = [];
if ($years > 0) $countdownDisplay['year'] = $years;
if ($months > 0) $countdownDisplay['month'] = $months;
if ($days > 0) $countdownDisplay['day'] = $days;
if ($hours > 0) $countdownDisplay['hour'] = $hours;
if ($minutes > 0) $countdownDisplay['minute'] = $minutes;
if ($seconds > 0) $countdownDisplay['second'] = $seconds;

$countdownDisplay = array_slice($countdownDisplay, 0, 2, true);

$countdownJson = json_encode([
    'units' => $countdownDisplay,
    'timestamp' => $deadlineDt->getTimestamp()
]);

$eventDate = !empty($event['date']) ? date("F j, Y", strtotime($event['date'])) : "Not Set";

$galleryStmt = $pdo->prepare("SELECT * FROM gallery WHERE event_id = ?");
$galleryStmt->execute([$eventId]);
$gallery = $galleryStmt->fetch(PDO::FETCH_ASSOC);
$images = [];
if ($gallery) {
    foreach (['img1', 'img2', 'img3', 'img4', 'img5', 'img6'] as $col) {
        if (!empty($gallery[$col])) {
            $images[] = $gallery[$col];
        }
    }
}

$locationQuery = urlencode($event['location'] ?? '');
$embedMapUrl = $locationQuery ? "https://www.google.com/maps?q={$locationQuery}&output=embed" : null;

$tickets_left = $ticketsRemaining;

$deadlineDt = new DateTime($event['deadline'], new DateTimeZone('Asia/Dhaka'));
$registration_end = $deadlineDt->getTimestamp();

$nowDt = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$now = $nowDt->getTimestamp();

if ($tickets_left <= 0) {
    $button_text = "Sold Out";
    $button_class = "bg-gray-500 cursor-not-allowed";
    $button_link = "#";
    $disabled = true;
} elseif ($now > $registration_end) {
    $button_text = "Closed";
    $button_class = "bg-gray-500 cursor-not-allowed";
    $button_link = "#";
    $disabled = true;
} else {
    $button_text = "Register Now";
    $button_class = "bg-purple-600 hover:bg-purple-700";
    $button_link = "/register.php?event=" . $event['id'];
    $disabled = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($event['name'] ?? 'Event') ?></title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<?php include "config/meta.php" ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .blur-bg {
            backdrop-filter: blur(8px);
            background-color: rgba(15, 23, 42, 0.6);
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-sans">

    <a href="/" class="fixed top-4 left-4 z-50 bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-600">← Back</a>


<button id="openShare" class="fixed top-4 right-4 z-50 bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-600 flex items-center gap-2">
    <i class="fas fa-share-alt"></i> Share
</button>

<?php

    $eventLink = "https://amarevents.zone.id/event/" . $slug;
    $encoded   = urlencode($eventLink);
?>
<?php if ($loggedInUserId && $loggedInUserId == $orgAuthorId): ?>
    <a href="/organizer/participants?id=<?= $event['id']; ?>"
       class="fixed bottom-6 right-6 z-50 bg-gray-600 hover:bg-gray-700
              text-white p-5 rounded-full shadow-xl flex items-center gap-2">
        <i class="fas fa-cogs"></i>
    </a>
<?php endif; ?>


<div id="shareModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
  <div class="bg-gray-900 rounded-2xl p-6 w-11/12 max-w-md shadow-2xl text-center relative">
      <button id="closeShare" class="absolute top-3 right-3 text-gray-400 hover:text-white">
          <i class="fas fa-times text-xl"></i>
      </button>
      <h2 class="text-2xl font-bold mb-4">Share This Event</h2>

      <div class="grid grid-cols-3 gap-4 mb-6">

          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encoded ?>"
             target="_blank"
             class="flex flex-col items-center gap-1 p-3 bg-blue-600 rounded-xl hover:scale-105 transition">
              <i class="fab fa-facebook-f text-2xl"></i>
              <span class="text-sm">Facebook</span>
          </a>


          <button onclick="copyLink()"
             class="flex flex-col items-center gap-1 p-3 bg-gradient-to-tr from-sky-500 to-blue-600
                    rounded-xl hover:scale-105 transition">
              <i class="fas fa-copy text-2xl"></i>
              <span class="text-sm">Copy</span>
          </button>


          <a href="https://wa.me/?text=<?= $encoded ?>"
             target="_blank"
             class="flex flex-col items-center gap-1 p-3 bg-green-500 rounded-xl hover:scale-105 transition">
              <i class="fab fa-whatsapp text-2xl"></i>
              <span class="text-sm">WhatsApp</span>
          </a>
      </div>


      <div class="flex flex-col items-center gap-3">
          <img id="qrImage"
               src="https://quickchart.io/qr?text=<?= $encoded ?>&size=200"
               alt="QR Code"
               class="w-40 h-40 rounded-lg shadow-lg bg-white p-1">
 <button id="downloadQR"
        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white">
    Download QR Code
</button>

      </div>

      <p id="copyMsg" class="text-green-400 mt-3 hidden">Link copied!</p>
  </div>
</div>

<script>
const openShare  = document.getElementById('openShare');
const closeShare = document.getElementById('closeShare');
const shareModal = document.getElementById('shareModal');

openShare.onclick  = () => shareModal.classList.remove('hidden');
closeShare.onclick = () => shareModal.classList.add('hidden');

function copyLink(){
    navigator.clipboard.writeText("<?= $eventLink ?>")
        .then(()=> {
            document.getElementById('copyMsg').classList.remove('hidden');
            setTimeout(()=>document.getElementById('copyMsg').classList.add('hidden'),1500);
        });
}

</script>
<script>
const qrUrl = "https://quickchart.io/qr?text=<?= $encoded ?>&size=200";

document.getElementById('downloadQR').addEventListener('click', async () => {
    try {
        const response = await fetch(qrUrl);
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = 'event-qr.png';      

        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch (err) {
        console.error('QR download failed:', err);
        alert('Unable to download QR code right now.');
    }
});
</script>

<?php
$hasLogo = !empty($event['logo']);
$hasName = !empty($event['name']);
$hasOrg = !empty($org['name']);
$hasText = $hasName || $hasOrg;
$showOverlay = $hasLogo || $hasText;
?>

<div class="w-full h-auto relative">
    <img src="<?= htmlspecialchars($event['banner'] ?? '') ?>" alt="Banner"
         class="w-full h-60 md:h-96 object-cover <?= $showOverlay ? 'filter blur-sm' : '' ?>" />

    <?php if ($showOverlay): ?>
        <div class="<?= $hasLogo
            ? 'absolute top-4 left-1/2 transform -translate-x-1/2 text-center'
            : 'absolute inset-0 flex flex-col justify-center items-center text-center px-4' ?>">

            <?php if ($hasLogo): ?>
                <img src="<?= htmlspecialchars($event['logo']) ?>"
                     class="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white object-cover mx-auto mb-3" />
            <?php endif; ?>

            <?php if ($hasName): ?>
                <h1 class="text-2xl md:text-4xl font-bold text-white drop-shadow-md">
                    <?= htmlspecialchars($event['name']) ?>
                </h1>
            <?php endif; ?>

            <?php if ($hasOrg): ?>
                <p class="text-sm md:text-base text-white drop-shadow-md">
                    Organized by <span class="font-semibold"><?= htmlspecialchars($org['name']) ?></span>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 mt-8 text-center">
        <div class="bg-gray-800 rounded-lg p-4">
            <p class="text-lg font-bold"><?= $ticketsRemaining ?></p>
            <p class="text-sm text-gray-400">Tickets Remaining</p>
        </div>

<div class="bg-gray-800 rounded-lg p-4">
    <p id="countdown" class="text-lg font-bold"></p>
    <p class="text-sm text-gray-400">Registration Ends</p>
</div>

<div class="bg-gray-800 rounded-lg p-4">
            <p class="text-lg font-bold"><?= $prize ?></p>
            <p class="text-sm text-gray-400">Ticket Prize</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <p class="text-lg font-bold"><?= $eventDate ?></p>
            <p class="text-sm text-gray-400">Event Date</p>
        </div>
    </div>
<?php
$notice = [];
if (!empty($event['notice'])) {
    $notice = json_decode($event['notice'], true);
}

if (!empty($notice) && is_array($notice)) {
    echo '<div class="flex flex-col items-center p-6 mt-10 gap-8">';
    foreach ($notice as $n) {
        echo '<div class="bg-gradient-to-r from-gray-800 via-gray-900 to-black w-[95%] max-w-3xl xl:max-w-4xl overflow-hidden rounded-2xl shadow-2xl transition-transform transform hover:scale-[1.02] hover:shadow-[0_8px_30px_rgba(0,0,0,0.6)]">';


        if (!empty($n['image'])) {
            echo '<div class="relative w-full flex justify-center bg-black">';
            echo '<img src="' . htmlspecialchars($n['image']) . '" alt="Notice Image" ';
            echo 'class="w-full max-h-[400px] object-contain md:object-cover" />';
            echo '</div>';
        }


        echo '<div class="p-6 text-center space-y-3">';
        if (!empty($n['name'])) {
            echo '<p class="text-gray-100 text-2xl md:text-3xl font-extrabold tracking-tight leading-snug font-sans">';
            echo htmlspecialchars($n['name']);
            echo '</p>';
        }
        if (!empty($n['description'])) {  
            echo '<p class="text-gray-300 text-base md:text-lg leading-relaxed font-light">';
            echo nl2br(htmlspecialchars($n['description']));
            echo '</p>';
        }
        echo '</div>';

        echo '</div>';
    }
    echo '</div>';
}
?>


    <div class="text-center mt-6">
    <a href="<?= $button_link ?>"
       class="<?= $button_class ?> px-6 py-3 rounded-full text-xl font-bold transition"
       <?= $disabled ? 'onclick="return false;"' : '' ?>>
       <?= $button_text ?>
    </a>
</div>

    <div class="p-6 mt-10 bg-gray-800 rounded-xl max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold mb-3">About the Event</h2>
        <div id="desc" class="text-sm md:text-base leading-relaxed max-h-72 overflow-hidden relative transition-all">
            <?= nl2br(htmlspecialchars($event['description'] ?? 'No description available.')) ?>
        </div>
        <button id="toggleBtn" class="mt-3 text-purple-400 underline">Show More</button>
    </div>

<?php

$socials = [];
if (!empty($event['socials'])) {
    $socials = json_decode($event['socials'], true);
}

$social_medias = [
    'behance'=>['icon'=>'fab fa-behance','color'=>'#053eff'],
'whatsapp' => [
    'icon'  => 'fab fa-whatsapp',
    'color' => '#25D366'
],
'x' => [
    'icon'  => 'fab fa-x-twitter',
    'color' => '#000000'
],
'telegram' => [
    'icon'  => 'fab fa-telegram',
    'color' => '#0088cc'
],
'messenger' => [
    'icon'  => 'fab fa-facebook-messenger',
    'color' => '#006AFF'
],

    'codepen'=>['icon'=>'fab fa-codepen','color'=>'#212121'],
    'deviantart'=>['icon'=>'fab fa-deviantart','color'=>'#4a5d4e'],
    'digg'=>['icon'=>'fab fa-digg','color'=>'#005be2'],
    'dribbble'=>['icon'=>'fab fa-dribbble','color'=>'#ea4c89'],
    'dropbox'=>['icon'=>'fab fa-dropbox','color'=>'#007ee5'],
    'facebook-f'=>['icon'=>'fab fa-facebook-f','color'=>'#3b5998'],
    'flickr'=>['icon'=>'fab fa-flickr','color'=>'#f40083'],
    'foursquare'=>['icon'=>'fab fa-foursquare','color'=>'#fc4575'],
    'google-plus'=>['icon'=>'fab fa-google-plus-g','color'=>'#df4a32'],
    'github'=>['icon'=>'fab fa-github','color'=>'#333333'],
    'instagram'=>['icon'=>'fab fa-instagram','color'=>'#C32AA3'],
    'linkedin'=>['icon'=>'fab fa-linkedin-in','color'=>'#007bb6'],
    'medium'=>['icon'=>'fab fa-medium','color'=>'#00ab6c'],
    'pinterest-p'=>['icon'=>'fab fa-pinterest-p','color'=>'#cb2027'],
    'get-pocket'=>['icon'=>'fab fa-get-pocket','color'=>'#ee4056'],
    'reddit-alien'=>['icon'=>'fab fa-reddit-alien','color'=>'#ff5700'],
    'skype'=>['icon'=>'fab fa-skype','color'=>'#00aff0'],
    'slideshare'=>['icon'=>'fab fa-slideshare','color'=>'#f7941e'],
    'snapchat-ghost'=>['icon'=>'fab fa-snapchat-ghost','color'=>'#fffc00'],
    'soundcloud'=>['icon'=>'fab fa-soundcloud','color'=>'#ff5500'],
    'spotify'=>['icon'=>'fab fa-spotify','color'=>'#1ed760'],
    'stumbleupon'=>['icon'=>'fab fa-stumbleupon','color'=>'#eb4924'],
    'tumblr'=>['icon'=>'fab fa-tumblr','color'=>'#35465d'],
    'twitch'=>['icon'=>'fab fa-twitch','color'=>'#6441A4'],
    'twitter'=>['icon'=>'fab fa-twitter','color'=>'#1DA1F2'],
    'vimeo'=>['icon'=>'fab fa-vimeo-v','color'=>'#aad450'],
    'vine'=>['icon'=>'fab fa-vine','color'=>'#00b489'],
    'vk'=>['icon'=>'fab fa-vk','color'=>'#4c75a3'],

    'yelp'=>['icon'=>'fab fa-yelp','color'=>'#d32323'],
    'youtube'=>['icon'=>'fab fa-youtube','color'=>'#FF0000'],
    'website'=>['icon'=>'fas fa-globe','color'=>'#1F2937']
];
?>

<?php if (!empty($socials)): ?>
<div class="p-6 mt-6 max-w-4xl mx-auto bg-gray-800 rounded-xl">
    <h2 class="text-xl font-bold mb-4">Connections</h2>
    <ul class="flex flex-wrap gap-4 justify-center">
        <?php foreach ($socials as $social):
            $type = strtolower($social['type']);
            $url = $social['url'] ?? '';
            if (empty($url) || !isset($social_medias[$type])) continue;
            $icon = $social_medias[$type]['icon'];
            $color = $social_medias[$type]['color'];
        ?>
        <li>
            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"
               class="w-12 h-12 flex items-center justify-center rounded-lg transition hover:scale-105"
               style="background-color: <?= $color ?>;">
                <i class="<?= $icon ?> text-white text-xl"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

    <?php if (!empty($images)): ?>
    <div class="p-6 mt-6 max-w-4xl mx-auto bg-gray-800 rounded-xl">
        <h2 class="text-xl font-bold mb-4">Gallery</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <?php foreach ($images as $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" class="rounded shadow cursor-pointer" onclick="openImage(this.src)" />
            <?php endforeach; ?>
        </div>
    </div>

    <div id="popup" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center hidden z-50">
        <span onclick="closePopup()" class="absolute top-5 right-5 text-white text-2xl cursor-pointer">✖</span>
        <img id="popupImg" src="" class="max-w-full max-h-full rounded" />
    </div>
    <?php endif; ?>

    <?php if ($embedMapUrl): ?>
    <div class="p-6 mt-6 max-w-4xl mx-auto bg-gray-800 rounded-xl">
        <h2 class="text-xl font-bold mb-4">Location Map</h2>
        <iframe src="<?= htmlspecialchars($embedMapUrl) ?>" class="w-full h-72 md:h-96 rounded" loading="lazy" allowfullscreen></iframe>
    </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>

    <script>
        const desc = document.getElementById("desc");
        const toggleBtn = document.getElementById("toggleBtn");
        toggleBtn.addEventListener("click", () => {
            desc.classList.toggle("max-h-72");
            toggleBtn.textContent = desc.classList.contains("max-h-72") ? "Show More" : "Show Less";
        });

        function openImage(src) {
            document.getElementById("popupImg").src = src;
            document.getElementById("popup").classList.remove("hidden");
        }

        function closePopup() {
            document.getElementById("popup").classList.add("hidden");
        }

    </script>
<script>
let countdownData = <?= $countdownJson ?>;

function updateCountdown() {
    let now = Math.floor(Date.now() / 1000);
    let diff = countdownData.timestamp - now;
    diff = Math.max(diff, 0);

    let years = Math.floor(diff / (365*86400));
    let months = Math.floor((diff % (365*86400)) / (30*86400));
    let days = Math.floor((diff % (30*86400)) / 86400);
    let hours = Math.floor((diff % 86400) / 3600);
    let minutes = Math.floor((diff % 3600) / 60);
    let seconds = diff % 60;

    let parts = [];
    if (years > 0) parts.push(years + "y");
    if (months > 0) parts.push(months + "m");
    if (days > 0) parts.push(days + "d");
    if (hours > 0) parts.push(hours + "h");
    if (minutes > 0) parts.push(minutes + "m");
    if (seconds > 0) parts.push(seconds + "s");

    let display = parts.slice(0,2).join(' ');

    document.getElementById("countdown").textContent = display;
}

updateCountdown();
setInterval(updateCountdown, 1000);
</script>
</body>
</html>

