<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

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

// ── Garante que o enum aceita todos os valores usados pelo código ─────────────
try {
    $pdo->exec("
        ALTER TABLE premiacao_classificados
        MODIFY COLUMN origem ENUM('popular','tecnica','complemento','ambos','juri')
        COLLATE utf8mb4_unicode_ci NOT NULL
    ");
} catch (PDOException $e) {
    // Ignora se já foi aplicado anteriormente
}

$pageTitle = 'Premiação - Períodos';
$mensagem  = '';
$erro      = '';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDatetimeLocal(?string $value): string
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') return '';
    $ts = strtotime($value);
    return $ts ? date('Y-m-d\\TH:i', $ts) : '';
}

function dataBr(?string $dt): string
{
    if (empty($dt) || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function calcularStatusAutomatico(string $inicio, string $fim, string $statusAtual = 'rascunho'): string
{
    if ($statusAtual === 'rascunho') return 'rascunho';
    $agora      = new DateTime('now');
    $dataInicio = new DateTime($inicio);
    $dataFim    = new DateTime($fim);
    if ($agora < $dataInicio) return 'agendada';
    if ($agora > $dataFim)    return 'encerrada';
    return 'em_andamento';
}

function labelStatus(string $status): string
{
    return match ($status) {
        'rascunho'     => 'Rascunho',
        'agendada'     => 'Agendada',
        'em_andamento' => 'Em andamento',
        'encerrada'    => 'Encerrada',
        'apurada'      => 'Apurada',
        default        => '-',
    };
}

function labelTipoFase(string $tipo): string
{
    return match ($tipo) {
        'inscricoes'         => 'Inscrições',
        'triagem_documental' => 'Triagem documental',
        'classificatoria'    => 'Classificatória',
        'final'              => 'Fase final',
        'resultado'          => 'Resultado',
        default              => $tipo,
    };
}

// ============================================================
// APURAÇÃO AUTOMÁTICA
// ============================================================

/**
 * Retorna a cláusula SQL "IN (...)" com os status elegíveis para entrar
 * no pool de uma determinada fase.
 *
 * Fase final        → apenas 'finalista'
 * Classificatória 1 → 'elegivel' OU 'classificada_fase_1' (permite re-apuração)
 * Classificatória N → 'classificada_fase_(N-1)' OU 'classificada_fase_N'
 *                     (o segundo cobre re-apurações da mesma rodada)
 */
function buildStatusPool(string $tipoFase, int $rodada): string
{
    if ($tipoFase === 'final') {
        return "IN ('finalista')";
    }
    if ($rodada <= 1) {
        // Rodada 1: aceita quem ainda está como elegível E quem já foi
        // marcado como classificada_fase_1 (re-apuração)
        return "IN ('elegivel','classificada_fase_1')";
    }
    // Rodadas 2+: aceita quem veio da fase anterior (N-1) E quem já foi
    // classificado nesta mesma rodada por apuração anterior (re-apuração)
    $statusAnterior = 'classificada_fase_' . ($rodada - 1);
    $statusAtual    = 'classificada_fase_' . $rodada;
    return "IN ('{$statusAnterior}','{$statusAtual}')";
}

function apurarEGravar(PDO $pdo, array $fase): array
{
    $faseId      = (int)$fase['id'];
    $premiacaoId = (int)$fase['premiacao_id'];
    $tipoFase    = $fase['tipo_fase'] ?? 'classificatoria';
    $rodada      = (int)($fase['rodada'] ?? 0);

    $statusNovo  = ($tipoFase === 'final') ? 'finalista' : 'classificada_fase_' . max(1, $rodada);
    $statusPool  = buildStatusPool($tipoFase, $rodada);

    // Coleta votos populares
    $vp = [];
    $st = $pdo->prepare("SELECT inscricao_id, COUNT(*) AS v FROM premiacao_votos_populares WHERE fase_id=? GROUP BY inscricao_id");
    $st->execute([$faseId]);
    foreach ($st->fetchAll() as $r) $vp[(int)$r['inscricao_id']] = (int)$r['v'];

    // Coleta votos técnicos
    $vt = [];
    $st = $pdo->prepare("SELECT inscricao_id, COUNT(*) AS v FROM premiacao_votos_tecnicos WHERE fase_id=? GROUP BY inscricao_id");
    $st->execute([$faseId]);
    foreach ($st->fetchAll() as $r) $vt[(int)$r['inscricao_id']] = (int)$r['v'];

    // Coleta votos de júri
    $vj = [];
    $st = $pdo->prepare("SELECT inscricao_id, COUNT(*) AS v FROM premiacao_votos_juri WHERE fase_id=? GROUP BY inscricao_id");
    $st->execute([$faseId]);
    foreach ($st->fetchAll() as $r) $vj[(int)$r['inscricao_id']] = (int)$r['v'];

    // Categorias da premiação
    $cats = $pdo->prepare("SELECT id, nome FROM premiacao_categorias WHERE premiacao_id=? ORDER BY ordem");
    $cats->execute([$premiacaoId]);

    $totalGravados = 0;
    $pdo->beginTransaction();

    try {
        // Limpa classificados anteriores desta fase
        $pdo->prepare("DELETE FROM premiacao_classificados WHERE fase_id=?")->execute([$faseId]);

        // Status que não devem ser rebaixados (fases futuras já concluídas)
        $statusProtegidos = ['finalista', 'vencedora'];
        for ($i = max(1, $rodada) + 1; $i <= 10; $i++) {
            $statusProtegidos[] = 'classificada_fase_' . $i;
        }

        foreach ($cats->fetchAll() as $cat) {
            $catId   = (int)$cat['id'];
            $catNome = $cat['nome'];

            // Busca inscrições elegíveis para esta fase/categoria
            $stPool = $pdo->prepare("SELECT id FROM premiacao_inscricoes WHERE premiacao_id=? AND categoria=? AND status $statusPool");
            $stPool->execute([$premiacaoId, $catNome]);
            $pool = array_map(fn($r) => (int)$r['id'], $stPool->fetchAll());

            if (empty($pool)) continue;

            $topPop  = (int)($fase['qtd_classificados_popular'] ?? 10);
            $topTec  = (int)($fase['qtd_classificados_tecnica'] ?? 10);
            $totalCl = $topPop + $topTec;

            if ($tipoFase === 'final') {
                $poolSet = array_flip($pool);
                $vpPool  = array_filter($vp, fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);
                $vjPool  = array_filter($vj, fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);
                $maxPop  = !empty($vpPool) ? max($vpPool) : 0;
                $resultado = [];
                foreach ($pool as $id) {
                    $pop  = $vpPool[$id] ?? 0;
                    $juri = $vjPool[$id] ?? 0;
                    $pp   = ($maxPop > 0 && $pop === $maxPop) ? 1 : 0;
                    $resultado[$id] = ['origem' => 'juri', 'total' => $pp + $juri, 'pop' => $pop, 'juri' => $juri];
                }
                uasort($resultado, fn($a, $b) => $b['total'] <=> $a['total'] ?: $b['juri'] <=> $a['juri'] ?: $b['pop'] <=> $a['pop']);
            } else {
                $poolSet = array_flip($pool);
                $vpF = array_filter($vp, fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);
                $vtF = array_filter($vt, fn($id) => isset($poolSet[$id]), ARRAY_FILTER_USE_KEY);

                arsort($vpF); $selPop = array_slice(array_keys($vpF), 0, $topPop, true);
                arsort($vtF); $selTec = array_slice(array_keys($vtF), 0, $topTec, true);

                $sel = [];
                foreach ($selPop as $id) {
                    $sel[$id] = ['origem' => 'popular', 'pop' => $vpF[$id] ?? 0, 'tec' => $vtF[$id] ?? 0];
                }
                foreach ($selTec as $id) {
                    if (!isset($sel[$id])) {
                        $sel[$id] = ['origem' => 'tecnica', 'pop' => $vpF[$id] ?? 0, 'tec' => $vtF[$id] ?? 0];
                    } else {
                        $sel[$id]['origem'] = 'ambos';
                    }
                }

                if (count($sel) < $totalCl) {
                    $todos = $pool;
                    usort($todos, fn($a, $b) => ($vpF[$b] ?? 0) <=> ($vpF[$a] ?? 0));
                    foreach ($todos as $id) {
                        if (count($sel) >= $totalCl) break;
                        if (!isset($sel[$id])) {
                            $sel[$id] = ['origem' => 'complemento', 'pop' => $vpF[$id] ?? 0, 'tec' => $vtF[$id] ?? 0];
                        }
                    }
                }

                $ord = ['ambos' => 0, 'popular' => 1, 'tecnica' => 2, 'complemento' => 3];
                uasort($sel, fn($a, $b) =>
                    ($ord[$a['origem']] ?? 9) <=> ($ord[$b['origem']] ?? 9)
                    ?: $b['pop'] <=> $a['pop']
                    ?: $b['tec'] <=> $a['tec']
                );
                $resultado = $sel;
            }

            $pos      = 1;
            $idsClass = [];

            foreach ($resultado as $inscId => $dados) {
                $stNeg = $pdo->prepare("SELECT negocio_id FROM premiacao_inscricoes WHERE id=?");
                $stNeg->execute([$inscId]);
                $negId = (int)$stNeg->fetchColumn();

                $pdo->prepare("
                    INSERT INTO premiacao_classificados (fase_id, categoria_id, negocio_id, posicao, origem, apurado_em)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE posicao=VALUES(posicao), origem=VALUES(origem), apurado_em=NOW()
                ")->execute([$faseId, $catId, $negId, $pos, $dados['origem']]);

                $totalGravados++;
                $idsClass[] = $inscId;

                // Avança status da inscrição (respeita status mais avançados)
                $stSt = $pdo->prepare("SELECT status FROM premiacao_inscricoes WHERE id=?");
                $stSt->execute([$inscId]);
                $stAtual = $stSt->fetchColumn();

                if (!in_array($stAtual, $statusProtegidos, true)) {
                    $pdo->prepare("UPDATE premiacao_inscricoes SET status=?, updated_at=NOW() WHERE id=?")
                        ->execute([$statusNovo, $inscId]);
                }

                $pos++;
            }

            // ── Marca como 'eliminada' quem estava no pool mas NÃO foi classificado ──
            if (!empty($idsClass)) {
                $ph = implode(',', array_fill(0, count($idsClass), '?'));
                $pdo->prepare("
                    UPDATE premiacao_inscricoes
                    SET status = 'eliminada', updated_at = NOW()
                    WHERE premiacao_id = ?
                      AND categoria    = ?
                      AND status       $statusPool
                      AND id NOT IN ($ph)
                ")->execute(array_merge([$premiacaoId, $catNome], $idsClass));
            } else {
                // Pool não vazio mas nenhum foi classificado: elimina todos do pool
                $pdo->prepare("
                    UPDATE premiacao_inscricoes
                    SET status = 'eliminada', updated_at = NOW()
                    WHERE premiacao_id = ?
                      AND categoria    = ?
                      AND status       $statusPool
                ")->execute([$premiacaoId, $catNome]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['gravados' => $totalGravados];
}

// ============================================================
$edicaoSelecionada = (int)($_GET['premiacao_id'] ?? $_POST['premiacao_id'] ?? 0);
$faseEdicaoId      = (int)($_GET['editar'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        // ── SALVAR FASE ────────────────────────────────────────────────────────
        if ($acao === 'salvar_fase') {
            $faseId                  = (int)($_POST['fase_id'] ?? 0);
            $premiacaoId             = (int)($_POST['premiacao_id'] ?? 0);
            $tipoFase                = trim($_POST['tipo_fase'] ?? '');
            $rodada                  = (int)($_POST['rodada'] ?? 0);
            $ordemExibicao           = (int)($_POST['ordem_exibicao'] ?? 0);
            $nome                    = trim($_POST['nome'] ?? '');
            $slug                    = trim($_POST['slug'] ?? '');
            $descricao               = trim($_POST['descricao'] ?? '');
            $dataInicio              = trim($_POST['data_inicio'] ?? '');
            $dataFim                 = trim($_POST['data_fim'] ?? '');
            $permiteVotoPopular      = isset($_POST['permite_voto_popular']) ? 1 : 0;
            $permiteAvaliacaoTecnica = isset($_POST['permite_avaliacao_tecnica']) ? 1 : 0;
            $permiteJuriFinal        = isset($_POST['permite_juri_final']) ? 1 : 0;
            $qtdClassificadosPopular = (int)($_POST['qtd_classificados_popular'] ?? 0);
            $qtdClassificadosTecnica = (int)($_POST['qtd_classificados_tecnica'] ?? 0);
            $qtdClassificadosFinal   = (int)($_POST['qtd_classificados_final'] ?? 0);
            $criterioDesempate       = trim($_POST['criterio_desempate'] ?? '');
            $statusManual            = trim($_POST['status'] ?? 'rascunho');

            if ($premiacaoId <= 0)    throw new Exception('Selecione uma edição da premiação.');
            if ($tipoFase === '')     throw new Exception('Selecione o tipo da fase.');
            if ($nome === '')         throw new Exception('Informe o nome da fase.');
            if ($slug === '')         throw new Exception('Informe o slug da fase.');
            if ($dataInicio === '' || $dataFim === '') throw new Exception('Informe as datas de início e fim.');

            $stmtEdicao = $pdo->prepare("SELECT id FROM premiacoes WHERE id=? LIMIT 1");
            $stmtEdicao->execute([$premiacaoId]);
            if (!$stmtEdicao->fetch()) throw new Exception('Edição da premiação não encontrada.');

            $inicioObj = new DateTime($dataInicio);
            $fimObj    = new DateTime($dataFim);
            if ($fimObj < $inicioObj) throw new Exception('A data/hora de fim não pode ser menor que a data/hora de início.');

            $stmtFasesMesmaEdicao = $pdo->prepare("SELECT id, ordem_exibicao FROM premiacao_fases WHERE premiacao_id=? AND id<>? ORDER BY ordem_exibicao ASC");
            $stmtFasesMesmaEdicao->execute([$premiacaoId, $faseId]);
            foreach ($stmtFasesMesmaEdicao->fetchAll() as $fe) {
                if ((int)$fe['ordem_exibicao'] === $ordemExibicao) throw new Exception('Já existe outra fase com esta ordem de exibição nesta edição.');
            }

            if ($tipoFase === 'inscricoes') {
                $sqlConflito = "SELECT pf.id FROM premiacao_fases pf WHERE pf.tipo_fase='inscricoes' AND pf.premiacao_id<>? AND pf.id<>? AND pf.status<>'rascunho' AND ((? BETWEEN pf.data_inicio AND pf.data_fim) OR (? BETWEEN pf.data_inicio AND pf.data_fim) OR (pf.data_inicio BETWEEN ? AND ?) OR (pf.data_fim BETWEEN ? AND ?)) LIMIT 1";
                $stmtC = $pdo->prepare($sqlConflito);
                $stmtC->execute([$premiacaoId, $faseId, $inicioObj->format('Y-m-d H:i:s'), $fimObj->format('Y-m-d H:i:s'), $inicioObj->format('Y-m-d H:i:s'), $fimObj->format('Y-m-d H:i:s'), $inicioObj->format('Y-m-d H:i:s'), $fimObj->format('Y-m-d H:i:s')]);
                if ($stmtC->fetch()) throw new Exception('Já existe uma fase de inscrições em conflito com este período.');
            }

            if ($tipoFase === 'classificatoria' && ($permiteVotoPopular !== 1 || $permiteAvaliacaoTecnica !== 1))
                throw new Exception('A fase classificatória deve habilitar voto popular e avaliação técnica ao mesmo tempo.');
            if ($tipoFase === 'classificatoria' && ($qtdClassificadosPopular <= 0 || $qtdClassificadosTecnica <= 0 || $qtdClassificadosFinal <= 0))
                throw new Exception('Informe as quantidades de classificados da fase classificatória.');
            if ($tipoFase === 'final' && ($permiteVotoPopular !== 1 || $permiteJuriFinal !== 1))
                throw new Exception('A fase final deve habilitar voto popular e júri final.');

            $statusSalvar = calcularStatusAutomatico($inicioObj->format('Y-m-d H:i:s'), $fimObj->format('Y-m-d H:i:s'), $statusManual);

            if ($faseId > 0) {
                $pdo->prepare("UPDATE premiacao_fases SET premiacao_id=?,tipo_fase=?,rodada=?,ordem_exibicao=?,nome=?,slug=?,descricao=?,data_inicio=?,data_fim=?,permite_voto_popular=?,permite_avaliacao_tecnica=?,permite_juri_final=?,qtd_classificados_popular=?,qtd_classificados_tecnica=?,qtd_classificados_final=?,criterio_desempate=?,status=?,updated_at=NOW() WHERE id=?")
                    ->execute([$premiacaoId,$tipoFase,$rodada>0?$rodada:null,$ordemExibicao,$nome,$slug,$descricao!==''?$descricao:null,$inicioObj->format('Y-m-d H:i:s'),$fimObj->format('Y-m-d H:i:s'),$permiteVotoPopular,$permiteAvaliacaoTecnica,$permiteJuriFinal,$qtdClassificadosPopular,$qtdClassificadosTecnica,$qtdClassificadosFinal,$criterioDesempate!==''?$criterioDesempate:null,$statusSalvar,$faseId]);
                $mensagem = 'Fase atualizada com sucesso.';
            } else {
                $pdo->prepare("INSERT INTO premiacao_fases (premiacao_id,tipo_fase,rodada,ordem_exibicao,nome,slug,descricao,data_inicio,data_fim,permite_voto_popular,permite_avaliacao_tecnica,permite_juri_final,qtd_classificados_popular,qtd_classificados_tecnica,qtd_classificados_final,criterio_desempate,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
                    ->execute([$premiacaoId,$tipoFase,$rodada>0?$rodada:null,$ordemExibicao,$nome,$slug,$descricao!==''?$descricao:null,$inicioObj->format('Y-m-d H:i:s'),$fimObj->format('Y-m-d H:i:s'),$permiteVotoPopular,$permiteAvaliacaoTecnica,$permiteJuriFinal,$qtdClassificadosPopular,$qtdClassificadosTecnica,$qtdClassificadosFinal,$criterioDesempate!==''?$criterioDesempate:null,$statusSalvar]);
                $mensagem = 'Fase cadastrada com sucesso.';
            }

            header('Location: premiacao_periodos.php?premiacao_id=' . $premiacaoId . '&ok=' . urlencode($mensagem));
            exit;
        }

        // ── RECALCULAR STATUS + APURAÇÃO AUTOMÁTICA ────────────────────────────
        if ($acao === 'recalcular_status') {
            $faseId = (int)($_POST['fase_id'] ?? 0);
            $stmt   = $pdo->prepare("SELECT * FROM premiacao_fases WHERE id=? LIMIT 1");
            $stmt->execute([$faseId]);
            $fase = $stmt->fetch();
            if (!$fase) throw new Exception('Fase não encontrada.');

            $novoStatus = calcularStatusAutomatico($fase['data_inicio'], $fase['data_fim'], 'agendada');
            $pdo->prepare("UPDATE premiacao_fases SET status=?,updated_at=NOW() WHERE id=?")->execute([$novoStatus, $faseId]);

            $msgExtra = '';
            $tipoApuravel = in_array($fase['tipo_fase'], ['classificatoria', 'final'], true);

            if ($novoStatus === 'encerrada' && $tipoApuravel) {
                $fase['status'] = $novoStatus;
                $res = apurarEGravar($pdo, $fase);
                $pdo->prepare("UPDATE premiacao_fases SET status='apurada',updated_at=NOW() WHERE id=?")->execute([$faseId]);
                $msgExtra = ' Apuração automática: ' . $res['gravados'] . ' classificados gravados.';
            }

            header('Location: premiacao_periodos.php?premiacao_id=' . (int)$fase['premiacao_id'] . '&ok=' . urlencode('Status recalculado.' . $msgExtra));
            exit;
        }

        // ── FORÇAR RE-APURAÇÃO (fase já apurada) ──────────────────────────────
        if ($acao === 'forcar_reapuracao') {
            $faseId = (int)($_POST['fase_id'] ?? 0);
            $stmt   = $pdo->prepare("SELECT * FROM premiacao_fases WHERE id=? LIMIT 1");
            $stmt->execute([$faseId]);
            $fase = $stmt->fetch();
            if (!$fase) throw new Exception('Fase não encontrada.');

            $tipoApuravel = in_array($fase['tipo_fase'], ['classificatoria', 'final'], true);
            if (!$tipoApuravel) throw new Exception('Este tipo de fase não possui apuração de classificados.');

            $res = apurarEGravar($pdo, $fase);
            $pdo->prepare("UPDATE premiacao_fases SET status='apurada',updated_at=NOW() WHERE id=?")->execute([$faseId]);

            header('Location: premiacao_periodos.php?premiacao_id=' . (int)$fase['premiacao_id'] . '&ok=' . urlencode('Re-apuração concluída: ' . $res['gravados'] . ' classificados gravados.'));
            exit;
        }

    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

if (isset($_GET['ok']) && $_GET['ok'] !== '') {
    $mensagem = trim($_GET['ok']);
}

$stmtPremiacoes = $pdo->query("SELECT id, nome, ano, slug, status FROM premiacoes ORDER BY ano DESC, id DESC");
$premiacoes = $stmtPremiacoes->fetchAll();

if ($edicaoSelecionada <= 0 && !empty($premiacoes)) {
    $edicaoSelecionada = (int)$premiacoes[0]['id'];
}

$premiacaoAtual = null;
foreach ($premiacoes as $premiacaoItem) {
    if ((int)$premiacaoItem['id'] === $edicaoSelecionada) {
        $premiacaoAtual = $premiacaoItem;
        break;
    }
}

$faseEdicao = [
    'id'                       => 0,
    'premiacao_id'             => $edicaoSelecionada,
    'tipo_fase'                => '',
    'rodada'                   => '',
    'ordem_exibicao'           => 0,
    'nome'                     => '',
    'slug'                     => '',
    'descricao'                => '',
    'data_inicio'              => '',
    'data_fim'                 => '',
    'permite_voto_popular'     => 0,
    'permite_avaliacao_tecnica'=> 0,
    'permite_juri_final'       => 0,
    'qtd_classificados_popular'=> 0,
    'qtd_classificados_tecnica'=> 0,
    'qtd_classificados_final'  => 0,
    'criterio_desempate'       => '',
    'status'                   => 'rascunho',
];

if ($faseEdicaoId > 0) {
    $stmtFase = $pdo->prepare("SELECT * FROM premiacao_fases WHERE id=? LIMIT 1");
    $stmtFase->execute([$faseEdicaoId]);
    $faseEncontrada = $stmtFase->fetch();
    if ($faseEncontrada) {
        $faseEdicao        = $faseEncontrada;
        $edicaoSelecionada = (int)$faseEncontrada['premiacao_id'];
    }
}

$fases = [];
if ($edicaoSelecionada > 0) {
    $stmtFases = $pdo->prepare("
        SELECT pf.*, p.nome AS premiacao_nome, p.ano AS premiacao_ano
        FROM premiacao_fases pf
        INNER JOIN premiacoes p ON p.id = pf.premiacao_id
        WHERE pf.premiacao_id = ?
        ORDER BY pf.ordem_exibicao ASC, pf.data_inicio ASC, pf.id ASC
    ");
    $stmtFases->execute([$edicaoSelecionada]);
    $fases = $stmtFases->fetchAll();
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Premiação - Períodos</h1>
            <p class="text-muted mb-0">Gerencie o calendário operacional de cada edição por fases.</p>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Edição</label>
                    <select name="premiacao_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Selecione</option>
                        <?php foreach ($premiacoes as $premiacao): ?>
                            <option value="<?= (int)$premiacao['id'] ?>" <?= (int)$premiacao['id'] === $edicaoSelecionada ? 'selected' : '' ?>>
                                <?= h($premiacao['nome']) ?> · <?= (int)$premiacao['ano'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($edicaoSelecionada > 0): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <strong><?= (int)$faseEdicao['id'] > 0 ? 'Editar fase' : 'Nova fase' ?></strong>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao" value="salvar_fase">
                    <input type="hidden" name="fase_id" value="<?= (int)$faseEdicao['id'] ?>">
                    <input type="hidden" name="premiacao_id" value="<?= (int)$edicaoSelecionada ?>">

                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tipo de fase</label>
                            <select name="tipo_fase" class="form-select" required>
                                <?php
                                $tipos = [
                                    'inscricoes'         => 'Inscrições',
                                    'triagem_documental' => 'Triagem documental',
                                    'classificatoria'    => 'Classificatória',
                                    'final'              => 'Fase final',
                                    'resultado'          => 'Resultado',
                                ];
                                foreach ($tipos as $valor => $label): ?>
                                    <option value="<?= h($valor) ?>" <?= ($faseEdicao['tipo_fase'] ?? '') === $valor ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Rodada</label>
                            <input type="number" name="rodada" class="form-control" min="0" value="<?= h((string)($faseEdicao['rodada'] ?? '')) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="ordem_exibicao" class="form-control" min="1" required value="<?= h((string)($faseEdicao['ordem_exibicao'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['rascunho','agendada','em_andamento','encerrada','apurada'] as $statusFase): ?>
                                    <option value="<?= h($statusFase) ?>" <?= ($faseEdicao['status'] ?? 'rascunho') === $statusFase ? 'selected' : '' ?>><?= h(labelStatus($statusFase)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" required value="<?= h($faseEdicao['nome'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" required value="<?= h($faseEdicao['slug'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Data/hora início</label>
                            <input type="datetime-local" name="data_inicio" class="form-control" required value="<?= h(formatDatetimeLocal($faseEdicao['data_inicio'] ?? '')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Data/hora fim</label>
                            <input type="datetime-local" name="data_fim" class="form-control" required value="<?= h(formatDatetimeLocal($faseEdicao['data_fim'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3"><?= h($faseEdicao['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_voto_popular" id="permite_voto_popular" <?= (int)($faseEdicao['permite_voto_popular'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_voto_popular">Permite voto popular</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_avaliacao_tecnica" id="permite_avaliacao_tecnica" <?= (int)($faseEdicao['permite_avaliacao_tecnica'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_avaliacao_tecnica">Permite avaliação técnica</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_juri_final" id="permite_juri_final" <?= (int)($faseEdicao['permite_juri_final'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_juri_final">Permite júri final</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados popular</label>
                            <input type="number" name="qtd_classificados_popular" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_popular'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados técnica</label>
                            <input type="number" name="qtd_classificados_tecnica" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_tecnica'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados final</label>
                            <input type="number" name="qtd_classificados_final" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_final'] ?? 0)) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Critério de desempate</label>
                            <input type="text" name="criterio_desempate" class="form-control" value="<?= h($faseEdicao['criterio_desempate'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-dark">
                            <?= (int)$faseEdicao['id'] > 0 ? 'Salvar alterações' : 'Cadastrar fase' ?>
                        </button>
                        <?php if ((int)$faseEdicao['id'] > 0): ?>
                            <a href="premiacao_periodos.php?premiacao_id=<?= (int)$edicaoSelecionada ?>" class="btn btn-outline-secondary">Cancelar edição</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong>Fases cadastradas</strong></div>
            <div class="card-body">
                <?php if (empty($fases)): ?>
                    <p class="text-muted mb-0">Nenhuma fase cadastrada para esta edição.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Fase</th>
                                    <th>Período</th>
                                    <th>Regras</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fases as $fase): ?>
                                <?php
                                $statusAuto   = calcularStatusAutomatico($fase['data_inicio'], $fase['data_fim'], $fase['status']);
                                $jaApurada    = ($fase['status'] === 'apurada');
                                $tipoApuravel = in_array($fase['tipo_fase'], ['classificatoria', 'final'], true);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= h($fase['nome']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= h(labelTipoFase((string)$fase['tipo_fase'])) ?>
                                            · Rodada <?= (int)($fase['rodada'] ?? 0) ?>
                                            · <?= h($fase['slug']) ?>
                                        </small>
                                        <?php if (!empty($fase['descricao'])): ?>
                                            <div class="small text-muted mt-1"><?= nl2br(h($fase['descricao'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>Início:</strong> <?= h(dataBr($fase['data_inicio'])) ?><br>
                                        <strong>Fim:</strong> <?= h(dataBr($fase['data_fim'])) ?>
                                    </td>
                                    <td>
                                        <div><strong>Ordem:</strong> <?= (int)$fase['ordem_exibicao'] ?></div>
                                        <div><strong>Popular:</strong> <?= (int)$fase['qtd_classificados_popular'] ?></div>
                                        <div><strong>Técnica:</strong> <?= (int)$fase['qtd_classificados_tecnica'] ?></div>
                                        <div><strong>Final:</strong> <?= (int)$fase['qtd_classificados_final'] ?></div>
                                        <div><strong>Desempate:</strong> <?= h((string)($fase['criterio_desempate'] ?: '—')) ?></div>
                                        <div class="small text-muted mt-1">
                                            <?= (int)$fase['permite_voto_popular'] === 1 ? 'Voto popular' : 'Sem voto popular' ?> /
                                            <?= (int)$fase['permite_avaliacao_tecnica'] === 1 ? 'Avaliação técnica' : 'Sem avaliação técnica' ?> /
                                            <?= (int)$fase['permite_juri_final'] === 1 ? 'Júri final' : 'Sem júri' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $jaApurada ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                            <?= h($jaApurada ? 'Apurada' : labelStatus($statusAuto)) ?>
                                        </span><br>
                                        <small class="text-muted">Salvo: <?= h((string)$fase['status']) ?></small>
                                    </td>
                                    <td class="d-flex gap-2 flex-wrap">
                                        <a href="premiacao_periodos.php?premiacao_id=<?= (int)$edicaoSelecionada ?>&editar=<?= (int)$fase['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>

                                        <?php if (!$jaApurada): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="acao" value="recalcular_status">
                                            <input type="hidden" name="fase_id" value="<?= (int)$fase['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-dark"
                                                onclick="return confirm('Recalcular status e, se encerrada, apurar classificados?')">
                                                Recalcular status
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($tipoApuravel): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="acao" value="forcar_reapuracao">
                                            <input type="hidden" name="fase_id" value="<?= (int)$fase['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                                onclick="return confirm('Re-apurar agora? Os classificados anteriores serão substituídos.')">
                                                <?= $jaApurada ? 'Re-apurar' : 'Apurar agora' ?>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
