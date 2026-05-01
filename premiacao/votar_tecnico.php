<?php
// /premiacao/votar_tecnico.php — Endpoint POST para registrar voto técnico
// ================================================================
// REGRAS:
// - Apenas usuários com role = 'tecnica' podem votar
// - Limite: 10 votos por técnico, por categoria (SEMPRE, em todas as fases)
// - Fase 1: Pode votar em qualquer elegível (até 10 por categoria)
// - Fase 2: Pode votar apenas nos classificados da F1 (até 10)
// - Final: Pode votar apenas nos classificados da F2 (até 10)
// - Tabela de classificados: premiacao_classificados (fase_id, categoria_id, negocio_id)
// ================================================================

ob_start();
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/premiacao_auth.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function jsonErro(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'erro' => $msg]);
    exit;
}

function jsonOk(string $msg, array $extra = []): never {
    echo json_encode(array_merge(['ok' => true, 'msg' => $msg], $extra));
    exit;
}

// ── Método ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErro('Método não permitido.', 405);
}

// ── Autenticação: somente role = tecnica ─────────────────────────────────────
$actor = premiacao_current_actor();

if (!$actor || $actor['tipo'] !== 'tecnico') {
    jsonErro('Você precisa estar logado como técnico para votar.', 401);
}

$eleitorId   = $actor['id'];
$tipoEleitor = 'tecnico';

$inscricaoId = (int)($_POST['inscricao_id'] ?? 0);
$faseId      = (int)($_POST['fase_id']      ?? 0);
$redirect    = $_POST['redirect'] ?? '/premiacao.php';

if ($inscricaoId <= 0 || $faseId <= 0) {
    jsonErro('Dados inválidos.');
}

// ── Valida fase: deve existir, estar em_andamento e permitir avaliação técnica ─
$stmtFase = $pdo->prepare("
    SELECT pf.id, pf.premiacao_id, pf.data_inicio, pf.data_fim,
           pf.tipo_fase, pf.rodada
    FROM premiacao_fases pf
    WHERE pf.id = ?
      AND pf.permite_avaliacao_tecnica = 1
      AND pf.status = 'em_andamento'
    LIMIT 1
");
$stmtFase->execute([$faseId]);
$fase = $stmtFase->fetch(PDO::FETCH_ASSOC);

if (!$fase) {
    jsonErro('Fase de votação técnica não encontrada ou encerrada.');
}

$agora = time();
$ini   = $fase['data_inicio'] ? strtotime($fase['data_inicio']) : 0;
$fim   = $fase['data_fim']    ? strtotime($fase['data_fim'])    : 0;
if (!$ini || !$fim || $agora < $ini || $agora > $fim) {
    jsonErro('A votação técnica não está aberta no momento.');
}

// ── Valida inscrição: elegível e pertencente à mesma premiação ────────────────
$stmtInsc = $pdo->prepare("
    SELECT pi.id, pi.negocio_id, pi.categoria
    FROM premiacao_inscricoes pi
    WHERE pi.id = ?
      AND pi.premiacao_id = ?
      AND pi.status = 'elegivel'
    LIMIT 1
");
$stmtInsc->execute([$inscricaoId, $fase['premiacao_id']]);
$inscricao = $stmtInsc->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) {
    jsonErro('Inscrição não encontrada ou negócio não elegível.');
}

$negocioId = (int)$inscricao['negocio_id'];

// ── Busca categoria_id a partir do nome da categoria na inscrição ─────────────
$stmtCat = $pdo->prepare("
    SELECT id FROM premiacao_categorias
    WHERE premiacao_id = ?
      AND nome = ?
    LIMIT 1
");
$stmtCat->execute([$fase['premiacao_id'], $inscricao['categoria']]);
$categoriaRow = $stmtCat->fetch(PDO::FETCH_ASSOC);

if (!$categoriaRow) {
    jsonErro('Categoria da inscrição não encontrada na premiação.');
}

$categoriaId = (int)$categoriaRow['id'];

// ── Validação de Classificação via premiacao_classificados ────────────────────
// Fase 2: só pode votar nos classificados da Fase 1
if ($fase['tipo_fase'] === 'classificatoria' && (int)$fase['rodada'] === 2) {

    $stmtFase1 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ?
          AND tipo_fase = 'classificatoria'
          AND rodada = 1
        LIMIT 1
    ");
    $stmtFase1->execute([$fase['premiacao_id']]);
    $fase1Row = $stmtFase1->fetch(PDO::FETCH_ASSOC);

    if ($fase1Row) {
        $stmtValida = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_classificados
            WHERE fase_id     = ?
              AND categoria_id = ?
              AND negocio_id   = ?
        ");
        $stmtValida->execute([(int)$fase1Row['id'], $categoriaId, $negocioId]);
        if ((int)$stmtValida->fetchColumn() === 0) {
            jsonErro('Este negócio não foi classificado na Fase 1. Você pode votar apenas nos classificados da Fase 1.');
        }
    }

// Fase Final: só pode votar nos classificados da Fase 2
} elseif ($fase['tipo_fase'] === 'final') {

    $stmtFase2 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ?
          AND tipo_fase = 'classificatoria'
          AND rodada = 2
        LIMIT 1
    ");
    $stmtFase2->execute([$fase['premiacao_id']]);
    $fase2Row = $stmtFase2->fetch(PDO::FETCH_ASSOC);

    if ($fase2Row) {
        $stmtValida = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_classificados
            WHERE fase_id     = ?
              AND categoria_id = ?
              AND negocio_id   = ?
        ");
        $stmtValida->execute([(int)$fase2Row['id'], $categoriaId, $negocioId]);
        if ((int)$stmtValida->fetchColumn() === 0) {
            jsonErro('Este negócio não é finalista. Você pode votar apenas nos 6 finalistas da Fase 2.');
        }
    }
}

// ── Limite: máximo 10 votos por técnico, por categoria (sempre) ───────────────
$stmtContaVotos = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_tecnicos
    WHERE fase_id     = ?
      AND categoria_id = ?
      AND tipo_eleitor = ?
      AND eleitor_id   = ?
");
$stmtContaVotos->execute([$faseId, $categoriaId, $tipoEleitor, $eleitorId]);
$votosJaFeitos = (int)$stmtContaVotos->fetchColumn();

$maxVotosPorCategoria = 10;

if ($votosJaFeitos >= $maxVotosPorCategoria) {
    jsonErro(
        "Você já votou em {$votosJaFeitos} inscrições desta categoria. " .
        "O máximo permitido é 10 votos por categoria.",
        400
    );
}

// ── Voto duplicado ────────────────────────────────────────────────────────────
$stmtDup = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_tecnicos
    WHERE fase_id      = ?
      AND inscricao_id = ?
      AND tipo_eleitor = ?
      AND eleitor_id   = ?
");
$stmtDup->execute([$faseId, $inscricaoId, $tipoEleitor, $eleitorId]);
if ((int)$stmtDup->fetchColumn() > 0) {
    jsonErro('Você já votou neste negócio.');
}

// ── Registra o voto ───────────────────────────────────────────────────────────
$stmtInsert = $pdo->prepare("
    INSERT INTO premiacao_votos_tecnicos
        (premiacao_id, fase_id, categoria_id, inscricao_id, tipo_eleitor, eleitor_id, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmtInsert->execute([
    $fase['premiacao_id'],
    $faseId,
    $categoriaId,
    $inscricaoId,
    $tipoEleitor,
    $eleitorId
]);

// ── Responde: AJAX → JSON, form normal → redirect ────────────────────────────
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    jsonOk('Voto técnico registrado com sucesso!');
}

$_SESSION['flash_success'] = 'Seu voto técnico foi registrado com sucesso!';
header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
exit;
