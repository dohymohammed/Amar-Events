<?php
session_start();
require_once 'config/db.php'; 

$sql = "SELECT e.*, o.name AS organizer_name 
        FROM events e
        JOIN organization o ON e.organization = o.id
        ORDER BY e.date ASC, e.creation_date DESC";
$stmt = $pdo->query($sql);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<?php include "config/meta.php" ?>
  <title>Events - Amar Events</title>
  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
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

<section class="search-section">
  <div class="container">
    <div class="search-container">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="eventSearch" placeholder="Search events by name..." class="search-input" onkeyup="filterEvents()" />
        <button class="search-btn" onclick="filterEvents()">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
  </div>
</section>

<section id="events" class="featured-events">
  <div class="container">
    <h2 class="section-title">Events</h2>
    <div id="eventsGrid" class="events-grid">
      <?php
      if (count($events) === 0) {
          echo "<p>No events found.</p>";
      } else {
          foreach ($events as $event):
              $eventDate = new DateTime($event['date'] ?: $event['creation_date']);
              $day = $eventDate->format('d');
              $month = strtoupper($eventDate->format('M'));
              $price = $event['prize'] ? '৳' . $event['prize'] : 'Free';

              
              $banner = $event['banner'] ?: 'default-banner.jpg';
              $location = $event['location'] ?: 'TBA';
      ?>
      <div class="event-card">
        <a href="/event?id=<?= htmlspecialchars($event['id']) ?>" style="text-decoration:none; color:inherit;">
          <div class="event-image">
            <div class="event-date">
              <span class="day"><?= $day ?></span>
              <span class="month"><?= $month ?></span>
            </div>
            <img src="<?= htmlspecialchars($banner) ?>" alt="<?= htmlspecialchars($event['name']) ?>" />
          </div>
          <div class="event-info">
            <h3 class="event-title"><?= htmlspecialchars($event['name']) ?></h3>
            <p class="event-price">Price Starts from: <span><?= htmlspecialchars($price) ?> </span></p>
            <div class="event-details">
              <p><i class="fas fa-building"></i> Organized By: <?= htmlspecialchars($event['organizer_name']) ?></p>
              <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($location) ?></p>
            </div>
          </div>
        </a>
      </div>
      <?php
          endforeach;
      }
      ?>
    </div>
  </div>
</section>

<?php
include "config/footer.php"
?>

<script>

  function filterEvents() {
    const input = document.getElementById('eventSearch');
    const filter = input.value.toLowerCase();
    const eventsGrid = document.getElementById('eventsGrid');
    const cards = eventsGrid.getElementsByClassName('event-card');

    for (let i = 0; i < cards.length; i++) {
      const title = cards[i].querySelector('.event-title').textContent.toLowerCase();
      if (title.indexOf(filter) > -1) {
        cards[i].style.display = "";
      } else {
        cards[i].style.display = "none";
      }
    }
  }
</script>



<script src="script.js"></script>
</body>
</html>
