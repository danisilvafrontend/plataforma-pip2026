<?php
// /app/helpers/auth.php
declare(strict_types=1);

// Inicia sessão somente se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retorna a role atual do usuário (string)
function current_user_role(): string {
    return $_SESSION['user_role'] ?? '';
}

// Retorna o ID do usuário logado ou null
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

// Checa se é superadmin
function is_superadmin(): bool {
    return current_user_role() === 'superadmin';
}

// Checa se é admin (inclui superadmin)
function is_admin(): bool {
    $r = current_user_role();
    return $r === 'admin' || $r === 'superadmin';
}
function is_juri(): bool {
    return current_user_role() === 'juri';
}

function is_tecnica(): bool {
    return current_user_role() === 'tecnica';
}

function is_juri_ou_tecnica(): bool {
    return in_array(current_user_role(), ['juri', 'tecnica'], true);
}

function can_see_admin_shortcuts(): bool {
    return is_admin();
}

function can_vote_admin(): bool {
    return in_array(current_user_role(), ['admin', 'superadmin', 'juri', 'tecnica'], true);
}
// Checa se usuário está autenticado e tem uma role permitida.
// Não inicia a sessão aqui (já iniciada no topo).
function require_admin_login(array $allowedRoles = ['superadmin', 'admin', 'juri', 'tecnica']): void {
    if (empty($_SESSION['user_id'])) {
        // não autenticado
        header('Location: /admin-login.php');
        exit;
    }

    if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
        // role não permitida — resposta 403
        http_response_code(403);
        // pequena mensagem; em produção substitua por template de erro
        echo 'Acesso negado';
        exit;
    }
}

// Verifica se o usuário atual tem pelo menos uma das roles fornecidas
function require_role(array $roles): void {
    if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        echo 'Acesso negado';
        exit;
    }
}

