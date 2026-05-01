<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna informações do usuário atualmente logado, com suporte a:
 * - Admin/Superadmin
 * - Técnico (bancada técnica)
 * - Jurado (fase final)
 * - Empreendedor (votação popular)
 * - Parceiro (votação popular)
 * - Sociedade Civil (votação popular)
 *
 * @return array|null Contexto do usuário ou null se não logado
 */
function premiacao_current_actor(): ?array
{
    // ── NÍVEL 1: Admin/Superadmin ─────────────────────────────────────────────
    // Acesso total ao sistema
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

    // ── NÍVEL 2: Técnico (Bancada Técnica) ─────────────────────────────────────
    // Avalia inscrições com notas técnicas
    // Vota apenas nas inscrições classificadas de cada fase
    // Fase 1: 10 inscrições por categoria
    // Fase 2: 3 inscrições por categoria
    // Fase Final: 1 inscrição por categoria
    if (
        !empty($_SESSION['user_id']) &&
        $_SESSION['user_role'] === 'tecnico'
    ) {
        return [
            'contexto' => 'backend',
            'tipo'     => 'tecnico',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'tecnico',
        ];
    }

    // ── NÍVEL 3: Jurado (Fase Final) ───────────────────────────────────────────
    // Vota na inscrição vencedora por categoria na fase final
    // Vota apenas UMA VEZ por categoria
    if (
        !empty($_SESSION['user_id']) &&
        $_SESSION['user_role'] === 'juri'
    ) {
        return [
            'contexto' => 'backend',
            'tipo'     => 'juri',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'juri',
        ];
    }

    // ── NÍVEL 4: Votação Popular (Frontend) ────────────────────────────────────
    // Qualquer usuário logado pode votar

    // 4.1: Empreendedor (user_id sem role ou role=user)
    // Pode votar em qualquer inscrição elegível de qualquer fase
    if (!empty($_SESSION['user_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'empreendedor',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'popular',
        ];
    }

    // 4.2: Parceiro logado
    if (!empty($_SESSION['parceiro_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'parceiro',
            'id'       => (int)$_SESSION['parceiro_id'],
            'role'     => 'popular',
        ];
    }

    // 4.3: Sociedade civil logada
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

    // Nenhum contexto encontrado
    return null;
}

/**
 * Verifica se o usuário atual é admin
 *
 * @return bool
 */
function premiacao_is_admin(): bool
{
    $actor = premiacao_current_actor();
    return $actor && $actor['contexto'] === 'admin';
}

/**
 * Verifica se o usuário atual é técnico
 *
 * @return bool
 */
function premiacao_is_tecnico(): bool
{
    $actor = premiacao_current_actor();
    return $actor && $actor['tipo'] === 'tecnico';
}

/**
 * Verifica se o usuário atual é jurado
 *
 * @return bool
 */
function premiacao_is_juri(): bool
{
    $actor = premiacao_current_actor();
    return $actor && $actor['tipo'] === 'juri';
}

/**
 * Verifica se o usuário atual pode votar (votação popular)
 *
 * @return bool
 */
function premiacao_can_vote_popular(): bool
{
    $actor = premiacao_current_actor();
    return $actor && $actor['contexto'] === 'frontend' && $actor['role'] === 'popular';
}

/**
 * Verifica se o usuário atual pode votar tecnicamente
 *
 * @return bool
 */
function premiacao_can_vote_tecnico(): bool
{
    return premiacao_is_tecnico();
}

/**
 * Verifica se o usuário atual pode votar como jurado
 *
 * @return bool
 */
function premiacao_can_vote_juri(): bool
{
    return premiacao_is_juri();
}

/**
 * Exige que o usuário seja admin
 * Se não for, exibe mensagem de erro e encerra
 *
 * @return void
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
 * Se não for, exibe mensagem de erro e encerra
 *
 * @return void
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
 * Se não for, exibe mensagem de erro e encerra
 *
 * @return void
 */
function premiacao_require_juri(): void
{
    if (!premiacao_is_juri()) {
        http_response_code(403);
        die('Acesso negado. Apenas jurados podem acessar este recurso.');
    }
}

/**
 * Exige que o usuário esteja logado
 * Se não estiver, redireciona para login
 *
 * @param string $redirectTo URL para redirecionar após login
 * @return void
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