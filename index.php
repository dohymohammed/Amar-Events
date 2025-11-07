<?php
/*
Amar Events - brief description
Copyright (C) 2025 Harun Abdullah

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version. 
*/


session_start();
require_once 'config/db.php'; 

$sqlFeatured = "SELECT e.*, o.name AS organizer_name 
                FROM events e
                JOIN organization o ON e.organization = o.id
                ORDER BY e.creation_date DESC
                LIMIT 2";
$stmtFeatured = $pdo->query($sqlFeatured);
$featuredEvents = $stmtFeatured->fetchAll(PDO::FETCH_ASSOC);

// uh all events 


$sqlAllEvents = "SELECT e.*, o.name AS organizer_name
                 FROM events e
                 JOIN organization o ON e.organization = o.id
                 ORDER BY e.creation_date DESC";
$stmt = $pdo->query($sqlAllEvents);
$AllEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);







$sqlTicketCount = "SELECT COUNT(*) AS total_success_tickets
                   FROM tickets
                   WHERE status = 'Success'";
$stmtTicketCount = $pdo->query($sqlTicketCount);
$ticketCount = $stmtTicketCount->fetch(PDO::FETCH_ASSOC)['total_success_tickets'] ?? 0;


$sqlEventCount = "SELECT COUNT(*) AS total_events FROM events";
$stmtEventCount = $pdo->query($sqlEventCount);
$eventCount = $stmtEventCount->fetch(PDO::FETCH_ASSOC)['total_events'] ?? 0;
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<?php include "config/meta.php" ?>
  <title>Amar Events - Your Destination is on the way</title>

  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>


<body>
 <div id="splashScreen" class="fixed inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center z-50">
  <img src="assests/apple-touch-icon.png" alt="AmarWorld" class="w-32 h-32 animate-spin-bounce">
</div>

<style>
@keyframes spin-bounce {
  0%   { transform: translateY(0) rotate(0deg); }
  25%  { transform: translateY(-20px) rotate(90deg); }
  50%  { transform: translateY(0) rotate(180deg); }
  75%  { transform: translateY(-20px) rotate(270deg); }
  100% { transform: translateY(0) rotate(360deg); }
}

.animate-spin-bounce {
  animation: spin-bounce 2s infinite ease-in-out;
}
</style>

<script>
window.addEventListener('load', () => {
  const splash = document.getElementById('splashScreen');
  splash.classList.add('opacity-0', 'transition', 'duration-700'); 
  setTimeout(() => splash.style.display = 'none', 700);
});
</script>







<header class="header">
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="amar-events-logo.png" alt="Amar Events Logo" width="160" height="80" />
      </div>

      <ul class="nav-menu">
        <li class="nav-item"><a href="/wiki" class="nav-link">WIKI</a></li>
        <li class="nav-item"><a href="/" class="nav-link"> HOME</a></li>
        <li class="nav-item"><a href="/events" class="nav-link">EVENTS</a></li>
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
<section id="home" class="hero relative h-screen w-full overflow-hidden mt-0">
  
  <div class="hero-slider absolute inset-0 -z-10">
    <div class="slide active">
      <img src="https://i.ibb.co/Xkd38j81/images.jpg" alt="Event Banner" class="w-full h-full object-cover"/>
    </div>
    <div class="slide">
      <img src="https://i.ibb.co/84B854QF/images-1.jpg" alt="Event Banner" class="w-full h-full object-cover"/>
    </div>
    <div class="slide">
      <img src="https://i.ibb.co/fV4K6R8H/images-2.jpg" alt="Event Banner" class="w-full h-full object-cover"/>
    </div>
    
    <div class="absolute inset-0 bg-black/40"></div>
  </div>

  <div class="hero-content absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center z-20 px-4">
    <h1 class="hero-title text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-4 leading-snug">
      Start your events <br>‎‎ <span class="typed-text text-indigo-400"> </span>
    </h1>
    <p class="text-gray-200 text-sm sm:text-base md:text-lg max-w-lg mx-auto mb-6">
      Create, manage, and sell tickets to your events with ease and style.
    </p>
    <div class="flex flex-col sm:flex-row justify-center gap-4">
      <a href="<?php echo isset($_SESSION['user']) ? '/dashboard' : '/login'; ?>"
         class="hero-btn bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-purple-600 hover:to-indigo-500 text-white px-6 py-3 rounded-full font-semibold shadow-lg transition transform hover:scale-105">
        <?php echo isset($_SESSION['user']) ? 'DASHBOARD' : 'GET STARTED'; ?>
      </a>
    </div>
  </div>

  <button class="slider-nav prev-btn z-20" onclick="changeSlide(-1)">
    <i class="fas fa-chevron-left text-white text-2xl"></i>
  </button>
  <button class="slider-nav next-btn z-20" onclick="changeSlide(1)">
    <i class="fas fa-chevron-right text-white text-2xl"></i>
  </button>

  
  <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 150" preserveAspectRatio="none">
    <path fill="#181818" d="M0,0 C480,150 960,0 1440,150 L1440,150 L0,150 Z"></path>
  </svg>

</section>


<style>
.hero {
  height: 100vh;
  width: 100%;
  position: relative;
  overflow: hidden;
}

.hero-slider .slide img {
  filter: brightness(0.75); 
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity 0.5s ease-in-out;
}

.hero-content .typed-text {
  color: #8b5cf6; 
}

.hero-btn {
  display: inline-block;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.hero-btn:hover {
  transform: scale(1.05);
}

.slider-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.4);
  padding: 12px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 20;
}

.prev-btn { left: 15px; }
.next-btn { right: 15px; }


@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem !important;
  }
  .hero-btn {
    width: 100%;
    text-align: center;
  }
}

.hero-title {
  font-weight: 900;
  font-size: 2.5rem; 
  line-height: 1;     
  white-space: nowrap; 
  overflow: hidden;
  text-overflow: ellipsis; 
  color: white;
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 1.75rem; 
  }
}

.typed-text {
  color: #8b5cf6; 
}
</style>


<?php
$target = 1500;
$monthStart = date("Y-m-01 00:00:00");
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE status='verified' AND created_at >= :mstart");
$stmt->execute([':mstart' => $monthStart]);
$row = $stmt->fetch();
$totalDonations = (int)($row['total'] ?? 0);
$progress = min(100, ($totalDonations / $target) * 100);
?>

<div id="donationBar" class="rounded-xl shadow-2xl p-6 max-w-lg mx-auto my-6 border border-gray-700 relative"
     style="background-color: #1e1e1e;">
  

  <button onclick="document.getElementById('donationBar').style.display='none';"
          class="absolute top-3 right-3 text-gray-400 hover:text-gray-200 text-xl font-bold">&times;</button>

  <div class="flex justify-between items-center mb-4">
    <h3 class="text-lg font-bold text-white tracking-wide">💖 Support AmarEvents</h3>

    <span class="text-gray-300 font-medium"><?= number_format($totalDonations,0) ?> / <?= number_format($target,0) ?> tk</span>
  </div>
  
  <div class="w-full bg-gray-800 rounded-full h-6 mb-4 border border-gray-700 overflow-hidden">
    <div class="h-6 rounded-full text-center text-white font-semibold transition-all duration-500"
         style="width:<?= round($progress) ?>%; background: linear-gradient(90deg, #6366F1, #EC4899); 
                box-shadow: 0 0 10px rgba(99,102,241,0.7), 0 0 10px rgba(236,72,153,0.7);">
      <?= round($progress) ?>%
    </div>
  </div>

  <a href="/donation"
     class="block text-center bg-gradient-to-r from-purple-600 to-pink-500 text-white font-bold py-3 rounded-xl hover:from-purple-700 hover:to-pink-600 transition shadow-lg hover:shadow-pink-500/50">
    Donate 
  </a>
</div>


<section id="events" class="featured-events">

  <div class="container">
    <h2 class="section-title">Featured Events</h2>
    <div class="events-grid">
      <?php foreach ($featuredEvents as $event):
        $eventDate = new DateTime($event['date']);
        $day = $eventDate->format('d');
        $month = strtoupper($eventDate->format('M'));
      ?>
      <div class="event-card">
        <a href="/event?id=<?= htmlspecialchars($event['id']) ?>" style="text-decoration:none; color:inherit;">
          <div class="event-image">
            <div class="event-date">
              <span class="day"><?= $day ?></span>
              <span class="month"><?= $month ?></span>
            </div>
            <img src="<?= htmlspecialchars($event['banner'] ?: 'default-banner.jpg') ?>" alt="<?= htmlspecialchars($event['name']) ?>" />
          </div>
          <div class="event-info">
            <h3 class="event-title"><?= htmlspecialchars($event['name']) ?></h3>
            <p class="event-price">Price: <span><?= htmlspecialchars('৳'.$event['prize'] ?: 'Free') ?></span></p>
            <div class="event-details">
              <p><i class="fas fa-building"></i> Organized By: <?= htmlspecialchars($event['organizer_name']) ?></p>
              <p><i class="fas fa-map-marker-alt"></i> Location: <?= htmlspecialchars($event['location'] ?: 'TBA') ?></p>
              <p><i class="fas fa-clock"></i> <?= $eventDate->format('F d, Y') ?></p>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<section class="py-16" style="background-color: #121212; color: #e0e0e0;">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 text-center">

      <div class="rounded-xl p-8 shadow-xl hover:scale-105 transform transition duration-300" style="background-color: #1f1f1f;">
        <i class="fas fa-ticket-alt text-indigo-400 text-5xl mb-4 drop-shadow-lg"></i>
        <h3 class="text-4xl font-extrabold text-white" data-target="<?= $ticketCount ?>" data-suffix=""><?= $ticketCount ?></h3>
        <p class="mt-2 text-gray-300 text-lg">Tickets Sold</p>
      </div>

      <div class="rounded-xl p-8 shadow-xl hover:scale-105 transform transition duration-300" style="background-color: #1f1f1f;">
        <i class="fas fa-laptop text-green-400 text-5xl mb-4 drop-shadow-lg"></i>
        <h3 class="text-4xl font-extrabold text-white" data-target="<?= $eventCount ?>" data-suffix=""><?= $eventCount ?></h3>
        <p class="mt-2 text-gray-300 text-lg">Events</p>
      </div>

      <div class="rounded-xl p-8 shadow-xl hover:scale-105 transform transition duration-300" style="background-color: #1f1f1f;">
        <i class="fas fa-handshake text-yellow-400 text-5xl mb-4 drop-shadow-lg"></i>
        <h3 class="text-4xl font-extrabold text-white" data-target="0" data-suffix="">0</h3>
        <p class="mt-2 text-gray-300 text-lg">Events Partnered</p>
      </div>

    </div>
  </div>
</section>













<!-- all events buddy ahh -->

<section id="events" class="py-10 bg-[rgb(30,30,30)] text-white">
  <div class="relative px-8">
    <h2 class="section-title">All Events</h2>

    <button id="scrollLeft" class="hidden md:flex items-center justify-center absolute top-1/2 -left-3 transform -translate-y-1/2 bg-[#383a40] hover:bg-[#44464d] text-white rounded-full p-3 shadow-lg z-10">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button id="scrollRight" class="hidden md:flex items-center justify-center absolute top-1/2 -right-3 transform -translate-y-1/2 bg-[#383a40] hover:bg-[#44464d] text-white rounded-full p-3 shadow-lg z-10">
      <i class="fas fa-chevron-right"></i>
    </button>

    <div id="eventsRow " class="flex overflow-x-auto space-x-5 pb-6 scroll-smooth no-scrollbar ">
      <?php foreach ($AllEvents as $event):
        $eventDate = new DateTime($event['date']);
        $day = $eventDate->format('d');
        $month = strtoupper($eventDate->format('M'));
      ?>
      <div class="min-w-[300px] max-w-[300px] bg-[rgba(42,42,42,255)] rounded-xl shadow-lg flex-shrink-0 hover:scale-105 transition-transform duration-300 ">
        <a href="/event?id=<?= htmlspecialchars($event['id']) ?>" class="block">
          <div class="relative">
            <div class="absolute top-2 left-2 bg-black/60 text-white rounded p-1 text-center text-xs">
              <span class="block font-bold"><?= $day ?></span>
              <span><?= $month ?></span>
            </div>
            <img src="<?= htmlspecialchars($event['banner'] ?: 'default-banner.jpg') ?>" alt="<?= htmlspecialchars($event['name']) ?>" class="w-full h-44 object-cover rounded-t-xl"/>
          </div>
          <div class="p-4">
                <h3 class="event-title text-white" ><?= htmlspecialchars($event['name']) ?></h3>
            <p class="event-price text-gray-200">Price: <span><?= htmlspecialchars('৳'.$event['prize'] ?: 'Free') ?></span></p>
            <div class="event-details">
              <p><i class="fas fa-building"></i> Organized By: <?= htmlspecialchars($event['organizer_name']) ?></p>
              <p><i class="fas fa-map-marker-alt"></i> Location: <?= htmlspecialchars($event['location'] ?: 'TBA') ?></p>
              <p><i class="fas fa-clock"></i> <?= $eventDate->format('F d, Y') ?></p>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<style>
  .no-scrollbar::-webkit-scrollbar { display: none; }
  .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
  const row = document.getElementById("eventsRow");
  document.getElementById("scrollLeft").addEventListener("click", () => {
    row.scrollBy({ left: -340, behavior: "smooth" });
  });
  document.getElementById("scrollRight").addEventListener("click", () => {
    row.scrollBy({ left: 340, behavior: "smooth" });
  });
</script>




<section class="max-w-6xl mx-auto px-6 py-16">
  <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-white-400 section-title">
    What AmarEvents Offers
  </h2>
  <div class="grid md:grid-cols-2 gap-8 text-lg text-gray-200">
    <ul class="space-y-4">
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Host events on a small budget</li>
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Choose from popular local events — science fairs, concerts, workshops</li>
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Accept mobile payments via Bkash, Nagad, Rocket</li>
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Automated confirmation via email (and SMS verification)</li>
    </ul>
    <ul class="space-y-4">
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Educational event discounts (up to 20%)</li>
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Designed for local communities across Bangladesh</li>
      <li class="flex items-center gap-2"><span class="text-green-400">✔</span> Perfect launchpad for conscious and budget-aware organizers</li>
    </ul>
  </div>
</section>
  



<section class="py-20 text-center bg-[#2b2d31] px-6">
  <h2 class="text-3xl md:text-4xl font-bold mb-6 text-white-400">Create Your Event</h2>
  <p class="max-w-2xl mx-auto mb-10 text-gray-400 text-base md:text-lg">
    Start and manage your events with an easy UI & full automation.
  </p>

  <div class="flex flex-col md:flex-row justify-center gap-6">
<a href="/organization" 
   class="w-full md:w-auto bg-sky-600 hover:bg-sky-700 px-8 py-3 rounded-xl font-semibold transition block text-center">
   Join As Organizer
</a>

<a href="/signup" 
   class="w-full md:w-auto bg-gray-700 hover:bg-gray-900 px-8 py-3 rounded-xl font-semibold transition block text-center">
   Sign Up
</a>

  </div>
</section>





  <section class="bg-[#181818] py-16">
    <div class="max-w-7xl mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center mb-14 text-white-400 section-title">Our Features</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">


        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/Kc4TGHG6/Smart-Select-20250831-180331-Chrome.jpg" class="rounded-lg mb-4" alt="custom logo & banner">
          <h3 class="font-semibold text-xl mb-2">Custom Event Logo & Banner</h3>

        </div>



        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/fzygLwfY/posts-image7.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Automated Mails & Sms</h3>
          <p class="text-gray-400">make your participants notify easily and instantly.</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/S7Gd9hH7/original-0dacf96b4015e058dec816b27183bb38-1.png" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Event Gallery</h3>
          <p class="text-gray-400">upload up to 6 images to your event page!</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/XTPFMZ3/67f7fafcba24c735237a4421-can-you-export-a-webflow-website-understanding-code-export-limitations.png" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">One Click Export</h3>
          <p class="text-gray-400">export your participant list in docx / pdf.</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/BKybMYFV/6505028.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Enhanced Security</h3>
          <p class="text-gray-400">protecting you and our services with enhanced protection.</p>
        </div>



        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/qLmFLprQ/screely-1754921281299.png" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Participants Dashbaord</h3>
          <p class="text-gray-400">participants login with the number to view their ticket details!</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/XZb2BwNQ/images-4.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Multiple Payment Option</h3>
          <p>we currently allow organizations to use bkash/nagad/rocket.</p>

        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/4w9zqbqb/Smart-Select-20250831-183713-Chrome.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Ticket & QR Code</h3>
          <p class="text-gray-400">scan the qr code to verify the ticket. The ticket will be sent though mail automaticly.<br> Participants can download the ticket anytime.</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/XZsCkCM8/images-2.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Social Account</h3>
          <p class="text-gray-400">add up to 28+ social media to your event page.</p>
        </div>

        <div class="bg-[#242424] rounded-2xl p-6 shadow hover:shadow-lg hover:scale-[1.02] transition">
          <img src="https://i.ibb.co.com/p691YQGw/images-3.jpg" class="rounded-lg mb-4" alt="">
          <h3 class="font-semibold text-xl mb-2">Map & location</h3>
          <p class="text-gray-400">show your event's location so participants can catch up easily.</p>
        </div>



      </div>
    </div>
  </section>











<!--
<section class="artists">
  <div class="container">
    <h2 class="section-title">Sponsors</h2>
    <div class="artists-grid">
      <a href="https://harunabdullah.is-a.dev" style="text-decoration: none;">
  <div class="artist-card">
    <div class="artist-image">
      <img src="https://i.ibb.co.com/Z1xx6NNy/me2.png" alt="Harun Abdullah" />
    </div>
    <h3 class="artist-name">Harun Abdullah</h3>
  </div>
</a> 

    </div>
  </div>
</section> -->











<!-- Install App -->








<?php
include "config/app.php";
?>

<?php

include "config/footer.php";
?>







<script src="script.js"></script>



<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
      .then(reg => console.log('Service Worker registered!', reg))
      .catch(err => console.log('Service Worker registration failed:', err));
  });
}

// type
const typedTextSpan = document.querySelector(".typed-text");
const phrases = ["with ease!", "in seconds!", "affordablely"];
let phraseIndex = 0;
let letterIndex = 0;
let currentPhrase = "";
let isDeleting = false;
let typingSpeed = 100;

function type() {
  if (phraseIndex >= phrases.length) phraseIndex = 0;
  currentPhrase = phrases[phraseIndex];

  if (!isDeleting) {
    typedTextSpan.textContent = currentPhrase.substring(0, letterIndex + 1);
    letterIndex++;
    if (letterIndex === currentPhrase.length) {
      isDeleting = true;
      setTimeout(type, 1000);
      return;
    }
  } else {
    typedTextSpan.textContent = currentPhrase.substring(0, letterIndex - 1);
    letterIndex--;
    if (letterIndex === 0) {
      isDeleting = false;
      phraseIndex++;
    }
  }
  setTimeout(type, isDeleting ? 50 : typingSpeed);
}

document.addEventListener("DOMContentLoaded", type);

</script>
<style>
  .hero-slider .slide img {
  filter: blur(3px);
 width:100%;
}

.hero-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;  
  color: white;
  text-align: center;
}
.hero-title {
  font-size: 1.5rem;      
  line-height: 1.2;    
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 1.5rem;  
  }
}
.hero-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 2;
  max-width: 90%;
  color: #fff;
}


.hero-title {
  
  font-weight: 900;
  
  
  background: white;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 15px #cacacaff,
               0 0 15px #cacacaff;
}





.hero-btn {
  display: inline-block;
  margin-top: 20px;
  padding: 12px 30px;
  font-weight: 700;
  color: #000;
  background: white;
  border-radius: 50px;
  box-shadow: 0 4px 15px #6e6e6eff;
  text-decoration: none;
  transition: transform 0.3s, box-shadow 0.3s;
}
.hero-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 240, 255, 0.8);
}




</style>
</body>
</html>
