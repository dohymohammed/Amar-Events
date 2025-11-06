<?php
session_start();
require_once 'config/db.php';



$whatsappNumber = 'whatsapp_number_without_+ 
exp: 8801234643263';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<?php include "config/meta.php" ?>
  <title>Organization - Amar Events</title>
  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>

</head>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>
<body>
<header class="header">
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="amar-events-logo.png" alt="Amar Events Logo" width="160" height="80" />
      </div>

      <ul class="nav-menu">
        <li class="nav-item"><a href="/wiki" class="nav-link">WIKI</a></li>
        <li class="nav-item"><a href="/" class="nav-link"> HOME</a></li>
        <li class="nav-item"><a href="/events" class="nav-link"> EVENTS</a></li>
        <li class="nav-item mobile-only">
          <a
            style="text-decoration: none;"
            href="<?php echo isset($_SESSION['user']) ? '/dashboard' : '/login'; ?>"
            class="list-event-btn mobile-btn"
          >
             
            <?php echo isset($_SESSION['user']) ? 'Dashboard' : 'START NOW'; ?>
          </a>
        </li>
      </ul>

      <a
        style="text-decoration: none;"
        href="<?php echo isset($_SESSION['user']) ? '/dashboard' : '/login'; ?>"
        class="list-event-btn"
      >
         
        <?php echo isset($_SESSION['user']) ? 'Dashboard' : 'START NOW'; ?>
      </a>

      <div class="hamburger">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>
    </div>
  </nav>
</header>

<div class="mobile-overlay"></div>

<div class="pt-32 px-4 md:px-8 lg:px-16">

    <div class="bg-gray-800 rounded-xl p-6 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-blue-400 mb-4 text-center">Organization Requirements</h2>
        <p class="text-gray-300 text-center mb-4">Please make sure you meet the following before submitting your request:</p>
        <ul class="list-disc list-inside space-y-2 text-gray-300">
            <li>Organization details ready</li>
            <li>Whatsapp</li>
            <li>website(optional)</li>
            <li>social accounts</li>
            <li>read and understand <a href="/wiki" style="color:yellow;">wiki</a></li>
            <li>must accept <a href="/rules" style="color:yellow;">rules/eula</a></li>
        </ul>
    </div>

    <div style="margin-bottom:4vh;" class="bg-gray-800 rounded-xl p-8 shadow-lg max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-blue-400 mb-6 text-center">Request Your Organization</h2>
        <p class="text-gray-300 mb-6 text-center">Fill out the form below. Your request will be sent directly to our WhatsApp support team.</p>

        <form id="orgForm" class="space-y-4">
            <div>
                <label for="name" class="block mb-1 font-semibold">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Your full name" required class="w-full p-3 rounded-lg bg-gray-700 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label for="orgName" class="block mb-1 font-semibold">Organization Name</label>
                <input type="text" id="orgName" name="orgName" placeholder="Organization name" required class="w-full p-3 rounded-lg bg-gray-700 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label for="phone" class="block mb-1 font-semibold">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="8801XXXXXXXXX" required class="w-full p-3 rounded-lg bg-gray-700 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label for="website" class="block mb-1 font-semibold">Website (Optional)</label>
                <input type="url" id="website" name="website" placeholder="https://example.com" class="w-full p-3 rounded-lg bg-gray-700 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label for="description" class="block mb-1 font-semibold">Description</label>
                <textarea id="description" name="description" placeholder="Brief description of your organization" rows="4" class="w-full p-3 rounded-lg bg-gray-700 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>
            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition-all">Request via WhatsApp <i class="fab fa-whatsapp ml-2"></i></button>
        </form>
    </div>

</div>

<?php include "config/footer.php"; ?>

<script>
document.getElementById('orgForm').addEventListener('submit', function(e){
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const orgName = document.getElementById('orgName').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const website = document.getElementById('website').value.trim();
    const description = document.getElementById('description').value.trim();

    let message = `Hello, I want to create an organization.%0AName: ${name}%0AOrganization: ${orgName}%0APhone: ${phone}`;
    if(website) message += `%0AWebsite: ${website}`;
    if(description) message += `%0ADescription: ${description}`;

    const whatsappNumber = '8801978431336';
    const whatsappLink = `https://wa.me/${whatsappNumber}?text=${message}`;

    window.open(whatsappLink, '_blank');
});
</script>




<script src="script.js"></script>
</body>
</html>
