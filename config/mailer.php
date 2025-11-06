<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path if needed

$mailer = new PHPMailer(true);

try {
    
    $mailer->isSMTP();
    $mailer->Host       = '';      // Your SMTP server
    $mailer->SMTPAuth   = true;
    $mailer->Username   = ''; // SMTP username
    $mailer->Password   = '';    // SMTP password
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mailer->Port       = 587;                    

    
    $mailer->setFrom('support@amarworld.me', 'Amar Events');

    
    $mailer->isHTML(true);
    $mailer->CharSet = 'UTF-8';

} catch (Exception $e) {
    
    echo "Mailer Error: {$mailer->ErrorInfo}";
}