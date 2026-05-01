<?php
// /premiacao/votar_juri.php — Endpoint POST para registrar voto do júri
// ================================================================
// ARQUIVO TEMPLATE - Precisa ser adaptado conforme sua autenticação
// ================================================================
// Na fase final, cada jurado vota UMA VEZ por categoria
// Registra qual inscrição/negócio o jurado escolhe para vencer aquela categoria

ob_start();
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/premiacao_auth.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../app/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'], $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro na conexão com banco de dados']);
    exit;
}

function jsonErro(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'erro' => $msg]);
    exit;
}

function jsonOk(string $msg, array $extra = []): never {
    echo json_encode(array_merge(['ok' => true, 'msg' => $msg], $extra));
    exit;
}

// ── Validações básicas ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErro('Método não permitido.', 405);
}

// ── Verificar autenticação de jurado ──────────────────────────────────────────
$actor = premiacao_current_actor();

if (!$actor || $actor['tipo'] !== 'juri') {
    jsonErro('Você precisa estar autenticado como jurado.', 401);
}

$juradoId = $actor['id'];

// ── Extrair parâmetros ────────────────────────────────────────────────────────
$inscricaoId = (int)($_POST['inscricao_id'] ?? 0);
$faseId      = (int)($_POST['fase_id']      ?? 0);
$categoriaId = (int)($_POST['categoria_id'] ?? 0);
$redirect    = $_POST['redirect'] ?? null;

// ── Whitelist de redirecionamento
$redirectValidos = ['/premiacao/painel_juri.php', '/premiacao/votacao_final.php', '/premiacao.php'];
$redirect = in_array($redirect, $redirectValidos, true) 
    ? $redirect 
    : '/premiacao/painel_juri.php';

if ($inscricaoId <= 0 || $faseId <= 0 || $categoriaId <= 0) {
    jsonErro('Dados inválidos.');
}

try {
    // ── Valida fase: deve ser a FASE FINAL e permitir voto de júri ──────────────
    $stmtFase = $pdo->prepare("
        SELECT pf.id, pf.premiacao_id, pf.data_inicio, pf.data_fim, pf.tipo_fase
        FROM premiacao_fases pf
        WHERE pf.id = ?
          AND pf.permite_juri_final = 1
          AND pf.status = 'em_andamento'
          AND pf.tipo_fase = 'final'
        LIMIT 1
    ");
    $stmtFase->execute([$faseId]);
    $fase = $stmtFase->fetch(PDO::FETCH_ASSOC);

    if (!$fase) {
        jsonErro('Fase de votação do júri não encontrada ou encerrada.');
    }

    // ── Valida janela de tempo ────────────────────────────────────────────────
    date_default_timezone_set('America/Sao_Paulo');
    $agora = new DateTime('now');
    $ini   = DateTime::createFromFormat('Y-m-d H:i:s', $fase['data_inicio']);
    $fim   = DateTime::createFromFormat('Y-m-d H:i:s', $fase['data_fim']);

    if (!$ini || !$fim || $agora < $ini || $agora > $fim) {
        jsonErro('O período de votação do júri não está aberto no momento.');
    }

    // ── Valida categoria ──────────────────────────────────────────────────────
    $stmtCat = $pdo->prepare("
        SELECT id FROM premiacao_categorias
        WHERE id = ?
          AND premiacao_id = ?
        LIMIT 1
    ");
    $stmtCat->execute([$categoriaId, $fase['premiacao_id']]);
    if (!$stmtCat->fetch()) {
        jsonErro('Categoria não encontrada.');
    }

    // ── Valida inscrição ──────────────────────────────────────────────────────
    // IMPORTANTE: A inscrição deve estar como FINALISTA (classificada nas fases anteriores)
    // Ajustar status conforme sua definição de finalista
    $stmtInsc = $pdo->prepare("
        SELECT pi.id, pi.negocio_id, pi.categoria
        FROM premiacao_inscricoes pi
        WHERE pi.id = ?
          AND pi.premiacao_id = ?
          AND pi.status IN ('classificada_fase_2', 'finalista')
        LIMIT 1
    ");
    $stmtInsc->execute([$inscricaoId, $fase['premiacao_id']]);
    $inscricao = $stmtInsc->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        jsonErro('Inscrição não encontrada ou negócio não é finalista.');
    }

    // ── Verifica voto duplicado ───────────────────────────────────────────────
    // Um jurado só vota UMA VEZ por categoria, por fase
    // A constraint usa (fase_id, categoria_id, user_id)
    // Então um jurado pode votar em inscrições diferentes da mesma categoria? Não!
    // Na fase final, cada jurado escolhe 1 vencedor por categoria
    $stmtDup = $pdo->prepare("
        SELECT COUNT(*) FROM premiacao_votos_juri
        WHERE fase_id      = ?
          AND categoria_id = ?
          AND user_id      = ?
    ");
    $stmtDup->execute([$faseId, $categoriaId, $juradoId]);
    if ((int)$stmtDup->fetchColumn() > 0) {
        jsonErro('Você já votou nesta categoria na fase final. Um voto por categoria.');
    }

    // ── Registra o voto do júri ───────────────────────────────────────────────
    // Nota: A tabela premiacao_votos_juri NÃO tem campo 'nota'
    // Apenas registra qual inscrição o jurado escolhe
    // A apuração final somará: 1 voto popular (ranking) + votos de cada jurado (1 voto por jurado)
    
    $stmtInsert = $pdo->prepare("
        INSERT INTO premiacao_votos_juri
            (premiacao_id, fase_id, categoria_id, inscricao_id, user_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmtInsert->execute([
        $fase['premiacao_id'],
        $faseId,
        $categoriaId,
        $inscricaoId,
        $juradoId
    ]);

    // ── Responde ──────────────────────────────────────────────────────────────
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        jsonOk('Seu voto foi registrado com sucesso!');
    }

    $_SESSION['flash_success'] = 'Seu voto foi registrado com sucesso!';
    header('Location: ' . $redirect);
    exit;

} catch (PDOException $e) {
    error_log('Erro ao registrar voto de júri: ' . $e->getMessage());
    jsonErro('Erro ao processar seu voto. Tente novamente mais tarde.', 500);
}