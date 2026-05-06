<?php
// /premiacao/votar.php — Endpoint POST para registrar voto popular
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

if (!$actor || $actor['contexto'] !== 'frontend') {
    jsonErro('Você precisa estar logado para votar.', 401);
}

$eleitorId   = $actor['id'];
$tipoEleitor = $actor['tipo'];

$inscricaoId = (int)($_POST['inscricao_id'] ?? 0);
$faseId      = (int)($_POST['fase_id']      ?? 0);
$redirect    = $_POST['redirect'] ?? '/premiacao.php';

if ($inscricaoId <= 0 || $faseId <= 0) {
    jsonErro('Dados inválidos.');
}

// ── Valida fase: deve existir, estar em_andamento e permitir voto popular ─────
$stmtFase = $pdo->prepare("
    SELECT pf.id, pf.premiacao_id, pf.data_inicio, pf.data_fim,
           pf.tipo_fase, pf.rodada
    FROM premiacao_fases pf
    WHERE pf.id = ?
      AND pf.permite_voto_popular = 1
      AND pf.status = 'em_andamento'
    LIMIT 1
");
$stmtFase->execute([$faseId]);
$fase = $stmtFase->fetch(PDO::FETCH_ASSOC);

if (!$fase) {
    jsonErro('Fase de votação não encontrada ou encerrada.');
}

$agora = time();
$ini   = $fase['data_inicio'] ? strtotime($fase['data_inicio']) : 0;
$fim   = $fase['data_fim']    ? strtotime($fase['data_fim'])    : 0;
if (!$ini || !$fim || $agora < $ini || $agora > $fim) {
    jsonErro('A votação não está aberta no momento.');
}

// ── Determina quais status de inscrição são válidos para esta fase ─────────────
// Fase final aceita 'finalista'; classificatórias aceitam 'elegivel' e
// 'classificada_fase_N' de acordo com a rodada; rodada 1 aceita ambos.
$tipoFase = $fase['tipo_fase'] ?? 'classificatoria';
$rodada   = (int)($fase['rodada'] ?? 1);

if ($tipoFase === 'final') {
    $statusValidos = "IN ('finalista')";
} elseif ($rodada <= 1) {
    $statusValidos = "IN ('elegivel','classificada_fase_1')";
} else {
    $statusAnterior = 'classificada_fase_' . ($rodada - 1);
    $statusAtual    = 'classificada_fase_' . $rodada;
    $statusValidos  = "IN ('{$statusAnterior}','{$statusAtual}')";
}

// ── Valida inscrição: deve ter status ativo e pertencer à mesma premiação ──────
$stmtInsc = $pdo->prepare("
    SELECT pi.id, pi.negocio_id, pi.categoria
    FROM premiacao_inscricoes pi
    WHERE pi.id = ?
      AND pi.premiacao_id = ?
      AND pi.status $statusValidos
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

// ── Verifica voto duplicado ───────────────────────────────────────────────────
$stmtDup = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_populares
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
    INSERT INTO premiacao_votos_populares
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
    jsonOk('Voto registrado com sucesso!');
}

$_SESSION['flash_success'] = 'Seu voto foi registrado com sucesso!';
header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
exit;
