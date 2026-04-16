<?php
// /premiacao/votar.php — Endpoint POST para registrar voto popular
session_start();

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
if (!isset($_SESSION['user_id'])) {
    jsonErro('Você precisa estar logado para votar.', 401);
}

$inscricaoId = (int)($_POST['inscricao_id'] ?? 0);
$faseId      = (int)($_POST['fase_id']      ?? 0);
$redirect    = $_POST['redirect'] ?? '/premiacao.php';

if ($inscricaoId <= 0 || $faseId <= 0) {
    jsonErro('Dados inválidos.');
}

// ── Valida fase: deve existir, estar em_andamento e permitir voto popular ─────
$stmtFase = $pdo->prepare("
    SELECT pf.id, pf.premiacao_id, p.data_inicio_votacao, p.data_fim_votacao
    FROM premiacao_fases pf
    INNER JOIN premiacoes p ON p.id = pf.premiacao_id
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
$ini   = strtotime($fase['data_inicio_votacao'] ?? '');
$fim   = strtotime($fase['data_fim_votacao']    ?? '');
if (!$ini || !$fim || $agora < $ini || $agora > $fim) {
    jsonErro('A votação não está aberta no momento.');
}

// ── Valida inscrição: deve ser elegível e pertencer à mesma premiação ─────────
$stmtInsc = $pdo->prepare("
    SELECT id, negocio_id FROM premiacao_inscricoes
    WHERE id = ?
      AND premiacao_id = ?
      AND status = 'elegivel'
    LIMIT 1
");
$stmtInsc->execute([$inscricaoId, $fase['premiacao_id']]);
$inscricao = $stmtInsc->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) {
    jsonErro('Inscrição não encontrada ou negócio não elegível.');
}

// ── Detecta tipo do eleitor ───────────────────────────────────────────────────
$tipoEleitor = $_SESSION['tipo_usuario'] ?? 'empreendedor';
if (!in_array($tipoEleitor, ['empreendedor', 'parceiro', 'sociedade_civil'], true)) {
    $tipoEleitor = 'empreendedor';
}

// ── Verifica voto duplicado ───────────────────────────────────────────────────
$stmtDup = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_populares
    WHERE fase_id     = ?
      AND inscricao_id = ?
      AND tipo_eleitor = ?
      AND eleitor_id  = ?
");
$stmtDup->execute([$faseId, $inscricaoId, $tipoEleitor, $_SESSION['user_id']]);
if ((int)$stmtDup->fetchColumn() > 0) {
    jsonErro('Você já votou neste negócio.');
}

// ── Registra o voto ───────────────────────────────────────────────────────────
$stmtInsert = $pdo->prepare("
    INSERT INTO premiacao_votos_populares
        (fase_id, inscricao_id, tipo_eleitor, eleitor_id, criado_em)
    VALUES (?, ?, ?, ?, NOW())
");
$stmtInsert->execute([$faseId, $inscricaoId, $tipoEleitor, $_SESSION['user_id']]);

// ── Se requisição AJAX responde JSON; se form POST normal faz redirect ─────────
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    jsonOk('Voto registrado com sucesso!');
}

$_SESSION['flash_success'] = 'Seu voto foi registrado com sucesso!';
header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
exit;