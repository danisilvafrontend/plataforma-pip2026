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

// ================================================================================
// HELPERS DE APURAÇÃO (mesma lógica do premiacao_apuracao.php)
// ================================================================================

function getVotosPopulares(PDO $pdo, int $faseId): array
{
    $stmt = $pdo->prepare("
        SELECT inscricao_id, COUNT(*) AS votos
        FROM premiacao_votos_populares
        WHERE fase_id = ?
        GROUP BY inscricao_id
        ORDER BY votos DESC
    ");
    $stmt->execute([$faseId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['inscricao_id']] = (int)$row['votos'];
    }
    return $result;
}

function getVotosTecnicos(PDO $pdo, int $faseId): array
{
    $stmt = $pdo->prepare("
        SELECT inscricao_id, COUNT(*) AS votos
        FROM premiacao_votos_tecnicos
        WHERE fase_id = ?
        GROUP BY inscricao_id
        ORDER BY votos DESC
    ");
    $stmt->execute([$faseId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['inscricao_id']] = (int)$row['votos'];
    }
    return $result;
}

function apurarFase(
    array $pool,
    array $votosPopulares,
    array $votosTecnicos,
    int   $topPopular,
    int   $topTecnica,
    int   $totalClassificados
): array {
    $poolSet = array_flip($pool);
    $vp = array_filter($votosPopulares, fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);
    $vt = array_filter($votosTecnicos,  fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);

    arsort($vp);
    $topPop = array_slice(array_keys($vp), 0, $topPopular, true);

    arsort($vt);
    $topTec = array_slice(array_keys($vt), 0, $topTecnica, true);

    $selecionados = [];
    foreach ($topPop as $id) {
        $selecionados[$id] = [
            'pop'    => $vp[$id] ?? 0,
            'tec'    => $vt[$id] ?? 0,
            'origem' => 'popular',
        ];
    }
    foreach ($topTec as $id) {
        if (!isset($selecionados[$id])) {
            $selecionados[$id] = [
                'pop'    => $vp[$id] ?? 0,
                'tec'    => $vt[$id] ?? 0,
                'origem' => 'tecnica',
            ];
        } else {
            $selecionados[$id]['origem'] = 'ambos';
        }
    }

    if (count($selecionados) < $totalClassificados) {
        $todos = $pool;
        usort($todos, fn($a, $b) => ($vp[$b] ?? 0) <=> ($vp[$a] ?? 0));
        foreach ($todos as $id) {
            if (count($selecionados) >= $totalClassificados) break;
            if (!isset($selecionados[$id])) {
                $selecionados[$id] = [
                    'pop'    => $vp[$id] ?? 0,
                    'tec'    => $vt[$id] ?? 0,
                    'origem' => 'complemento',
                ];
            }
        }
    }

    $ordemOrigem = ['ambos' => 0, 'popular' => 1, 'tecnica' => 2, 'complemento' => 3];
    uasort($selecionados, function ($a, $b) use ($ordemOrigem) {
        $oa = $ordemOrigem[$a['origem']] ?? 9;
        $ob = $ordemOrigem[$b['origem']] ?? 9;
        if ($oa !== $ob) return $oa <=> $ob;
        if ($b['pop'] !== $a['pop']) return $b['pop'] <=> $a['pop'];
        return $b['tec'] <=> $a['tec'];
    });

    return $selecionados;
}

// ================================================================================
// FILTROS
// ================================================================================
$filtroPremiacao = (int)($_GET['premiacao_id'] ?? 0);
$filtroFase      = (int)($_GET['fase_id']      ?? 0);
$acao            = $_POST['acao'] ?? '';

$premiacoes = $pdo->query("
    SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC, id DESC
")->fetchAll();

$fases = [];
if ($filtroPremiacao > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome, tipo_fase, qtd_classificados_popular, qtd_classificados_tecnica,
               ordem_exibicao, status
        FROM premiacao_fases
        WHERE premiacao_id = ?
          AND tipo_fase = 'classificatoria'
        ORDER BY ordem_exibicao ASC
    ");
    $stmt->execute([$filtroPremiacao]);
    $fases = $stmt->fetchAll();
}

$faseAtual = null;
foreach ($fases as $f) {
    if ((int)$f['id'] === $filtroFase) {
        $faseAtual = $f;
        break;
    }
}
if (!$faseAtual && count($fases) > 0) {
    $faseAtual = $fases[0];
    $filtroFase = (int)$faseAtual['id'];
}

// ================================================================================
// STATUS_NOVO a partir da fase (fase 1 → classificada_fase_1, fase 2 → classificada_fase_2)
// ================================================================================
function statusNovoPorFase(array $fase): string
{
    $ordem = (int)($fase['ordem_exibicao'] ?? 1);
    return match(true) {
        $ordem === 1 => 'classificada_fase_1',
        $ordem === 2 => 'classificada_fase_2',
        default      => 'classificada_fase_' . $ordem,
    };
}

// ================================================================================
// APURAÇÃO
// ================================================================================
$apuracaoPorCategoria = [];
$jaGravados = [];

if ($filtroPremiacao > 0 && $faseAtual) {

    $faseId = (int)$faseAtual['id'];
    $ordemAtual = (int)($faseAtual['ordem_exibicao'] ?? 1);

    // Verifica se já existem registros gravados para esta fase
    $stmtGravados = $pdo->prepare("
        SELECT COUNT(*) FROM premiacao_classificados WHERE fase_id = ?
    ");
    $stmtGravados->execute([$faseId]);
    $jaGravados[$faseId] = (int)$stmtGravados->fetchColumn();

    // Busca todas as categorias
    $stmtCats = $pdo->prepare("
        SELECT pc.id AS cat_id, pc.nome AS cat_nome, pc.ordem AS cat_ordem
        FROM premiacao_categorias pc
        WHERE pc.premiacao_id = ?
        ORDER BY pc.ordem
    ");
    $stmtCats->execute([$filtroPremiacao]);
    $cats = $stmtCats->fetchAll();

    foreach ($cats as $cat) {
        $catId = (int)$cat['cat_id'];

        // Pool: fase 1 = todas elegíveis; fase 2+ = classificadas da fase anterior
        if ($ordemAtual <= 1) {
            $stmtPool = $pdo->prepare("
                SELECT pi.id AS inscricao_id
                FROM premiacao_inscricoes pi
                WHERE pi.premiacao_id = ?
                  AND pi.categoria_id = ?
                  AND pi.status IN ('elegivel','classificada_fase_1','classificada_fase_2','finalista')
            ");
        } else {
            $stmtPool = $pdo->prepare("
                SELECT pi.id AS inscricao_id
                FROM premiacao_inscricoes pi
                WHERE pi.premiacao_id = ?
                  AND pi.categoria_id = ?
                  AND pi.status IN ('classificada_fase_1','classificada_fase_2','finalista')
            ");
        }
        $stmtPool->execute([$filtroPremiacao, $catId]);
        $pool = array_map(fn($r) => (int)$r['inscricao_id'], $stmtPool->fetchAll());

        if (empty($pool)) continue;

        $votosPopulares = getVotosPopulares($pdo, $faseId);
        $votosTecnicos  = getVotosTecnicos($pdo, $faseId);

        $topPop    = (int)($faseAtual['qtd_classificados_popular'] ?? 5);
        $topTec    = (int)($faseAtual['qtd_classificados_tecnica'] ?? 5);
        $totalClass = min(count($pool), $topPop + $topTec);

        $resultado = apurarFase($pool, $votosPopulares, $votosTecnicos, $topPop, $topTec, $totalClass);

        if (empty($resultado)) continue;

        // Enriquece com dados do negócio
        $ids = implode(',', array_map('intval', array_keys($resultado)));
        $stmtNeg = $pdo->query("
            SELECT
                pi.id AS inscricao_id,
                pi.status AS status_inscricao,
                n.nome_fantasia,
                n.municipio,
                n.estado
            FROM premiacao_inscricoes pi
            INNER JOIN negocios n ON n.id = pi.negocio_id
            WHERE pi.id IN ($ids)
        ");
        $negMap = [];
        foreach ($stmtNeg->fetchAll() as $row) {
            $negMap[(int)$row['inscricao_id']] = $row;
        }

        $maxPop = max([1, ...array_values($votosPopulares)] ?: [1]);
        $maxTec = max([1, ...array_values($votosTecnicos)]  ?: [1]);

        $itens = [];
        $pos   = 1;
        foreach ($resultado as $inscId => $dados) {
            $neg = $negMap[$inscId] ?? [];
            $itens[] = array_merge($dados, [
                'inscricao_id'    => $inscId,
                'pos'             => $pos++,
                'nome_fantasia'   => $neg['nome_fantasia'] ?? 'Não identificado',
                'municipio'       => $neg['municipio'] ?? '',
                'estado'          => $neg['estado'] ?? '',
                'status_inscricao'=> $neg['status_inscricao'] ?? '',
                'max_pop'         => $maxPop,
                'max_tec'         => $maxTec,
            ]);
        }

        $apuracaoPorCategoria[$catId] = [
            'nome'        => $cat['cat_nome'],
            'ordem'       => $cat['cat_ordem'],
            'total_pool'  => count($pool),
            'total_class' => count($resultado),
            'itens'       => $itens,
        ];
    }
}

// ================================================================================
// AÇÃO: GRAVAR CLASSIFICADOS
// ================================================================================
$mensagens = [];
$erros     = [];

if ($acao === 'gravar' && $filtroPremiacao > 0 && $faseAtual && !empty($apuracaoPorCategoria)) {

    $faseId      = (int)$faseAtual['id'];
    $statusNovo  = statusNovoPorFase($faseAtual);
    $ordemAtual  = (int)($faseAtual['ordem_exibicao'] ?? 1);

    // Status que NÃO devem ser rebaixados (finalistas/classificadas de fases POSTERIORES)
    $statusProtegidos = ['finalista'];
    for ($i = $ordemAtual + 1; $i <= 10; $i++) {
        $statusProtegidos[] = 'classificada_fase_' . $i;
    }

    try {
        $pdo->beginTransaction();

        // 1. Limpa classificados já gravados desta fase (para re-apuração idempotente)
        $pdo->prepare("DELETE FROM premiacao_classificados WHERE fase_id = ?")->execute([$faseId]);

        // Verifica se existe a tabela premiacao_classificados_fases e limpa também
        $tabelasExistentes = $pdo->query("SHOW TABLES LIKE 'premiacao_classificados_fases'")->fetchAll();
        $temClassFases = !empty($tabelasExistentes);
        if ($temClassFases) {
            $pdo->prepare("DELETE FROM premiacao_classificados_fases WHERE fase_id = ?")->execute([$faseId]);
        }

        $totalGravados = 0;
        $totalAtualizados = 0;

        foreach ($apuracaoPorCategoria as $catId => $cat) {
            foreach ($cat['itens'] as $item) {
                $inscId = (int)$item['inscricao_id'];
                $pos    = (int)$item['pos'];
                $origem = $item['origem'];

                // 2. Insere em premiacao_classificados
                $pdo->prepare("
                    INSERT INTO premiacao_classificados
                        (fase_id, categoria_id, inscricao_id, posicao, origem, criado_em)
                    VALUES
                        (?, ?, ?, ?, ?, NOW())
                ")->execute([$faseId, $catId, $inscId, $pos, $origem]);
                $totalGravados++;

                // 3. Insere em premiacao_classificados_fases (se existir)
                if ($temClassFases) {
                    $pdo->prepare("
                        INSERT INTO premiacao_classificados_fases
                            (fase_id, categoria_id, inscricao_id, posicao, origem, criado_em)
                        VALUES
                            (?, ?, ?, ?, ?, NOW())
                    ")->execute([$faseId, $catId, $inscId, $pos, $origem]);
                }

                // 4. Atualiza status da inscrição (somente se não for status mais avançado)
                $stmtStatus = $pdo->prepare("
                    SELECT status FROM premiacao_inscricoes WHERE id = ?
                ");
                $stmtStatus->execute([$inscId]);
                $statusAtual = $stmtStatus->fetchColumn();

                if (!in_array($statusAtual, $statusProtegidos, true)) {
                    $pdo->prepare("
                        UPDATE premiacao_inscricoes
                        SET status = ?, updated_at = NOW()
                        WHERE id = ?
                    ")->execute([$statusNovo, $inscId]);
                    $totalAtualizados++;
                }
            }

            // 5. Atualiza as NÃO classificadas de volta para 'elegivel'
            //    (apenas se eram classificada_fase_X desta ordem — não rebaixa finalistas)
            $idsClassificados = array_map(fn($i) => (int)$i['inscricao_id'], $cat['itens']);
            if (!empty($idsClassificados)) {
                $placeholders = implode(',', array_fill(0, count($idsClassificados), '?'));
                $params = array_merge(
                    [$filtroPremiacao, $catId],
                    $idsClassificados
                );
                // Rebaixa para 'elegivel' somente as que estavam como classificada desta fase
                $pdo->prepare("
                    UPDATE premiacao_inscricoes
                    SET status = 'elegivel', updated_at = NOW()
                    WHERE premiacao_id = ?
                      AND categoria_id = ?
                      AND status = ?
                      AND id NOT IN ($placeholders)
                ")->execute(array_merge([$filtroPremiacao, $catId, $statusNovo], $idsClassificados));
            }
        }

        $pdo->commit();
        $mensagens[] = "✅ <strong>$totalGravados</strong> classificados gravados com sucesso na fase <strong>" . h($faseAtual['nome']) . "</strong>.";
        $mensagens[] = "📝 <strong>$totalAtualizados</strong> inscrições tiveram o status atualizado para <code>$statusNovo</code>.";

        // Recarrega os gravados
        $stmtGravados = $pdo->prepare("SELECT COUNT(*) FROM premiacao_classificados WHERE fase_id = ?");
        $stmtGravados->execute([$faseId]);
        $jaGravados[$faseId] = (int)$stmtGravados->fetchColumn();

    } catch (Throwable $e) {
        $pdo->rollBack();
        $erros[] = "❌ Erro ao gravar: " . h($e->getMessage());
    }
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">
                <i class="bi bi-check2-square me-2" style="color:#1E3425;"></i>
                Gravar Classificados
            </h1>
            <p class="text-muted mb-0">
                Apura os votos da fase classificatória e grava os classificados nas tabelas.
                <strong>Esta ação pode ser re-executada</strong> — ela limpa e regrava os dados da fase selecionada.
            </p>
        </div>
        <a href="premiacao_apuracao.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> Ver Apuração (somente leitura)
        </a>
    </div>

    <!-- Alertas -->
    <?php foreach ($mensagens as $msg): ?>
        <div class="alert alert-success border-0 shadow-sm mb-3" style="font-size:14px;">
            <?= $msg ?>
        </div>
    <?php endforeach; ?>
    <?php foreach ($erros as $err): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="font-size:14px;">
            <?= $err ?>
        </div>
    <?php endforeach; ?>

    <!-- Filtros -->
    <div class="filtros-card mb-4">
        <form method="GET" class="row g-2 align-items-end">

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Premiação</label>
                <select name="premiacao_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Selecione...</option>
                    <?php foreach ($premiacoes as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>"
                            <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                            <?= h($pr['nome']) ?> (<?= (int)$pr['ano'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($filtroPremiacao > 0 && count($fases) > 0): ?>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Fase Classificatória</label>
                <select name="fase_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($fases as $f): ?>
                        <option value="<?= (int)$f['id'] ?>"
                            <?= $filtroFase === (int)$f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nome']) ?>
                            (<?= (int)$f['qtd_classificados_popular'] ?>pop + <?= (int)$f['qtd_classificados_tecnica'] ?>tec por cat.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-auto">
                <a href="premiacao_apuracao_gravar.php" class="btn btn-sm btn-outline-secondary" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>

    <?php if (!$filtroPremiacao): ?>
        <div class="text-center py-5">
            <i class="bi bi-funnel fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted fs-5 mb-1">Selecione uma Premiação para continuar.</p>
        </div>

    <?php elseif (empty($apuracaoPorCategoria)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted fs-5 mb-1">Nenhuma inscrição elegível encontrada para esta fase.</p>
            <p class="text-muted" style="font-size:13px;">
                Verifique se as inscrições têm status correto e se há votos registrados.
            </p>
        </div>

    <?php else: ?>

        <!-- Banner de alerta se já gravado -->
        <?php $gravadosCount = $jaGravados[$filtroFase] ?? 0; ?>
        <?php if ($gravadosCount > 0): ?>
            <div class="alert alert-warning d-flex gap-2 align-items-start mb-4" style="font-size:13px;">
                <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
                <div>
                    <strong>Esta fase já possui <?= $gravadosCount ?> classificado(s) gravado(s).</strong>
                    Ao gravar novamente, os dados anteriores serão <u>substituídos</u> pela apuração atual.
                    Confira o resultado abaixo antes de confirmar.
                </div>
            </div>
        <?php endif; ?>

        <!-- Info da fase -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#e7f5ff;">
                        <i class="bi bi-people-fill" style="color:#084298;"></i>
                    </div>
                    <div class="prem-kpi-valor"><?= (int)($faseAtual['qtd_classificados_popular'] ?? 0) ?></div>
                    <div class="prem-kpi-label">Top Popular</div>
                    <div class="prem-kpi-sub">por categoria</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#eaf7ef;">
                        <i class="bi bi-clipboard-data-fill" style="color:#1E3425;"></i>
                    </div>
                    <div class="prem-kpi-valor"><?= (int)($faseAtual['qtd_classificados_tecnica'] ?? 0) ?></div>
                    <div class="prem-kpi-label">Top Técnica</div>
                    <div class="prem-kpi-sub">por categoria</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#f3f0ff;">
                        <i class="bi bi-grid-fill" style="color:#5a3e9a;"></i>
                    </div>
                    <div class="prem-kpi-valor"><?= count($apuracaoPorCategoria) ?></div>
                    <div class="prem-kpi-label">Categorias</div>
                    <div class="prem-kpi-sub">apuradas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:<?= $gravadosCount > 0 ? '#fff3cd' : '#eaf7ef' ?>;">
                        <i class="bi bi-database-fill-check" style="color:<?= $gravadosCount > 0 ? '#856404' : '#1E3425' ?>;"></i>
                    </div>
                    <div class="prem-kpi-valor"><?= $gravadosCount ?></div>
                    <div class="prem-kpi-label">Já Gravados</div>
                    <div class="prem-kpi-sub">nesta fase</div>
                </div>
            </div>
        </div>

        <!-- Tabelas por categoria -->
        <?php
        $origensBadge = [
            'popular'     => ['#e7f5ff', '#084298', 'bi-people-fill',    'Popular'],
            'tecnica'     => ['#eaf7ef', '#1E3425', 'bi-clipboard-data', 'Técnica'],
            'ambos'       => ['#CDDE00', '#1E3425', 'bi-star-fill',      'Ambos'],
            'complemento' => ['#f0f0f0', '#6c7a6e', 'bi-plus-circle',    'Complemento'],
        ];
        ?>
        <?php foreach ($apuracaoPorCategoria as $catId => $cat): ?>
            <div class="mb-4">
                <div class="cat-header">
                    <i class="bi bi-award-fill fs-4"></i>
                    <span class="cat-nome"><?= h($cat['nome']) ?></span>
                    <span class="cat-badge">
                        <?= $cat['total_class'] ?> classificado<?= $cat['total_class'] !== 1 ? 's' : '' ?>
                        / <?= $cat['total_pool'] ?> no pool
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background:#f0f4f1;">
                            <tr>
                                <th class="ps-3" style="width:50px;">Pos.</th>
                                <th>Negócio</th>
                                <th class="text-center" style="width:120px;">Votos Popular</th>
                                <th class="text-center" style="width:120px;">Votos Técnica</th>
                                <th class="text-center" style="width:110px;">Origem</th>
                                <th class="pe-3" style="width:110px;">Status Atual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cat['itens'] as $item): ?>
                                <?php
                                    $pos    = $item['pos'];
                                    $medal  = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '#' . $pos };
                                    $origem = $item['origem'] ?? 'complemento';
                                    [$bg, $color, $icon, $label] = $origensBadge[$origem] ?? $origensBadge['complemento'];
                                    $stMap = [
                                        'finalista'           => ['bg-info text-dark',     'Finalista'],
                                        'classificada_fase_2' => ['bg-primary',             'Class. F2'],
                                        'classificada_fase_1' => ['bg-secondary',           'Class. F1'],
                                        'elegivel'            => ['bg-light text-dark border','Elegível'],
                                        'vencedora'           => ['bg-success',              'Vencedora'],
                                    ];
                                    [$sc, $sl] = $stMap[$item['status_inscricao']] ?? ['bg-light text-dark border', h($item['status_inscricao'])];
                                ?>
                                <tr>
                                    <td class="ps-3 text-center">
                                        <span class="rank-pos">
                                            <?= $medal ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:13px;"><?= h($item['nome_fantasia']) ?></div>
                                        <?php if ($item['municipio']): ?>
                                            <div style="font-size:11px;color:#9aab9d;"><?= h($item['municipio']) ?>/<?= h($item['estado']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="fw-semibold" style="color:#084298;"><?= (int)($item['pop'] ?? 0) ?></span>
                                            <div style="width:70px;height:4px;background:#e7f5ff;border-radius:3px;overflow:hidden;">
                                                <div style="height:100%;background:#084298;border-radius:3px;
                                                    width:<?= $item['max_pop'] > 0 ? min(100, round((($item['pop'] ?? 0) / $item['max_pop']) * 100)) : 0 ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="fw-semibold" style="color:#1E3425;"><?= (int)($item['tec'] ?? 0) ?></span>
                                            <div style="width:70px;height:4px;background:#eaf7ef;border-radius:3px;overflow:hidden;">
                                                <div style="height:100%;background:#1E3425;border-radius:3px;
                                                    width:<?= $item['max_tec'] > 0 ? min(100, round((($item['tec'] ?? 0) / $item['max_tec']) * 100)) : 0 ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;
                                                     background:<?= $bg ?>;color:<?= $color ?>;font-size:11px;font-weight:700;white-space:nowrap;">
                                            <i class="bi <?= $icon ?>"></i> <?= $label ?>
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        <span class="badge <?= $sc ?>" style="font-size:10px;"><?= $sl ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Botão de Gravação -->
        <div class="card border-0 shadow-sm mt-3 mb-4" style="background:#f9fafb;">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold mb-1" style="font-size:14px;">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            O que será gravado ao confirmar:
                        </div>
                        <ul class="mb-0 ps-3" style="font-size:13px;color:#555;">
                            <li>Registros inseridos em <code>premiacao_classificados</code> para a fase <strong><?= h($faseAtual['nome']) ?></strong></li>
                            <?php
                            $tblCf = $pdo->query("SHOW TABLES LIKE 'premiacao_classificados_fases'")->fetchAll();
                            if (!empty($tblCf)): ?>
                            <li>Registros inseridos em <code>premiacao_classificados_fases</code></li>
                            <?php endif; ?>
                            <li>Status das inscrições classificadas atualizado para <code><?= statusNovoPorFase($faseAtual) ?></code></li>
                            <li>Gravações anteriores desta fase serão substituídas</li>
                        </ul>
                    </div>
                    <form method="POST"
                          action="premiacao_apuracao_gravar.php?premiacao_id=<?= $filtroPremiacao ?>&fase_id=<?= $filtroFase ?>"
                          onsubmit="return confirm('Confirmar gravação dos <?= array_sum(array_column($apuracaoPorCategoria, 'total_class')) ?> classificados da fase «<?= addslashes($faseAtual['nome']) ?>»?\n\nEsta ação substituirá eventuais dados anteriores desta fase.');">
                        <button type="submit" name="acao" value="gravar"
                                class="btn btn-lg px-5 fw-bold"
                                style="background:#1E3425;color:#CDDE00;border:none;">
                            <i class="bi bi-check2-square me-2"></i>
                            Gravar <?= array_sum(array_column($apuracaoPorCategoria, 'total_class')) ?> Classificados
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div><!-- /container-fluid -->

<style>
.rank-pos {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-weight: 700;
    background: #f0f4f1;
    color: #6c7a6e;
    font-size: 12px;
}
.cat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #1E3425;
    color: #CDDE00;
    padding: 10px 16px;
    border-radius: 10px 10px 0 0;
}
.cat-nome { font-weight: 700; font-size: 15px; }
.cat-badge {
    background: rgba(205,222,0,.2);
    color: #CDDE00;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 700;
}
.filtros-card {
    background: #fff;
    border: 1px solid #e8ede9;
    border-radius: 10px;
    padding: 16px 20px;
}
.prem-kpi-card {
    background: #fff;
    border: 1px solid #e8ede9;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
}
.prem-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 18px;
}
.prem-kpi-valor { font-size: 22px; font-weight: 800; color: #1E3425; line-height: 1.1; }
.prem-kpi-label { font-size: 12px; font-weight: 600; color: #445; margin-top: 2px; }
.prem-kpi-sub   { font-size: 10px; color: #9aab9d; }
</style>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
