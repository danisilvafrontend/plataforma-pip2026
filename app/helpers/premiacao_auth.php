<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function premiacao_current_actor(): ?array
{
    // Admin/Superadmin: tem user_id E role específico de admin
    $rolesAdmin = ['admin', 'superadmin'];
    if (
        !empty($_SESSION['user_id']) &&
        !empty($_SESSION['user_role']) &&
        in_array($_SESSION['user_role'], $rolesAdmin, true)
    ) {
        return [
            'contexto' => 'admin',
            'tipo'     => 'admin_user',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => (string)$_SESSION['user_role'],
        ];
    }

    // Empreendedor logado (user_id presente, com qualquer role não-admin)
    if (!empty($_SESSION['user_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'empreendedor',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'popular',
        ];
    }

    // Parceiro logado
    if (!empty($_SESSION['parceiro_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'parceiro',
            'id'       => (int)$_SESSION['parceiro_id'],
            'role'     => 'popular',
        ];
    }

    // Sociedade civil logada
    if (
        !empty($_SESSION['logado']) &&
        ($_SESSION['usuario_tipo'] ?? '') === 'sociedade_civil' &&
        !empty($_SESSION['usuario_id'])
    ) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'sociedade_civil',
            'id'       => (int)$_SESSION['usuario_id'],
            'role'     => 'popular',
        ];
    }

    return null;
}