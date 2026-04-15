<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function premiacao_current_actor(): ?array
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
        return [
            'contexto' => 'admin',
            'tipo' => 'admin_user',
            'id' => (int) $_SESSION['user_id'],
            'role' => (string) $_SESSION['user_role'],
        ];
    }

    if (!empty($_SESSION['user_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo' => 'empreendedor',
            'id' => (int) $_SESSION['user_id'],
            'role' => 'popular',
        ];
    }

    if (!empty($_SESSION['parceiro_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo' => 'parceiro',
            'id' => (int) $_SESSION['parceiro_id'],
            'role' => 'popular',
        ];
    }

    if (!empty($_SESSION['logado']) && ($_SESSION['usuario_tipo'] ?? '') === 'sociedade_civil' && !empty($_SESSION['usuario_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo' => 'sociedade_civil',
            'id' => (int) $_SESSION['usuario_id'],
            'role' => 'popular',
        ];
    }

    return null;
}