<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$organizerId = $_SESSION['user']['id'];

if (!isset($_GET['id'])) die("Event ID missing.");
$eventId = intval($_GET['id']);


$eventId = intval($_GET['id']);
$organizerId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT events.* 
    FROM events 
    INNER JOIN organization ON events.organization = organization.id 
    WHERE events.id = ? AND organization.authorid = ?
");
$stmt->execute([$eventId, $organizerId]);
$eventsConfig = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$eventsConfig) die("Event not found.");



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'toggle') {
        $type = $_POST['type']; 

        $current = intval($_POST['current']); 

        $newValue = $current ? 0 : 1;

        if (!in_array($type, ['email','payment'])) {
            echo json_encode(['status'=>'error','message'=>'Invalid type']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE events SET $type=? WHERE id=?");
        $stmt->execute([$newValue, $eventId]);

        echo json_encode(['status'=>'ok','newValue'=>$newValue]);
        exit;
    }

    echo json_encode(['status'=>'error']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Configuration - Organizer</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<link rel="stylesheet" href="/assests/navbar.css">
</head>
<body class="bg-[#12121c] text-white min-h-screen flex flex-col">

<?php 
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>

<main class="flex-grow md:ml-72 p-6 container mx-auto max-w-full overflow-auto mb-16 md:mb-0">
    <h1 class="text-3xl font-bold mb-6">Event Configuration</h1>

    <div class="grid md:grid-cols-2 gap-6">

        <div class="bg-gray-800 p-6 rounded-xl shadow-md flex flex-col items-center">
            <h2 class="text-xl font-semibold mb-4">Email Notifications</h2>
            <p class="mb-4">Current Status: <span id="email-status" class="font-bold"><?= $eventsConfig['email'] ? 'ON' : 'OFF' ?></span></p>
            <button data-type="email" data-current="<?= $eventsConfig['email'] ?>" class="toggle-btn px-6 py-2 rounded text-white font-semibold <?= $eventsConfig['email'] ? 'bg-green-600 hover:bg-green-500' : 'bg-red-600 hover:bg-red-500' ?>">
                <?= $eventsConfig['email'] ? 'Turn OFF' : 'Turn ON' ?>
            </button>
        </div>

        <div class="bg-gray-800 p-6 rounded-xl shadow-md flex flex-col items-center">
            <h2 class="text-xl font-semibold mb-4">Payments</h2>
            <p class="mb-4">Current Status: <span id="payment-status" class="font-bold"><?= $eventsConfig['payment'] ? 'ON' : 'OFF' ?></span></p>
            <button data-type="payment" data-current="<?= $eventsConfig['payment'] ?>" class="toggle-btn px-6 py-2 rounded text-white font-semibold <?= $eventsConfig['payment'] ? 'bg-green-600 hover:bg-green-500' : 'bg-red-600 hover:bg-red-500' ?>">
                <?= $eventsConfig['payment'] ? 'Turn OFF' : 'Turn ON' ?>
            </button>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const type = btn.dataset.type;
        const current = parseInt(btn.dataset.current);

        if(!confirm(`Are you sure you want to turn ${current ? 'OFF' : 'ON'} ${type}?`)) return;

        try {
            const response = await axios.post('', new URLSearchParams({action:'toggle', type:type, current:current}));
            if(response.data.status === 'ok'){
                const newValue = response.data.newValue;
                btn.dataset.current = newValue;
                btn.textContent = newValue ? 'Turn OFF' : 'Turn ON';
                btn.classList.remove('bg-green-600','bg-red-600','hover:bg-green-500','hover:bg-red-500');
                if(newValue){
                    btn.classList.add('bg-green-600','hover:bg-green-500');
                }else{
                    btn.classList.add('bg-red-600','hover:bg-red-500');
                }
                document.getElementById(type+'-status').textContent = newValue ? 'ON' : 'OFF';
            }else{
                alert('Failed to update configuration.');
            }
        } catch(err){
            alert('Error toggling configuration.');
            console.error(err);
        }
    });
});
</script>

</body>
</html>