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
// HELPERS DE APURAÇÃO
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

function getVotosJuri(PDO $pdo, int $faseId): array
{
    $stmt = $pdo->prepare("
        SELECT inscricao_id, COUNT(*) AS votos
        FROM premiacao_votos_juri
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
        $selecionados[$id] = ['pop' => $vp[$id] ?? 0, 'tec' => $vt[$id] ?? 0, 'origem' => 'popular'];
    }
    foreach ($topTec as $id) {
        if (!isset($selecionados[$id])) {
            $selecionados[$id] = ['pop' => $vp[$id] ?? 0, 'tec' => $vt[$id] ?? 0, 'origem' => 'tecnica'];
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
                $selecionados[$id] = ['pop' => $vp[$id] ?? 0, 'tec' => $vt[$id] ?? 0, 'origem' => 'complemento'];
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

function apurarFaseFinal(
    array $pool,
    array $votosPopulares,
    array $votosJuri
): array {
    $maxPop = 0;
    foreach ($pool as $id) {
        $v = $votosPopulares[$id] ?? 0;
        if ($v > $maxPop) $maxPop = $v;
    }
    $result = [];
    foreach ($pool as $id) {
        $pop  = $votosPopulares[$id] ?? 0;
        $juri = $votosJuri[$id] ?? 0;
        $pontoPop = ($maxPop > 0 && $pop === $maxPop) ? 1 : 0;
        $result[$id] = [
            'votos_popular' => $pop,
            'ponto_popular' => $pontoPop,
            'votos_juri'    => $juri,
            'total'         => $pontoPop + $juri,
        ];
    }
    uasort($result, function ($a, $b) {
        if ($b['total']      !== $a['total'])      return $b['total']      <=> $a['total'];
        if ($b['votos_juri'] !== $a['votos_juri']) return $b['votos_juri'] <=> $a['votos_juri'];
        return $b['votos_popular'] <=> $a['votos_popular'];
    });
    return $result;
}

// Retorna o status novo a partir da ordem da fase
function statusNovoPorOrdem(int $ordem): string
{
    return 'classificada_fase_' . $ordem;
}

// ================================================================================
// FILTROS
// ================================================================================
$filtroPremiacao = (int)($_GET['premiacao_id'] ?? 0);
$filtroCategoria = (int)($_GET['categoria_id'] ?? 0);
$filtroFase      = (int)($_GET['fase_id']      ?? 0);
$acao            = $_POST['acao'] ?? '';

$premiacoes = $pdo->query("
    SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC, id DESC
")->fetchAll();

$categorias = [];
if ($filtroPremiacao > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome FROM premiacao_categorias WHERE premiacao_id = ? ORDER BY ordem
    ");
    $stmt->execute([$filtroPremiacao]);
    $categorias = $stmt->fetchAll();
}

$fases = [];
if ($filtroPremiacao > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome, tipo_fase, qtd_classificados_popular, qtd_classificados_tecnica,
               ordem_exibicao, permite_voto_popular, permite_juri_final, permite_voto_tecnico, status
        FROM premiacao_fases
        WHERE premiacao_id = ?
        ORDER BY ordem_exibicao ASC
    ");
    $stmt->execute([$filtroPremiacao]);
    $fases = $stmt->fetchAll();
}

$faseAtual = null;
foreach ($fases as $f) {
    if ((int)$f['id'] === $filtroFase) { $faseAtual = $f; break; }
}
if (!$faseAtual && count($fases) > 0) {
    $faseAtual = $fases[0];
    $filtroFase = (int)$faseAtual['id'];
}

// ================================================================================
// MONTA POOL E APURAÇÃO
// ================================================================================
$apuracaoPorCategoria = [];

if ($filtroPremiacao > 0 && $faseAtual) {
    $tipoFase   = $faseAtual['tipo_fase'] ?? 'classificatoria';
    $faseId     = (int)$faseAtual['id'];
    $ordemAtual = (int)($faseAtual['ordem_exibicao'] ?? 1);

    $stmtCats = $pdo->prepare("
        SELECT pc.id AS cat_id, pc.nome AS cat_nome, pc.ordem AS cat_ordem
        FROM premiacao_categorias pc
        WHERE pc.premiacao_id = ?
        " . ($filtroCategoria > 0 ? 'AND pc.id = ?' : '') . "
        ORDER BY pc.ordem
    ");
    $params = [$filtroPremiacao];
    if ($filtroCategoria > 0) $params[] = $filtroCategoria;
    $stmtCats->execute($params);
    $cats = $stmtCats->fetchAll();

    foreach ($cats as $cat) {
        $catId   = (int)$cat['cat_id'];
        $catNome = $cat['cat_nome']; // VARCHAR em premiacao_inscricoes.categoria

        // ── Pool ──────────────────────────────────────────────────────────────
        // premiacao_inscricoes.categoria é VARCHAR (nome), não ID numérico.
        if ($tipoFase === 'classificatoria') {
            if ($ordemAtual <= 1) {
                $statusPool = "IN ('elegivel','classificada_fase_1','classificada_fase_2','finalista')";
            } else {
                $statusPool = "IN ('classificada_fase_1','classificada_fase_2','finalista')";
            }
        } else {
            $statusPool = "IN ('finalista')";
        }

        $stmtPool = $pdo->prepare("
            SELECT pi.id AS inscricao_id
            FROM premiacao_inscricoes pi
            WHERE pi.premiacao_id = ?
              AND pi.categoria    = ?
              AND pi.status $statusPool
        ");
        $stmtPool->execute([$filtroPremiacao, $catNome]);
        $pool = array_map(fn($r) => (int)$r['inscricao_id'], $stmtPool->fetchAll());

        if (empty($pool)) continue;

        $votosPopulares = getVotosPopulares($pdo, $faseId);
        $votosTecnicos  = getVotosTecnicos($pdo, $faseId);
        $votosJuri      = getVotosJuri($pdo, $faseId);

        $topPop     = (int)($faseAtual['qtd_classificados_popular'] ?? 10);
        $topTec     = (int)($faseAtual['qtd_classificados_tecnica'] ?? 10);
        $totalClass = min(count($pool), $topPop + $topTec);

        if ($tipoFase === 'final') {
            $resultado = apurarFaseFinal($pool, $votosPopulares, $votosJuri);
        } else {
            $resultado = apurarFase($pool, $votosPopulares, $votosTecnicos, $topPop, $topTec, $totalClass);
        }

        if (empty($resultado)) continue;

        $ids = implode(',', array_map('intval', array_keys($resultado)));
        $stmtNeg = $pdo->query("
            SELECT
                pi.id          AS inscricao_id,
                pi.status      AS status_inscricao,
                n.id           AS negocio_id,
                n.nome_fantasia,
                n.municipio,
                n.estado,
                na.logo_negocio
            FROM premiacao_inscricoes pi
            INNER JOIN negocios n              ON n.id  = pi.negocio_id
            LEFT  JOIN negocio_apresentacao na ON na.negocio_id = n.id
            WHERE pi.id IN ($ids)
        ");
        $negMap = [];
        foreach ($stmtNeg->fetchAll() as $row) {
            $negMap[(int)$row['inscricao_id']] = $row;
        }

        $maxPop = max([1, ...array_values($votosPopulares)] ?: [1]);
        $maxTec = max([1, ...array_values($votosTecnicos)]  ?: [1]);
        $maxJur = max([1, ...array_values($votosJuri)]      ?: [1]);

        $itens = [];
        $pos   = 1;
        foreach ($resultado as $inscId => $dados) {
            $neg = $negMap[$inscId] ?? [];
            $itens[] = array_merge($dados, [
                'inscricao_id'     => $inscId,
                'pos'              => $pos++,
                'negocio_id'       => $neg['negocio_id']    ?? 0,
                'nome_fantasia'    => $neg['nome_fantasia']  ?? 'Não identificado',
                'municipio'        => $neg['municipio']      ?? '',
                'estado'           => $neg['estado']         ?? '',
                'logo_negocio'     => $neg['logo_negocio']   ?? '',
                'status_inscricao' => $neg['status_inscricao'] ?? '',
                'max_pop'          => $maxPop,
                'max_tec'          => $maxTec,
                'max_jur'          => $maxJur,
            ]);
        }

        $apuracaoPorCategoria[$catId] = [
            'nome'       => $catNome,
            'ordem'      => $cat['cat_ordem'],
            'total_pool' => count($pool),
            'total_class'=> count($resultado),
            'itens'      => $itens,
        ];
    }
}

// ================================================================================
// AÇÃO: GRAVAR CLASSIFICADOS  (POST acao=gravar)
// ================================================================================
$mensagens = [];
$erros     = [];
$jaGravados = 0;

if ($filtroPremiacao > 0 && $faseAtual) {
    $stmtJaGrav = $pdo->prepare("SELECT COUNT(*) FROM premiacao_classificados WHERE fase_id = ?");
    $stmtJaGrav->execute([(int)$faseAtual['id']]);
    $jaGravados = (int)$stmtJaGrav->fetchColumn();
}

if ($acao === 'gravar' && $filtroPremiacao > 0 && $faseAtual && !empty($apuracaoPorCategoria)) {

    $faseId     = (int)$faseAtual['id'];
    $ordemAtual = (int)($faseAtual['ordem_exibicao'] ?? 1);
    $statusNovo = statusNovoPorOrdem($ordemAtual);
    $tipoFase   = $faseAtual['tipo_fase'] ?? 'classificatoria';

    // Status que NÃO devem ser rebaixados
    $statusProtegidos = ['finalista'];
    for ($i = $ordemAtual + 1; $i <= 10; $i++) {
        $statusProtegidos[] = 'classificada_fase_' . $i;
    }

    try {
        $pdo->beginTransaction();

        // 1. Limpa gravações anteriores desta fase
        $pdo->prepare("DELETE FROM premiacao_classificados WHERE fase_id = ?")->execute([$faseId]);

        $totalGravados    = 0;
        $totalAtualizados = 0;

        foreach ($apuracaoPorCategoria as $catId => $cat) {

            foreach ($cat['itens'] as $item) {
                $inscId = (int)$item['inscricao_id'];
                $pos    = (int)$item['pos'];
                $origem = $item['origem'] ?? 'popular';

                // 2. Busca negocio_id da inscrição
                $stmtNegId = $pdo->prepare("SELECT negocio_id FROM premiacao_inscricoes WHERE id = ?");
                $stmtNegId->execute([$inscId]);
                $negocioId = (int)$stmtNegId->fetchColumn();

                // 3. Insere em premiacao_classificados
                $pdo->prepare("
                    INSERT INTO premiacao_classificados
                        (fase_id, categoria_id, negocio_id, posicao, origem, apurado_em)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE posicao = VALUES(posicao), origem = VALUES(origem), apurado_em = NOW()
                ")->execute([$faseId, $catId, $negocioId, $pos, $origem]);
                $totalGravados++;

                // 4. Atualiza status da inscrição (respeita status mais avançado)
                $stmtSt = $pdo->prepare("SELECT status FROM premiacao_inscricoes WHERE id = ?");
                $stmtSt->execute([$inscId]);
                $statusAtual = $stmtSt->fetchColumn();

                if (!in_array($statusAtual, $statusProtegidos, true)) {
                    $pdo->prepare("
                        UPDATE premiacao_inscricoes SET status = ?, updated_at = NOW() WHERE id = ?
                    ")->execute([$statusNovo, $inscId]);
                    $totalAtualizados++;
                }
            }

            // 5. Rebaixa para 'elegivel' as NÃO classificadas desta categoria
            $idsClassificados = array_map(fn($i) => (int)$i['inscricao_id'], $cat['itens']);
            if (!empty($idsClassificados)) {
                $ph = implode(',', array_fill(0, count($idsClassificados), '?'));
                $pdo->prepare("
                    UPDATE premiacao_inscricoes
                    SET status = 'elegivel', updated_at = NOW()
                    WHERE premiacao_id = ?
                      AND categoria    = ?
                      AND status       = ?
                      AND id NOT IN ($ph)
                ")->execute(array_merge(
                    [$filtroPremiacao, $cat['nome'], $statusNovo],
                    $idsClassificados
                ));
            }
        }

        $pdo->commit();
        $jaGravados = $totalGravados;
        $mensagens[] = "✅ <strong>$totalGravados</strong> classificados gravados na fase <strong>" . h($faseAtual['nome']) . "</strong>.";
        $mensagens[] = "📝 <strong>$totalAtualizados</strong> inscrições com status atualizado para <code>$statusNovo</code>.";

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
            <h1 class="mb-1">Apuração da Premiação</h1>
            <p class="text-muted mb-0">Visualize o resultado e grave os classificados de cada fase.</p>
        </div>
        <?php if ($faseAtual): ?>
            <span class="badge px-3 py-2" style="background:#1E3425;color:#CDDE00;font-size:13px;">
                <i class="bi bi-layers me-1"></i>
                <?= h($faseAtual['nome']) ?> &mdash;
                <?= match($faseAtual['tipo_fase'] ?? '') {
                    'classificatoria' => 'Classificatória',
                    'final'           => 'Fase Final',
                    default           => ucfirst($faseAtual['tipo_fase'] ?? '')
                } ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Alertas de gravação -->
    <?php foreach ($mensagens as $msg): ?>
        <div class="alert alert-success border-0 shadow-sm mb-3" style="font-size:14px;"><?= $msg ?></div>
    <?php endforeach; ?>
    <?php foreach ($erros as $err): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="font-size:14px;"><?= $err ?></div>
    <?php endforeach; ?>

    <!-- Filtros -->
    <div class="filtros-card mb-4">
        <form method="GET" class="row g-2 align-items-end" id="formFiltro">

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Premiação</label>
                <select name="premiacao_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Selecione...</option>
                    <?php foreach ($premiacoes as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>" <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                            <?= h($pr['nome']) ?> (<?= (int)$pr['ano'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($filtroPremiacao > 0): ?>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Fase</label>
                <select name="fase_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($fases as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $filtroFase === (int)$f['id'] ? 'selected' : '' ?>>
                            <?= h($f['nome']) ?>
                            (<?= match($f['tipo_fase'] ?? '') {
                                'classificatoria' => 'Classificatória',
                                'final'           => 'Final',
                                default           => ucfirst($f['tipo_fase'] ?? '')
                            } ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Categoria (opcional)</label>
                <select name="categoria_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $filtroCategoria === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-auto">
                <a href="premiacao_apuracao.php" class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <?php endif; ?>

        </form>
    </div>

    <?php if (!$filtroPremiacao): ?>
        <div class="text-center py-5">
            <i class="bi bi-funnel fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted fs-5 mb-1">Selecione uma Premiação para iniciar a apuração.</p>
        </div>

    <?php elseif (empty($apuracaoPorCategoria)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted fs-5 mb-1">Nenhuma inscrição elegível encontrada para esta fase.</p>
            <p class="text-muted" style="font-size:13px;">Verifique se as inscrições têm status correto e se existem votos registrados.</p>
        </div>

    <?php else: ?>

        <!-- KPIs da fase -->
        <?php if ($faseAtual): ?>
        <div class="row g-3 mb-4">
            <?php if (($faseAtual['tipo_fase'] ?? '') !== 'final'): ?>
            <div class="col-6 col-md-2">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#e7f5ff;"><i class="bi bi-people-fill" style="color:#084298;"></i></div>
                    <div class="prem-kpi-valor"><?= (int)($faseAtual['qtd_classificados_popular'] ?? 0) ?></div>
                    <div class="prem-kpi-label">Top Popular</div>
                    <div class="prem-kpi-sub">por categoria</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#eaf7ef;"><i class="bi bi-clipboard-data-fill" style="color:#1E3425;"></i></div>
                    <div class="prem-kpi-valor"><?= (int)($faseAtual['qtd_classificados_tecnica'] ?? 0) ?></div>
                    <div class="prem-kpi-label">Top Técnica</div>
                    <div class="prem-kpi-sub">por categoria</div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-12 col-md-6">
                <div class="alert alert-info mb-0" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Fase Final:</strong> líder popular ganha +1 ponto + votos do júri. Maior total vence.
                </div>
            </div>
            <?php endif; ?>
            <div class="col-6 col-md-2">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:#f3f0ff;"><i class="bi bi-grid-fill" style="color:#5a3e9a;"></i></div>
                    <div class="prem-kpi-valor"><?= count($apuracaoPorCategoria) ?></div>
                    <div class="prem-kpi-label">Categorias</div>
                    <div class="prem-kpi-sub">apuradas</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="prem-kpi-card">
                    <div class="prem-kpi-icon" style="background:<?= $jaGravados > 0 ? '#fff3cd' : '#eaf7ef' ?>;"><i class="bi bi-database-fill-check" style="color:<?= $jaGravados > 0 ? '#856404' : '#1E3425' ?>;"></i></div>
                    <div class="prem-kpi-valor"><?= $jaGravados ?></div>
                    <div class="prem-kpi-label">Já Gravados</div>
                    <div class="prem-kpi-sub">nesta fase</div>
                </div>
            </div>

            <!-- Botão Gravar (canto direito dos KPIs) -->
            <div class="col-12 col-md-4 d-flex align-items-center justify-content-end">
                <?php
                    $totalParaGravar = array_sum(array_column($apuracaoPorCategoria, 'total_class'));
                    $faseNomeEsc     = addslashes($faseAtual['nome']);
                ?>
                <?php if ($jaGravados > 0): ?>
                    <div class="me-3" style="font-size:12px;color:#856404;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Já gravado. Regravar substituirá os dados.
                    </div>
                <?php endif; ?>
                <form method="POST"
                      action="premiacao_apuracao.php?premiacao_id=<?= $filtroPremiacao ?>&fase_id=<?= $filtroFase ?><?= $filtroCategoria ? '&categoria_id='.$filtroCategoria : '' ?>"
                      onsubmit="return confirm('Gravar <?= $totalParaGravar ?> classificados da fase &laquo;<?= $faseNomeEsc ?>&raquo;?<?= $jaGravados > 0 ? '\n\nISSO SUBSTITUIRÁ OS DADOS ANTERIORES.' : '' ?>')">
                    <button type="submit" name="acao" value="gravar"
                            class="btn fw-bold px-4"
                            style="background:#1E3425;color:#CDDE00;border:none;white-space:nowrap;">
                        <i class="bi bi-check2-square me-2"></i>
                        Gravar <?= $totalParaGravar ?> Classificados
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resultados por categoria -->
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
                        / <?= $cat['total_pool'] ?> inscrições
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background:#f0f4f1;">
                            <tr>
                                <th class="ps-3" style="width:50px;">Pos.</th>
                                <th>Negócio</th>
                                <?php if (($faseAtual['tipo_fase'] ?? '') === 'final'): ?>
                                    <th class="text-center" style="width:110px;">Votos Popular</th>
                                    <th class="text-center" style="width:110px;">Ponto Popular</th>
                                    <th class="text-center" style="width:110px;">Votos Júri</th>
                                    <th class="text-center" style="width:100px;">Total</th>
                                <?php else: ?>
                                    <th class="text-center" style="width:140px;">Votos Popular</th>
                                    <th class="text-center" style="width:140px;">Votos Técnica</th>
                                    <th class="text-center" style="width:120px;">Origem</th>
                                <?php endif; ?>
                                <th class="pe-3" style="width:120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cat['itens'] as $item): ?>
                                <?php
                                    $pos        = $item['pos'];
                                    $medal      = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '#'.$pos };
                                    $isFinal    = ($faseAtual['tipo_fase'] ?? '') === 'final';
                                    $isVencedor = $isFinal && $pos === 1;
                                ?>
                                <tr style="<?= $isVencedor ? 'background:#f5ffe8;' : '' ?>">
                                    <td class="ps-3 text-center">
                                        <span class="rank-pos" style="background:<?= $isVencedor ? '#CDDE00' : '#f0f4f1' ?>;color:<?= $isVencedor ? '#1E3425' : '#6c7a6e' ?>;">
                                            <?php if ($isVencedor): ?><i class="bi bi-trophy-fill" style="font-size:14px;"></i><?php else: ?><?= $medal ?><?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item['logo_negocio'])): ?>
                                                <img src="<?= h($item['logo_negocio']) ?>" alt="" width="32" height="32"
                                                     style="border-radius:6px;object-fit:cover;" loading="lazy">
                                            <?php else: ?>
                                                <div style="width:32px;height:32px;border-radius:6px;background:#e8ede9;
                                                            display:flex;align-items:center;justify-content:center;">
                                                    <i class="bi bi-building" style="color:#6c7a6e;font-size:14px;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold" style="font-size:13px;">
                                                    <?= h($item['nome_fantasia']) ?>
                                                    <?php if ($isVencedor): ?> 👑<?php endif; ?>
                                                </div>
                                                <?php if ($item['municipio']): ?>
                                                    <div style="font-size:11px;color:#9aab9d;"><?= h($item['municipio']) ?>/<?= h($item['estado']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <?php if ($isFinal): ?>
                                        <td class="text-center">
                                            <span class="pontos-pill" style="background:#e7f5ff;color:#084298;font-size:12px;"><?= (int)$item['votos_popular'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$item['ponto_popular'] > 0): ?>
                                                <span class="pontos-pill" style="background:#CDDE00;color:#1E3425;font-size:12px;">+1</span>
                                            <?php else: ?>
                                                <span style="color:#9aab9d;font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="pontos-pill" style="background:#f3f0ff;color:#5a3e9a;font-size:12px;"><?= (int)$item['votos_juri'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="pontos-pill" style="background:#1E3425;color:#CDDE00;font-size:14px;font-weight:700;"><?= (int)$item['total'] ?></span>
                                        </td>
                                    <?php else: ?>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="fw-semibold" style="color:#084298;"><?= (int)($item['pop'] ?? 0) ?></span>
                                                <div style="width:80px;height:5px;background:#e7f5ff;border-radius:3px;overflow:hidden;">
                                                    <div style="height:100%;background:#084298;border-radius:3px;
                                                                width:<?= $item['max_pop'] > 0 ? min(100, round((($item['pop'] ?? 0)/$item['max_pop'])*100)) : 0 ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="fw-semibold" style="color:#1E3425;"><?= (int)($item['tec'] ?? 0) ?></span>
                                                <div style="width:80px;height:5px;background:#eaf7ef;border-radius:3px;overflow:hidden;">
                                                    <div style="height:100%;background:#1E3425;border-radius:3px;
                                                                width:<?= $item['max_tec'] > 0 ? min(100, round((($item['tec'] ?? 0)/$item['max_tec'])*100)) : 0 ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                                $origem = $item['origem'] ?? 'complemento';
                                                [$bg, $color, $icon, $label] = $origensBadge[$origem] ?? $origensBadge['complemento'];
                                            ?>
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;
                                                         border-radius:999px;background:<?= $bg ?>;color:<?= $color ?>;
                                                         font-size:11px;font-weight:700;white-space:nowrap;">
                                                <i class="bi <?= $icon ?>"></i> <?= $label ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>

                                    <td class="pe-3">
                                        <?php
                                            $stMap = [
                                                'finalista'           => ['bg-info text-dark',      'Finalista'],
                                                'classificada_fase_2' => ['bg-primary',              'Class. F2'],
                                                'classificada_fase_1' => ['bg-secondary',            'Class. F1'],
                                                'elegivel'            => ['bg-light text-dark border','Elegível'],
                                                'vencedora'           => ['bg-success',               'Vencedora'],
                                            ];
                                            [$sc, $sl] = $stMap[$item['status_inscricao']] ?? ['bg-light text-dark border', h($item['status_inscricao'])];
                                        ?>
                                        <span class="badge <?= $sc ?>" style="font-size:10px;"><?= $sl ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Legenda origem (fases classificatórias) -->
        <?php if ($faseAtual && ($faseAtual['tipo_fase'] ?? '') !== 'final'): ?>
        <div class="card border-0 shadow-sm mt-2" style="background:#f9fafb;">
            <div class="card-body py-2 px-3">
                <small class="text-muted fw-semibold me-3">Origem dos classificados:</small>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#CDDE00;color:#1E3425;font-size:11px;font-weight:700;" class="me-2"><i class="bi bi-star-fill"></i> Ambos</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#e7f5ff;color:#084298;font-size:11px;font-weight:700;" class="me-2"><i class="bi bi-people-fill"></i> Popular</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#eaf7ef;color:#1E3425;font-size:11px;font-weight:700;" class="me-2"><i class="bi bi-clipboard-data"></i> Técnica</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#f0f0f0;color:#6c7a6e;font-size:11px;font-weight:700;"><i class="bi bi-plus-circle"></i> Complemento</span>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div><!-- /container-fluid -->

<style>
.rank-pos { display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;font-weight:700; }
.pontos-pill { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-weight:700;font-size:13px; }
.cat-header { display:flex;align-items:center;gap:10px;background:#1E3425;color:#CDDE00;padding:10px 16px;border-radius:10px 10px 0 0; }
.cat-nome { font-weight:700;font-size:15px; }
.cat-badge { background:rgba(205,222,0,.2);color:#CDDE00;font-size:11px;padding:2px 8px;border-radius:999px;font-weight:700; }
.filtros-card { background:#fff;border:1px solid #e8ede9;border-radius:10px;padding:16px 20px;margin-bottom:24px; }
.prem-kpi-card { background:#fff;border:1px solid #e8ede9;border-radius:12px;padding:14px 16px;text-align:center; }
.prem-kpi-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:18px; }
.prem-kpi-valor { font-size:22px;font-weight:800;color:#1E3425;line-height:1.1; }
.prem-kpi-label { font-size:12px;font-weight:600;color:#445;margin-top:2px; }
.prem-kpi-sub   { font-size:10px;color:#9aab9d; }
</style>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
