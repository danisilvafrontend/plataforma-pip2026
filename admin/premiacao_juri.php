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

$pageTitle = 'Premiação — Júri';

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || str_starts_with($dt, '0000')) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function badgeStatus(string $status): string
{
    $map = [
        'votou'      => ['#d1e7dd', '#0f5132', 'bi-check2-circle',  'Votou'],
        'pendente'   => ['#fff3cd', '#856404', 'bi-clock-history',  'Pendente'],
        'ausente'    => ['#fde8ea', '#842029', 'bi-x-circle',       'Ausente'],
    ];
    [$bg, $color, $icon, $label] = $map[$status] ?? ['#e2e3e5', '#41464b', 'bi-dash', ucfirst($status)];
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
$filtroJurado    = (int)  ($_GET['user_id']      ?? 0);

// ── Anos disponíveis ──────────────────────────────────────────────────────────
$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

// ── Premiações por ano ────────────────────────────────────────────────────────
if ($filtroAno > 0) {
    $stmtPrem = $pdo->prepare("SELECT id, nome FROM premiacoes WHERE ano = ? ORDER BY id DESC");
    $stmtPrem->execute([$filtroAno]);
} else {
    $stmtPrem = $pdo->query("SELECT id, nome FROM premiacoes ORDER BY ano DESC, id DESC");
}
$premiacoes = $stmtPrem->fetchAll();

// ── Fases com júri habilitado ─────────────────────────────────────────────────
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

// ── Categorias ────────────────────────────────────────────────────────────────
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

// ── Jurados (role = 'juri') ───────────────────────────────────────────────────
$jurados = $pdo->query("
    SELECT id, nome FROM users
    WHERE role = 'juri' AND status = 'ativo'
    ORDER BY nome
")->fetchAll();

// ── WHERE base ────────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($filtroAno > 0)       { $where[] = 'p.ano = ?';           $params[] = $filtroAno; }
if ($filtroPremiacao > 0) { $where[] = 'vj.premiacao_id = ?'; $params[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $where[] = 'vj.fase_id = ?';      $params[] = $filtroFase; }
if ($filtroCategoria > 0) { $where[] = 'vj.categoria_id = ?'; $params[] = $filtroCategoria; }
if ($filtroJurado > 0)    { $where[] = 'vj.user_id = ?';      $params[] = $filtroJurado; }
$whereSql = implode(' AND ', $where);

// ── KPIs ──────────────────────────────────────────────────────────────────────
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

$totalVotos         = (int)($kpi['total_votos']          ?? 0);
$juradosVotaram     = (int)($kpi['jurados_votaram']      ?? 0);
$negociosVotados    = (int)($kpi['negocios_votados']     ?? 0);
$categoriasComVoto  = (int)($kpi['categorias_com_voto']  ?? 0);

// ── Total de jurados cadastrados (ativo) ──────────────────────────────────────
$totalJurados = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='juri' AND status='ativo'")->fetchColumn();
$pctParticipacao = $totalJurados > 0 ? round(($juradosVotaram / $totalJurados) * 100) : 0;

// ── Painel de participação: jurado × categorias ───────────────────────────────
// Busca todos os jurados ativos e, para cada um, quantos votos deu por categoria
$painelWhere  = ['1=1'];
$painelParams = [];
if ($filtroAno > 0)       { $painelWhere[] = 'p.ano = ?';           $painelParams[] = $filtroAno; }
if ($filtroPremiacao > 0) { $painelWhere[] = 'vj.premiacao_id = ?'; $painelParams[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $painelWhere[] = 'vj.fase_id = ?';      $painelParams[] = $filtroFase; }
if ($filtroCategoria > 0) { $painelWhere[] = 'vj.categoria_id = ?'; $painelParams[] = $filtroCategoria; }
$painelWhereSql = implode(' AND ', $painelWhere);

$stmtPainel = $pdo->prepare("
    SELECT
        u.id                    AS user_id,
        u.nome                  AS jurado_nome,
        u.email                 AS jurado_email,
        pc.id                   AS categoria_id,
        pc.nome                 AS categoria_nome,
        COUNT(vj.id)            AS votos_categoria,
        MAX(vj.created_at)      AS ultimo_voto
    FROM users u
    CROSS JOIN premiacao_categorias pc
    LEFT JOIN premiacao_votos_juri vj
        ON  vj.user_id      = u.id
        AND vj.categoria_id = pc.id
    LEFT JOIN premiacoes p ON p.id = vj.premiacao_id
    WHERE u.role   = 'juri'
      AND u.status = 'ativo'
      AND ($painelWhereSql)
    GROUP BY u.id, u.nome, u.email, pc.id, pc.nome
    ORDER BY u.nome, pc.ordem
");
// Ajusta params: os params do painel não têm vj. no cross join direto
// então precisamos repetir as condições apenas onde vj existe
// Simplificando: se não há filtro de fase/premiacao, mostramos tudo
$painelParamsFinal = [];
if ($filtroAno > 0)       $painelParamsFinal[] = $filtroAno;
if ($filtroPremiacao > 0) $painelParamsFinal[] = $filtroPremiacao;
if ($filtroFase > 0)      $painelParamsFinal[] = $filtroFase;
if ($filtroCategoria > 0) $painelParamsFinal[] = $filtroCategoria;
// Os params do WHERE interno (1=1 + filtros) precisam ser duplicados
$stmtPainel->execute(array_merge($painelParamsFinal, $painelParamsFinal));

$painelRaw = $stmtPainel->fetchAll();

// Organiza em [user_id => ['nome' => ..., 'email' => ..., 'categorias' => [...]]]
$painelPorJurado = [];
$todasCategorias = [];
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
    $painelPorJurado[$uid]['categorias'][$row['categoria_id']] = [
        'votos'  => (int)$row['votos_categoria'],
        'ultimo' => $row['ultimo_voto'],
    ];
    $painelPorJurado[$uid]['total'] += (int)$row['votos_categoria'];
    if ($row['ultimo_voto'] && (!$painelPorJurado[$uid]['ultimo'] || $row['ultimo_voto'] > $painelPorJurado[$uid]['ultimo'])) {
        $painelPorJurado[$uid]['ultimo'] = $row['ultimo_voto'];
    }
    $todasCategorias[$row['categoria_id']] = $row['categoria_nome'];
}

// ── Ranking de negócios por votos de júri ────────────────────────────────────
$rankWhere  = $where;
$rankParams = $params;
$rankWhereSql = implode(' AND ', $rankWhere);

$stmtRank = $pdo->prepare("
    SELECT
        n.nome_fantasia,
        pc.nome             AS categoria_nome,
        COUNT(vj.id)        AS total_votos,
        COUNT(DISTINCT vj.user_id) AS jurados_votaram
    FROM premiacao_votos_juri vj
    INNER JOIN premiacoes           p  ON p.id  = vj.premiacao_id
    INNER JOIN premiacao_categorias pc ON pc.id = vj.categoria_id
    INNER JOIN premiacao_inscricoes pi ON pi.id = vj.inscricao_id
    INNER JOIN negocios             n  ON n.id  = pi.negocio_id
    WHERE $rankWhereSql
    GROUP BY vj.inscricao_id, n.nome_fantasia, pc.nome
    ORDER BY total_votos DESC, jurados_votaram DESC
");
$stmtRank->execute($rankParams);
$ranking = $stmtRank->fetchAll();

// ── Log de votos individuais ──────────────────────────────────────────────────
$stmtVotos = $pdo->prepare("
    SELECT
        vj.id,
        vj.created_at,
        vj.user_id,
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
");
$stmtVotos->execute($params);
$votos = $stmtVotos->fetchAll();

require_once $appBase . '/views/admin/header.php';
?>

<style>
/* ── KPI ── */
.kpi-card {
    border-radius:12px; padding:20px 22px; display:flex; flex-direction:column;
    gap:6px; box-shadow:0 1px 4px rgba(30,52,37,.08);
    background:#fff; border:1px solid #e8ede9; height:100%;
}
.kpi-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:4px; }
.kpi-valor { font-size:28px;font-weight:800;color:#1E3425;line-height:1; }
.kpi-label { font-size:12px;color:#6c7a6e;font-weight:500; }
.kpi-sub   { font-size:11px;color:#9aab9d; }
.progress-thin { height:5px;border-radius:99px;background:#e8ede9;overflow:hidden;margin-top:6px; }
.progress-thin .bar { height:100%;border-radius:99px;background:#CDDE00; }

/* ── Filtros ── */
.filtros-card { background:#fff;border:1px solid #e8ede9;border-radius:12px;padding:18px 20px;margin-bottom:24px; }

/* ── Painel de participação ── */
.painel-table th {
    font-size:11px;text-transform:uppercase;letter-spacing:.5px;
    color:#6c7a6e;font-weight:600;border-bottom:2px solid #e8ede9;white-space:nowrap;
}
.painel-table td { vertical-align:middle;font-size:13px;border-color:#f0f4f1; }
.painel-table tbody tr:hover { background:#f7faf7; }

.cell-voto-ok   { background:#d1e7dd;color:#0f5132;border-radius:8px;padding:3px 8px;font-size:12px;font-weight:700;display:inline-block;white-space:nowrap; }
.cell-voto-zero { background:#fde8ea;color:#842029;border-radius:8px;padding:3px 8px;font-size:12px;font-weight:700;display:inline-block;white-space:nowrap; }

/* ── Ranking ── */
.rank-bar-wrap { background:#e8ede9;border-radius:99px;height:6px; }
.rank-bar-fill { height:6px;border-radius:99px;background:#CDDE00; }
.rank-medal    { font-size:15px;min-width:24px;text-align:center; }

/* ── Log ── */
.log-table th { font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6c7a6e;font-weight:600;border-bottom:2px solid #e8ede9; }
.log-table td { vertical-align:middle;font-size:13px;border-color:#f0f4f1; }
.log-table tbody tr:hover { background:#f7faf7; }
</style>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação — Júri</h1>
            <p class="text-muted mb-0">Acompanhe os votos dos jurados na fase final da premiação.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border" style="font-size:12px;padding:8px 12px;">
                <i class="bi bi-person-badge me-1"></i>
                <?= $totalJurados ?> jurado<?= $totalJurados !== 1 ? 's' : '' ?> cadastrado<?= $totalJurados !== 1 ? 's' : '' ?>
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

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-hand-index-thumb-fill" style="color:#1E3425;"></i>
                </div>
                <div class="kpi-valor"><?= number_format($totalVotos) ?></div>
                <div class="kpi-label">Total de Votos do Júri</div>
                <div class="kpi-sub">votos registrados</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#e7f5ff;">
                    <i class="bi bi-person-check-fill" style="color:#084298;"></i>
                </div>
                <div class="kpi-valor"><?= $juradosVotaram ?> <span style="font-size:14px;color:#9aab9d;">/ <?= $totalJurados ?></span></div>
                <div class="kpi-label">Jurados que Votaram</div>
                <div class="progress-thin"><div class="bar" style="width:<?= $pctParticipacao ?>%;"></div></div>
                <div class="kpi-sub"><?= $pctParticipacao ?>% de participação</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fffbe6;">
                    <i class="bi bi-trophy-fill" style="color:#856404;"></i>
                </div>
                <div class="kpi-valor"><?= $negociosVotados ?></div>
                <div class="kpi-label">Negócios Votados</div>
                <div class="kpi-sub">negócios distintos</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#f3f0ff;">
                    <i class="bi bi-grid-fill" style="color:#5a3e9a;"></i>
                </div>
                <div class="kpi-valor"><?= $categoriasComVoto ?></div>
                <div class="kpi-label">Categorias com Voto</div>
                <div class="kpi-sub">de <?= count($todasCategorias) ?> categoria<?= count($todasCategorias) !== 1 ? 's' : '' ?> disponíve<?= count($todasCategorias) !== 1 ? 'is' : 'l' ?></div>
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
                                        <?php $votsoCat = $jurado['categorias'][$catId]['votos'] ?? 0; ?>
                                        <td class="text-center">
                                            <?php if ($votsoCat > 0): ?>
                                                <span class="cell-voto-ok">
                                                    <i class="bi bi-check2"></i> <?= $votsoCat ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="cell-voto-zero">
                                                    <i class="bi bi-dash"></i> 0
                                                </span>
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

    <!-- Ranking + Log -->
    <div class="row g-4">

        <!-- Ranking por negócio -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-fill me-2" style="color:#CDDE00;"></i>Placar por Negócio
                    </h5>
                    <span class="badge" style="background:#e8ede9;color:#6c7a6e;font-size:10px;">
                        votos do júri
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ranking)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block fs-2 mb-2"></i>
                            Sem votos registrados ainda.
                        </div>
                    <?php else: ?>
                        <?php $maxVotos = max(1, (int)($ranking[0]['total_votos'] ?? 1)); ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($ranking as $i => $row): ?>
                                <li class="px-3 py-2 <?= $i < count($ranking) - 1 ? 'border-bottom' : '' ?>"
                                    style="border-color:#f0f4f1;">

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
                                            <div class="fw-semibold text-truncate" style="font-size:13px;"
                                                title="<?= h($row['nome_fantasia']) ?>">
                                                <?= h($row['nome_fantasia']) ?>
                                            </div>
                                            <div class="text-muted" style="font-size:11px;"><?= h($row['categoria_nome']) ?></div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-bold" style="font-size:16px;color:#1E3425;">
                                                <?= $row['total_votos'] ?>
                                            </div>
                                            <div style="font-size:10px;color:#9aab9d;">
                                                <?= $row['jurados_votaram'] ?> jurado<?= $row['jurados_votaram'] != 1 ? 's' : '' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Barra proporcional -->
                                    <div class="rank-bar-wrap ms-5">
                                        <div class="rank-bar-fill"
                                            style="width:<?= round(($row['total_votos'] / $maxVotos) * 100) ?>%">
                                        </div>
                                    </div>

                                    <!-- Progresso: quantos dos jurados votaram neste negócio -->
                                    <div class="ms-5 mt-1" style="font-size:10px;color:#9aab9d;">
                                        <?php $pctJurado = $totalJurados > 0 ? round(($row['jurados_votaram'] / $totalJurados) * 100) : 0; ?>
                                        <?= $pctJurado ?>% dos jurados votaram neste negócio
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Log de votos -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2" style="color:#CDDE00;"></i>Log de Votos
                    </h5>
                    <span class="text-muted" style="font-size:12px;">
                        <?= count($votos) ?> registro<?= count($votos) !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($votos)): ?>
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
                                    <?php foreach ($votos as $voto): ?>
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
                                            <td class="pe-3" style="font-size:11px;white-space:nowrap;">
                                                <?= dataBr($voto['created_at']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->

</div>

<script>
document.getElementById('selectAno').addEventListener('change', function () { this.form.submit(); });
document.getElementById('selectPremiacao').addEventListener('change', function () { this.form.submit(); });
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>