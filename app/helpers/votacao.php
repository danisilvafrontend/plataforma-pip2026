<?php
function usuarioPodeVotarPopular(array $user): bool
{
    return in_array($user['tipo_publico'] ?? '', ['empreendedor', 'parceiro', 'sociedade_civil'], true);
}

function usuarioEhJuri(array $user): bool
{
    return ($user['role'] ?? '') === 'juri';
}

function podeVotarNoNegocio(PDO $pdo, int $faseId, int $inscricaoId, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM premiacao_votos_populares
        WHERE fase_id = ? AND inscricao_id = ? AND user_id = ?
    ");
    $stmt->execute([$faseId, $inscricaoId, $userId]);
    return (int)$stmt->fetchColumn() === 0;
}

function podeVotarJuriNaCategoria(PDO $pdo, int $faseId, int $categoriaId, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM premiacao_votos_juri
        WHERE fase_id = ? AND categoria_id = ? AND user_id = ?
    ");
    $stmt->execute([$faseId, $categoriaId, $userId]);
    return (int)$stmt->fetchColumn() === 0;
}
?>