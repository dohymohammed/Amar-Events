<?php
require_once 'inc/db.php';
require_once 'inc/auth.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'make_admin') {
        $pdo->prepare("UPDATE users SET admin = 1 WHERE id = ?")->execute([$userId]);
    } elseif ($action === 'remove_admin') {
        $pdo->prepare("UPDATE users SET admin = 0 WHERE id = ?")->execute([$userId]);
    } elseif ($action === 'make_organizer') {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $username = $stmt->fetchColumn();

        $pdo->prepare("INSERT INTO organization (name, authorid) VALUES (?, ?)")->execute([$username, $userId]);
        $pdo->prepare("UPDATE users SET type = 'organizer' WHERE id = ?")->execute([$userId]);
    } elseif ($action === 'make_user') {
        $pdo->prepare("DELETE FROM organization WHERE authorid = ?")->execute([$userId]);

        $eventIds = $pdo->query("SELECT id FROM events WHERE organization = $userId")->fetchAll(PDO::FETCH_COLUMN);
        if ($eventIds) {
            $in = str_repeat('?,', count($eventIds)-1) . '?';
            $pdo->prepare("DELETE FROM tickets WHERE eventid IN ($in)")->execute($eventIds);
            $pdo->prepare("DELETE FROM payments WHERE eventid IN ($in)")->execute($eventIds);
            $pdo->prepare("DELETE FROM events WHERE id IN ($in)")->execute($eventIds);
        }

        $pdo->prepare("UPDATE users SET type = 'user' WHERE id = ?")->execute([$userId]);
    } elseif ($action === 'update_eventcount') {
        $count = max(0, min(9, intval($_POST['eventcount'] ?? 0)));
        $pdo->prepare("UPDATE organization SET eventcount = ? WHERE authorid = ?")->execute([$count, $userId]);
    }

    header("Location: users.php");
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT id, email, username, number, admin, type FROM users";
$params = [];
if ($search !== '') {
    $sql .= " WHERE email LIKE :f OR username LIKE :f OR number LIKE :f";
    $params['f'] = "%$search%";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventcounts = [];
$orgs = $pdo->query("SELECT authorid, eventcount FROM organization")->fetchAll(PDO::FETCH_KEY_PAIR);
if ($orgs) {
    $eventcounts = $orgs;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users – Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<header class="bg-white shadow p-4 flex justify-between items-center">
  <h1 class="text-xl font-bold text-gray-800">Manage Users</h1>
  <a href="index.php" class="text-blue-600 hover:underline">&larr; Dashboard</a>
</header>

<main class="p-6 space-y-4">
  <form method="get">
    <input name="search" placeholder="Search email/username/number…" value="<?= htmlspecialchars($search) ?>"
      class="w-full md:w-1/3 p-2 border rounded">
  </form>

  <div class="bg-white shadow rounded overflow-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2">ID</th>
          <th class="p-2">Email</th>
          <th class="p-2">Username</th>
          <th class="p-2">Number</th>
          <th class="p-2">Admin</th>
          <th class="p-2">Role</th>
          <th class="p-2">Event Count</th>
          <th class="p-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="p-2"><?= $u['id'] ?></td>
          <td class="p-2"><?= htmlspecialchars($u['email']) ?></td>
          <td class="p-2"><?= htmlspecialchars($u['username']) ?></td>
          <td class="p-2"><?= htmlspecialchars($u['number']) ?></td>
          <td class="p-2"><?= $u['admin'] ? 'Yes' : 'No' ?></td>
          <td class="p-2"><?= ucfirst($u['type'] ?? 'user') ?></td>
          <td class="p-2">
            <?php if ($u['type'] === 'organizer'): ?>
              <form method="post" class="flex items-center space-x-1">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="action" value="update_eventcount">
                <input type="number" name="eventcount" value="<?= $eventcounts[$u['id']] ?? 0 ?>" min="0" max="9" class="w-16 p-1 border rounded">
                <button class="bg-gray-800 text-white px-2 py-1 rounded text-xs">Save</button>
              </form>
            <?php else: ?>
              <span class="text-gray-400 italic">N/A</span>
            <?php endif; ?>
          </td>
          <td class="p-2 space-y-1">
            <form method="post" class="inline-block">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="<?= $u['admin'] ? 'remove_admin' : 'make_admin' ?>">
              <button class="<?= $u['admin'] ? 'bg-red-500' : 'bg-blue-500' ?> text-white px-2 py-1 rounded text-xs">
                <?= $u['admin'] ? 'Remove Admin' : 'Make Admin' ?>
              </button>
            </form>

            <form method="post" class="inline-block">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="<?= $u['type']==='organizer' ? 'make_user' : 'make_organizer' ?>">
              <button class="<?= $u['type']==='organizer' ? 'bg-yellow-500' : 'bg-green-500' ?> text-white px-2 py-1 rounded text-xs">
                <?= $u['type']==='organizer' ? 'Make User' : 'Make Organizer' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>



