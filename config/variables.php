<?php

define('ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

// please check ur db location or ur a dead meat
define('DB', ROOT . 'config/db.php');
define('SMTP', ROOT . 'config/smtp.php');
?>