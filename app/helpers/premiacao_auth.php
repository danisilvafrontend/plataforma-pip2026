<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna informações do usuário atualmente logado, com suporte a:
 * - Admin/Superadmin
 * - Técnico (bancada técnica)  — role = 'tecnico' ou 'tecnica'
 * - Jurado (fase final)        — role = 'juri'
 * - Empreendedor (votação popular)
 * - Parceiro (votação popular)
 * - Sociedade Civil (votação popular)
 *
 * @return array|null Contexto do usuário ou null se não logado
 */
function premiacao_current_actor(): ?array
{
    // Normaliza a role uma única vez
    $role = isset($_SESSION['user_role']) ? strtolower(trim((string)$_SESSION['user_role'])) : '';
    $uid  = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($uid <= 0 && empty($_SESSION['parceiro_id']) && empty($_SESSION['usuario_id'])) {
        return null;
    }

    // ── Técnico (Bancada Técnica) ─────────────────────────────────────────────
    // Role pode ser 'tecnico' ou 'tecnica' (variações históricas do sistema)
    if ($uid > 0 && in_array($role, ['tecnico', 'tecnica'], true)) {
        return [
            'contexto' => 'backend',
            'tipo'     => 'tecnico',
            'id'       => $uid,
            'role'     => 'tecnico',
        ];
    }

    // ── Jurado (Fase Final) ───────────────────────────────────────────────────
    if ($uid > 0 && $role === 'juri') {
        return [
            'contexto' => 'backend',
            'tipo'     => 'juri',
            'id'       => $uid,
            'role'     => 'juri',
        ];
    }

    // ── Admin / Superadmin ────────────────────────────────────────────────────
    if ($uid > 0 && in_array($role, ['admin', 'superadmin'], true)) {
        return [
            'contexto' => 'admin',
            'tipo'     => 'admin_user',
            'id'       => $uid,
            'role'     => $role,
        ];
    }

    // ── Votação Popular (Frontend) ────────────────────────────────────────────

    // Empreendedor (user_id presente, role não classificada acima)
    if ($uid > 0) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'empreendedor',
            'id'       => $uid,
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

/**
 * Verifica se o usuário atual é admin
 */
function premiacao_is_admin(): bool
{
    $actor = premiacao_current_actor();
    return $actor !== null && $actor['contexto'] === 'admin';
}

/**
 * Verifica se o usuário atual é técnico
 */
function premiacao_is_tecnico(): bool
{
    $actor = premiacao_current_actor();
    return $actor !== null && $actor['tipo'] === 'tecnico';
}

/**
 * Verifica se o usuário atual é jurado
 */
function premiacao_is_juri(): bool
{
    $actor = premiacao_current_actor();
    return $actor !== null && $actor['tipo'] === 'juri';
}

/**
 * Verifica se o usuário atual pode votar na votação popular
 */
function premiacao_can_vote_popular(): bool
{
    $actor = premiacao_current_actor();
    return $actor !== null && $actor['contexto'] === 'frontend' && $actor['role'] === 'popular';
}

/**
 * Verifica se o usuário atual pode votar tecnicamente
 */
function premiacao_can_vote_tecnico(): bool
{
    return premiacao_is_tecnico();
}

/**
 * Verifica se o usuário atual pode votar como jurado
 */
function premiacao_can_vote_juri(): bool
{
    return premiacao_is_juri();
}

/**
 * Exige que o usuário seja admin
 */
function premiacao_require_admin(): void
{
    if (!premiacao_is_admin()) {
        http_response_code(403);
        die('Acesso negado. Apenas administradores podem acessar este recurso.');
    }
}

/**
 * Exige que o usuário seja técnico
 */
function premiacao_require_tecnico(): void
{
    if (!premiacao_is_tecnico()) {
        http_response_code(403);
        die('Acesso negado. Apenas técnicos podem acessar este recurso.');
    }
}

/**
 * Exige que o usuário seja jurado
 */
function premiacao_require_juri(): void
{
    if (!premiacao_is_juri()) {
        http_response_code(403);
        die('Acesso negado. Apenas jurados podem acessar este recurso.');
    }
}

/**
 * Exige que o usuário esteja logado; redireciona para login se não estiver
 */
function premiacao_require_login(string $redirectTo = ''): void
{
    $actor = premiacao_current_actor();

    if (!$actor) {
        $_SESSION['login_redirect'] = $redirectTo ?: $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }
}
