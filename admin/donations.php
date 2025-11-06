<?php
require_once __DIR__ . '/inc/db.php';
require_once 'inc/auth.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($_POST['action'] === 'verify') {
            $stmt = $pdo->prepare("UPDATE donations SET status='verified' WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['status'=>'success','msg'=>'Donation verified']);
        } elseif ($_POST['action'] === 'fail') {
            $stmt = $pdo->prepare("DELETE FROM donations WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['status'=>'success','msg'=>'Donation deleted']);
        } else {
            echo json_encode(['status'=>'error','msg'=>'Invalid action']);
        }
    } else {
        echo json_encode(['status'=>'error','msg'=>'Invalid ID']);
    }
    exit;
}

$where = [];
$params = [];

if(isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])){
    $where[] = "DATE_FORMAT(created_at,'%Y-%m')=:month";
    $params[':month'] = $_GET['month'];
}

if(!empty($_GET['organization'])){
    $where[] = "organization=:org";
    $params[':org'] = $_GET['organization'];
}

if(!empty($_GET['type'])){
    $where[] = "type=:type";
    $params[':type'] = $_GET['type'];
}

$whereSQL = $where ? "WHERE ".implode(" AND ",$where) : "";
$stmt = $pdo->prepare("SELECT * FROM donations $whereSQL ORDER BY created_at DESC");
$stmt->execute($params);
$donations = $stmt->fetchAll();

$orgs = $pdo->query("SELECT DISTINCT name FROM organization ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Admin - Donations</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-white shadow-md p-4 flex flex-col md:flex-row items-center justify-between">
  <h1 class="text-xl font-bold text-gray-800 mb-2 md:mb-0">AmarEvents Admin</h1>
  <div class="flex space-x-4">
    <a href="/admin/" class="text-blue-600 hover:underline font-medium">Dashboard</a>
    <a href="/admin/users.php" class="text-blue-600 hover:underline font-medium">Users</a>
    <a href="/admin/donations.php" class="text-blue-600 hover:underline font-medium font-semibold">Donations</a>
    <a href="/logout" class="text-red-600 hover:underline font-medium">Logout</a>
  </div>
</nav>

<main class="p-6 space-y-6">

  <h2 class="text-2xl font-bold text-gray-700 text-center">📝 Donations Dashboard</h2>

  <form method="GET" class="flex flex-wrap gap-4 mb-6 items-end justify-center">
    <div>
      <label class="block text-sm font-medium text-gray-700">Month</label>
      <input type="month" name="month" value="<?= htmlspecialchars($_GET['month'] ?? '') ?>" class="p-2 border rounded">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Organization</label>
      <select name="organization" class="p-2 border rounded">
        <option value="">All</option>
        <?php foreach($orgs as $o): ?>
        <option value="<?= htmlspecialchars($o) ?>" <?= (($_GET['organization'] ?? '') === $o) ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Payment Type</label>
      <select name="type" class="p-2 border rounded">
        <option value="">All</option>
        <option value="bkash" <?= (($_GET['type'] ?? '')==='bkash')?'selected':'' ?>>bKash</option>
        <option value="nagad" <?= (($_GET['type'] ?? '')==='nagad')?'selected':'' ?>>Nagad</option>
      </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Filter</button>
    <a href="/admin/donations.php" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 transition">Reset</a>
  </form>

  <div class="bg-white p-4 rounded shadow overflow-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr class="text-left">
          <th class="p-2">#</th>
          <th class="p-2">Name</th>
          <th class="p-2">Email</th>
          <th class="p-2">Phone</th>
          <th class="p-2">Org</th>
          <th class="p-2">Amount</th>
          <th class="p-2">Payment</th>
          <th class="p-2">Transaction ID</th>
          <th class="p-2">Status</th>
          <th class="p-2">Date</th>
          <th class="p-2">Action</th>
        </tr>
      </thead>
      <tbody id="donationTableBody">
        <?php if(count($donations)===0): ?>
        <tr>
          <td colspan="11" class="text-center py-4 text-gray-500">No donations found.</td>
        </tr>
        <?php else: ?>
        <?php foreach($donations as $i=>$d): ?>
        <tr class="<?= $i%2===0?'bg-gray-50':'' ?>" id="donation-<?= $d['id'] ?>">
          <td class="p-2"><?= $i+1 ?></td>
          <td class="p-2"><?= htmlspecialchars($d['name'] ?? '-') ?></td>
          <td class="p-2"><?= htmlspecialchars($d['email']) ?></td>
          <td class="p-2"><?= htmlspecialchars($d['number']) ?></td>
          <td class="p-2"><?= htmlspecialchars($d['organization'] ?? '-') ?></td>
          <td class="p-2 font-semibold text-green-600"><?= number_format($d['amount'],0) ?> tk</td>
          <td class="p-2"><?= htmlspecialchars($d['type']) ?></td>
          <td class="p-2"><?= htmlspecialchars($d['transactionID']) ?></td>
          <td class="p-2" id="status-<?= $d['id'] ?>">
            <?php if($d['status']==='pending'): ?>
              <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full text-xs">Pending</span>
            <?php elseif($d['status']==='verified'): ?>
              <span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-xs">Verified</span>
            <?php else: ?>
              <span class="bg-red-200 text-red-800 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($d['status']) ?></span>
            <?php endif; ?>
          </td>
          <td class="p-2"><?= date("Y-m-d H:i", strtotime($d['created_at'])) ?></td>
          <td class="p-2 space-x-2">
            <?php if($d['status']==='pending'): ?>
              <button onclick="updateDonation(<?= $d['id'] ?>,'verify')" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm">Confirm</button>
              <button onclick="updateDonation(<?= $d['id'] ?>,'fail')" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm">Fail</button>
            <?php else: ?>
              <span class="text-gray-500 text-sm">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<script>
function updateDonation(id,action){
    if(!confirm(`Are you sure to ${action==='verify'?'confirm':'fail'} this donation?`)) return;
    fetch('',{
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=${action}&id=${id}`
    })
    .then(r=>r.json())
    .then(data=>{
        if(data.status==='success'){
            if(action==='verify'){
                document.getElementById(`status-${id}`).innerHTML='<span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-xs">Verified</span>';
                document.getElementById(`donation-${id}`).querySelectorAll('button').forEach(b=>b.remove());
            } else {
                document.getElementById(`donation-${id}`).remove();
            }
        } else {
            alert(data.msg);
        }
    })
    .catch(e=>alert('Error: '+e));
}
</script>

</body>
</html>
