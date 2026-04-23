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
    die('Erro na conexão: ' . $e->getMessage());
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function dataBr(?string $d): string
{
    if (empty($d) || str_starts_with($d, '0000')) return '—';
    return date('d/m/Y \à\s H:i', strtotime($d));
}

// ── Ação: publicar / despublicar resultado ────────────────────────────────────
$msg = '';
$msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $resultadoId = (int)($_POST['resultado_id'] ?? 0);
    $action      = $_POST['action'];

    if ($resultadoId > 0) {
        try {
            if ($action === 'publicar') {
                $pdo->prepare("
                    UPDATE premiacao_resultados_finais
                    SET publicar_resultado = 1, publicado_em = NOW()
                    WHERE id = ?
                ")->execute([$resultadoId]);
                $msg = 'Resultado publicado com sucesso!';
            } elseif ($action === 'despublicar') {
                $pdo->prepare("
                    UPDATE premiacao_resultados_finais
                    SET publicar_resultado = 0, publicado_em = NULL
                    WHERE id = ?
                ")->execute([$resultadoId]);
                $msg = 'Resultado despublicado.';
                $msgType = 'warning';
            } elseif ($action === 'marcar_vencedor') {
                // Desmarca outros vencedores da mesma categoria/premiação antes
                $pdo->prepare("
                    UPDATE premiacao_resultados_finais
                    SET vencedor = 0
                    WHERE premiacao_id = (SELECT premiacao_id FROM premiacao_resultados_finais WHERE id = ?)
                      AND categoria_id = (SELECT categoria_id FROM premiacao_resultados_finais WHERE id = ?)
                      AND id != ?
                ")->execute([$resultadoId, $resultadoId, $resultadoId]);
                $pdo->prepare("
                    UPDATE premiacao_resultados_finais SET vencedor = 1 WHERE id = ?
                ")->execute([$resultadoId]);
                $msg = 'Vencedor marcado com sucesso!';
            } elseif ($action === 'desmarcar_vencedor') {
                $pdo->prepare("
                    UPDATE premiacao_resultados_finais SET vencedor = 0 WHERE id = ?
                ")->execute([$resultadoId]);
                $msg = 'Vencedor desmarcado.';
                $msgType = 'warning';
            }
        } catch (PDOException $e) {
            $msg = 'Erro: ' . $e->getMessage();
            $msgType = 'danger';
        }
    }

    // Redireciona para evitar reenvio de formulário
    $qs = http_build_query(array_filter([
        'premiacao_id' => $_POST['premiacao_id'] ?? '',
        'categoria_id' => $_POST['categoria_id_filtro'] ?? '',
        '_msg'         => urlencode($msg),
        '_tipo'        => $msgType,
    ]));
    header("Location: premiacao_resultados.php?$qs");
    exit;
}

// Mensagem via redirect
if (!empty($_GET['_msg'])) {
    $msg = urldecode($_GET['_msg']);
    $msgType = $_GET['_tipo'] ?? 'success';
}

// ── Filtros ────────────────────────────────────────────────────────────────────
$filtroPremiacao = (int)($_GET['premiacao_id'] ?? 0);
$filtroCategoria = (int)($_GET['categoria_id'] ?? 0);

// ── Listas de filtro ──────────────────────────────────────────────────────────
$premiacoes = $pdo->query("
    SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC, id DESC
")->fetchAll();

$categorias = [];
if ($filtroPremiacao > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome FROM premiacao_categorias
        WHERE premiacao_id = ? ORDER BY ordem
    ");
    $stmt->execute([$filtroPremiacao]);
    $categorias = $stmt->fetchAll();
} else {
    $categorias = $pdo->query("
        SELECT pc.id, CONCAT(p.nome,' — ',pc.nome) AS nome
        FROM premiacao_categorias pc
        INNER JOIN premiacoes p ON p.id = pc.premiacao_id
        ORDER BY p.ano DESC, pc.ordem
    ")->fetchAll();
}

// ── KPIs globais ──────────────────────────────────────────────────────────────
$kpiWhere  = ['1=1'];
$kpiParams = [];
if ($filtroPremiacao > 0) { $kpiWhere[] = 'prf.premiacao_id = ?'; $kpiParams[] = $filtroPremiacao; }
if ($filtroCategoria > 0) { $kpiWhere[] = 'prf.categoria_id = ?'; $kpiParams[] = $filtroCategoria; }
$kpiSql = implode(' AND ', $kpiWhere);

$kpi = $pdo->prepare("
    SELECT
        COUNT(*)                                     AS total_registros,
        SUM(prf.vencedor)                            AS total_vencedores,
        SUM(prf.publicar_resultado)                  AS total_publicados,
        COUNT(*) - SUM(prf.publicar_resultado)       AS total_pendentes
    FROM premiacao_resultados_finais prf
    WHERE $kpiSql
");
$kpi->execute($kpiParams);
$kpi = $kpi->fetch();

// ── Dados principais agrupados por categoria ──────────────────────────────────
$dataWhere  = ['1=1'];
$dataParams = [];
if ($filtroPremiacao > 0) { $dataWhere[] = 'prf.premiacao_id = ?'; $dataParams[] = $filtroPremiacao; }
if ($filtroCategoria > 0) { $dataWhere[] = 'prf.categoria_id = ?'; $dataParams[] = $filtroCategoria; }
$dataSql = implode(' AND ', $dataWhere);

$stmtData = $pdo->prepare("
    SELECT
        prf.id                          AS resultado_id,
        prf.premiacao_id,
        prf.vencedor,
        prf.publicar_resultado,
        prf.publicado_em,
        prf.pontos_juri,
        prf.ponto_voto_popular,
        prf.total_pontos,
        prf.created_at,
        prf.updated_at,
        pc.id                           AS categoria_id,
        pc.nome                         AS categoria_nome,
        pc.ordem                        AS categoria_ordem,
        p.id                            AS premiacao_id2,
        p.nome                          AS premiacao_nome,
        p.ano                           AS premiacao_ano,
        n.nome_fantasia,
        n.municipio,
        n.estado,
        na.logo_negocio,
        na.frase_negocio,
        pi2.categoria                   AS categoria_inscricao,
        pi2.status                      AS status_inscricao,
        e.nome                          AS empreendedor_nome,
        e.email                         AS empreendedor_email,
        -- votos populares da fase final (coluna votos_populares em premiacao_resultados_fase)
        COALESCE(
            (SELECT SUM(prf2.votos_populares)
             FROM premiacao_resultados_fase prf2
             INNER JOIN premiacao_fases pf2 ON pf2.id = prf2.fase_id
             WHERE prf2.inscricao_id = prf.inscricao_id
               AND prf2.premiacao_id = prf.premiacao_id
               AND pf2.tipo_fase = 'final'
             LIMIT 1), 0
        )                               AS votos_populares_final,
        -- total de votos do júri
        (SELECT COUNT(*)
         FROM premiacao_votos_juri vj
         WHERE vj.inscricao_id = prf.inscricao_id
           AND vj.premiacao_id = prf.premiacao_id
        )                               AS total_votos_juri,
        -- posição na última fase classificatória ou final
        (SELECT prf3.classificacao
         FROM premiacao_resultados_fase prf3
         INNER JOIN premiacao_fases pf3 ON pf3.id = prf3.fase_id
         WHERE prf3.inscricao_id = prf.inscricao_id
           AND prf3.premiacao_id = prf.premiacao_id
           AND pf3.tipo_fase IN ('classificatoria', 'final')
         ORDER BY pf3.ordem_exibicao DESC
         LIMIT 1
        )                               AS ultima_classificacao,
        sn.score_geral,
        sn.score_impacto,
        sn.score_escala
    FROM premiacao_resultados_finais prf
    INNER JOIN premiacao_categorias  pc  ON pc.id  = prf.categoria_id
    INNER JOIN premiacoes            p   ON p.id   = prf.premiacao_id
    INNER JOIN premiacao_inscricoes  pi2 ON pi2.id = prf.inscricao_id
    INNER JOIN negocios              n   ON n.id   = pi2.negocio_id
    INNER JOIN empreendedores        e   ON e.id   = pi2.empreendedor_id
    LEFT  JOIN negocio_apresentacao  na  ON na.negocio_id = n.id
    LEFT  JOIN scores_negocios       sn  ON sn.negocio_id = n.id
    WHERE $dataSql
    ORDER BY pc.ordem ASC, prf.total_pontos DESC, prf.pontos_juri DESC
");
$stmtData->execute($dataParams);
$rows = $stmtData->fetchAll();

// Agrupa por categoria
$porCategoria = [];
foreach ($rows as $row) {
    $catId = $row['categoria_id'];
    if (!isset($porCategoria[$catId])) {
        $porCategoria[$catId] = [
            'nome'   => $row['categoria_nome'],
            'ordem'  => $row['categoria_ordem'],
            'itens'  => [],
        ];
    }
    $porCategoria[$catId]['itens'][] = $row;
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Resultados da Premiação</h1>
            <p class="text-muted mb-0">
                Visualize, defina os vencedores e controle a publicação do resultado por categoria.
            </p>
        </div>
    </div>

    <!-- Alerta de feedback -->
    <?php if ($msg): ?>
        <div class="alert alert-<?= h($msgType) ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : ($msgType === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill') ?>"></i>
            <?= h($msg) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-trophy-fill" style="color:#1E3425;"></i>
                </div>
                <div class="prem-kpi-valor"><?= (int)($kpi['total_vencedores'] ?? 0) ?></div>
                <div class="prem-kpi-label">Vencedores Definidos</div>
                <div class="prem-kpi-sub">de <?= (int)($kpi['total_registros'] ?? 0) ?> finalistas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#e8f5e9;">
                    <i class="bi bi-megaphone-fill" style="color:#198754;"></i>
                </div>
                <div class="prem-kpi-valor"><?= (int)($kpi['total_publicados'] ?? 0) ?></div>
                <div class="prem-kpi-label">Resultados Publicados</div>
                <div class="prem-kpi-sub">visíveis para o público</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#fff3cd;">
                    <i class="bi bi-hourglass-split" style="color:#856404;"></i>
                </div>
                <div class="prem-kpi-valor"><?= (int)($kpi['total_pendentes'] ?? 0) ?></div>
                <div class="prem-kpi-label">Aguardando Publicação</div>
                <div class="prem-kpi-sub">resultados não divulgados</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prem-kpi-card">
                <div class="prem-kpi-icon" style="background:#f3f0ff;">
                    <i class="bi bi-grid-fill" style="color:#5a3e9a;"></i>
                </div>
                <div class="prem-kpi-valor"><?= count($porCategoria) ?></div>
                <div class="prem-kpi-label">Categorias com Resultado</div>
                <div class="prem-kpi-sub">neste filtro</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Premiação</label>
                <select name="premiacao_id" class="form-select form-select-sm" id="selectPremiacao">
                    <option value="">Todas</option>
                    <?php foreach ($premiacoes as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>"
                            <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                            <?= h($pr['nome']) ?> (<?= (int)$pr['ano'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Categoria</label>
                <select name="categoria_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"
                            <?= $filtroCategoria === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
                <a href="premiacao_resultados.php" class="btn btn-sm btn-outline-secondary" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Resultados por categoria -->
    <?php if (empty($porCategoria)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted mb-1 fs-5">Nenhum resultado final registrado.</p>
            <p class="text-muted" style="font-size:13px;">
                Os resultados aparecem aqui após a apuração da fase final da premiação.
            </p>
        </div>
    <?php else: ?>

        <?php foreach ($porCategoria as $catId => $categoria): ?>
            <div class="mb-4">

                <!-- Header da categoria -->
                <div class="cat-header">
                    <i class="bi bi-award-fill fs-4"></i>
                    <span class="cat-nome"><?= h($categoria['nome']) ?></span>
                    <span class="cat-badge">
                        <?= count($categoria['itens']) ?> finalista<?= count($categoria['itens']) !== 1 ? 's' : '' ?>
                    </span>
                    <?php
                        $vencedoresNoCat = array_filter($categoria['itens'], fn($i) => (int)$i['vencedor'] === 1);
                        $publicadosNoCat = array_filter($categoria['itens'], fn($i) => (int)$i['publicar_resultado'] === 1);
                    ?>
                    <?php if (count($vencedoresNoCat) > 0): ?>
                        <span class="cat-badge ms-auto" style="background:rgba(205,222,0,.25);color:#CDDE00;">
                            <i class="bi bi-trophy-fill me-1"></i>
                            Vencedor definido
                        </span>
                    <?php endif; ?>
                    <?php if (count($publicadosNoCat) > 0): ?>
                        <span class="cat-badge" style="background:rgba(25,135,84,.25);color:#a3ffcc;">
                            <i class="bi bi-megaphone-fill me-1"></i>
                            <?= count($publicadosNoCat) ?> publicado<?= count($publicadosNoCat) !== 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Cards de cada negócio -->
                <?php foreach ($categoria['itens'] as $idx => $item): ?>
                    <?php
                        $isVenc = (int)$item['vencedor'] === 1;
                        $isPub  = (int)$item['publicar_resultado'] === 1;
                        $cardClass = $isVenc ? 'is-vencedor' : ($isPub ? 'is-publicado' : '');
                        $pos   = $idx + 1;
                        $medal = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '#' . $pos };
                    ?>
                    <div class="negocio-card <?= $cardClass ?> p-3">
                        <div class="d-flex flex-wrap align-items-start gap-3">

                            <!-- Posição + logo -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <div class="rank-pos <?= $isVenc ? '' : '' ?>"
                                    style="background:<?= $isVenc ? '#CDDE00' : '#f0f4f1' ?>;
                                           color:<?= $isVenc ? '#1E3425' : '#6c7a6e' ?>;">
                                    <?php if ($isVenc): ?>
                                        <i class="bi bi-trophy-fill" style="font-size:14px;"></i>
                                    <?php else: ?>
                                        <span style="font-size:13px;"><?= $pos ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($item['logo_negocio'])): ?>
                                    <img src="<?= h($item['logo_negocio']) ?>"
                                         alt="<?= h($item['nome_fantasia']) ?>"
                                         class="logo-thumb">
                                <?php else: ?>
                                    <div class="logo-placeholder"><i class="bi bi-building"></i></div>
                                <?php endif; ?>
                            </div>

                            <!-- Dados do negócio -->
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="fw-bold" style="font-size:15px;">
                                        <?= h($item['nome_fantasia']) ?>
                                        <?php if ($isVenc): ?>
                                            <span class="vencedor-crown ms-1" title="Vencedor">👑</span>
                                        <?php endif; ?>
                                    </span>
                                    <!-- Status da inscrição -->
                                    <?php
                                        $statusMap = [
                                            'vencedora'        => ['bg-success', 'Vencedora'],
                                            'finalista'        => ['bg-info text-dark', 'Finalista'],
                                            'classificada_fase_2' => ['bg-primary', 'Class. Fase 2'],
                                            'classificada_fase_1' => ['bg-secondary', 'Class. Fase 1'],
                                            'elegivel'         => ['bg-light text-dark border', 'Elegível'],
                                        ];
                                        [$sc, $sl] = $statusMap[$item['status_inscricao']] ?? ['bg-light text-dark border', h($item['status_inscricao'])];
                                    ?>
                                    <span class="badge <?= $sc ?>" style="font-size:10px;"><?= $sl ?></span>
                                </div>

                                <?php if (!empty($item['frase_negocio'])): ?>
                                    <div class="text-muted mb-1" style="font-size:12px;font-style:italic;">
                                        "<?= h($item['frase_negocio']) ?>"
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <span style="font-size:11px;color:#6c7a6e;">
                                        <i class="bi bi-person me-1"></i><?= h($item['empreendedor_nome']) ?>
                                    </span>
                                    <?php if ($item['municipio']): ?>
                                        <span style="font-size:11px;color:#9aab9d;">·</span>
                                        <span style="font-size:11px;color:#6c7a6e;">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?= h($item['municipio']) ?>/<?= h($item['estado']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span style="font-size:11px;color:#9aab9d;">·</span>
                                    <span style="font-size:11px;color:#6c7a6e;"><?= h($item['premiacao_nome']) ?> <?= (int)$item['premiacao_ano'] ?></span>
                                </div>
                            </div>

                            <!-- Pontuação -->
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">

                                <!-- Total de pontos -->
                                <span class="pontos-pill" style="background:#1E3425;color:#CDDE00;font-size:14px;">
                                    <i class="bi bi-star-fill" style="font-size:11px;"></i>
                                    <?= (int)$item['total_pontos'] ?> pts
                                </span>

                                <div class="d-flex gap-1 flex-wrap justify-content-end">
                                    <!-- Votos júri -->
                                    <span class="score-mini" style="background:#e7f5ff;color:#084298;"
                                          title="Votos do júri">
                                        <i class="bi bi-people-fill"></i>
                                        <?= (int)$item['total_votos_juri'] ?> júri
                                    </span>
                                    <!-- Ponto popular -->
                                    <?php if ((int)$item['ponto_voto_popular'] > 0): ?>
                                        <span class="score-mini" style="background:#fff3cd;color:#856404;"
                                              title="Ponto do voto popular">
                                            <i class="bi bi-people"></i>
                                            +<?= (int)$item['ponto_voto_popular'] ?> popular
                                        </span>
                                    <?php endif; ?>
                                    <!-- Score técnico -->
                                    <?php if ($item['score_geral']): ?>
                                        <span class="score-mini" style="background:#eaf7ef;color:#1E3425;"
                                              title="Score técnico do negócio">
                                            <i class="bi bi-graph-up-arrow"></i>
                                            <?= number_format((float)$item['score_geral'], 1) ?> score
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Última classificação -->
                                <?php if ($item['ultima_classificacao']): ?>
                                    <span style="font-size:10px;color:#9aab9d;">
                                        <i class="bi bi-hash"></i>
                                        <?= (int)$item['ultima_classificacao'] ?>º classificado
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Ações -->
                            <div class="d-flex flex-column gap-2 flex-shrink-0 align-items-end">

                                <!-- Status de publicação -->
                                <?php if ($isPub): ?>
                                    <span class="status-badge-pub" style="background:#d1e7dd;color:#0f5132;">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Publicado em <?= dataBr($item['publicado_em']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge-pub" style="background:#fff3cd;color:#856404;">
                                        <i class="bi bi-clock-history"></i>
                                        Não publicado
                                    </span>
                                <?php endif; ?>

                                <!-- Botão: marcar/desmarcar vencedor -->
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('<?= $isVenc
                                          ? 'Desmarcar este negócio como vencedor?'
                                          : 'Marcar ' . addslashes(h($item['nome_fantasia'])) . ' como vencedor desta categoria?' ?>')">
                                    <input type="hidden" name="resultado_id"       value="<?= (int)$item['resultado_id'] ?>">
                                    <input type="hidden" name="premiacao_id"       value="<?= $filtroPremiacao ?>">
                                    <input type="hidden" name="categoria_id_filtro" value="<?= $filtroCategoria ?>">
                                    <input type="hidden" name="action"             value="<?= $isVenc ? 'desmarcar_vencedor' : 'marcar_vencedor' ?>">
                                    <button type="submit"
                                        class="btn btn-sm <?= $isVenc ? 'btn-warning' : 'btn-outline-warning' ?>"
                                        style="font-size:12px;">
                                        <i class="bi bi-trophy<?= $isVenc ? '-fill' : '' ?> me-1"></i>
                                        <?= $isVenc ? 'Desmarcar vencedor' : 'Marcar como vencedor' ?>
                                    </button>
                                </form>

                                <!-- Botão: publicar/despublicar resultado -->
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('<?= $isPub
                                          ? 'Despublicar este resultado? Ele ficará oculto para o público.'
                                          : 'Publicar o resultado de ' . addslashes(h($item['nome_fantasia'])) . '? Ficará visível ao público.' ?>')">
                                    <input type="hidden" name="resultado_id"       value="<?= (int)$item['resultado_id'] ?>">
                                    <input type="hidden" name="premiacao_id"       value="<?= $filtroPremiacao ?>">
                                    <input type="hidden" name="categoria_id_filtro" value="<?= $filtroCategoria ?>">
                                    <input type="hidden" name="action"             value="<?= $isPub ? 'despublicar' : 'publicar' ?>">
                                    <button type="submit"
                                        class="btn btn-sm <?= $isPub ? 'btn-outline-danger' : 'btn-success' ?>"
                                        style="font-size:12px;">
                                        <i class="bi bi-<?= $isPub ? 'eye-slash' : 'megaphone' ?> me-1"></i>
                                        <?= $isPub ? 'Despublicar' : 'Publicar resultado' ?>
                                    </button>
                                </form>
                            </div>

                        </div><!-- /d-flex -->
                    </div><!-- /negocio-card -->
                <?php endforeach; ?>

            </div><!-- /mb-4 -->
        <?php endforeach; ?>

    <?php endif; ?>

</div><!-- /container-fluid -->

<?php require_once $appBase . '/views/admin/footer.php'; ?>