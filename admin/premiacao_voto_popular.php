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

$pageTitle = 'Premiação — Votos Populares';

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || str_starts_with($dt, '0000')) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function badgeTipoEleitor(string $tipo): string
{
    $map = [
        'empreendedor'    => ['#e0f0e9', '#1E3425', 'bi-person-badge',   'Empreendedor'],
        'parceiro'        => ['#e7f5ff', '#084298', 'bi-diagram-3-fill', 'Parceiro'],
        'sociedade_civil' => ['#fff3cd', '#856404', 'bi-people-fill',    'Soc. Civil'],
    ];
    [$bg, $color, $icon, $label] = $map[$tipo] ?? ['#e2e3e5', '#41464b', 'bi-person', ucfirst($tipo)];
    return '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;
        border-radius:999px;background:' . $bg . ';color:' . $color . ';
        font-size:11px;font-weight:700;white-space:nowrap;">
        <i class="bi ' . $icon . '"></i>' . h($label) . '</span>';
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$filtroAno       = (int)  ($_GET['ano']          ?? 0);
$filtroPremiacao = (int)  ($_GET['premiacao_id'] ?? 0);
$filtroFase      = (int)  ($_GET['fase_id']      ?? 0);
$filtroCategoria = (int)  ($_GET['categoria_id'] ?? 0);
$filtroTipo      =        trim($_GET['tipo_eleitor'] ?? '');
$filtroBusca     =        trim($_GET['busca']        ?? '');
$pagina          = max(1, (int)($_GET['pag']         ?? 1));
$porPagina       = 50;
$offset          = ($pagina - 1) * $porPagina;

// ── Anos disponíveis ──────────────────────────────────────────────────────────
$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

// ── Premiações filtradas por ano ──────────────────────────────────────────────
if ($filtroAno > 0) {
    $stmtPrem = $pdo->prepare("SELECT id, nome FROM premiacoes WHERE ano = ? ORDER BY id DESC");
    $stmtPrem->execute([$filtroAno]);
} else {
    $stmtPrem = $pdo->query("SELECT id, nome FROM premiacoes ORDER BY ano DESC, id DESC");
}
$premiacoes = $stmtPrem->fetchAll();

// ── Fases filtradas por premiação (apenas com voto popular) ───────────────────
if ($filtroPremiacao > 0) {
    $stmtF = $pdo->prepare("
        SELECT id, nome FROM premiacao_fases
        WHERE premiacao_id = ? AND permite_voto_popular = 1
        ORDER BY ordem_exibicao
    ");
    $stmtF->execute([$filtroPremiacao]);
    $fases = $stmtF->fetchAll();
} else {
    $fases = $pdo->query("
        SELECT pf.id, CONCAT(p.nome, ' — ', pf.nome) AS nome
        FROM premiacao_fases pf
        INNER JOIN premiacoes p ON p.id = pf.premiacao_id
        WHERE pf.permite_voto_popular = 1
        ORDER BY p.ano DESC, pf.ordem_exibicao
    ")->fetchAll();
}

// ── Categorias filtradas por premiação ────────────────────────────────────────
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

// ── Monta cláusula WHERE compartilhada ────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($filtroAno > 0)       { $where[] = 'p.ano = ?';           $params[] = $filtroAno; }
if ($filtroPremiacao > 0) { $where[] = 'vp.premiacao_id = ?'; $params[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $where[] = 'vp.fase_id = ?';      $params[] = $filtroFase; }
if ($filtroCategoria > 0) { $where[] = 'vp.categoria_id = ?'; $params[] = $filtroCategoria; }
if ($filtroTipo !== '')   { $where[] = 'vp.tipo_eleitor = ?';  $params[] = $filtroTipo; }
if ($filtroBusca !== '')  { $where[] = 'n.nome_fantasia LIKE ?'; $params[] = '%' . $filtroBusca . '%'; }
$whereSql = implode(' AND ', $where);

// ── KPIs (query leve sem JOIN de nome de eleitor) ─────────────────────────────
$kpiRow = $pdo->prepare("
    SELECT
        COUNT(*)                                           AS total_votos,
        COUNT(DISTINCT vp.tipo_eleitor, vp.eleitor_id)    AS eleitores_unicos,
        COUNT(DISTINCT vp.inscricao_id)                   AS negocios_votados,
        SUM(vp.tipo_eleitor = 'empreendedor')             AS votos_empreendedor,
        SUM(vp.tipo_eleitor = 'parceiro')                 AS votos_parceiro,
        SUM(vp.tipo_eleitor = 'sociedade_civil')          AS votos_sociedade
    FROM premiacao_votos_populares vp
    INNER JOIN premiacoes p           ON p.id  = vp.premiacao_id
    INNER JOIN premiacao_inscricoes   pi ON pi.id = vp.inscricao_id
    INNER JOIN negocios               n  ON n.id  = pi.negocio_id
    WHERE $whereSql
");
$kpiRow->execute($params);
$kpi = $kpiRow->fetch();

$totalVotos      = (int)($kpi['total_votos']        ?? 0);
$eleitoresUnicos = (int)($kpi['eleitores_unicos']   ?? 0);
$negociosVotados = (int)($kpi['negocios_votados']   ?? 0);
$votosEmp        = (int)($kpi['votos_empreendedor'] ?? 0);
$votosPar        = (int)($kpi['votos_parceiro']     ?? 0);
$votosSoc        = (int)($kpi['votos_sociedade']    ?? 0);
$mediaPorNegocio = $negociosVotados > 0 ? round($totalVotos / $negociosVotados, 1) : 0;

// ── Ranking Top 10 com cache de 60s ──────────────────────────────────────────
$cacheKey  = md5('ranking_' . $whereSql . implode('_', $params));
$cacheFile = sys_get_temp_dir() . '/pip_vp_rank_' . $cacheKey . '.json';
$cacheTtl  = 60;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    $rankingData = json_decode(file_get_contents($cacheFile), true);
} else {
    $stmtRank = $pdo->prepare("
        SELECT
            n.id                                        AS negocio_id,
            n.nome_fantasia,
            pc.nome                                     AS categoria_nome,
            COUNT(*)                                    AS total_votos,
            SUM(vp.tipo_eleitor = 'empreendedor')       AS v_emp,
            SUM(vp.tipo_eleitor = 'parceiro')           AS v_par,
            SUM(vp.tipo_eleitor = 'sociedade_civil')    AS v_soc
        FROM premiacao_votos_populares vp
        INNER JOIN premiacoes           p  ON p.id  = vp.premiacao_id
        INNER JOIN premiacao_categorias pc ON pc.id = vp.categoria_id
        INNER JOIN premiacao_inscricoes pi ON pi.id = vp.inscricao_id
        INNER JOIN negocios             n  ON n.id  = pi.negocio_id
        WHERE $whereSql
        GROUP BY vp.inscricao_id, n.id, n.nome_fantasia, pc.nome
        ORDER BY total_votos DESC
        LIMIT 10
    ");
    $stmtRank->execute($params);
    $rankingData = $stmtRank->fetchAll();
    file_put_contents($cacheFile, json_encode($rankingData));
}

// ── Total de registros para paginação ─────────────────────────────────────────
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_votos_populares vp
    INNER JOIN premiacoes         p  ON p.id  = vp.premiacao_id
    INNER JOIN premiacao_inscricoes pi ON pi.id = vp.inscricao_id
    INNER JOIN negocios           n  ON n.id  = pi.negocio_id
    WHERE $whereSql
");
$stmtTotal->execute($params);
$totalRegistros = (int) $stmtTotal->fetchColumn();
$totalPaginas   = (int) ceil($totalRegistros / $porPagina);
if ($pagina > $totalPaginas && $totalPaginas > 0) $pagina = $totalPaginas;

// ── Query principal paginada (50 por página) ──────────────────────────────────
$stmtVotos = $pdo->prepare("
    SELECT
        vp.id,
        vp.tipo_eleitor,
        vp.eleitor_id,
        vp.created_at,
        n.nome_fantasia,
        n.municipio,
        n.estado,
        n.id                AS negocio_id,
        pc.nome             AS categoria_nome,
        pf.nome             AS fase_nome,
        pr.nome             AS premiacao_nome,
        pr.ano              AS premiacao_ano,
        CASE vp.tipo_eleitor
            WHEN 'empreendedor'    THEN CONCAT(e.nome,   ' ', e.sobrenome)
            WHEN 'parceiro'        THEN par.nome_fantasia
            WHEN 'sociedade_civil' THEN CONCAT(sc.nome,  ' ', sc.sobrenome)
            ELSE '—'
        END AS eleitor_nome
    FROM premiacao_votos_populares vp
    INNER JOIN premiacoes            pr  ON pr.id  = vp.premiacao_id
    INNER JOIN premiacao_fases       pf  ON pf.id  = vp.fase_id
    INNER JOIN premiacao_categorias  pc  ON pc.id  = vp.categoria_id
    INNER JOIN premiacao_inscricoes  pi  ON pi.id  = vp.inscricao_id
    INNER JOIN negocios              n   ON n.id   = pi.negocio_id
    LEFT  JOIN empreendedores        e   ON e.id   = vp.eleitor_id AND vp.tipo_eleitor = 'empreendedor'
    LEFT  JOIN parceiros             par ON par.id  = vp.eleitor_id AND vp.tipo_eleitor = 'parceiro'
    LEFT  JOIN sociedade_civil       sc  ON sc.id   = vp.eleitor_id AND vp.tipo_eleitor = 'sociedade_civil'
    WHERE $whereSql
    ORDER BY vp.created_at DESC
    LIMIT $porPagina OFFSET $offset
");
$stmtVotos->execute($params);
$votos = $stmtVotos->fetchAll();

// ── QueryString base para links de paginação ──────────────────────────────────
$qsBase = http_build_query(array_filter([
    'ano'          => $filtroAno       ?: null,
    'premiacao_id' => $filtroPremiacao ?: null,
    'fase_id'      => $filtroFase      ?: null,
    'categoria_id' => $filtroCategoria ?: null,
    'tipo_eleitor' => $filtroTipo      ?: null,
    'busca'        => $filtroBusca     ?: null,
], fn($v) => $v !== null && $v !== ''));

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação — Votos Populares</h1>
            <p class="text-muted mb-0">Acompanhe em tempo real os votos registrados na premiação.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border" style="font-size:12px;padding:8px 12px;">
                <i class="bi bi-bar-chart-steps me-1"></i>
                <?= number_format($totalRegistros) ?> voto<?= $totalRegistros !== 1 ? 's' : '' ?> no total
            </span>
            <a href="?<?= $qsBase ?>&pag=1" class="btn btn-sm btn-outline-secondary" title="Atualizar">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">

        <!-- Total de votos -->
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card prem-vp">
                <div class="prem-kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-hand-thumbs-up-fill" style="color:#1E3425;"></i>
                </div>
                <div class="prem-kpi-valor"><?= number_format($totalVotos) ?></div>
                <div class="prem-kpi-label">Total de Votos</div>
                <div class="prem-kpi-sub">resultado dos filtros aplicados</div>
            </div>
        </div>

        <!-- Eleitores únicos -->
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card prem-vp">
                <div class="prem-kpi-icon" style="background:#e7f5ff;">
                    <i class="bi bi-people-fill" style="color:#084298;"></i>
                </div>
                <div class="prem-kpi-valor"><?= number_format($eleitoresUnicos) ?></div>
                <div class="prem-kpi-label">Eleitores Únicos</div>
                <div class="prem-kpi-sub">usuários distintos que votaram</div>
            </div>
        </div>

        <!-- Negócios votados -->
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card prem-vp">
                <div class="prem-kpi-icon" style="background:#fffbe6;">
                    <i class="bi bi-trophy-fill" style="color:#856404;"></i>
                </div>
                <div class="prem-kpi-valor"><?= number_format($negociosVotados) ?></div>
                <div class="prem-kpi-label">Negócios Votados</div>
                <div class="prem-kpi-sub">média de <?= number_format($mediaPorNegocio, 1) ?> votos/negócio</div>
            </div>
        </div>

        <!-- Distribuição por tipo -->
        <div class="col-6 col-md-3">
            <div class="kpi-card prem-vp">
                <div class="kpi-icon" style="background:#f3f0ff;">
                    <i class="bi bi-pie-chart-fill" style="color:#5a3e9a;"></i>
                </div>
                <div class="prem-kpi-valor" style="font-size:18px;margin-bottom:4px;">Distribuição</div>
                <div class="prem-kpi-label mb-2">por tipo de eleitor</div>
                <?php
                $tiposKpi = [
                    ['label' => 'Empreendedor', 'valor' => $votosEmp, 'cor' => '#1E3425'],
                    ['label' => 'Parceiro',     'valor' => $votosPar, 'cor' => '#084298'],
                    ['label' => 'Soc. Civil',   'valor' => $votosSoc, 'cor' => '#CDDE00'],
                ];
                foreach ($tiposKpi as $t):
                    $pct = $totalVotos > 0 ? round(($t['valor'] / $totalVotos) * 100) : 0;
                ?>
                    <div class="dist-item mb-1">
                        <span class="dist-dot" style="background:<?= $t['cor'] ?>;"></span>
                        <span class="text-muted" style="min-width:85px;"><?= $t['label'] ?></span>
                        <div class="dist-bar-wrap">
                            <div class="dist-bar-fill" style="width:<?= $pct ?>%;background:<?= $t['cor'] ?>;"></div>
                        </div>
                        <span class="fw-bold" style="min-width:32px;text-align:right;"><?= number_format($t['valor']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="filtros-card">
        <form method="GET" id="formFiltros" class="row g-2 align-items-end">
            <input type="hidden" name="pag" value="1">

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Ano</label>
                <select name="ano" class="form-select form-select-sm" id="selectAno">
                    <option value="">Todos</option>
                    <?php foreach ($anos as $a): ?>
                        <option value="<?= (int)$a ?>" <?= $filtroAno === (int)$a ? 'selected' : '' ?>>
                            <?= (int)$a ?>
                        </option>
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
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Fase</label>
                <select name="fase_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($fases as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $filtroFase === (int)$f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Categoria</label>
                <select name="categoria_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $filtroCategoria === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Eleitor</label>
                <select name="tipo_eleitor" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="empreendedor"    <?= $filtroTipo === 'empreendedor'    ? 'selected' : '' ?>>Empreendedor</option>
                    <option value="parceiro"        <?= $filtroTipo === 'parceiro'        ? 'selected' : '' ?>>Parceiro</option>
                    <option value="sociedade_civil" <?= $filtroTipo === 'sociedade_civil' ? 'selected' : '' ?>>Soc. Civil</option>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Negócio</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                    placeholder="Nome..." value="<?= h($filtroBusca) ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-search"></i>
                </button>
                <a href="premiacao_voto_popular.php" class="btn btn-sm btn-outline-secondary w-100" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Ranking + Tabela -->
    <div class="row g-4">

        <!-- Ranking Top 10 -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-fill me-2" style="color:#CDDE00;"></i>Top 10 Negócios
                    </h5>
                    <span class="badge" style="background:#e8ede9;color:#6c7a6e;font-size:10px;">
                        cache 60s
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($rankingData)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block fs-2 mb-2"></i>
                            Sem votos registrados ainda.
                        </div>
                    <?php else: ?>
                        <?php $maxVotos = max(1, (int)($rankingData[0]['total_votos'] ?? 1)); ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($rankingData as $i => $row): ?>
                                <li class="px-3 py-2 <?= $i < count($rankingData) - 1 ? 'border-bottom' : '' ?>"
                                    style="border-color:#f0f4f1;">

                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rank-medal">
                                            <?= match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '<span style="font-size:11px;color:#9aab9d;font-weight:700;">#'.($i+1).'</span>' } ?>
                                        </span>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-semibold text-truncate" style="font-size:13px;"
                                                title="<?= h($row['nome_fantasia']) ?>">
                                                <?= h($row['nome_fantasia']) ?>
                                            </div>
                                            <div class="text-muted" style="font-size:11px;"><?= h($row['categoria_nome']) ?></div>
                                        </div>
                                        <span class="fw-bold" style="font-size:15px;color:#1E3425;flex-shrink:0;">
                                            <?= number_format((int)$row['total_votos']) ?>
                                        </span>
                                    </div>

                                    <!-- barra proporcional -->
                                    <div class="rank-bar-wrap ms-5 mb-1">
                                        <div class="rank-bar-fill"
                                            style="width:<?= round(($row['total_votos'] / $maxVotos) * 100) ?>%"></div>
                                    </div>

                                    <!-- breakdown por tipo -->
                                    <div class="d-flex gap-3 ms-5" style="font-size:10px;color:#9aab9d;">
                                        <span title="Empreendedores">
                                            <i class="bi bi-person-badge"></i> <?= number_format((int)$row['v_emp']) ?>
                                        </span>
                                        <span title="Parceiros">
                                            <i class="bi bi-diagram-3-fill"></i> <?= number_format((int)$row['v_par']) ?>
                                        </span>
                                        <span title="Sociedade Civil">
                                            <i class="bi bi-people-fill"></i> <?= number_format((int)$row['v_soc']) ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabela paginada -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">

                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2" style="color:#CDDE00;"></i>Votos Registrados
                    </h5>
                    <span class="text-muted" style="font-size:12px;">
                        <?php if ($totalRegistros > 0): ?>
                            <?= number_format(($pagina - 1) * $porPagina + 1) ?>–<?= number_format(min($pagina * $porPagina, $totalRegistros)) ?>
                            de <?= number_format($totalRegistros) ?>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($votos)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhum voto encontrado com os filtros aplicados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table votos-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Negócio</th>
                                        <th>Categoria / Fase</th>
                                        <th>Eleitor</th>
                                        <th>Tipo</th>
                                        <th class="pe-3">Data do Voto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($votos as $voto): ?>
                                        <tr>
                                            <td class="ps-3 text-muted" style="font-size:11px;">
                                                <?= number_format((int)$voto['id']) ?>
                                            </td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:13px;">
                                                    <?= h($voto['nome_fantasia']) ?>
                                                </div>
                                                <?php if ($voto['municipio'] || $voto['estado']): ?>
                                                    <div class="text-muted" style="font-size:11px;">
                                                        <i class="bi bi-geo-alt"></i>
                                                        <?= h(trim(($voto['municipio'] ?? '') . '/' . ($voto['estado'] ?? ''), '/')) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-size:12px;font-weight:600;">
                                                    <?= h($voto['categoria_nome']) ?>
                                                </div>
                                                <div class="text-muted" style="font-size:11px;">
                                                    <?= h($voto['fase_nome']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size:12px;">
                                                    <?= h($voto['eleitor_nome'] ?: '—') ?>
                                                </div>
                                                <div class="text-muted" style="font-size:11px;">
                                                    ID <?= (int)$voto['eleitor_id'] ?>
                                                </div>
                                            </td>
                                            <td><?= badgeTipoEleitor($voto['tipo_eleitor']) ?></td>
                                            <td class="pe-3" style="font-size:11px;white-space:nowrap;">
                                                <?= dataBr($voto['created_at']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($totalPaginas > 1): ?>
                            <div class="paginacao-wrap">
                                <span>
                                    Página <strong><?= $pagina ?></strong> de <strong><?= $totalPaginas ?></strong>
                                    &nbsp;·&nbsp; <?= number_format($totalRegistros) ?> votos
                                </span>
                                <div class="paginacao-nums">

                                    <?php if ($pagina > 1): ?>
                                        <a href="?<?= $qsBase ?>&pag=1" title="Primeira">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                        <a href="?<?= $qsBase ?>&pag=<?= $pagina - 1 ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Janela de páginas: mostra até 5 ao redor da atual
                                    $janela = 2;
                                    $ini    = max(1, $pagina - $janela);
                                    $fim    = min($totalPaginas, $pagina + $janela);

                                    if ($ini > 1) {
                                        echo '<a href="?' . $qsBase . '&pag=1">1</a>';
                                        if ($ini > 2) echo '<span class="reticencias">…</span>';
                                    }

                                    for ($p = $ini; $p <= $fim; $p++):
                                        if ($p === $pagina):
                                            echo '<span>' . $p . '</span>';
                                        else:
                                            echo '<a href="?' . $qsBase . '&pag=' . $p . '">' . $p . '</a>';
                                        endif;
                                    endfor;

                                    if ($fim < $totalPaginas) {
                                        if ($fim < $totalPaginas - 1) echo '<span class="reticencias">…</span>';
                                        echo '<a href="?' . $qsBase . '&pag=' . $totalPaginas . '">' . $totalPaginas . '</a>';
                                    }
                                    ?>

                                    <?php if ($pagina < $totalPaginas): ?>
                                        <a href="?<?= $qsBase ?>&pag=<?= $pagina + 1 ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                        <a href="?<?= $qsBase ?>&pag=<?= $totalPaginas ?>" title="Última">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->

</div>


<script>
// Auto-submit ao mudar Ano ou Premiação para recarregar os selects dependentes
document.getElementById('selectAno').addEventListener('change', function () {
    this.form.submit();
});
document.getElementById('selectPremiacao').addEventListener('change', function () {
    this.form.submit();
});
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>