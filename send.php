<?php
require_once 'config.php';
$message = "Ragamaguru verification code is: 123456. Valid for 10 minutes.";
$message2 = urlencode($message);

$sms_sent = sendSMS('94743605229', $message2);
var_dump($sms_sent);
?>