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
    die('Erro na conexão: ' . $e->getMessage());
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function dataBr(?string $d): string
{
    if (empty($d) || str_starts_with($d, '0000')) return '—';
    return date('d/m/Y H:i', strtotime($d));
}

$msg     = '';
$msgType = 'success';

// ── POST: publicar ganhadores da fase final ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao        = $_POST['acao'] ?? '';
    $premiacaoId = (int)($_POST['premiacao_id'] ?? 0);
    $faseId      = (int)($_POST['fase_id'] ?? 0);

    try {
        if ($acao === 'publicar_ganhadores' && $faseId > 0) {
            // Pega os 1º colocados de cada categoria da fase final
            $rows = $pdo->prepare("
                SELECT pc.negocio_id, pc.categoria_id
                FROM premiacao_classificados pc
                WHERE pc.fase_id = ? AND pc.posicao = 1
            ");
            $rows->execute([$faseId]);
            $ganhadores = $rows->fetchAll();

            if (empty($ganhadores)) {
                throw new Exception('Nenhum 1º colocado encontrado na fase final apurada. Verifique a apuração.');
            }

            $pdo->beginTransaction();
            foreach ($ganhadores as $g) {
                // Upsert em premiacao_resultados_finais marcado como vencedor + publicado
                $pdo->prepare("
                    INSERT INTO premiacao_resultados_finais
                        (premiacao_id, fase_id, categoria_id, negocio_id, inscricao_id, vencedor, publicar_resultado, publicado_em, created_at, updated_at)
                    SELECT
                        ?, ?, ?, pi.negocio_id, pi.id, 1, 1, NOW(), NOW(), NOW()
                    FROM premiacao_inscricoes pi
                    WHERE pi.negocio_id = ? AND pi.premiacao_id = ?
                    LIMIT 1
                    ON DUPLICATE KEY UPDATE
                        vencedor = 1,
                        publicar_resultado = 1,
                        publicado_em = IF(publicado_em IS NULL, NOW(), publicado_em),
                        updated_at = NOW()
                ")->execute([$premiacaoId, $faseId, $g['categoria_id'], $g['negocio_id'], $premiacaoId]);
            }
            $pdo->commit();

            $msg = count($ganhadores) . ' ganhador(es) publicado(s) com sucesso!';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg     = 'Erro: ' . $e->getMessage();
        $msgType = 'danger';
    }

    $qs = http_build_query(array_filter([
        'premiacao_id' => $premiacaoId ?: '',
        '_msg'         => $msg,
        '_tipo'        => $msgType,
    ]));
    header("Location: premiacao_resultados.php?$qs");
    exit;
}

if (!empty($_GET['_msg'])) {
    $msg     = $_GET['_msg'];
    $msgType = $_GET['_tipo'] ?? 'success';
}

// ── Filtros ────────────────────────────────────────────────────────────────────
$filtroPremiacao = (int)($_GET['premiacao_id'] ?? 0);

$premiacoes = $pdo->query("SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC, id DESC")->fetchAll();

if ($filtroPremiacao <= 0 && !empty($premiacoes)) {
    $filtroPremiacao = (int)$premiacoes[0]['id'];
}

// ── Fases com classificados ────────────────────────────────────────────────────
$fases = [];
if ($filtroPremiacao > 0) {
    $stFases = $pdo->prepare("
        SELECT pf.id, pf.nome, pf.tipo_fase, pf.status, pf.ordem_exibicao
        FROM premiacao_fases pf
        WHERE pf.premiacao_id = ?
          AND pf.status IN ('apurada','encerrada')
          AND EXISTS (SELECT 1 FROM premiacao_classificados pc WHERE pc.fase_id = pf.id)
        ORDER BY pf.ordem_exibicao ASC
    ");
    $stFases->execute([$filtroPremiacao]);
    $fases = $stFases->fetchAll();
}

// ── Fase final apurada (para botão publicar) ───────────────────────────────────
$faseFinalApurada = null;
if ($filtroPremiacao > 0) {
    $stFF = $pdo->prepare("
        SELECT pf.id, pf.nome
        FROM premiacao_fases pf
        WHERE pf.premiacao_id = ? AND pf.tipo_fase = 'final' AND pf.status = 'apurada'
        LIMIT 1
    ");
    $stFF->execute([$filtroPremiacao]);
    $faseFinalApurada = $stFF->fetch() ?: null;
}

// ── Classificados por fase/categoria ─────────────────────────────────────────
$classificadosPorFase = [];
foreach ($fases as $fase) {
    $faseId = (int)$fase['id'];
    $stCl = $pdo->prepare("
        SELECT
            pc.posicao,
            pc.origem,
            pc.apurado_em,
            cat.nome  AS categoria_nome,
            cat.ordem AS categoria_ordem,
            n.nome_fantasia,
            n.municipio,
            n.estado,
            e.nome    AS empreendedor_nome,
            na.logo_negocio,
            -- votos populares nesta fase
            (SELECT COUNT(*) FROM premiacao_votos_populares vp WHERE vp.inscricao_id = pi.id AND vp.fase_id = pc.fase_id) AS votos_pop,
            -- votos técnicos nesta fase
            (SELECT COUNT(*) FROM premiacao_votos_tecnicos vt WHERE vt.inscricao_id = pi.id AND vt.fase_id = pc.fase_id) AS votos_tec,
            -- votos juri nesta fase
            (SELECT COUNT(*) FROM premiacao_votos_juri     vj WHERE vj.inscricao_id = pi.id AND vj.fase_id = pc.fase_id) AS votos_juri,
            -- publicado?
            COALESCE(
                (SELECT prf.publicar_resultado FROM premiacao_resultados_finais prf
                 WHERE prf.negocio_id = pc.negocio_id AND prf.premiacao_id = ? LIMIT 1), 0
            ) AS publicado
        FROM premiacao_classificados pc
        INNER JOIN premiacao_categorias cat ON cat.id = pc.categoria_id
        INNER JOIN negocios n               ON n.id   = pc.negocio_id
        INNER JOIN premiacao_inscricoes pi  ON pi.negocio_id = pc.negocio_id AND pi.premiacao_id = ?
        INNER JOIN empreendedores e         ON e.id   = pi.empreendedor_id
        LEFT  JOIN negocio_apresentacao na  ON na.negocio_id = n.id
        WHERE pc.fase_id = ?
        ORDER BY cat.ordem ASC, pc.posicao ASC
    ");
    $stCl->execute([$filtroPremiacao, $filtroPremiacao, $faseId]);
    $rows = $stCl->fetchAll();

    $porCat = [];
    foreach ($rows as $r) {
        $cn = $r['categoria_nome'];
        if (!isset($porCat[$cn])) $porCat[$cn] = ['ordem' => $r['categoria_ordem'], 'itens' => []];
        $porCat[$cn]['itens'][] = $r;
    }
    $classificadosPorFase[$faseId] = [
        'fase'    => $fase,
        'porCat'  => $porCat,
    ];
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Resultados da Premiação</h1>
            <p class="text-muted mb-0">Classificados por fase. Publique os ganhadores após a fase final.</p>
        </div>

        <?php if ($faseFinalApurada): ?>
            <form method="POST"
                  onsubmit="return confirm('Publicar os ganhadores (1º colocado de cada categoria) da fase final? Esta ação marca e publica automaticamente no site.')">
                <input type="hidden" name="acao"         value="publicar_ganhadores">
                <input type="hidden" name="premiacao_id" value="<?= $filtroPremiacao ?>">
                <input type="hidden" name="fase_id"      value="<?= (int)$faseFinalApurada['id'] ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-trophy-fill me-2"></i>
                    Publicar Ganhadores — <?= h($faseFinalApurada['nome']) ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= h($msgType) ?> alert-dismissible fade show" role="alert">
            <?= h($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtro de premiação -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Edição</label>
                    <select name="premiacao_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($premiacoes as $pr): ?>
                            <option value="<?= (int)$pr['id'] ?>" <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                                <?= h($pr['nome']) ?> (<?= (int)$pr['ano'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($fases)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted fs-5 mb-1">Nenhuma fase apurada ainda.</p>
            <p class="text-muted" style="font-size:13px;">Os classificados aparecem aqui após o recálculo de status em <a href="premiacao_periodos.php?premiacao_id=<?= $filtroPremiacao ?>">Períodos</a>.</p>
        </div>
    <?php else: ?>

        <?php foreach ($classificadosPorFase as $faseId => $bloco): ?>
            <?php
                $fase      = $bloco['fase'];
                $labelTipo = match($fase['tipo_fase']) {
                    'classificatoria' => 'Classificatória',
                    'final'           => 'Fase Final',
                    default           => ucfirst($fase['tipo_fase']),
                };
            ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white d-flex align-items-center gap-2">
                    <i class="bi bi-layers-fill"></i>
                    <strong><?= h($fase['nome']) ?></strong>
                    <span class="badge bg-secondary ms-1"><?= h($labelTipo) ?></span>
                    <span class="badge bg-success ms-1"><?= h($fase['status']) ?></span>
                    <?php if ($fase['tipo_fase'] === 'final'): ?>
                        <span class="badge ms-1" style="background:#CDDE00;color:#1E3425;">🏆 Fase Final</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">

                    <?php foreach ($bloco['porCat'] as $catNome => $catDados): ?>
                        <div class="border-bottom">
                            <div class="px-3 py-2 bg-light d-flex align-items-center gap-2">
                                <i class="bi bi-award text-muted"></i>
                                <strong style="font-size:13px;"><?= h($catNome) ?></strong>
                                <span class="badge bg-secondary" style="font-size:10px;"><?= count($catDados['itens']) ?> classificados</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Negócio</th>
                                            <th>Origem</th>
                                            <?php if ($fase['tipo_fase'] !== 'final'): ?>
                                                <th>Votos Pop.</th>
                                                <th>Votos Téc.</th>
                                            <?php else: ?>
                                                <th>Votos Pop.</th>
                                                <th>Votos Júri</th>
                                            <?php endif; ?>
                                            <th>Apurado em</th>
                                            <?php if ($fase['tipo_fase'] === 'final'): ?>
                                                <th>Publicado</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($catDados['itens'] as $item): ?>
                                        <?php
                                            $pos    = (int)$item['posicao'];
                                            $medal  = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $pos . 'º' };
                                            $isPub  = (int)$item['publicado'] === 1;
                                        ?>
                                        <tr class="<?= ($pos === 1 && $fase['tipo_fase'] === 'final') ? 'table-warning' : '' ?>">
                                            <td class="fw-bold text-center"><?= $medal ?></td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:13px;"><?= h($item['nome_fantasia']) ?></div>
                                                <div class="text-muted" style="font-size:11px;">
                                                    <i class="bi bi-person me-1"></i><?= h($item['empreendedor_nome']) ?>
                                                    <?php if ($item['municipio']): ?>
                                                        · <i class="bi bi-geo-alt me-1"></i><?= h($item['municipio']) ?>/<?= h($item['estado']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    $origemLabel = match($item['origem']) {
                                                        'popular'     => '<span class="badge bg-warning text-dark">Popular</span>',
                                                        'tecnica'     => '<span class="badge bg-primary">Técnica</span>',
                                                        'ambos'       => '<span class="badge bg-success">Ambos</span>',
                                                        'juri'        => '<span class="badge bg-dark">Júri</span>',
                                                        'complemento' => '<span class="badge bg-secondary">Complemento</span>',
                                                        default       => '<span class="badge bg-light text-dark">' . h($item['origem']) . '</span>',
                                                    };
                                                    echo $origemLabel;
                                                ?>
                                            </td>
                                            <td><?= (int)$item['votos_pop'] ?></td>
                                            <?php if ($fase['tipo_fase'] !== 'final'): ?>
                                                <td><?= (int)$item['votos_tec'] ?></td>
                                            <?php else: ?>
                                                <td><?= (int)$item['votos_juri'] ?></td>
                                            <?php endif; ?>
                                            <td style="font-size:11px;"><?= dataBr($item['apurado_em']) ?></td>
                                            <?php if ($fase['tipo_fase'] === 'final'): ?>
                                                <td>
                                                    <?php if ($isPub): ?>
                                                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Sim</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Não</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
