<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<?php

$navLinks = [
    'dashboard' => ['', 'index','/'],
    'events'    => ['events', 'participants','edit','fields','socials','email','sms','notice','configure'],
    'gallery'   => ['gallery', 'image'],
    'payments'  => ['payments', 'payment-config'],
    'settings'  => ['settings']
];


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); 
$uriParts = explode('/', trim($uri, '/'));
$currentPage = $uriParts[1] ?? '';



function isActive($keys, $currentPage) {
    return in_array($currentPage, $keys) ? 'active' : '';
}
?>


<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">
      <img src="https://ik.imagekit.io/amarevents/logo.png" alt="Amar Events Logo"/>
    </div>
  </div>
  <div class="nav-menu">
    <a href="/organizer/" class="nav-item <?= isActive($navLinks['dashboard'], $currentPage) ?>">
      <i class="fas fa-chart-line"></i><span>Dashboard</span>
    </a>
    <a href="/organizer/events" class="nav-item <?= isActive($navLinks['events'], $currentPage) ?>">
      <i class="fas fa-calendar-alt"></i><span>Events</span>
    </a>
    <a href="/organizer/gallery" class="nav-item <?= isActive($navLinks['gallery'], $currentPage) ?>">
      <i class="fas fa-images"></i><span>Gallery</span>
    </a>
    <a href="/organizer/payments" class="nav-item <?= isActive($navLinks['payments'], $currentPage) ?>">
      <i class="fas fa-credit-card"></i><span>Payments</span>
    </a>
    <a href="/organizer/settings" class="nav-item <?= isActive($navLinks['settings'], $currentPage) ?>">
      <i class="fas fa-cogs"></i><span>Settings</span>
    </a>
  </div>
  <div class="sidebar-footer">
    <a href="/logout" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>
</nav>


<nav class="mobile-nav rounded-t-3xl" id="mobileNav">
  <a href="/organizer/gallery" class="mobile-nav-item <?= isActive($navLinks['gallery'], $currentPage) ?>">
    <i class="fas fa-images"></i><span>Gallery</span><div class="nav-indicator"></div>
  </a>
  <a href="/organizer/events" class="mobile-nav-item <?= isActive($navLinks['events'], $currentPage) ?>">
    <i class="fas fa-calendar-alt"></i><span>Events</span><div class="nav-indicator"></div>
  </a>

  <a href="/organizer/" class="center-button <?= isActive($navLinks['dashboard'], $currentPage) ?>">
    <i class="fas fa-house-chimney"></i>
  </a>
  <a href="/organizer/payments" class="mobile-nav-item <?= isActive($navLinks['payments'], $currentPage) ?>">
    <i class="fas fa-credit-card"></i><span>Payments</span><div class="nav-indicator"></div>
  </a>
  <a href="/organizer/settings" class="mobile-nav-item <?= isActive($navLinks['settings'], $currentPage) ?>">
    <i class="fas fa-cogs"></i><span>Settings</span><div class="nav-indicator"></div>
  </a>
</nav>
<script>
  document.querySelectorAll('.sidebar .nav-item').forEach(item=>{
  item.addEventListener('click',()=>{document.querySelectorAll('.sidebar .nav-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
document.querySelectorAll('.mobile-nav-item').forEach(item=>{
  item.addEventListener('click',()=>{document.querySelectorAll('.mobile-nav-item').forEach(i=>i.classList.remove('active'));document.querySelector('.center-button').classList.remove('active');item.classList.add('active');});
});
document.querySelector('.center-button').addEventListener('click',()=>{document.querySelectorAll('.mobile-nav-item').forEach(i=>i.classList.remove('active'));document.querySelector('.center-button').classList.add('active');});
</script>


