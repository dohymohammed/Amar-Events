<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require("config/db.php");

$submitted = false;
$thanksMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitDonation'])) {
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $org    = $_POST['organization'] ?? null;
    $type   = $_POST['type'] ?? '';
    $amount = (int) ($_POST['amount'] ?? 0);
    $trx    = trim($_POST['transactionID'] ?? '');
    $note   = "Donation via site";

    $errors = [];
    if (!$name) $errors[] = "Name is required";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (!$number) $errors[] = "Phone number is required";
    if (!$type || !in_array($type,['bkash','nagad'])) $errors[] = "Select a valid payment type";
    if ($amount < 100) $errors[] = "Minimum donation is 100 tk";
    if (!$trx || strlen($trx) < 10) $errors[] = "Valid Transaction ID is required";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE transactionID=:trx");
    $stmt->execute([':trx'=>$trx]);
    if ($stmt->fetchColumn() > 0) $errors[] = "This Transaction ID has already been used";

    if (empty($errors)) {
        if (substr($number,0,3) !== "+88") $number = "+88".$number;
        try {
            $stmt = $pdo->prepare("INSERT INTO donations 
                (email, number, organization, note, type, amount, transactionID, status) 
                VALUES (:email,:number,:organization,:note,:type,:amount,:transactionID,'pending')");
            $stmt->execute([
                ':email'=>$email,
                ':number'=>$number,
                ':organization'=>$org,
                ':note'=>$note,
                ':type'=>$type,
                ':amount'=>$amount,
                ':transactionID'=>$trx
            ]);
            $submitted = true;
            $thanksMsg = "Thanks $name for your donation of $amount tk via $type!";
            header("Location: ".$_SERVER['PHP_SELF']."?thanks=1&msg=".urlencode($thanksMsg));
            exit;
        } catch(Exception $e){
            $errors[] = "Database error: ".$e->getMessage();
        }
    }
}

if (isset($_GET['thanks']) && $_GET['thanks']==1){
    $submitted = true;
    $thanksMsg = $_GET['msg'] ?? "Thanks for your donation!";
}

$target = 1500;
$monthStart = date("Y-m-01 00:00:00");
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE status='verified' AND created_at>=:mstart");
$stmt->execute([':mstart'=>$monthStart]);
$row = $stmt->fetch();
$totalDonations = (int)($row['total'] ?? 0);
$progress = min(100, ($totalDonations/$target)*100);

$orgs = $pdo->query("SELECT id,name FROM organization ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>AmarEvents Donation</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-lg p-8 bg-white rounded-2xl shadow-xl">
  <h2 class="text-3xl font-bold text-center mb-4 text-indigo-600">🎉 AmarEvents Donations</h2>
  <p class="text-center text-gray-600 mb-4 font-medium">Goal: <?= $totalDonations ?>/<?= $target ?> tk this month</p>

  <div class="relative w-full bg-gray-200 h-5 rounded-full overflow-hidden mb-6">
    <div class="absolute top-0 left-0 h-5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-center text-white font-semibold text-xs flex items-center justify-center" style="width:<?= $progress ?>%">
      <?= $totalDonations ?>/<?= $target ?> tk
    </div>
  </div>

<?php if($submitted): ?>
  <div class="text-center">
    <h3 class="text-xl font-semibold text-green-600 mb-4">🎉 Thank You!</h3>
    <p class="text-gray-700"><?= htmlspecialchars($thanksMsg) ?></p>
  </div>
<?php else: ?>
  <?php if(!empty($errors)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 space-y-1">
      <?php foreach($errors as $err) echo "<p>$err</p>"; ?>
    </div>
  <?php endif; ?>

<form method="POST" class="space-y-6">

  <div id="step1" class="step block space-y-4">
    <h3 class="text-lg font-semibold mb-2">Step 1: Your Info</h3>
    <input type="text" name="name" placeholder="Full Name*" required class="w-full p-3 border rounded">
    <input type="email" name="email" placeholder="Email*" required class="w-full p-3 border rounded">
    <input type="text" name="number" placeholder="Phone Number*" maxlength="13" required class="w-full p-3 border rounded">
    <select name="organization" class="w-full p-3 border rounded">
      <option value="">None</option>
      <?php foreach($orgs as $o): ?>
        <option value="<?= htmlspecialchars($o['name']) ?>"><?= htmlspecialchars($o['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="showStep(2)" class="w-full py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Continue</button>
  </div>

  <div id="step2" class="step hidden space-y-3">
    <h3 class="text-lg font-semibold mb-2">Step 2: Select Payment</h3>
    <label class="flex items-center border p-3 rounded cursor-pointer transition hover:shadow-lg">
      <input type="radio" name="type" value="bkash" required class="mr-3">
      <img src="https://i.ibb.co.com/Vdnp0n4/BKash-Icon2-Logo-wine.png" class="w-12 h-12 mr-3">
      <span class="font-medium">bKash</span>
    </label>
    <label class="flex items-center border p-3 rounded cursor-pointer transition hover:shadow-lg">
      <input type="radio" name="type" value="nagad" required class="mr-3">
      <img src="https://i.ibb.co.com/HDWzVnPf/Nagad-Logo-wine.png" class="w-12 h-12 mr-3">
      <span class="font-medium">Nagad</span>
    </label>
    <button type="button" onclick="showStep(3)" class="w-full py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Continue</button>
  </div>

<?php
$bkash_number = "+8801978431336";   
$nagad_number = "+8801830573674";  
?>

<div id="step3" class="step hidden space-y-4">
  <h3 class="text-lg font-semibold mb-2">Step 3: Payment Details</h3>
  

  <div id="paymentInfo" class="p-4 border rounded-lg bg-gray-50 hidden">
    <p class="text-gray-800 font-semibold">Send money to:</p>
    <p id="paymentNumber" class="text-lg font-bold text-indigo-600"></p>
    <p class="text-sm text-gray-600 mt-1">Account Type: <span class="font-medium">Personal</span></p>
  </div>

  <input type="number" name="amount" placeholder="Amount (100+)" min="100" required 
         class="w-full p-3 border rounded">
  <input type="text" name="transactionID" placeholder="Transaction ID" minlength="10" maxlength="18" required 
         class="w-full p-3 border rounded">

  <button type="submit" name="submitDonation" 
          class="w-full py-3 bg-green-600 text-white rounded hover:bg-green-700 transition">
    Paid
  </button>
</div>

</form>
<?php endif; ?>
</div>
<script>

function showStep(n){

  const currentStep = document.querySelector('.step:not(.hidden)');
  if (!currentStep) return;

  const inputs = currentStep.querySelectorAll('input, select');
  let valid = true;

  inputs.forEach(input => {
    if (!input.checkValidity()) {
      input.reportValidity(); 

      valid = false;
    }
  });

  if (!valid) return;

  document.querySelectorAll('.step').forEach(s=>s.classList.add('hidden'));

  document.getElementById('step'+n).classList.remove('hidden');
}

document.querySelectorAll('.step input[type="radio"]').forEach(radio=>{
  radio.addEventListener('change', ()=>{

    const labels = radio.closest('.step').querySelectorAll('label');
    labels.forEach(lbl=>lbl.classList.remove('border-indigo-600','shadow-lg'));

    radio.closest('label').classList.add('border-indigo-600','shadow-lg');
  });
});



document.querySelectorAll('input[name="type"]').forEach(radio => {
  radio.addEventListener('change', () => {
    const paymentInfo = document.getElementById('paymentInfo');
    const paymentNumber = document.getElementById('paymentNumber');
    if (radio.checked) {
      if (radio.value === "bkash") {
        paymentNumber.textContent = "<?= $bkash_number ?> (bKash)";
      } else if (radio.value === "nagad") {
        paymentNumber.textContent = "<?= $nagad_number ?> (Nagad)";
      }
      paymentInfo.classList.remove("hidden");
    }
  });
});
</script>

</body>
</html>

