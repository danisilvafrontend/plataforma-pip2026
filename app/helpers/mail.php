<?php
// app/helpers/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

/**
 * Função centralizada para envio de e-mails
 */
function send_mail($toEmail, $toName, $subject, $bodyHtml, $bodyAlt = '') {
    $config = require __DIR__ . '/../config/mail.php';

    $mail = new PHPMailer(true);
    try {
        // Configuração SMTP
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        // Remetente
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyAlt ?: strip_tags($bodyHtml);

        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Retorna a URL base do sistema dinamicamente (com http ou https).
 * Funciona para o ambiente atual (Homologação, Produção ou Localhost).
 */
function get_base_url() {
    // Verifica se está usando HTTPS
    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        // Útil para balanceadores de carga na AWS
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isSecure = true;
    }

    $protocolo = $isSecure ? "https" : "http";
    
    // Pega o domínio atual (seja staging.impactospositivos.com, vitrine... ou local)
    $dominio = $_SERVER['HTTP_HOST'] ?? 'vitrine.impactospositivos.com'; // Fallback para produção se rodar via Cron/CLI

    return $protocolo . "://" . $dominio;
}
