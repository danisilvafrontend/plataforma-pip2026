<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
];

$appBase = null;
foreach ($possibleAppPaths as $p) {
    if (is_dir($p)) {
        $appBase = realpath($p);
        break;
    }
}

if ($appBase === null) {
    die('Erro: pasta app não encontrada.');
}

require_once $appBase . '/helpers/auth.php';
require_once $appBase . '/helpers/mail.php';

require_admin_login();

try {
    $ok = send_mail(
        'danisilva.frontend@gmail.com',
        'Daniela Silva',
        'Teste de envio - Impactos Positivos',
        '<h1>Teste de envio</h1><p>Se chegou, o SMTP está funcionando.</p>',
        'Teste de envio'
    );

    echo '<pre>';
    var_dump($ok);
    echo '</pre>';
} catch (Throwable $e) {
    echo 'Erro ao enviar: ' . htmlspecialchars($e->getMessage());
}