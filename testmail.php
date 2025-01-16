<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);
$mail->SMTPDebug = 4;
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mohammedmaizan@gmail.com';
    $mail->Password = 'ocbocejxyxquwxic';  // Use App Password if applicable
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('mohammedmaizan@gmail.com', 'Maizan');
    $mail->addAddress('mohamedmaizanmunas@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email from WAMP Server';
    $mail->Body    = 'This is a test email sent from WAMP using PHPMailer.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
// ocbocejxyxquwxic