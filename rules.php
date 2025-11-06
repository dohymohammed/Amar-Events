<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />

<meta name="title" content="Amar Events – Discover & Book Innovative Local Events">
<meta name="description" content="Amar Events is your go-to platform in Bangladesh to explore, support, and attend exciting local events — science fairs, concerts, workshops, and more!">
<meta name="keywords" content="Amar Events, Events Bangladesh, Thakurgaon Events, School Science Fiesta, Ticket Booking, Amar Events App">
<meta name="author" content="Amar Events Team">
<meta name="robots" content="index, follow">
<meta name="language" content="en">
<meta name="revisit-after" content="7 days">


<meta property="og:type" content="website">
<meta property="og:url" content="https://amarevents.zone.id">
<meta property="og:title" content="Amar Events – Discover & Book Local Events in Bangladesh">
<meta property="og:description" content="Join Amar Events to explore local happenings like the TGBHS Science Fiesta in Thakurgaon — easy booking, secure payments, and a seamless experience.">
<meta property="og:image" content="https://i.ibb.co.com/BYK3fmx/Smart-Select-20250822-142251-Chrome.jpg">
<meta property="og:site_name" content="Amar Events">
<meta property="og:locale" content="en_US">

<!-- Twitter / X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://amarevents.zone.id">
<meta name="twitter:title" content="Amar Events – Discover & Book Local Events in Bangladesh">
<meta name="twitter:description" content="Explore and book events like the TGBHS Science Fiesta. Secure, local ticketing — all at your fingertips with Amar Events.">
<meta name="twitter:image" content="https://i.ibb.co.com/BYK3fmx/Smart-Select-20250822-142251-Chrome.jpg">


<link rel="icon" type="image/png" href="/assests/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/assests/favicon.svg" />
<link rel="shortcut icon" href="/assests/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/assests/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="Amar Events" />
<link rel="manifest" href="/assests/site.webmanifest" />
  <title>Rules - Amar Events</title>
<link rel="stylesheet" href="styles.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-inter">

<header class="header">
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="amar-events-logo.png" alt="Amar Events Logo" width="160" height="80" />
      </div>

      <ul class="nav-menu">
        <li class="nav-item"><a href="/wiki" class="nav-link"><i class="fas fa-book"></i> WIKI</a></li>
        <li class="nav-item"><a href="/" class="nav-link"><i class="fas fa-home"></i> HOME</a></li>
        <li class="nav-item"><a href="/events" class="nav-link"><i class="fas fa-calendar-alt"></i> EVENTS</a></li>
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

<main class="container mx-auto px-4 py-12">
  <h1 class="text-4xl font-bold text-center mb-12 text-indigo-400">Event Rules</h1>

  <div class="max-w-xl mx-auto mb-8">
    <input type="text" id="ruleSearch" placeholder="Search rules..." 
           class="w-full p-3 rounded-lg bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
           onkeyup="filterRules()" />
  </div>

  <div id="rulesGrid" class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3"></div>
</main>

<?php include "config/footer.php"; ?>

<script>

  const rules = [
    {
    icon: "fas fa-pen",
    title: "Event Abuse",
    desc: "Do not use multiple events for one event sloth. You're not allowed to edit your previous event details and set your next event with that!"
  },
  {
    icon: "fas fa-shield-alt",
    title: "Platform Security",
    desc: "Do not attempt to hack, manipulate, or access unauthorized sections of the platform."
  },
  {
    icon: "fas fa-ban",
    title: "No DDoS or Spam",
    desc: "Any activity intended to overload or disrupt the platform, including spam, is strictly prohibited."
  },
  {
    icon: "fas fa-comment-slash",
    title: "Respectful Communication",
    desc: "Avoid abusive, offensive, or discriminatory language when interacting on the platform."
  },
  {
    icon: "fas fa-file-alt",
    title: "Accurate Information",
    desc: "Provide truthful and verifiable information when registering or submitting event-related data."
  },
  {
    icon: "fas fa-gavel",
    title: "Compliance",
    desc: "All participants and organizers must comply with local laws and platform policies."
  },
  {
    icon: "fas fa-user-shield",
    title: "Privacy Protection",
    desc: "Respect the privacy of others. Do not share personal information without consent."
  },
  {
    icon: "fas fa-handshake",
    title: "Professional Conduct",
    desc: "Interact professionally with organizers, participants, and attendees at all times."
  },
  {
    icon: "fas fa-calendar-alt",
    title: "Event Deadlines",
    desc: "Follow all deadlines for submissions, registrations, and event participation."
  },
  {
    icon: "fas fa-lightbulb",
    title: "Original Content",
    desc: "Submit original content only. Plagiarism or unauthorized use of others' work is prohibited."
  },
  {
    icon: "fas fa-bullhorn",
    title: "Announcements",
    desc: "Follow official announcements and updates. Avoid spreading unverified information."
  },
  {
    icon: "fas fa-cogs",
    title: "Platform Usage",
    desc: "Use the platform only for its intended purpose. Misuse may lead to account suspension."
  },
  {
    icon: "fas fa-star",
    title: "Maintain Integrity",
    desc: "Uphold honesty and fairness in all interactions and event-related activities."
  }
];


  const rulesGrid = document.getElementById('rulesGrid');

  function renderRules(rulesArray) {
    rulesGrid.innerHTML = "";
    rulesArray.forEach(rule => {
      const card = document.createElement('div');
      card.className = "bg-gray-800 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1";

      card.innerHTML = `
        <div class="flex items-center justify-center mb-4 text-indigo-400 text-3xl">
          <i class="${rule.icon}"></i>
        </div>
        <h2 class="text-xl font-semibold mb-2">${rule.title}</h2>
        <p class="text-gray-300">${rule.desc}</p>
      `;

      rulesGrid.appendChild(card);
    });
  }

  function filterRules() {
    const query = document.getElementById('ruleSearch').value.toLowerCase();
    const filtered = rules.filter(rule => rule.title.toLowerCase().includes(query) || rule.desc.toLowerCase().includes(query));
    renderRules(filtered);
  }

  renderRules(rules);
</script>

<script src="script.js"></script>
</body>
</html>
