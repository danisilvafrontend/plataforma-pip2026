<?php
// app/helpers/mail.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

function send_mail($toEmail, $toName, $subject, $bodyHtml, $bodyAlt = '')
{
    $config = require __DIR__ . '/../config/mail.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);

        // Debug opcional em log
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        // $mail->Debugoutput = function ($str, $level) {
        //     error_log("PHPMailer [$level]: " . $str);
        // };

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress(trim((string)$toEmail), trim((string)$toName));

        $mail->Subject = (string)$subject;
        $mail->Body    = (string)$bodyHtml;
        $mail->AltBody = $bodyAlt !== '' ? (string)$bodyAlt : strip_tags((string)$bodyHtml);

        if (!$mail->send()) {
            throw new Exception('PHPMailer send() falhou: ' . $mail->ErrorInfo);
        }

        return true;
    } catch (\Throwable $e) {
        error_log('Erro ao enviar e-mail para ' . $toEmail . ': ' . $e->getMessage());
        throw new Exception('Erro ao enviar e-mail para ' . $toEmail . ': ' . $e->getMessage());
    }
}

function get_base_url()
{
    $isSecure = false;

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isSecure = true;
    }

    $protocolo = $isSecure ? 'https' : 'http';
    $dominio = $_SERVER['HTTP_HOST'] ?? 'vitrine.impactospositivos.com';

    return $protocolo . '://' . $dominio;
}