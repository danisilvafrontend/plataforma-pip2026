<?php
// /public_html/logout.php
declare(strict_types=1);

// Inicia sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa dados da sessão
$_SESSION = [];

// Se estiver usando cookie de sessão, remove-o também
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroi a sessão do lado do servidor
session_destroy();

// Opcional: limpar cookies de autenticação adicionais (exemplos)
// setcookie('remember_me', '', time() - 42000, '/');

// Redireciona para a página pública inicial
header('Location: /index.php');
exit;