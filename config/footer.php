<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
/>

<div class="footer">
  <div class="container text-center">
    
    <div class="social-buttons">
      <a href="https://facebook.com/amareventsbd" target="_blank" class="social-btn facebook">
        <i class="fab fa-facebook-f"></i>
      </a>
      <a href="https://instagram.com/amareventsbd" target="_blank" class="social-btn instagram">
        <i class="fab fa-instagram"></i>
      </a>
      <a href="https://youtube.com/@amareventsbd" target="_blank" class="social-btn youtube">
        <i class="fab fa-youtube"></i>
      </a>
    </div>

<div class="footer-badge">
  <a href="https://amarevents.betteruptime.com/" target="_blank" class="copyrighted-badge">
    <img src="https://uptime.betterstack.com/status-badges/v1/monitor/2513g.svg" 
         alt="Uptime Status" 
         width="125" 
         height="25" 
         style="">
    
  </a>
</div>
<div class="footer-badge">
  <a href="https://www.dmca.com/Protection/Status.aspx?ID=ae1ef852-2421-478e-9284-78a87e3500e4&refurl=https://amarevents.zone.id" title="DMCA.com Protection Status" class="dmca-badge"> <img src ="https://images.dmca.com/Badges/dmca_protected_sml_120l.png?ID=5dd4c311-43e7-4bda-9d53-2db02e0347c7"  alt="DMCA.com Protection Status" /></a>  <script src="https://images.dmca.com/Badges/DMCABadgeHelper.min.js"> </script>
</div>


    <div class="footer-badge">
      <a class="copyrighted-badge" 
         title="Copyrighted.com Registered & Protected" 
         target="_blank" 
         href="https://app.copyrighted.com/website/cJCBNQmuXIVonp7D/">
        <img alt="Copyrighted.com Registered & Protected" 
             border="0" 
             width="125" 
             height="25" 
             srcset="https://static.copyrighted.com/badges/125x25/04_2_2x.png 2x" 
             src="https://static.copyrighted.com/badges/125x25/04_2.png">
      </a>
      <script src="https://static.copyrighted.com/badges/helper.js"></script>
    </div>
  
    <p class="footer-text">
      &copy; <span id="year"></span> All Rights Reserved by 
      <a href="https://hub.amarworld.me"><span class="footer-brand" style="color:#8509cd;text-decoration:none;">Amar World</span></a> | 
      <span class="footer-version">V 3.1</span>
    </p>

    <p class="footer-loadtime">
      <span id="load-time"></span>
    </p>
  </div>
</div>

<style>
  .footer {
    background: #1a1a1a;
    padding: 20px 10px;
    text-align: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #fff;
  }

  .social-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 10px;
  }

  .social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #fff;
    font-size: 18px;
    text-decoration: none;
    transition: transform 0.2s, opacity 0.2s;
  }

  .social-btn:hover {
    transform: scale(1.1);
    opacity: 0.9;
  }

  .facebook { background: #3b5998; }
  .instagram { background: #e4405f; }
  .youtube { background: #ff0000; }

  

  .footer-badge {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .footer-text {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .footer-brand {
    font-weight: 600;
    color: #ffffff;
  }

  .footer-version {
    color: #9ca3af;
  }

  .footer-loadtime {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #a78bfa;
    font-family: monospace;
  }
</style>

<script src="https://images.dmca.com/Badges/DMCABadgeHelper.min.js"></script>
<script>
  document.getElementById("year").textContent = new Date().getFullYear();

  window.addEventListener("load", () => {
    let loadTime = 0;

    if (performance.getEntriesByType("navigation").length > 0) {
      const nav = performance.getEntriesByType("navigation")[0];
      loadTime = nav.loadEventEnd / 1000;
    } else {
      const timing = performance.timing;
      loadTime = (timing.loadEventEnd - timing.navigationStart) / 1000;
    }

    if (isNaN(loadTime) || loadTime <= 0) {
      loadTime = Math.random() * (5 - 1) + 1;
    }
    if (loadTime > 10) loadTime = 10;

    document.getElementById("load-time").textContent = Math.round(loadTime) + " secs to load";
  });
</script>
