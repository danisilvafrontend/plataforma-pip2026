<?php
// /premiacao/votar_tecnico.php — Endpoint POST para registrar voto técnico
// ================================================================
// REGRAS CORRIGIDAS:
// - Limite: 10 votos por técnico, por categoria (SEMPRE, em todas as fases)
// - Fase 1: Pode votar em qualquer elegível (até 10 por categoria)
// - Fase 2: Pode votar apenas nos 20 classificados da F1 (até 10)
// - Final: Pode votar apenas nos 6 finalistas da F2 (até 10)
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

// ── Validações básicas ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErro('Método não permitido.', 405);
}

$actor = premiacao_current_actor();

// Validar que é técnico
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

// ── Valida fase: deve existir, estar em_andamento e permitir votação técnica ──
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

// ── Valida inscrição: deve ser elegível e pertencer à mesma premiação ─────────
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

// ── Validação de Classificação (Fase 2 e Final) ────────────────────────────
if ($fase['tipo_fase'] === 'classificatoria' && $fase['rodada'] == 2) {
    // Fase 2: validar contra classificados da Fase 1
    $stmtFase1 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ? AND tipo_fase = 'classificatoria' AND rodada = 1
        LIMIT 1
    ");
    $stmtFase1->execute([$fase['premiacao_id']]);
    $fase1Row = $stmtFase1->fetch(PDO::FETCH_ASSOC);

    if ($fase1Row) {
        $fase1Id = (int)$fase1Row['id'];
        
        $stmtValidaFase1 = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_classificados_fase
            WHERE fase_id = ?
              AND categoria_id = ?
              AND inscricao_id = ?
              AND status = 'classificado'
        ");
        $stmtValidaFase1->execute([$fase1Id, $categoriaId, $inscricaoId]);
        if ((int)$stmtValidaFase1->fetchColumn() === 0) {
            jsonErro('Esta inscrição não foi classificada na Fase 1. Você pode votar apenas nos 20 classificados da Fase 1.');
        }
    }
}

elseif ($fase['tipo_fase'] === 'final') {
    // Fase Final: validar contra classificados da Fase 2
    $stmtFase2 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ? AND tipo_fase = 'classificatoria' AND rodada = 2
        LIMIT 1
    ");
    $stmtFase2->execute([$fase['premiacao_id']]);
    $fase2Row = $stmtFase2->fetch(PDO::FETCH_ASSOC);

    if ($fase2Row) {
        $fase2Id = (int)$fase2Row['id'];
        
        $stmtValidaFase2 = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_classificados_fase
            WHERE fase_id = ?
              AND categoria_id = ?
              AND inscricao_id = ?
              AND status = 'classificado'
        ");
        $stmtValidaFase2->execute([$fase2Id, $categoriaId, $inscricaoId]);
        if ((int)$stmtValidaFase2->fetchColumn() === 0) {
            jsonErro('Esta inscrição não é finalista. Você pode votar apenas nos 6 finalistas da Fase 2.');
        }
    }
}

// ── VALIDAÇÃO DE LIMITE: Máximo 10 votos por técnico, por categoria (SEMPRE) ──
$stmtContaVotos = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_tecnicos
    WHERE fase_id = ?
      AND categoria_id = ?
      AND tipo_eleitor = ?
      AND eleitor_id = ?
");
$stmtContaVotos->execute([$faseId, $categoriaId, $tipoEleitor, $eleitorId]);
$votosJaFeitos = (int)$stmtContaVotos->fetchColumn();

$maxVotosPorCategoria = 10;  // SEMPRE 10, em todas as fases

if ($votosJaFeitos >= $maxVotosPorCategoria) {
    jsonErro(
        "Você já votou em $votosJaFeitos inscrições desta categoria. " .
        "O máximo permitido é 10 votos por categoria.",
        400
    );
}

// ── Verifica voto duplicado ───────────────────────────────────────────────────
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

// ── Se requisição AJAX responde JSON; se form POST normal faz redirect ────────
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    jsonOk('Voto técnico registrado com sucesso!');
}

$_SESSION['flash_success'] = 'Seu voto técnico foi registrado com sucesso!';
header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
exit;