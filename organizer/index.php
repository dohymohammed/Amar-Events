<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM organization WHERE authorid = ?");
$stmt->execute([$user_id]);
$organization = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organization) {
    die("Organization not found for current user.");
}
$organization_id = $organization['id'];



$stmt = $pdo->prepare("
    SELECT o.*,
           COALESCE(SUM(t.money), 0) AS totalEarn
    FROM organization o
    LEFT JOIN tickets t
        ON t.organization = o.id
        AND t.status = 'Success'
    WHERE o.authorid = ?
    GROUP BY o.id
");
$stmt->execute([$user_id]);
$organization = $stmt->fetch(PDO::FETCH_ASSOC);

$totalEarn = $organization['totalEarn'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE organization = ? AND status = 'Success'");
$stmt->execute([$organization_id]);
$totalParticipants = (int)$stmt->fetchColumn();

$totalSlotsLeft = (int)($organization['eventcount'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM events WHERE organization = ? ORDER BY creation_date DESC LIMIT 1");
$stmt->execute([$organization_id]);
$currentEvent = $stmt->fetch(PDO::FETCH_ASSOC);

function fetchParticipants($pdo, $organization_id, $range) {
    $dateCondition = '';
    $params = [$organization_id];

    if ($range === '7') $dateCondition = "AND DATE(creation_date) >= CURDATE() - INTERVAL 7 DAY";
    elseif ($range === '30') $dateCondition = "AND DATE(creation_date) >= CURDATE() - INTERVAL 30 DAY";

    $sql = "
        SELECT DATE(creation_date) AS day, COUNT(*) AS participants 
        FROM tickets 
        WHERE organization = ? AND status = 'Success' $dateCondition
        GROUP BY day ORDER BY day ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fillDates(array $data, int $days): array {
    $result = [];
    $map = [];
    foreach ($data as $row) $map[$row['day']] = (int)$row['participants'];

    $period = new DatePeriod(
        new DateTime("-" . ($days - 1) . " days"),
        new DateInterval('P1D'),
        $days
    );
    foreach ($period as $date) {
        $d = $date->format('Y-m-d');
        $result[] = ['day' => $d, 'participants' => $map[$d] ?? 0];
    }
    return $result;
}

$data7 = fillDates(fetchParticipants($pdo, $organization_id, '7'), 7);
$data30 = fillDates(fetchParticipants($pdo, $organization_id, '30'), 30);
$dataAll = fetchParticipants($pdo, $organization_id, 'all');


?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Organizer Dashboard - Amar Events</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assests/navbar.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>
<body>

<div class="dashboard-container">
 
<?php require_once __DIR__ . '/../config/navbar.php';
?>
  <!-- Main Content -->
  <main class="main-content">
    <div id="content-area">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-[#1e1e2f] p-6 rounded-xl shadow hover:shadow-lg transition flex flex-col items-center">
          <div class="text-gray-400 uppercase text-sm font-bold mb-1">Total Earned</div>
          <div class="text-4xl font-extrabold"><?= number_format($totalEarn, 2) ?>৳</div>
        </div>
        <div class="bg-[#1e1e2f] p-6 rounded-xl shadow hover:shadow-lg transition flex flex-col items-center">
          <div class="text-gray-400 uppercase text-sm font-bold mb-1">Participants</div>
          <div class="text-4xl font-extrabold"><?= $totalParticipants ?></div>
        </div>
        <div class="bg-[#1e1e2f] p-6 rounded-xl shadow hover:shadow-lg transition flex flex-col items-center">
          <div class="text-gray-400 uppercase text-sm font-bold mb-1">Event Slots</div>
          <div class="text-4xl font-extrabold"><?= $totalSlotsLeft ?></div>
        </div>
      </div>

      <section class="bg-[#1e1e2f] p-6 rounded-xl shadow mb-8">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Participants Chart</h2>
          <div class="flex gap-2">
            <button data-range="7" class="range-btn bg-blue-600 px-3 py-1 rounded text-white hover:bg-blue-700">7 Days</button>
            <button data-range="30" class="range-btn bg-gray-700 px-3 py-1 rounded text-white hover:bg-blue-700">1 Month</button>
            <button data-range="all" class="range-btn bg-gray-700 px-3 py-1 rounded text-white hover:bg-blue-700">All Time</button>
          </div>
        </div>
        <canvas id="participantsChart" class="w-full h-64"></canvas>
      </section>

      <section class="mb-8">
        <h2 class="text-xl font-semibold mb-4">Current Event</h2>
        <?php if ($currentEvent): ?>
          <div class="bg-[#1e1e2f] rounded-xl shadow overflow-hidden flex flex-col md:flex-row">
            <img src="<?= htmlspecialchars($currentEvent['banner'] ?: 'https://ik.imagekit.io/amarworld/default_event_banner.jpg') ?>" alt="Event Banner" class="w-full md:w-72 object-cover" />
            <div class="p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-2xl font-bold mb-2"><?= htmlspecialchars($currentEvent['name']) ?></h3>
                <p class="text-gray-400 mb-1">Location: <?= htmlspecialchars($currentEvent['location']) ?></p>
                <p class="text-gray-400 mb-1">Date: <?= date('d M Y', strtotime($currentEvent['date'])) ?></p>
              </div>
              <div class="mt-4 flex gap-4">
                <a href="/event?id=<?= $currentEvent['id'] ?>" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">View</a>
                <a href="/organizer/participants?id=<?= $currentEvent['id'] ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded font-semibold transition">Manage</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <p class="text-gray-500">No current event found.</p>
        <?php endif; ?>
      </section>

    </div>
  </main>


</div>

<footer class="footer">All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.</footer>

<script>
document.querySelectorAll('.sidebar .nav-item').forEach(item=>{
  item.addEventListener('click',()=>{document.querySelectorAll('.sidebar .nav-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
document.querySelectorAll('.mobile-nav-item').forEach(item=>{
  item.addEventListener('click',()=>{document.querySelectorAll('.mobile-nav-item').forEach(i=>i.classList.remove('active'));document.querySelector('.center-button').classList.remove('active');item.classList.add('active');});
});
document.querySelector('.center-button').addEventListener('click',()=>{document.querySelectorAll('.mobile-nav-item').forEach(i=>i.classList.remove('active'));document.querySelector('.center-button').classList.add('active');});

const data7 = <?= json_encode($data7) ?>;
const data30 = <?= json_encode($data30) ?>;
const dataAll = <?= json_encode($dataAll) ?>;

function formatLabels(data) {
  const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return data.map(d=>{
    const dateObj = new Date(d.day);
    return months[dateObj.getMonth()] + " " + dateObj.getDate();
  });
}
function prepareDataset(data){return data.map(d=>d.participants);}
let currentRange='7';
const ctx=document.getElementById('participantsChart').getContext('2d');
const participantsChart=new Chart(ctx,{type:'bar',data:{labels:formatLabels(data7),datasets:[{label:'Participants',backgroundColor:'#3b82f6',borderColor:'#2563eb',borderWidth:1,data:prepareDataset(data7)}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});
document.querySelectorAll('.range-btn').forEach(btn=>btn.addEventListener('click',()=>{const range=btn.getAttribute('data-range');if(range===currentRange)return;document.querySelectorAll('.range-btn').forEach(b=>{b.classList.remove('bg-blue-600');b.classList.add('bg-gray-700');});btn.classList.remove('bg-gray-700');btn.classList.add('bg-blue-600');currentRange=range;let newData; if(range==='7')newData=data7; else if(range==='30')newData=data30; else newData=dataAll;participantsChart.data.labels=formatLabels(newData);participantsChart.data.datasets[0].data=prepareDataset(newData);participantsChart.update();}));
</script>

</body>
</html>