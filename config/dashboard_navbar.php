<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<?php

$navLinks = [
    'dashboard' => ['', 'index','/'],
    'account'   => ['account'],
    'organizer' => ['organizer']
];



$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); 
$uriParts = explode('/', trim($uri, '/'));
$currentPage = $uriParts[1] ?? '';



function isActive($keys, $currentPage) {
    if (!is_array($keys)) return '';
    return in_array($currentPage, $keys) ? 'active' : '';
}


$check = $pdo->prepare("SELECT type, admin FROM users WHERE id = ?");
    $check->execute([$user_id]);
    $user = $check->fetch(PDO::FETCH_ASSOC);
?>


<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">
      <img src="https://ik.imagekit.io/amarevents/logo.png" alt="Amar Events Logo"/>
    </div>
  </div>
  <div class="nav-menu">
    <a href="/dashboard/" class="nav-item <?= isActive($navLinks['dashboard'], $currentPage) ?>">
      <i class="fas fa-house-chimney"></i><span>Home</span>
    </a>

<a href="/dashboard/account" class="nav-item <?= isActive($navLinks['account'], $currentPage) ?>">
      <i class="fas fa-user"></i><span>Account</span>
    </a>



  <?php  if ($user) {

        if ($user['type'] === 'organizer'): ?>
            <a href="/organizer/" class="nav-item">
                <i class="fas fa-building"></i><span>Organization</span>
            </a>
        <?php endif; ?>

        <?php

        if ($user['admin'] == 1): ?>
            <a href="/admin/" class="nav-item">
                <i class="fa-solid fa-screwdriver-wrench"></i><span>Admin Panel</span>
            </a>
        <?php endif;
  }
   ?>

  </div>


  
  <div class="sidebar-footer">
    <a href="/logout" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>
</nav>


<nav class="mobile-nav rounded-t-3xl" id="mobileNav">
<a href="/dashboard/" class="mobile-nav-item <?= isActive($navLinks['dashboard'], $currentPage) ?>">
    <i class="fas fa-house-chimney"></i><span>Home</span><div class="nav-indicator"></div>
  </a>
  <a href="/dashboard/account" class="mobile-nav-item <?= isActive($navLinks['account'], $currentPage) ?>">
    <i class="fas fa-user"></i><span>Account</span><div class="nav-indicator"></div>
  </a>



<?php  if ($user) {

        if ($user['type'] === 'organizer'): ?>
  <a href="/organizer/" class="center-button ">
    <i class="fas fa-building"></i>
  </a>
        <?php endif; ?>

        <?php

        if ($user['admin'] == 1): ?>
  <a href="/admin/" class="center-button">
    <i class="fa-solid fa-screwdriver-wrench"></i>
  </a>
        <?php endif;
  }
   ?>

  <a href="/logout" class="mobile-nav-item ">
    <i class="fas fa-sign-out-alt"></i><span>Logout</span><div class="nav-indicator"></div>
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
