<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

function sendEmail($toEmail, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Ganti email + app password
        $mail->Username = 'emailkamu@gmail.com';
        $mail->Password = 'app-password';

        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Pengirim
        $mail->setFrom('emailkamu@gmail.com', 'Perpustakaan Sekolah');
        $mail->addAddress($toEmail);

        // Isi
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
