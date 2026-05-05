<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

$appBase = dirname(__DIR__) . '/app';
$config  = require $appBase . '/config/db.php';

$dsn  = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'], $config['dbname'], $config['port'], $config['charset']);
$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || str_starts_with($dt, '0000')) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

// ── Filtros ─────────────────────────────────────────────────────────────────────
$filtroAno       = (int) ($_GET['ano']          ?? 0);
$filtroPremiacao = (int) ($_GET['premiacao_id'] ?? 0);
$filtroFase      = (int) ($_GET['fase_id']      ?? 0);
$filtroCategoria = (int) ($_GET['categoria_id'] ?? 0);
$filtroJurado    = (int) ($_GET['user_id']      ?? 0);

// Paginação do log
$logPorPagina = 20;
$logPagina    = max(1, (int)($_GET['log_pag'] ?? 1));
$logOffset    = ($logPagina - 1) * $logPorPagina;

// ── Listas para filtros ──────────────────────────────────────────────────────────
$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

if ($filtroAno > 0) {
    $stmtPrem = $pdo->prepare("SELECT id, nome FROM premiacoes WHERE ano = ? ORDER BY id DESC");
    $stmtPrem->execute([$filtroAno]);
} else {
    $stmtPrem = $pdo->query("SELECT id, nome FROM premiacoes ORDER BY ano DESC, id DESC");
}
$premiacoes = $stmtPrem->fetchAll();

if ($filtroPremiacao > 0) {
    $stmtF = $pdo->prepare("
        SELECT id, nome FROM premiacao_fases
        WHERE premiacao_id = ? AND permite_juri_final = 1
        ORDER BY ordem_exibicao
    ");
    $stmtF->execute([$filtroPremiacao]);
    $fases = $stmtF->fetchAll();
} else {
    $fases = $pdo->query("
        SELECT pf.id, CONCAT(p.nome, ' — ', pf.nome) AS nome
        FROM premiacao_fases pf
        INNER JOIN premiacoes p ON p.id = pf.premiacao_id
        WHERE pf.permite_juri_final = 1
        ORDER BY p.ano DESC, pf.ordem_exibicao
    ")->fetchAll();
}

if ($filtroPremiacao > 0) {
    $stmtC = $pdo->prepare("SELECT id, nome FROM premiacao_categorias WHERE premiacao_id = ? ORDER BY ordem");
    $stmtC->execute([$filtroPremiacao]);
    $categorias = $stmtC->fetchAll();
} else {
    $categorias = $pdo->query("
        SELECT pc.id, CONCAT(p.nome, ' — ', pc.nome) AS nome
        FROM premiacao_categorias pc
        INNER JOIN premiacoes p ON p.id = pc.premiacao_id
        ORDER BY p.ano DESC, pc.ordem
    ")->fetchAll();
}

// Jurados ativos
$jurados      = $pdo->query("
    SELECT id, nome FROM users
    WHERE role = 'juri' AND status = 'ativo'
    ORDER BY nome
")->fetchAll();
$totalJurados = count($jurados);

// ── WHERE base ────────────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($filtroAno > 0)       { $where[] = 'p.ano = ?';           $params[] = $filtroAno; }
if ($filtroPremiacao > 0) { $where[] = 'vj.premiacao_id = ?'; $params[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $where[] = 'vj.fase_id = ?';      $params[] = $filtroFase; }
if ($filtroCategoria > 0) { $where[] = 'vj.categoria_id = ?'; $params[] = $filtroCategoria; }
if ($filtroJurado > 0)    { $where[] = 'vj.user_id = ?';      $params[] = $filtroJurado; }
$whereSql = implode(' AND ', $where);

// ── KPIs ───────────────────────────────────────────────────────────────────────────
$kpiRow = $pdo->prepare("
    SELECT
        COUNT(*)                        AS total_votos,
        COUNT(DISTINCT vj.user_id)      AS jurados_votaram,
        COUNT(DISTINCT vj.inscricao_id) AS negocios_votados,
        COUNT(DISTINCT vj.categoria_id) AS categorias_com_voto
    FROM premiacao_votos_juri vj
    INNER JOIN premiacoes p ON p.id = vj.premiacao_id
    WHERE $whereSql
");
$kpiRow->execute($params);
$kpi = $kpiRow->fetch();

$totalVotos        = (int)($kpi['total_votos']         ?? 0);
$juradosVotaram    = (int)($kpi['jurados_votaram']     ?? 0);
$negociosVotados   = (int)($kpi['negocios_votados']    ?? 0);
$categoriasComVoto = (int)($kpi['categorias_com_voto'] ?? 0);
$pctParticipacao   = $totalJurados > 0 ? round(($juradosVotaram / $totalJurados) * 100) : 0;

// ── Ranking por categoria (abas) ────────────────────────────────────────────────────
$stmtCatsComVoto = $pdo->prepare("
    SELECT DISTINCT
        pc.id   AS categoria_id,
        pc.nome AS categoria_nome,
        pc.ordem
    FROM premiacao_votos_juri vj
    INNER JOIN premiacoes           p  ON p.id  = vj.premiacao_id
    INNER JOIN premiacao_categorias pc ON pc.id = vj.categoria_id
    WHERE $whereSql
    ORDER BY pc.ordem, pc.nome
");
$stmtCatsComVoto->execute($params);
$catsComVoto = $stmtCatsComVoto->fetchAll();

$rankingPorCategoria = [];
foreach ($catsComVoto as $cat) {
    $paramsRank = array_merge($params, [$cat['categoria_id']]);
    $stmtRank = $pdo->prepare("
        SELECT
            n.nome_fantasia,
            pc.nome                        AS categoria_nome,
            COUNT(vj.id)                   AS total_votos,
            COUNT(DISTINCT vj.user_id)     AS jurados_que_votaram,
            ROUND(
                COUNT(DISTINCT vj.user_id) * 100.0
                / NULLIF(
                    (SELECT COUNT(*) FROM users WHERE role = 'juri' AND status = 'ativo'), 0
                )
            , 1)                           AS pct_adesao
        FROM premiacao_votos_juri vj
        INNER JOIN premiacoes           p   ON p.id   = vj.premiacao_id
        INNER JOIN premiacao_categorias pc  ON pc.id  = vj.categoria_id
        INNER JOIN premiacao_inscricoes pi2 ON pi2.id = vj.inscricao_id
        INNER JOIN negocios             n   ON n.id   = pi2.negocio_id
        WHERE $whereSql
          AND vj.categoria_id = ?
        GROUP BY vj.inscricao_id, n.nome_fantasia, pc.nome
        ORDER BY jurados_que_votaram DESC, total_votos DESC
        LIMIT 10
    ");
    $stmtRank->execute($paramsRank);
    $rankingPorCategoria[$cat['categoria_id']] = [
        'nome'  => $cat['categoria_nome'],
        'itens' => $stmtRank->fetchAll(),
    ];
}

// ── Categorias para painel jurado × categorias ─────────────────────────────────────
if ($filtroPremiacao > 0) {
    $stmtCP = $pdo->prepare("SELECT id, nome FROM premiacao_categorias WHERE premiacao_id = ? ORDER BY ordem");
    $stmtCP->execute([$filtroPremiacao]);
    $categorias_painel = $stmtCP->fetchAll();
} else {
    $categorias_painel = $pdo->query("SELECT id, nome FROM premiacao_categorias ORDER BY ordem")->fetchAll();
}

$todasCategorias = [];
foreach ($categorias_painel as $c) {
    $todasCategorias[$c['id']] = $c['nome'];
}

// ── Painel: jurado × categorias ─────────────────────────────────────────────────────
$painelParams  = [];
$painelWhere   = ['1=1'];
if ($filtroAno > 0)       { $painelWhere[] = 'p.ano = ?';           $painelParams[] = $filtroAno; }
if ($filtroPremiacao > 0) { $painelWhere[] = 'vj.premiacao_id = ?'; $painelParams[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $painelWhere[] = 'vj.fase_id = ?';      $painelParams[] = $filtroFase; }
if ($filtroCategoria > 0) { $painelWhere[] = 'vj.categoria_id = ?'; $painelParams[] = $filtroCategoria; }
$painelWhereSql = implode(' AND ', $painelWhere);

$stmtPainel = $pdo->prepare("
    SELECT
        u.id               AS user_id,
        u.nome             AS jurado_nome,
        u.email            AS jurado_email,
        vj.categoria_id,
        COUNT(vj.id)       AS votos_categoria,
        MAX(vj.created_at) AS ultimo_voto
    FROM users u
    LEFT JOIN premiacao_votos_juri vj ON vj.user_id = u.id
    LEFT JOIN premiacoes p ON p.id = vj.premiacao_id
    WHERE u.role = 'juri' AND u.status = 'ativo'
      AND ($painelWhereSql OR vj.id IS NULL)
    GROUP BY u.id, u.nome, u.email, vj.categoria_id
    ORDER BY u.nome, vj.categoria_id
");
$stmtPainel->execute($painelParams);
$painelRaw = $stmtPainel->fetchAll();

$painelPorJurado = [];
foreach ($painelRaw as $row) {
    $uid = $row['user_id'];
    if (!isset($painelPorJurado[$uid])) {
        $painelPorJurado[$uid] = [
            'nome'       => $row['jurado_nome'],
            'email'      => $row['jurado_email'],
            'categorias' => [],
            'total'      => 0,
            'ultimo'     => null,
        ];
    }
    if ($row['categoria_id']) {
        $painelPorJurado[$uid]['categorias'][$row['categoria_id']] = (int)$row['votos_categoria'];
        $painelPorJurado[$uid]['total'] += (int)$row['votos_categoria'];
        if ($row['ultimo_voto'] && (!$painelPorJurado[$uid]['ultimo'] || $row['ultimo_voto'] > $painelPorJurado[$uid]['ultimo'])) {
            $painelPorJurado[$uid]['ultimo'] = $row['ultimo_voto'];
        }
    }
}

// ── Log — total para paginação ───────────────────────────────────────────────────────────
$stmtLogTotal = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM premiacao_votos_juri vj
    INNER JOIN premiacoes p ON p.id = vj.premiacao_id
    WHERE $whereSql
");
$stmtLogTotal->execute($params);
$logTotal    = (int)($stmtLogTotal->fetchColumn() ?? 0);
$logTotalPag = (int)ceil($logTotal / $logPorPagina);
$logPagina   = min($logPagina, max(1, $logTotalPag));

$stmtVotos = $pdo->prepare("
    SELECT
        vj.id,
        vj.created_at,
        u.nome              AS jurado_nome,
        u.email             AS jurado_email,
        n.nome_fantasia,
        pc.nome             AS categoria_nome,
        pf.nome             AS fase_nome,
        pr.nome             AS premiacao_nome,
        pr.ano              AS premiacao_ano
    FROM premiacao_votos_juri vj
    INNER JOIN premiacoes           pr ON pr.id = vj.premiacao_id
    INNER JOIN premiacao_fases      pf ON pf.id = vj.fase_id
    INNER JOIN premiacao_categorias pc ON pc.id = vj.categoria_id
    INNER JOIN premiacao_inscricoes pi ON pi.id = vj.inscricao_id
    INNER JOIN negocios             n  ON n.id  = pi.negocio_id
    INNER JOIN users                u  ON u.id  = vj.user_id
    INNER JOIN premiacoes           p  ON p.id  = vj.premiacao_id
    WHERE $whereSql
    ORDER BY vj.created_at DESC
    LIMIT $logPorPagina OFFSET $logOffset
");
$stmtVotos->execute($params);
$log = $stmtVotos->fetchAll();

// Helper: URL de paginação do log
function logPageUrl(int $pag, array $get): string
{
    $q = array_filter([
        'ano'          => $get['ano']          ?? null,
        'premiacao_id' => $get['premiacao_id'] ?? null,
        'fase_id'      => $get['fase_id']      ?? null,
        'categoria_id' => $get['categoria_id'] ?? null,
        'user_id'      => $get['user_id']      ?? null,
        'log_pag'      => $pag > 1 ? $pag : null,
    ]);
    return 'premiacao_juri.php' . ($q ? '?' . http_build_query($q) : '');
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação — Júri</h1>
            <p class="text-muted mb-0">
                Registro dos votos do júri na fase final da premiação.
                <span class="ms-2 badge" style="background:#eaf7ef;color:#1E3425;font-size:11px;">
                    Fase Final: top 1 por categoria definido pelo júri
                </span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border" style="font-size:12px;padding:8px 12px;">
                <i class="bi bi-person-badge me-1"></i>
                <?= $totalJurados ?> jurado<?= $totalJurados !== 1 ? 's' : '' ?> ativo<?= $totalJurados !== 1 ? 's' : '' ?>
            </span>
            <a href="premiacao_juri.php?<?= http_build_query(array_filter([
                'ano'          => $filtroAno       ?: null,
                'premiacao_id' => $filtroPremiacao ?: null,
                'fase_id'      => $filtroFase      ?: null,
                'categoria_id' => $filtroCategoria ?: null,
                'user_id'      => $filtroJurado    ?: null,
            ])) ?>" class="btn btn-sm btn-outline-secondary" title="Atualizar">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-hand-index-thumb-fill" style="color:#1E3425;"></i>
                </div>
                <div class="prem-kpi-valor"><?= number_format($totalVotos) ?></div>
                <div class="prem-kpi-label">Total de Votos do Júri</div>
                <div class="prem-kpi-sub">votos registrados</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#e7f5ff;">
                    <i class="bi bi-person-check-fill" style="color:#084298;"></i>
                </div>
                <div class="prem-kpi-valor">
                    <?= $juradosVotaram ?>
                    <span style="font-size:14px;color:#9aab9d;">/ <?= $totalJurados ?></span>
                </div>
                <div class="prem-kpi-label">Jurados que Votaram</div>
                <div class="progress-thin"><div class="bar" style="width:<?= $pctParticipacao ?>%;"></div></div>
                <div class="prem-kpi-sub"><?= $pctParticipacao ?>% de participação</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#fffbe6;">
                    <i class="bi bi-trophy-fill" style="color:#856404;"></i>
                </div>
                <div class="prem-kpi-valor"><?= $negociosVotados ?></div>
                <div class="prem-kpi-label">Negócios Votados</div>
                <div class="prem-kpi-sub">negócios distintos</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#f3f0ff;">
                    <i class="bi bi-grid-fill" style="color:#5a3e9a;"></i>
                </div>
                <div class="prem-kpi-valor"><?= $categoriasComVoto ?></div>
                <div class="prem-kpi-label">Categorias com Voto</div>
                <div class="prem-kpi-sub">de <?= count($todasCategorias) ?> categoria<?= count($todasCategorias) !== 1 ? 's' : '' ?> disponíve<?= count($todasCategorias) !== 1 ? 'is' : 'l' ?></div>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="filtros-card">
        <form method="GET" class="row g-2 align-items-end">

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Ano</label>
                <select name="ano" class="form-select form-select-sm" id="selectAno">
                    <option value="">Todos</option>
                    <?php foreach ($anos as $a): ?>
                        <option value="<?= (int)$a ?>" <?= $filtroAno === (int)$a ? 'selected' : '' ?>><?= (int)$a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Premiação</label>
                <select name="premiacao_id" class="form-select form-select-sm" id="selectPremiacao">
                    <option value="">Todas</option>
                    <?php foreach ($premiacoes as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>" <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                            <?= h($pr['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Fase Final</label>
                <select name="fase_id" class="form-select form-select-sm" id="selectFase">
                    <option value="">Todas</option>
                    <?php foreach ($fases as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $filtroFase === (int)$f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Categoria (log)</label>
                <select name="categoria_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $filtroCategoria === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Jurado</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($jurados as $j): ?>
                        <option value="<?= (int)$j['id'] ?>" <?= $filtroJurado === (int)$j['id'] ? 'selected' : '' ?>>
                            <?= h($j['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-search"></i>
                </button>
                <a href="premiacao_juri.php" class="btn btn-sm btn-outline-secondary w-100" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Painel de Participação dos Jurados -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-person-lines-fill me-2" style="color:#CDDE00;"></i>
                Painel de Participação
            </h5>
            <span class="text-muted" style="font-size:12px;">votos por jurado × categoria</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($painelPorJurado)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Nenhum jurado cadastrado ou sem votos no período.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table painel-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Jurado</th>
                                <th>E-mail</th>
                                <?php foreach ($todasCategorias as $catId => $catNome): ?>
                                    <th class="text-center"><?= h($catNome) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Total</th>
                                <th class="pe-3">Último voto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($painelPorJurado as $uid => $jurado): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= h($jurado['nome']) ?></td>
                                    <td class="text-muted" style="font-size:12px;"><?= h($jurado['email']) ?></td>
                                    <?php foreach ($todasCategorias as $catId => $catNome): ?>
                                        <?php $qtd = $jurado['categorias'][$catId] ?? 0; ?>
                                        <td class="text-center">
                                            <?php if ($qtd > 0): ?>
                                                <span class="cell-ok"><i class="bi bi-check2"></i> <?= $qtd ?></span>
                                            <?php else: ?>
                                                <span class="cell-zero"><i class="bi bi-dash"></i> 0</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center">
                                        <strong style="color:<?= $jurado['total'] > 0 ? '#1E3425' : '#842029' ?>;">
                                            <?= $jurado['total'] ?>
                                        </strong>
                                    </td>
                                    <td class="pe-3" style="font-size:11px;white-space:nowrap;color:#6c7a6e;">
                                        <?= $jurado['ultimo'] ? dataBr($jurado['ultimo']) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Placar por Categoria (abas) + Log -->
    <div class="row g-4">

        <!-- Ranking por categoria -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-fill me-2" style="color:#CDDE00;"></i>
                        Placar por Categoria
                    </h5>
                    <span class="badge" style="background:#e8ede9;color:#6c7a6e;font-size:10px;">top 10 por categoria</span>
                </div>

                <?php if (empty($rankingPorCategoria)): ?>
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-inbox d-block fs-2 mb-2"></i>
                        Sem votos registrados ainda.
                    </div>
                <?php else: ?>
                    <!-- Abas de categoria -->
                    <div class="card-header bg-white border-top-0 pt-0 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" id="tabsCategorias" role="tablist">
                            <?php $firstCat = true; foreach ($rankingPorCategoria as $catId => $catData): ?>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link <?= $firstCat ? 'active' : '' ?>"
                                        id="tab-cat-<?= $catId ?>-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#tab-cat-<?= $catId ?>"
                                        type="button"
                                        role="tab"
                                        style="font-size:12px;padding:6px 10px;"
                                    >
                                        <?= h($catData['nome']) ?>
                                    </button>
                                </li>
                            <?php $firstCat = false; endforeach; ?>
                        </ul>
                    </div>

                    <div class="tab-content" id="tabsCategoriasContent">
                        <?php $firstCat = true; foreach ($rankingPorCategoria as $catId => $catData): ?>
                            <div
                                class="tab-pane fade <?= $firstCat ? 'show active' : '' ?>"
                                id="tab-cat-<?= $catId ?>"
                                role="tabpanel"
                            >
                                <?php if (empty($catData['itens'])): ?>
                                    <div class="text-center py-4 text-muted" style="font-size:13px;">
                                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                                        Sem votos nesta categoria.
                                    </div>
                                <?php else: ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($catData['itens'] as $i => $row): ?>
                                            <li class="px-3 py-2 <?= $i < count($catData['itens']) - 1 ? 'border-bottom' : '' ?>" style="border-color:#f0f4f1;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="rank-medal">
                                                        <?= match($i) {
                                                            0 => '🥇',
                                                            1 => '🥈',
                                                            2 => '🥉',
                                                            default => '<span style="font-size:11px;color:#9aab9d;font-weight:700;">#' . ($i + 1) . '</span>'
                                                        } ?>
                                                    </span>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <div class="fw-semibold text-truncate" style="font-size:13px;" title="<?= h($row['nome_fantasia']) ?>">
                                                            <?= h($row['nome_fantasia']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-end flex-shrink-0">
                                                        <div class="fw-bold" style="font-size:15px;color:#1E3425;"><?= $row['pct_adesao'] ?>%</div>
                                                        <div style="font-size:10px;color:#9aab9d;">
                                                            <?= $row['jurados_que_votaram'] ?>/<?= $totalJurados ?> jurado<?= $row['jurados_que_votaram'] != 1 ? 's' : '' ?>
                                                            &middot;
                                                            <?= $row['total_votos'] ?> voto<?= $row['total_votos'] != 1 ? 's' : '' ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="rank-bar-wrap ms-5">
                                                    <div class="rank-bar-fill" style="width:<?= min(100, (float)$row['pct_adesao']) ?>%"></div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php $firstCat = false; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Log de votos com paginação -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2" style="color:#CDDE00;"></i>
                        Log de Votos do Júri
                    </h5>
                    <span class="text-muted" style="font-size:12px;">
                        <?= number_format($logTotal) ?> registro<?= $logTotal !== 1 ? 's' : '' ?>
                        <?php if ($logTotalPag > 1): ?>
                            &middot; pág. <?= $logPagina ?>/<?= $logTotalPag ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($log)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhum voto de júri encontrado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table log-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Jurado</th>
                                        <th>Negócio</th>
                                        <th>Categoria</th>
                                        <th>Fase</th>
                                        <th class="pe-3">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($log as $voto): ?>
                                        <tr>
                                            <td class="ps-3 text-muted" style="font-size:11px;"><?= (int)$voto['id'] ?></td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:13px;"><?= h($voto['jurado_nome']) ?></div>
                                                <div class="text-muted" style="font-size:11px;"><?= h($voto['jurado_email']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size:13px;font-weight:600;"><?= h($voto['nome_fantasia']) ?></div>
                                                <div class="text-muted" style="font-size:11px;"><?= h($voto['premiacao_nome']) ?> · <?= (int)$voto['premiacao_ano'] ?></div>
                                            </td>
                                            <td style="font-size:12px;"><?= h($voto['categoria_nome']) ?></td>
                                            <td style="font-size:12px;color:#6c7a6e;"><?= h($voto['fase_nome']) ?></td>
                                            <td class="pe-3" style="font-size:11px;white-space:nowrap;"><?= dataBr($voto['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação do log -->
                        <?php if ($logTotalPag > 1): ?>
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-white" style="font-size:12px;">
                                <span class="text-muted">
                                    Exibindo <?= ($logOffset + 1) ?>–<?= min($logOffset + $logPorPagina, $logTotal) ?> de <?= number_format($logTotal) ?> registros
                                </span>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 gap-1">

                                        <li class="page-item <?= $logPagina <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= logPageUrl($logPagina - 1, $_GET) ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>

                                        <?php
                                        $janela = 2;
                                        $inicio = max(1, $logPagina - $janela);
                                        $fim    = min($logTotalPag, $logPagina + $janela);
                                        if ($inicio > 1): ?>
                                            <li class="page-item"><a class="page-link" href="<?= logPageUrl(1, $_GET) ?>">1</a></li>
                                            <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($pg = $inicio; $pg <= $fim; $pg++): ?>
                                            <li class="page-item <?= $pg === $logPagina ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= logPageUrl($pg, $_GET) ?>"><?= $pg ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($fim < $logTotalPag): ?>
                                            <?php if ($fim < $logTotalPag - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="<?= logPageUrl($logTotalPag, $_GET) ?>"><?= $logTotalPag ?></a></li>
                                        <?php endif; ?>

                                        <li class="page-item <?= $logPagina >= $logTotalPag ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= logPageUrl($logPagina + 1, $_GET) ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->

</div>

<script>
document.getElementById('selectAno').addEventListener('change', function () { this.form.submit(); });
document.getElementById('selectPremiacao').addEventListener('change', function () { this.form.submit(); });
document.getElementById('selectFase').addEventListener('change', function () { this.form.submit(); });
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
