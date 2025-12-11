<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';


function sendVerificationEmail($email, $code, $username) {
    $mail = new PHPMailer(true);

    try {
        // Настройки сервера
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nozimjonh6@gmail.com';
        $mail->Password   = 'cchhkpmifglyeeku'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Адреса
        $mail->setFrom('nozimjonh6@gmail.com', 'Регистрация');
        $mail->addAddress($email);

        // Контент
        $mail->isHTML(true);
        $mail->Subject = 'Ваш код';
        $mail->Body    = "<h3>Здравствуйте, $username!</h3><p>Ваш код: <b>$code</b></p>";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
