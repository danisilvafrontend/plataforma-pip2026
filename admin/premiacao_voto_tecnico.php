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

// ── Filtros ───────────────────────────────────────────────────────────────────
$filtroAno       = (int)  ($_GET['ano']          ?? 0);
$filtroPremiacao = (int)  ($_GET['premiacao_id'] ?? 0);
$filtroFase      = (int)  ($_GET['fase_id']      ?? 0);
$filtroCategoria = (int)  ($_GET['categoria_id'] ?? 0);
$filtroTecnico   = (int)  ($_GET['user_id']      ?? 0);

// ── Anos ──────────────────────────────────────────────────────────────────────
$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

// ── Premiações ────────────────────────────────────────────────────────────────
if ($filtroAno > 0) {
    $stmtPrem = $pdo->prepare("SELECT id, nome FROM premiacoes WHERE ano = ? ORDER BY id DESC");
    $stmtPrem->execute([$filtroAno]);
} else {
    $stmtPrem = $pdo->query("SELECT id, nome FROM premiacoes ORDER BY ano DESC, id DESC");
}
$premiacoes = $stmtPrem->fetchAll();

// ── Fases com avaliação técnica habilitada ─────────────────────────────────────
if ($filtroPremiacao > 0) {
    $stmtF = $pdo->prepare("
        SELECT id, nome FROM premiacao_fases
        WHERE premiacao_id = ? AND permite_avaliacao_tecnica = 1
        ORDER BY ordem_exibicao
    ");
    $stmtF->execute([$filtroPremiacao]);
    $fases = $stmtF->fetchAll();
} else {
    $fases = $pdo->query("
        SELECT pf.id, CONCAT(p.nome, ' — ', pf.nome) AS nome
        FROM premiacao_fases pf
        INNER JOIN premiacoes p ON p.id = pf.premiacao_id
        WHERE pf.permite_avaliacao_tecnica = 1
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

// ── Técnicos (role = 'tecnico') ───────────────────────────────────────────────
$tecnicos = $pdo->query("
    SELECT id, nome FROM users
    WHERE role = 'tecnico' AND status = 'ativo'
    ORDER BY nome
")->fetchAll();

$totalTecnicos = count($tecnicos);

// ── WHERE base ────────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($filtroAno > 0)       { $where[] = 'p.ano = ?';            $params[] = $filtroAno; }
if ($filtroPremiacao > 0) { $where[] = 'psa.premiacao_id = ?'; $params[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $where[] = 'psa.fase_id = ?';      $params[] = $filtroFase; }
if ($filtroCategoria > 0) { $where[] = 'psa.categoria_id = ?'; $params[] = $filtroCategoria; }
if ($filtroTecnico > 0)   { $where[] = 'psa.selecionado_por_user_id = ?'; $params[] = $filtroTecnico; }
$whereSql = implode(' AND ', $where);

// ── KPIs ──────────────────────────────────────────────────────────────────────
$kpiRow = $pdo->prepare("
    SELECT
        COUNT(*)                                   AS total_selecoes,
        COUNT(DISTINCT psa.selecionado_por_user_id) AS tecnicos_que_avaliaram,
        COUNT(DISTINCT psa.inscricao_id)            AS negocios_selecionados,
        COUNT(DISTINCT psa.categoria_id)            AS categorias_com_selecao
    FROM premiacao_selecoes_admin psa
    INNER JOIN premiacoes p ON p.id = psa.premiacao_id
    WHERE psa.tipo_selecao = 'tecnica'
      AND $whereSql
");
$kpiRow->execute($params);
$kpi = $kpiRow->fetch();

$totalSelecoes       = (int)($kpi['total_selecoes']          ?? 0);
$tecnicosAvaliaram   = (int)($kpi['tecnicos_que_avaliaram']  ?? 0);
$negociosSelecionados = (int)($kpi['negocios_selecionados']  ?? 0);
$categoriasComSel    = (int)($kpi['categorias_com_selecao']  ?? 0);
$pctParticipacao     = $totalTecnicos > 0 ? round(($tecnicosAvaliaram / $totalTecnicos) * 100) : 0;

// ── Ranking: negócios mais selecionados pela bancada técnica ──────────────────
$stmtRank = $pdo->prepare("
    SELECT
        n.nome_fantasia,
        pi2.categoria                               AS categoria_inscricao,
        pc.nome                                     AS categoria_nome,
        COUNT(psa.id)                               AS total_selecoes,
        COUNT(DISTINCT psa.selecionado_por_user_id) AS tecnicos_que_escolheram,
        ROUND(
            COUNT(DISTINCT psa.selecionado_por_user_id) * 100.0
            / NULLIF((SELECT COUNT(*) FROM users WHERE role='tecnico' AND status='ativo'), 0)
        , 1)                                        AS pct_adesao,
        sn.score_geral,
        sn.score_impacto,
        sn.score_escala
    FROM premiacao_selecoes_admin psa
    INNER JOIN premiacoes           p   ON p.id   = psa.premiacao_id
    INNER JOIN premiacao_categorias pc  ON pc.id  = psa.categoria_id
    INNER JOIN premiacao_inscricoes pi2 ON pi2.id = psa.inscricao_id
    INNER JOIN negocios             n   ON n.id   = pi2.negocio_id
    LEFT  JOIN scores_negocios      sn  ON sn.negocio_id = pi2.negocio_id
    WHERE psa.tipo_selecao = 'tecnica'
      AND $whereSql
    GROUP BY psa.inscricao_id, n.nome_fantasia, pi2.categoria, pc.nome,
             sn.score_geral, sn.score_impacto, sn.score_escala
    ORDER BY tecnicos_que_escolheram DESC, sn.score_geral DESC
");
$stmtRank->execute($params);
$ranking = $stmtRank->fetchAll();

// ── Painel: técnico × categorias disponíveis ─────────────────────────────────
// Para cada técnico ativo, mostra quantas seleções fez por categoria
$categorias_painel = $pdo->query("
    SELECT pc.id, pc.nome
    FROM premiacao_categorias pc
    ORDER BY pc.ordem
")->fetchAll();

$todasCategorias = [];
foreach ($categorias_painel as $c) {
    $todasCategorias[$c['id']] = $c['nome'];
}

// Quantas seleções cada técnico fez por categoria (filtros aplicados)
$painelParams = [];
$painelWhere  = ['psa.tipo_selecao = \'tecnica\''];
if ($filtroAno > 0)       { $painelWhere[] = 'p.ano = ?';            $painelParams[] = $filtroAno; }
if ($filtroPremiacao > 0) { $painelWhere[] = 'psa.premiacao_id = ?'; $painelParams[] = $filtroPremiacao; }
if ($filtroFase > 0)      { $painelWhere[] = 'psa.fase_id = ?';      $painelParams[] = $filtroFase; }
if ($filtroCategoria > 0) { $painelWhere[] = 'psa.categoria_id = ?'; $painelParams[] = $filtroCategoria; }
$painelWhereSql = implode(' AND ', $painelWhere);

$stmtPainel = $pdo->prepare("
    SELECT
        u.id                AS user_id,
        u.nome              AS tecnico_nome,
        u.email             AS tecnico_email,
        psa.categoria_id,
        COUNT(psa.id)       AS selecoes_cat,
        MAX(psa.created_at) AS ultimo_em
    FROM users u
    LEFT JOIN premiacao_selecoes_admin psa ON psa.selecionado_por_user_id = u.id
    LEFT JOIN premiacoes p ON p.id = psa.premiacao_id
    WHERE u.role = 'tecnico' AND u.status = 'ativo'
      AND ($painelWhereSql OR psa.id IS NULL)
    GROUP BY u.id, u.nome, u.email, psa.categoria_id
    ORDER BY u.nome, psa.categoria_id
");
$stmtPainel->execute($painelParams);
$painelRaw = $stmtPainel->fetchAll();

$painelPorTecnico = [];
foreach ($painelRaw as $row) {
    $uid = $row['user_id'];
    if (!isset($painelPorTecnico[$uid])) {
        $painelPorTecnico[$uid] = [
            'nome'       => $row['tecnico_nome'],
            'email'      => $row['tecnico_email'],
            'categorias' => [],
            'total'      => 0,
            'ultimo'     => null,
        ];
    }
    if ($row['categoria_id']) {
        $painelPorTecnico[$uid]['categorias'][$row['categoria_id']] = (int)$row['selecoes_cat'];
        $painelPorTecnico[$uid]['total'] += (int)$row['selecoes_cat'];
        if ($row['ultimo_em'] && (!$painelPorTecnico[$uid]['ultimo'] || $row['ultimo_em'] > $painelPorTecnico[$uid]['ultimo'])) {
            $painelPorTecnico[$uid]['ultimo'] = $row['ultimo_em'];
        }
    }
}

// ── Log detalhado ─────────────────────────────────────────────────────────────
$stmtLog = $pdo->prepare("
    SELECT
        psa.id,
        psa.created_at,
        u.nome              AS tecnico_nome,
        u.email             AS tecnico_email,
        n.nome_fantasia,
        pc.nome             AS categoria_nome,
        pf.nome             AS fase_nome,
        pr.nome             AS premiacao_nome,
        pr.ano              AS premiacao_ano,
        psa.observacao,
        sn.score_geral,
        sn.score_impacto
    FROM premiacao_selecoes_admin psa
    INNER JOIN premiacoes           pr  ON pr.id  = psa.premiacao_id
    INNER JOIN premiacao_fases      pf  ON pf.id  = psa.fase_id
    INNER JOIN premiacao_categorias pc  ON pc.id  = psa.categoria_id
    INNER JOIN premiacao_inscricoes pi2 ON pi2.id = psa.inscricao_id
    INNER JOIN negocios             n   ON n.id   = pi2.negocio_id
    INNER JOIN users                u   ON u.id   = psa.selecionado_por_user_id
    INNER JOIN premiacoes           p   ON p.id   = psa.premiacao_id
    LEFT  JOIN scores_negocios      sn  ON sn.negocio_id = pi2.negocio_id
    WHERE psa.tipo_selecao = 'tecnica'
      AND $whereSql
    ORDER BY psa.created_at DESC
");
$stmtLog->execute($params);
$log = $stmtLog->fetchAll();

// ── Contexto da regra para a fase selecionada ─────────────────────────────────
$regraFase = null;
if ($filtroFase > 0) {
    $stmtRegra = $pdo->prepare("
        SELECT tipo_fase, max_classificados_tecnica, max_classificados_popular, max_classificados_total
        FROM premiacao_fases WHERE id = ?
    ");
    $stmtRegra->execute([$filtroFase]);
    $regraFase = $stmtRegra->fetch() ?: null;
}

require_once $appBase . '/views/admin/header.php';
?>

<style>
/* ── KPI ── */
.kpi-card {
    border-radius:12px;padding:20px 22px;display:flex;flex-direction:column;
    gap:6px;box-shadow:0 1px 4px rgba(30,52,37,.08);
    background:#fff;border:1px solid #e8ede9;height:100%;
}
.kpi-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:4px; }
.kpi-valor { font-size:28px;font-weight:800;color:#1E3425;line-height:1; }
.kpi-label { font-size:12px;color:#6c7a6e;font-weight:500; }
.kpi-sub   { font-size:11px;color:#9aab9d; }
.progress-thin { height:5px;border-radius:99px;background:#e8ede9;overflow:hidden;margin-top:6px; }
.progress-thin .bar { height:100%;border-radius:99px;background:#CDDE00; }

/* ── Alerta de regra ── */
.regra-alert {
    background:#fffbe6;border:1px solid #ffe58f;border-radius:10px;
    padding:12px 16px;font-size:12px;color:#5a4a00;margin-bottom:20px;
    display:flex;align-items:center;gap:10px;
}

/* ── Painel ── */
.painel-table th {
    font-size:11px;text-transform:uppercase;letter-spacing:.5px;
    color:#6c7a6e;font-weight:600;border-bottom:2px solid #e8ede9;white-space:nowrap;
}
.painel-table td { vertical-align:middle;font-size:13px;border-color:#f0f4f1; }
.painel-table tbody tr:hover { background:#f7faf7; }

.cell-ok   { background:#d1e7dd;color:#0f5132;border-radius:8px;padding:3px 8px;font-size:12px;font-weight:700;display:inline-block;white-space:nowrap; }
.cell-zero { background:#fde8ea;color:#842029;border-radius:8px;padding:3px 8px;font-size:12px;font-weight:700;display:inline-block;white-space:nowrap; }

/* ── Ranking ── */
.rank-bar-wrap { background:#e8ede9;border-radius:99px;height:6px; }
.rank-bar-fill { height:6px;border-radius:99px;background:#CDDE00; }
.rank-medal    { font-size:15px;min-width:24px;text-align:center; }
.score-pill {
    display:inline-flex;align-items:center;gap:3px;padding:2px 7px;
    border-radius:99px;font-size:10px;font-weight:700;
}

/* ── Log ── */
.log-table th { font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6c7a6e;font-weight:600;border-bottom:2px solid #e8ede9; }
.log-table td { vertical-align:middle;font-size:13px;border-color:#f0f4f1; }
.log-table tbody tr:hover { background:#f7faf7; }

.filtros-card { background:#fff;border:1px solid #e8ede9;border-radius:12px;padding:18px 20px;margin-bottom:24px; }
</style>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Bancada Técnica</h1>
            <p class="text-muted mb-0">
                Registro das seleções da avaliação técnica nas fases classificatórias.
                <span class="ms-2 badge" style="background:#eaf7ef;color:#1E3425;font-size:11px;">
                    Fase 1: top 10 técnica + top 10 popular → até 20 classificados
                </span>
                <span class="badge" style="background:#eaf7ef;color:#1E3425;font-size:11px;">
                    Fase 2: top 3 técnica + top 3 popular → até 6 classificados
                </span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border" style="font-size:12px;padding:8px 12px;">
                <i class="bi bi-clipboard2-check me-1"></i>
                <?= $totalTecnicos ?> técnico<?= $totalTecnicos !== 1 ? 's' : '' ?> ativo<?= $totalTecnicos !== 1 ? 's' : '' ?>
            </span>
            <a href="premiacao_bancada_tecnica.php?<?= http_build_query(array_filter([
                'ano'          => $filtroAno       ?: null,
                'premiacao_id' => $filtroPremiacao ?: null,
                'fase_id'      => $filtroFase      ?: null,
                'categoria_id' => $filtroCategoria ?: null,
                'user_id'      => $filtroTecnico   ?: null,
            ])) ?>" class="btn btn-sm btn-outline-secondary" title="Atualizar">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>

    <!-- Alerta da regra da fase selecionada -->
    <?php if ($regraFase): ?>
        <?php
            $topTec  = (int)($regraFase['max_classificados_tecnica']  ?? 10);
            $topPop  = (int)($regraFase['max_classificados_popular']  ?? 10);
            $topTotal= (int)($regraFase['max_classificados_total']    ?? 20);
            $tipoF   = $regraFase['tipo_fase'] ?? '';
        ?>
        <div class="regra-alert">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>
                <strong>Regra desta fase:</strong>
                Bancada técnica seleciona até <strong><?= $topTec ?></strong> negócios por categoria.
                Esses <?= $topTec ?> se unem aos top <?= $topPop ?> do voto popular (removendo duplicados)
                para compor até <strong><?= $topTotal ?> classificados</strong> por categoria.
                <?php if ($topTotal > ($topTec + $topPop)): ?>
                    Vagas restantes são preenchidas pelo próximo elegível não duplicado.
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-check2-square" style="color:#1E3425;"></i>
                </div>
                <div class="kpi-valor"><?= number_format($totalSelecoes) ?></div>
                <div class="kpi-label">Total de Seleções Técnicas</div>
                <div class="kpi-sub">registros na bancada</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#e7f5ff;">
                    <i class="bi bi-person-check-fill" style="color:#084298;"></i>
                </div>
                <div class="kpi-valor">
                    <?= $tecnicosAvaliaram ?>
                    <span style="font-size:14px;color:#9aab9d;">/ <?= $totalTecnicos ?></span>
                </div>
                <div class="kpi-label">Técnicos que Avaliaram</div>
                <div class="progress-thin"><div class="bar" style="width:<?= $pctParticipacao ?>%;"></div></div>
                <div class="kpi-sub"><?= $pctParticipacao ?>% de participação</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fffbe6;">
                    <i class="bi bi-building-fill-check" style="color:#856404;"></i>
                </div>
                <div class="kpi-valor"><?= $negociosSelecionados ?></div>
                <div class="kpi-label">Negócios Selecionados</div>
                <div class="kpi-sub">negócios únicos escolhidos</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#f3f0ff;">
                    <i class="bi bi-grid-fill" style="color:#5a3e9a;"></i>
                </div>
                <div class="kpi-valor"><?= $categoriasComSel ?></div>
                <div class="kpi-label">Categorias Avaliadas</div>
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
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Fase Classificatória</label>
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
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Técnico</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $filtroTecnico === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= h($t['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-search"></i>
                </button>
                <a href="premiacao_bancada_tecnica.php" class="btn btn-sm btn-outline-secondary w-100" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Painel de Participação dos Técnicos -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-person-lines-fill me-2" style="color:#CDDE00;"></i>
                Painel de Participação
            </h5>
            <span class="text-muted" style="font-size:12px;">seleções por técnico × categoria</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($painelPorTecnico)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Nenhum técnico cadastrado ou sem avaliações no período.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table painel-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Técnico</th>
                                <th>E-mail</th>
                                <?php foreach ($todasCategorias as $catId => $catNome): ?>
                                    <th class="text-center"><?= h($catNome) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Total</th>
                                <th class="pe-3">Última seleção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($painelPorTecnico as $uid => $tecnico): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= h($tecnico['nome']) ?></td>
                                    <td class="text-muted" style="font-size:12px;"><?= h($tecnico['email']) ?></td>
                                    <?php foreach ($todasCategorias as $catId => $catNome): ?>
                                        <?php $qtd = $tecnico['categorias'][$catId] ?? 0; ?>
                                        <td class="text-center">
                                            <?php if ($qtd > 0): ?>
                                                <span class="cell-ok"><i class="bi bi-check2"></i> <?= $qtd ?></span>
                                            <?php else: ?>
                                                <span class="cell-zero"><i class="bi bi-dash"></i> 0</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center">
                                        <strong style="color:<?= $tecnico['total'] > 0 ? '#1E3425' : '#842029' ?>;">
                                            <?= $tecnico['total'] ?>
                                        </strong>
                                    </td>
                                    <td class="pe-3" style="font-size:11px;white-space:nowrap;color:#6c7a6e;">
                                        <?= $tecnico['ultimo'] ? dataBr($tecnico['ultimo']) : '<span class="text-muted">—</span>' ?>
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

        <!-- Ranking de negócios -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-fill me-2" style="color:#CDDE00;"></i>
                        Mais Selecionados pela Bancada
                    </h5>
                    <span class="badge" style="background:#e8ede9;color:#6c7a6e;font-size:10px;">
                        % adesão dos técnicos
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ranking)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block fs-2 mb-2"></i>
                            Nenhuma seleção registrada.
                        </div>
                    <?php else: ?>
                        <?php $maxSel = max(1, (int)($ranking[0]['tecnicos_que_escolheram'] ?? 1)); ?>
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
                                            <div class="fw-bold" style="font-size:15px;color:#1E3425;">
                                                <?= $row['pct_adesao'] ?>%
                                            </div>
                                            <div style="font-size:10px;color:#9aab9d;">
                                                <?= $row['tecnicos_que_escolheram'] ?>/<?= $totalTecnicos ?> técnico<?= $row['tecnicos_que_escolheram'] != 1 ? 's' : '' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Barra de adesão proporcional -->
                                    <div class="rank-bar-wrap ms-5">
                                        <div class="rank-bar-fill"
                                            style="width:<?= min(100, (float)$row['pct_adesao']) ?>%">
                                        </div>
                                    </div>

                                    <!-- Scores (critério de desempate) -->
                                    <?php if ($row['score_geral'] || $row['score_impacto']): ?>
                                        <div class="ms-5 mt-1 d-flex gap-1 flex-wrap">
                                            <?php if ($row['score_geral']): ?>
                                                <span class="score-pill" style="background:#e8f4e8;color:#1E3425;">
                                                    <i class="bi bi-graph-up-arrow" style="font-size:9px;"></i>
                                                    Score <?= number_format((float)$row['score_geral'], 1) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($row['score_impacto']): ?>
                                                <span class="score-pill" style="background:#e7f5ff;color:#084298;">
                                                    Impacto <?= number_format((float)$row['score_impacto'], 1) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Log de seleções -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2" style="color:#CDDE00;"></i>
                        Log de Seleções
                    </h5>
                    <span class="text-muted" style="font-size:12px;">
                        <?= count($log) ?> registro<?= count($log) !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($log)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhuma seleção técnica registrada.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table log-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Técnico</th>
                                        <th>Negócio</th>
                                        <th>Categoria</th>
                                        <th>Fase</th>
                                        <th>Score</th>
                                        <th class="pe-3">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($log as $row): ?>
                                        <tr>
                                            <td class="ps-3 text-muted" style="font-size:11px;"><?= (int)$row['id'] ?></td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:13px;"><?= h($row['tecnico_nome']) ?></div>
                                                <div class="text-muted" style="font-size:11px;"><?= h($row['tecnico_email']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size:13px;font-weight:600;"><?= h($row['nome_fantasia']) ?></div>
                                                <div class="text-muted" style="font-size:11px;">
                                                    <?= h($row['premiacao_nome']) ?> · <?= (int)$row['premiacao_ano'] ?>
                                                </div>
                                            </td>
                                            <td style="font-size:12px;"><?= h($row['categoria_nome']) ?></td>
                                            <td style="font-size:12px;color:#6c7a6e;"><?= h($row['fase_nome']) ?></td>
                                            <td>
                                                <?php if ($row['score_geral']): ?>
                                                    <span class="score-pill" style="background:#e8f4e8;color:#1E3425;">
                                                        <?= number_format((float)$row['score_geral'], 1) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-3" style="font-size:11px;white-space:nowrap;">
                                                <?= dataBr($row['created_at']) ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($row['observacao'])): ?>
                                            <tr style="background:#fafbfa;">
                                                <td colspan="7" class="ps-5 py-1" style="font-size:11px;color:#6c7a6e;border-top:none;">
                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                    <?= h($row['observacao']) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
document.getElementById('selectAno').addEventListener('change', function () { this.form.submit(); });
document.getElementById('selectPremiacao').addEventListener('change', function () { this.form.submit(); });
document.getElementById('selectFase').addEventListener('change', function () { this.form.submit(); });
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>