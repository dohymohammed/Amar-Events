<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 
require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail($to, $code) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = '';          
        $mail->SMTPAuth = true;
        $mail->Username = ''; 
        $mail->Password = '';     
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('support@amarworld.me', 'AmarEvents');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Your AmarEvents Verification Code';

       
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="x-ua-compatible" content="ie=edge" />
  <title>Verify your email</title>
  <style>
    body { background:#fbfaf6; font-family: Arial, sans-serif; color:#111; margin:0; padding:0; }
    .container { width:100%; max-width:600px; margin:0 auto; }
    .header-bar { background:#121212; color:#fff; padding:18px 24px; font-weight:700; font-size:18px; }
    .brand-icon { width:18px; height:18px; margin-right:8px; display:inline-block; vertical-align:-3px; background:#00b67a; border-radius:3px; }
    .card { background:#ffffff; border-radius:6px; box-shadow:0 0 0 1px rgba(17,17,17,.04); margin:20px; }
    .content { padding:28px; font-size:16px; line-height:1.55; }
    .code-box { margin:18px 0; display:inline-block; padding:12px 16px; font-size:28px; font-weight:700; border:1px solid #e6e6e6; border-radius:6px; letter-spacing:2px; }
    .thanks { padding:0 28px 24px; font-size:16px; }
    .links { text-align:center; font-size:14px; color:#3869d4; padding:14px 24px 24px; }
    .links a { color:#3869d4; }
    .footnote { color:#666; font-size:12px; line-height:1.55; text-align:center; padding:0 24px 18px; }
    .address { text-align:center; font-size:12px; color:#666; padding:6px 24px 24px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-bar">
      <span class="brand-icon"></span> AmarEvents
    </div>

    <div class="card">
      <div class="content">
        <p>Dear User,</p>
        <p>Thanks for joining AmarEvents</p>
        <p>Here’s your code to finish setting up your account:</p>
        <div class="code-box">$code</div>
      </div>
      <div class="thanks">
        Regards,<br/>AmarEvents
      </div>
    </div>

    <div class="links">
      <a href="https://amarevents.zone.id" target="_blank">AmarEvents</a> | 
      <a href="https://www.trustpilot.com/review/amarworld.me" target="_blank">Trustpilot</a> | 
      <a href="mailto:amareventsbd@gmail.com" target="_blank">Contact</a>
    </div>

    
    <div class="address">
      AmarEvents, Thakurgaon Sadar, Rangpur Bangladesh | <a href="https://amarevents.zone.id/rules" target="_blank">Privacy Policy</a>
    </div>
  </div>
</body>
</html>
HTML;

        $mail->AltBody = "Your verification code is: $code";

        $mail->send();
        return true;
    } catch (Exception $e) {
        
        return false;
    }
}

