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
$errors = [];
$success = null;


 $eventId = $_GET['id'] ?? null;
if (! $eventId) die("No event specified.");


$stmt = $pdo->prepare("SELECT * FROM events WHERE id=? AND organization=?");
$stmt->execute([ $eventId, $org_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Event not found or you do not have permission.");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['add_field'])) {
        $name = trim($_POST['field_name'] ?? '');
        $type = $_POST['field_type'] ?? '';
        $options = $_POST['options'] ?? null;
        $required = isset($_POST['required']) ? 1 : 0;

        if (!$name || !$type) {
            $errors[] = "Field name and type are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO custom_fields (eventid, field_name, field_type, options, required) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([ $eventId, $name, $type, $options, $required]);
            $success = "Custom field added successfully.";
        }
    }

    
    if (isset($_POST['edit_field'])) {
        $field_id = $_POST['field_id'] ?? null;
        $name = trim($_POST['field_name'] ?? '');
        $type = $_POST['field_type'] ?? '';
        $options = $_POST['options'] ?? null;
        $required = isset($_POST['required']) ? 1 : 0;

        if ($field_id && $name && $type) {
            $stmt = $pdo->prepare("UPDATE custom_fields SET field_name=?, field_type=?, options=?, required=? WHERE id=? AND eventid=?");
            $stmt->execute([$name, $type, $options, $required, $field_id,  $eventId]);
            $success = "Custom field updated successfully.";
        }
    }

    
if (isset($_POST['delete_field'])) {
    $field_id = $_POST['field_id'] ?? null;
    if ($field_id) {

        $stmt = $pdo->prepare("DELETE FROM ticket_custom_values WHERE field_id=?");
        $stmt->execute([$field_id]);


        $stmt = $pdo->prepare("DELETE FROM custom_fields WHERE id=? AND eventid=?");
        $stmt->execute([$field_id,  $eventId]);

        $success = "Custom field and all its responses deleted successfully.";
    }
}


}


$stmt = $pdo->prepare("SELECT * FROM custom_fields WHERE eventid=? ORDER BY id");
$stmt->execute([ $eventId]);
$custom_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-edit-field]').forEach(function(form) {
        form.addEventListener('submit', function() {
            const optionsList = form.querySelector('.options_list');
            const hiddenInput = form.querySelector('input[name=\"options\"]');
            if (optionsList && hiddenInput) {
                const values = Array.from(optionsList.querySelectorAll('input')).map(i => i.value.trim()).filter(v => v);
                hiddenInput.value = values.join(',');
            }
        });
    });
});
</script>";
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title>Manage Custom Fields - <?= htmlspecialchars($event['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assests/navbar.css">
<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#12121c] text-white flex flex-col min-h-screen">



<?php
require_once __DIR__ . '/../config/navbar.php';
require_once __DIR__ . '/../config/sub-events-navbar.php';
?>









<main class="md:ml-72 p-6 w-full max-w-4xl mx-auto mb-16 md:mb-0">
    <h1 class="text-2xl font-bold mb-6">Manage Custom Fields for "<?= htmlspecialchars($event['name']) ?>"</h1>

    <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-600 text-white rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-4 bg-red-600 text-white rounded">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Add New Field -->
    <div class="mb-8 p-4 bg-gray-800 rounded shadow space-y-4">
        <h2 class="text-xl font-semibold mb-2">Add New Field</h2>
        <form method="POST" class="space-y-3" onsubmit="document.getElementById('add_options_hidden').value = getOptions('add_options_list')">
            <label class="block">
                Field Name:
                <input type="text" name="field_name" required class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded" placeholder="e.g. T-shirt Size">
            </label>

            <label class="block">
                Field Type:
                <select name="field_type" id="add_field_type" required class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded">
                    <option value="text">Text</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="radio">Radio Button</option>
                    <option value="checkbox">Checkbox</option>
                </select>
            </label>

            <div id="add_options_container" class="hidden space-y-2">
                <label class="block font-medium">Options:</label>
                <div id="add_options_list" class="space-y-1"></div>
                <button type="button" id="add_option_btn" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">Add Option</button>
                <input type="hidden" name="options" id="add_options_hidden">
            </div>

            <label class="inline-flex items-center mt-2">
                <input type="checkbox" name="required" class="mr-2 accent-blue-500">
                Required
            </label>

            <button type="submit" name="add_field" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">Add Field</button>
        </form>
    </div>

    <!-- Existing Fields -->
    <div class="space-y-4">
        <h2 class="text-xl font-semibold mb-2">Existing Fields</h2>
        <?php foreach ($custom_fields as $f): ?>
            <form method="POST" class="p-4 bg-gray-800 rounded shadow space-y-2" data-edit-field 
                  onsubmit="this.querySelector('input[name=options]').value = getOptionsFromForm(this)">
                <input type="hidden" name="field_id" value="<?= $f['id'] ?>">

                <label class="block">
                    Field Name:
                    <input type="text" name="field_name" value="<?= htmlspecialchars($f['field_name']) ?>" class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded">
                </label>

                <label class="block">
                    Field Type:
                    <select name="field_type" class="field_type w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded">
                        <option value="text" <?= $f['field_type']=='text'?'selected':'' ?>>Text</option>
                        <option value="dropdown" <?= $f['field_type']=='dropdown'?'selected':'' ?>>Dropdown</option>
                        <option value="radio" <?= $f['field_type']=='radio'?'selected':'' ?>>Radio</option>
                        <option value="checkbox" <?= $f['field_type']=='checkbox'?'selected':'' ?>>Checkbox</option>
                    </select>
                </label>

                <div class="options_container space-y-2 <?= in_array($f['field_type'],['dropdown','radio','checkbox'])?'':'hidden' ?>">
                    <label class="block font-medium">Options:</label>
                    <div class="options_list space-y-1"></div>
                    <button type="button" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm add_option_btn">Add Option</button>
                    <input type="hidden" name="options" value="<?= htmlspecialchars($f['options']) ?>">
                </div>

                <label class="inline-flex items-center mt-2">
                    <input type="checkbox" name="required" class="mr-2 accent-blue-500" <?= $f['required']?'checked':'' ?>>
                    Required
                </label>

                <div class="flex gap-2 mt-2">
                    <button type="submit" name="edit_field" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded font-semibold transition">Save</button>
                    <button type="submit" name="delete_field" onclick="return confirm('⚠️ Deleting this field will permanently remove all submitted responses. Are you sure?')" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded font-semibold transition">Delete</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</main>

<footer class="bg-[#1e1e2f] text-center text-sm text-gray-400 py-4 mt-auto">
    All rights reserved by <strong>Amar Events</strong>. A sub-company of <strong>AmarWorld</strong>.
</footer>
<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<script>
lucide.createIcons();





// --- Helpers ---
function getOptions(listId) {
    const list = document.getElementById(listId);
    return Array.from(list.querySelectorAll('input')).map(i => i.value.trim()).filter(v => v).join(',');
}

function getOptionsFromForm(form) {
    const list = form.querySelector('.options_list');
    return Array.from(list.querySelectorAll('input')).map(i => i.value.trim()).filter(v => v).join(',');
}

// --- Dynamic options: Add Field ---
function setupDynamicOptions(fieldTypeSelectId, optionsContainerId, optionsListId, hiddenInputId, addBtnId) {
    const typeSelect = document.getElementById(fieldTypeSelectId);
    const container = document.getElementById(optionsContainerId);
    const list = document.getElementById(optionsListId);

    document.getElementById(addBtnId).addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-center';
        div.innerHTML = `<input type="text" class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded" placeholder="Option">
                         <button type="button" class="px-2 py-1 bg-red-600 hover:bg-red-700 rounded text-sm remove-btn">X</button>`;
        list.appendChild(div);
        div.querySelector('input').addEventListener('input', () => document.getElementById(hiddenInputId).value = getOptions(optionsListId));
        div.querySelector('.remove-btn').addEventListener('click', () => { div.remove(); document.getElementById(hiddenInputId).value = getOptions(optionsListId); });
    });

    typeSelect.addEventListener('change', () => {
        if (['dropdown','radio','checkbox'].includes(typeSelect.value)) container.classList.remove('hidden');
        else {
            container.classList.add('hidden');
            list.innerHTML = '';
            document.getElementById(hiddenInputId).value = '';
        }
    });
}
setupDynamicOptions('add_field_type','add_options_container','add_options_list','add_options_hidden','add_option_btn');

// --- Dynamic options: Edit Fields ---
document.querySelectorAll('form[data-edit-field]').forEach(form => {
    const fieldTypeSelect = form.querySelector('select[name="field_type"]');
    const optionsContainer = form.querySelector('.options_container');
    const optionsList = form.querySelector('.options_list');
    const hiddenInput = form.querySelector('input[name="options"]');

    function updateHidden() {
        hiddenInput.value = Array.from(optionsList.querySelectorAll('input')).map(i => i.value.trim()).filter(v => v).join(',');
    }

    // Populate existing options
    if (hiddenInput.value) {
        hiddenInput.value.split(',').forEach(opt => {
            if(!opt) return;
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-center';
            div.innerHTML = `<input type="text" class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded" value="${opt}">
                             <button type="button" class="px-2 py-1 bg-red-600 hover:bg-red-700 rounded text-sm remove-btn">X</button>`;
            optionsList.appendChild(div);
            div.querySelector('input').addEventListener('input', updateHidden);
            div.querySelector('.remove-btn').addEventListener('click', () => { div.remove(); updateHidden(); });
        });
    }

    function checkType() {
        if (['dropdown','radio','checkbox'].includes(fieldTypeSelect.value)) optionsContainer.classList.remove('hidden');
        else optionsContainer.classList.add('hidden');
    }
    fieldTypeSelect.addEventListener('change', checkType);
    checkType();

    form.querySelector('.add_option_btn').addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-center';
        div.innerHTML = `<input type="text" class="w-full bg-[#1e1e2f] border border-gray-700 p-2 rounded" placeholder="Option">
                         <button type="button" class="px-2 py-1 bg-red-600 hover:bg-red-700 rounded text-sm remove-btn">X</button>`;
        optionsList.appendChild(div);
        div.querySelector('input').addEventListener('input', updateHidden);
        div.querySelector('.remove-btn').addEventListener('click', () => { div.remove(); updateHidden(); });
        updateHidden();
    });
});
</script>

<script>
const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggleSidebar");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("-translate-x-full");
      });
    }

    document.getElementById('eventsDropdownBtn').addEventListener('click', () => {
      document.getElementById('eventsDropdown').classList.toggle('hidden');
    });

  const paymentBtn = document.getElementById('paymentDropdownBtn');
  const paymentDropdown = document.getElementById('paymentDropdown');
  if (paymentBtn && paymentDropdown) {
    paymentBtn.addEventListener('click', () => {
      paymentDropdown.classList.toggle('hidden');
    });
  }
</script>

</body>
</html>
