<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <title>Wiki - Amar Events</title>
<?php include "config/meta.php" ?>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php session_start(); ?>
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

    <section class="wiki-hero">
        <div class="container">
            <h1 class="hero-title">Amar Wiki & FAQ</h1>
            <p class="hero-<subtitle></subtitle>">Everything you need to know about events and our platform</p>
        </div>
    </section>

    <section class="faq">
        <div class="container">
            <h2 class="section-title">Amar Events Guide</h2>
            <div class="faq-container">
                <div class="faq-item active">
                    <div class="faq-question">
                        <span>How to register for an organization?</span>
                        <i class="fas fa-minus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Send your organization details and user details in <a href="/organization" style="color:yellow;">request organization</a>. You must verify your organization with details.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How to book a ticket?</span>
                        <i class="fas fa-plus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Go to <a href="/events">Events</a> & Click the event. 
                        Click the Register button. You'll see a form fill it up with your information. You must provide your valid information.
                        Check your mail for verification code and paste it here then select payment method. We have Bkash/Nagad/Rocket. 

                    then click the below button. Copy the number along with the ticket prize.
                    <br>
                    Open Bkash app and
                    select send money 
                    then paste the number there and continue paste the ticket prize there and click the below button after payment. Copy and Paste your trans Id to the field. Just click Done & wait for the confirmation mail from event organizer.
                    </p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>how to contact with the owner?</span>
                        <i class="fas fa-plus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p> You can contact with the owner with whatsapp.
                      <br>  </p>

<a href="https://wa.me/8801978431336" target="_blank" class="whatsapp-btn">
    <i class="fab fa-whatsapp"></i> Contact via WhatsApp
</a>
<style>

.whatsapp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #25D366; 
    color: #fff;
    font-weight: bold;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 16px;
    transition: background-color 0.3s, transform 0.2s;
}

.whatsapp-btn i {
    font-size: 20px;
}

.whatsapp-btn:hover {
    background-color: #1ebe57;
    transform: translateY(-2px);
}

.whatsapp-btn:active {
    transform: translateY(0);
}
</style>
                    </div>
                </div>

<div class="faq-item">
                    <div class="faq-question">
                        <span>Want to host something bigger?</span>
                        <i class="fas fa-plus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p> If you're seeking for full automation and 1k+ participants
                      <br> We recommend you to use <a href="https://myeventspark.com" style="font-style:none; color:yellow;weight:700;">EVENTSPARK</a>. You can easily handle your large event there with instant support!</p>
                    </div>
                </div>

            </div>
        </div>
    </section>



    <section class="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What is Amar Events?</span>
                        <i class="fas fa-minus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>it is a event management project to event organizers. It's main goal is to allow others to create events even with the low budget.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Prizes</span>
                        <i class="fas fa-plus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>It depends on  participants count. Please mention your plan in the chat below.<br> We're the most affordable ticket seller in the market!</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Discounts</span>
                        <i class="fas fa-plus faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>for educational events, we offer up to 20% discount.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

   <?php
include "config/footer.php"
?>
    <button id="backToTop" class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>



    <script src="script.js"></script>
</body>
</html>